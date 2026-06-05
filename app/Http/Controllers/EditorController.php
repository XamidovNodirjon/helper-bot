<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EditorController extends Controller
{
    /**
     * Show the main Web IDE editor page.
     */
    public function index()
    {
        return view('editor');
    }

    /**
     * Get the file tree structure of the workspace.
     */
    public function getFiles()
    {
        $basePath = base_path();
        $tree = $this->buildTree($basePath, $basePath);
        return response()->json($tree);
    }

    /**
     * Get the raw content of a specific file.
     */
    public function getFile(Request $request)
    {
        $relativePath = $request->get('path');
        if (empty($relativePath)) {
            return response()->json(['error' => 'Path parameter is required'], 400);
        }

        $fullPath = base_path($relativePath);
        
        // Safety check to prevent directory traversal
        if (!$this->isPathSafe($fullPath)) {
            return response()->json(['error' => 'Unauthorized path access'], 403);
        }

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File does not exist'], 404);
        }

        if (is_dir($fullPath)) {
            return response()->json(['error' => 'Path points to a directory, not a file'], 400);
        }

        $content = file_get_contents($fullPath);
        return response()->json([
            'path' => $relativePath,
            'name' => basename($fullPath),
            'content' => $content
        ]);
    }

    /**
     * Save/overwrite content to a specific file.
     */
    public function saveFile(Request $request)
    {
        $relativePath = $request->json('path');
        $content = $request->json('content');

        if (empty($relativePath)) {
            return response()->json(['error' => 'Path parameter is required'], 400);
        }

        $fullPath = base_path($relativePath);

        // Safety check to prevent directory traversal
        if (!$this->isPathSafe($fullPath)) {
            return response()->json(['error' => 'Unauthorized path access'], 403);
        }

        // Backup existing file if any before saving
        if (file_exists($fullPath) && is_file($fullPath)) {
            $backupDir = storage_path('editor_backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $backupFile = $backupDir . '/' . basename($fullPath) . '_' . date('Ymd_His') . '.bak';
            copy($fullPath, $backupFile);
        }

        try {
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $content);
            return response()->json(['success' => true, 'message' => 'Fayl muvaffaqiyatli saqlandi!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Faylga yozishda xatolik: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Run a predetermined command in the background and return console output.
     */
    public function runCommand(Request $request)
    {
        $cmdKey = $request->json('command');
        $allowedCommands = [
            'clear_cache' => 'php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear',
            'route_list' => 'php artisan route:list',
            'migrate' => 'php artisan migrate',
            'test_scraper' => 'python app/Services/olx_scraper.py --category=uy --region=tashkent --district=23 --price_max=1000 --currency=usd --area_min=30 --area_max=100',
            'test_scraper_office' => 'python app/Services/olx_scraper.py --category=office --region=tashkent --district=23 --price_max=1000 --currency=usd',
            'view_logs' => 'tail -n 100 storage/logs/laravel.log'
        ];

        // On Windows tail is not native, replace view_logs command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $allowedCommands['view_logs'] = 'powershell -Command "Get-Content -Tail 50 storage/logs/laravel.log"';
        }

        if (!array_key_exists($cmdKey, $allowedCommands)) {
            return response()->json(['error' => 'Command not allowed'], 403);
        }

        $command = $allowedCommands[$cmdKey];
        Log::info("Web IDE running command: $command");
        
        $output = shell_exec($command);
        
        return response()->json([
            'command' => $command,
            'output' => $output ?: 'Buyruq bajarildi, lekin chiqish ma\'lumoti bo\'sh.'
        ]);
    }

    /**
     * Recursive function to build a file tree structure.
     */
    protected function buildTree($dir, $basePath)
    {
        $result = [];
        $cdir = scandir($dir);
        
        // System folders/files to exclude from view
        $exclude = [
            '.',
            '..',
            'vendor',
            'node_modules',
            '.git',
            '.idea',
            '.vscode',
            'storage/framework',
            'storage/logs',
            '.phpunit.cache',
            '.env.backup',
            'bootstrap/cache'
        ];

        foreach ($cdir as $value) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;
            $relPath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $fullPath);
            $relPathNormalized = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);

            // Skip excluded items
            $isExcluded = false;
            foreach ($exclude as $ex) {
                if ($relPathNormalized === $ex || strpos($relPathNormalized, $ex . '/') === 0) {
                    $isExcluded = true;
                    break;
                }
            }

            if ($isExcluded) {
                continue;
            }

            if (is_dir($fullPath)) {
                $children = $this->buildTree($fullPath, $basePath);
                $result[] = [
                    'name' => $value,
                    'path' => $relPathNormalized,
                    'type' => 'directory',
                    'children' => $children
                ];
            } else {
                $result[] = [
                    'name' => $value,
                    'path' => $relPathNormalized,
                    'type' => 'file'
                ];
            }
        }

        // Sort directories first, then files
        usort($result, function ($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['type'] === 'directory' ? -1 : 1;
        });

        return $result;
    }

    /**
     * Ensure path is secure and stays inside base workspace.
     */
    protected function isPathSafe($path)
    {
        $realPath = realpath($path);
        // If file doesn't exist yet, get real path of directory
        if ($realPath === false) {
            $realPath = realpath(dirname($path));
        }

        $basePath = realpath(base_path());

        if ($realPath === false || $basePath === false) {
            return false;
        }

        // Must start with base path
        return strpos($realPath, $basePath) === 0;
    }
}
