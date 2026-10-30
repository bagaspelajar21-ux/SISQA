<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicationFlowFile;
use App\Models\BugEvidence;
use App\Models\RetestEvidence;
use App\Models\TestExecutionEvidence;
use App\Models\UsageGuideFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class FileAccessController extends Controller
{
    private function getFileRecord(string $type, int $id)
    {
        return match ($type) {
            'bug' => BugEvidence::findOrFail($id),
            'execution' => TestExecutionEvidence::findOrFail($id),
            'retest' => RetestEvidence::findOrFail($id),
            'flow' => ApplicationFlowFile::findOrFail($id),
            'guide' => UsageGuideFile::findOrFail($id),
            default => abort(404, 'Tipe berkas tidak dikenali.'),
        };
    }

    private function resolvePhysicalPath($fileRecord): string
    {
        $candidates = [
            Storage::disk('local')->path($fileRecord->file_path),
            storage_path('app/' . $fileRecord->file_path),
            storage_path('app/private/' . $fileRecord->file_path),
            storage_path('app/public/' . $fileRecord->file_path),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        abort(404, 'Berkas fisik tidak ditemukan pada server.');
    }

    public function download(Request $request, string $type, int $id)
    {
        $fileRecord = $this->getFileRecord($type, $id);
        $path = $this->resolvePhysicalPath($fileRecord);

        return response()->download($path, $fileRecord->original_name);
    }

    public function preview(Request $request, string $type, int $id)
    {
        $fileRecord = $this->getFileRecord($type, $id);
        $path = $this->resolvePhysicalPath($fileRecord);

        $ext = strtolower($fileRecord->file_type ?: pathinfo($fileRecord->original_name, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'txt', 'log', 'md', 'csv' => 'text/plain; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            default => $fileRecord->mime_type ?: 'application/octet-stream',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $fileRecord->original_name . '"',
        ]);
    }

    /**
     * Extract structured content for in-system preview of PPTX, DOCX, XLSX, CSV, TXT, JSON, etc.
     */
    public function contentPreview(Request $request, string $type, int $id): JsonResponse
    {
        $fileRecord = $this->getFileRecord($type, $id);
        $path = $this->resolvePhysicalPath($fileRecord);
        $ext = strtolower($fileRecord->file_type ?: pathinfo($fileRecord->original_name, PATHINFO_EXTENSION));

        try {
            if ($ext === 'pptx') {
                return response()->json($this->extractPptxSlides($path, $fileRecord));
            }

            if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
                return response()->json($this->extractSpreadsheet($path, $fileRecord));
            }

            if ($ext === 'docx') {
                return response()->json($this->extractDocx($path, $fileRecord));
            }

            if (in_array($ext, ['txt', 'json', 'xml', 'drawio', 'md', 'log'])) {
                $content = file_get_contents($path);
                return response()->json([
                    'preview_type' => 'text',
                    'original_name' => $fileRecord->original_name,
                    'file_type' => $ext,
                    'content' => mb_substr($content ?: '', 0, 100000),
                ]);
            }

            return response()->json([
                'preview_type' => 'native',
                'original_name' => $fileRecord->original_name,
                'file_type' => $ext,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'preview_type' => 'error',
                'original_name' => $fileRecord->original_name,
                'file_type' => $ext,
                'message' => 'Tidak dapat mengekstrak pratinjau internal: ' . $e->getMessage(),
            ]);
        }
    }

    private function extractPptxSlides(string $path, $fileRecord): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal membuka arsip presentasi PPTX.');
        }

        // Collect all slide XML files
        $slideFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#i', $name, $m)) {
                $slideFiles[(int) $m[1]] = $name;
            }
        }
        ksort($slideFiles);

        $slides = [];
        foreach ($slideFiles as $slideNum => $slideXmlPath) {
            $xmlContent = $zip->getFromName($slideXmlPath);
            $paragraphs = [];

            if ($xmlContent) {
                $dom = new \DOMDocument();
                @$dom->loadXML($xmlContent);
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

                // Extract paragraphs (<a:p>)
                foreach ($xpath->query('//a:p') as $pNode) {
                    $runs = [];
                    foreach ($xpath->query('.//a:t', $pNode) as $tNode) {
                        $runs[] = $tNode->textContent;
                    }
                    $line = trim(implode('', $runs));
                    if ($line !== '') {
                        $paragraphs[] = $line;
                    }
                }
            }

            // Extract slide images via _rels
            $relsPath = "ppt/slides/_rels/slide{$slideNum}.xml.rels";
            $relsXml = $zip->getFromName($relsPath);
            $images = [];
            if ($relsXml) {
                $relsDom = new \DOMDocument();
                @$relsDom->loadXML($relsXml);
                foreach ($relsDom->getElementsByTagName('Relationship') as $rel) {
                    $target = $rel->getAttribute('Target');
                    $typeAttr = $rel->getAttribute('Type');
                    if (str_contains($typeAttr, '/image') && $target) {
                        // Resolve '../media/image1.png' -> 'ppt/media/image1.png'
                        $mediaPath = str_starts_with($target, '../')
                            ? 'ppt/' . substr($target, 3)
                            : 'ppt/slides/' . ltrim($target, '/');
                        $imgBytes = $zip->getFromName($mediaPath);
                        if ($imgBytes !== false && strlen($imgBytes) < 5 * 1024 * 1024) {
                            $imgExt = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                            $imgMime = match ($imgExt) {
                                'jpg', 'jpeg' => 'image/jpeg',
                                'gif' => 'image/gif',
                                'svg' => 'image/svg+xml',
                                'webp' => 'image/webp',
                                default => 'image/png',
                            };
                            $images[] = 'data:' . $imgMime . ';base64,' . base64_encode($imgBytes);
                        }
                    }
                }
            }

            $title = $paragraphs[0] ?? "Slide {$slideNum}";
            $bullets = array_slice($paragraphs, 1);

            $slides[] = [
                'slide_number' => $slideNum,
                'title' => $title,
                'bullets' => $bullets,
                'images' => $images,
            ];
        }

        $zip->close();

        return [
            'preview_type' => 'pptx',
            'original_name' => $fileRecord->original_name,
            'file_type' => 'pptx',
            'total_slides' => count($slides),
            'slides' => $slides,
        ];
    }

    private function extractSpreadsheet(string $path, $fileRecord): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $rows = $worksheet->toArray(null, true, true, false);
            // Limit to first 150 rows and 20 cols for fast UI preview
            $trimmedRows = array_slice($rows, 0, 150);
            $trimmedRows = array_map(fn($r) => array_slice($r, 0, 20), $trimmedRows);

            // Filter out completely empty trailing rows
            $filtered = array_values(array_filter($trimmedRows, function ($row) {
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string) $cell) !== '') {
                        return true;
                    }
                }
                return false;
            }));

            $sheets[] = [
                'name' => $worksheet->getTitle(),
                'rows' => $filtered,
            ];
        }

        return [
            'preview_type' => 'spreadsheet',
            'original_name' => $fileRecord->original_name,
            'file_type' => $fileRecord->file_type,
            'sheets' => $sheets,
        ];
    }

    private function extractDocx(string $path, $fileRecord): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal membuka dokumen DOCX.');
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $paragraphs = [];
        if ($xmlContent) {
            $dom = new \DOMDocument();
            @$dom->loadXML($xmlContent);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            foreach ($xpath->query('//w:p') as $pNode) {
                $runs = [];
                foreach ($xpath->query('.//w:t', $pNode) as $tNode) {
                    $runs[] = $tNode->textContent;
                }
                $line = trim(implode('', $runs));
                if ($line !== '') {
                    $paragraphs[] = $line;
                }
            }
        }

        // Extract images in word/media/
        $images = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^word/media/.*\.(png|jpe?g|gif|webp|svg)$#i', $name)) {
                $imgBytes = $zip->getFromName($name);
                if ($imgBytes !== false && strlen($imgBytes) < 5 * 1024 * 1024) {
                    $imgExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $imgMime = $imgExt === 'jpg' || $imgExt === 'jpeg' ? 'image/jpeg' : "image/{$imgExt}";
                    $images[] = 'data:' . $imgMime . ';base64,' . base64_encode($imgBytes);
                }
            }
        }

        $zip->close();

        return [
            'preview_type' => 'docx',
            'original_name' => $fileRecord->original_name,
            'file_type' => 'docx',
            'paragraphs' => $paragraphs,
            'images' => $images,
        ];
    }
}
