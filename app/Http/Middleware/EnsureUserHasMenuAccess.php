<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! ($user instanceof User)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $menuPaths = $this->menuPathsForRequest($request);
        $hasMenuAccess = collect($menuPaths)
            ->contains(fn (string $menuPath): bool => RolePermissionService::hasMenuAccess($user, $menuPath));

        if (! $hasMenuAccess) {
            return response()->json([
                'message' => 'Akses ke menu ini tidak diberikan oleh Administrator.',
            ], 403);
        }

        $requiredFeature = $this->featureForRequest($request);

        if ($requiredFeature !== null && ! RolePermissionService::hasFeatureAccess($user, $requiredFeature)) {
            return response()->json([
                'message' => 'Akses ke fitur ini tidak diberikan oleh Administrator.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function menuPathsForRequest(Request $request): array
    {
        $uri = preg_replace('#^api/#', '', $request->route()->uri()) ?? '';

        if (str_starts_with($uri, 'cascade/')) {
            return match (basename($uri)) {
                'apps', 'modules', 'menus' => [
                    '/schedules',
                    '/test-cases',
                    '/bugs',
                    '/flows',
                    '/guides',
                    '/reports',
                    '/reports/wa',
                ],
                'flows' => ['/schedules', '/test-cases', '/bugs', '/flows'],
                'users' => ['/schedules', '/test-cases', '/bugs', '/retests'],
                default => [],
            };
        }

        if ($uri === 'reports/summary') {
            return ['/reports', '/reports/wa'];
        }

        if (str_starts_with($uri, 'files/')) {
            return match ($request->route('type')) {
                'bug' => ['/bugs'],
                'execution' => ['/test-cases'],
                'retest' => ['/retests'],
                'flow' => ['/flows'],
                'guide' => ['/guides'],
                default => [],
            };
        }

        return match (true) {
            $uri === 'dashboard' => ['/'],
            str_starts_with($uri, 'schedules') => ['/schedules'],
            str_starts_with($uri, 'test-cases') => ['/test-cases'],
            str_starts_with($uri, 'bugs') => ['/bugs'],
            str_starts_with($uri, 'retests') => ['/retests'],
            str_starts_with($uri, 'flows') => ['/flows'],
            str_starts_with($uri, 'guides') => ['/guides'],
            str_starts_with($uri, 'reports/export') => ['/reports'],
            str_starts_with($uri, 'work-logs') => ['/reports/weekly'],
            str_starts_with($uri, 'activity-logs') => ['/activity-logs'],
            default => [],
        };
    }

    private function featureForRequest(Request $request): ?string
    {
        $uri = preg_replace('#^api/#', '', $request->route()->uri()) ?? '';
        $method = $request->method();
        $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if (str_starts_with($uri, 'schedules') && $isWrite) {
            return 'create_schedule';
        }

        if (str_starts_with($uri, 'test-cases/excel-template') || str_starts_with($uri, 'test-cases/import-excel')) {
            return 'import_test_excel';
        }

        if (str_contains($uri, 'test-cases/{testCase}/execute')) {
            return 'execute_test';
        }

        if (preg_match('#^test-cases(?:/\\{test_?case\\})?$#', $uri) === 1 && $isWrite) {
            return 'create_test';
        }

        if (str_starts_with($uri, 'bugs/excel-template') || str_starts_with($uri, 'bugs/import-excel')) {
            return 'import_bug_excel';
        }

        if ($uri === 'bugs' && $method === 'POST') {
            return 'create_bug';
        }

        if ((str_starts_with($uri, 'bugs/') || $uri === 'bugs') && $isWrite) {
            return 'edit_bug';
        }

        if ($uri === 'retests' && $method === 'POST') {
            return 'create_retest';
        }

        if (str_starts_with($uri, 'flows') && $isWrite) {
            return 'manage_flow';
        }

        if (str_starts_with($uri, 'reports/export')) {
            return 'export_report';
        }

        return null;
    }
}
