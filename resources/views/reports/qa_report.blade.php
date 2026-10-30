<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SISQA - Laporan Quality Assurance</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111827;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #2563EB;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563EB;
            text-transform: uppercase;
        }
        .header-sub {
            font-size: 11px;
            color: #4B5563;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            font-size: 9px;
        }
        .meta-table td {
            padding: 3px 6px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .kpi-table th {
            background-color: #2563EB;
            color: #ffffff;
            font-size: 9px;
            padding: 6px;
            text-align: center;
        }
        .kpi-table td {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            font-size: 13px;
            font-weight: bold;
            padding: 6px;
            text-align: center;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1E3A8A;
            border-left: 3px solid #2563EB;
            padding-left: 6px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background-color: #F1F5F9;
            color: #334155;
            border: 1px solid #CBD5E1;
            padding: 5px;
            font-size: 8.5px;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #E2E8F0;
            padding: 4px 5px;
            font-size: 8.5px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-pass { background-color: #DCFCE7; color: #166534; }
        .badge-fail { background-color: #FEE2E2; color: #991B1B; }
        .badge-pending { background-color: #FEF3C7; color: #92400E; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94A3B8;
            text-align: right;
            border-top: 1px solid #E2E8F0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="header-title">SISQA — Software Quality Assurance System</div>
                    <div class="header-sub">Laporan Komprehensif Eksekusi Pengujian & Defect Tracking</div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 9px; color: #64748B;">Tanggal Pembuatan:</div>
                    <div style="font-weight: bold;">{{ $data['generated_at'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 15%; font-weight: bold;">Cakupan Aplikasi:</td>
            <td style="width: 35%;">{{ $data['filters']['application'] }}</td>
            <td style="width: 15%; font-weight: bold;">Cakupan Modul:</td>
            <td style="width: 35%;">{{ $data['filters']['module'] }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Rentang Periode:</td>
            <td>{{ $data['filters']['start_date'] }} s/d {{ $data['filters']['end_date'] }}</td>
            <td style="font-weight: bold;">Dicetak Oleh:</td>
            <td>{{ $user->name }} ({{ strtoupper($user->role) }})</td>
        </tr>
    </table>

    <!-- KPI Summary -->
    <table class="kpi-table">
        <thead>
            <tr>
                <th>TOTAL TEST CASE</th>
                <th>PASS</th>
                <th>FAILED</th>
                <th>PENDING</th>
                <th>NOT TESTED</th>
                <th>SUCCESS RATE</th>
                <th>BUG AKTIF</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $data['summary']['total_test_cases'] }}</td>
                <td style="color: #166534;">{{ $data['summary']['pass'] }}</td>
                <td style="color: #991B1B;">{{ $data['summary']['failed'] }}</td>
                <td style="color: #92400E;">{{ $data['summary']['pending'] }}</td>
                <td style="color: #4B5563;">{{ $data['summary']['not_tested'] }}</td>
                <td style="color: #2563EB;">{{ $data['summary']['success_rate'] }}%</td>
                <td style="color: #DC2626;">{{ $data['summary']['active_bugs'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Executions -->
    <div class="section-title">Riwayat Eksekusi Pengujian ({{ count($data['executions']) }} Data)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">Kode TC</th>
                <th style="width: 16%;">Aplikasi & Modul</th>
                <th>Judul Skenario</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 14%;">Tester</th>
                <th style="width: 14%;">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['executions'] as $idx => $exec)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="font-weight: bold;">{{ $exec->testCase?->test_case_code }}</td>
                <td>{{ $exec->testCase?->application?->name }} / {{ $exec->testCase?->module?->name ?? '-' }}</td>
                <td>{{ $exec->testCase?->title }}</td>
                <td>
                    <span class="badge {{ $exec->status === 'PASS' ? 'badge-pass' : ($exec->status === 'FAILED' ? 'badge-fail' : 'badge-pending') }}">
                        {{ $exec->status }}
                    </span>
                </td>
                <td>{{ $exec->tester?->name }}</td>
                <td>{{ $exec->executed_at?->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94A3B8;">Tidak ada data eksekusi dalam rentang filter ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Bugs -->
    <div class="section-title">Daftar Bug & Defect ({{ count($data['bugs']) }} Tiket)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">Kode Bug</th>
                <th style="width: 15%;">Aplikasi</th>
                <th>Judul Bug</th>
                <th style="width: 10%;">Severity</th>
                <th style="width: 10%;">Priority</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 13%;">Pelapor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['bugs'] as $idx => $bug)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="font-weight: bold; color: #DC2626;">{{ $bug->bug_code }}</td>
                <td>{{ $bug->application?->name }}</td>
                <td>{{ $bug->title }}</td>
                <td style="font-weight: bold;">{{ $bug->severity }}</td>
                <td>{{ $bug->priority }}</td>
                <td>{{ $bug->status }}</td>
                <td>{{ $bug->reporter?->name }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; color: #94A3B8;">Tidak ada tiket bug yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        SISQA System — Dokumen resmi hasil pengujian Quality Assurance internal perusahaan.
    </div>
</body>
</html>
