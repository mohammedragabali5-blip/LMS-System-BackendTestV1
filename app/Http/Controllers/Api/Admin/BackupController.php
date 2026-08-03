<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function create()
    {
        $filename = 'backup-'.now()->format('Y_m_d_His').'.sql';
        $path = storage_path("app/backups/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $db = config('database.connections.mysql');

        $process = new Process([
            'mysqldump',
            '-h', $db['host'],
            '-u', $db['username'],
            '-p'.$db['password'],
            $db['database'],
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            return response()->json(['message' => 'Backup failed.', 'error' => $process->getErrorOutput()], 500);
        }

        file_put_contents($path, $process->getOutput());
        AuditLogService::log('backup.created', null, $filename);

        return response()->json(['message' => 'Backup created.', 'file' => $filename]);
    }

    public function restore(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate(['filename' => 'required|string']);
        $path = storage_path("app/backups/{$validated['filename']}");

        if (! file_exists($path)) {
            return response()->json(['message' => 'Backup file not found.'], 404);
        }

        $db = config('database.connections.mysql');

        $process = Process::fromShellCommandline(
            'mysql -h '.escapeshellarg($db['host']).' -u '.escapeshellarg($db['username']).
            ' -p'.escapeshellarg($db['password']).' '.escapeshellarg($db['database']).
            ' < '.escapeshellarg($path)
        );
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            return response()->json(['message' => 'Restore failed.', 'error' => $process->getErrorOutput()], 500);
        }

        AuditLogService::log('backup.restored', null, $validated['filename']);

        return response()->json(['message' => 'Backup restored.']);
    }
}
