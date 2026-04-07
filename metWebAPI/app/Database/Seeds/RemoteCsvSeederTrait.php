<?php
namespace App\Database\Seeds;

trait RemoteCsvSeederTrait
{
    protected function resolveCsvPath(string $filename): ?string
    {
        $localPath = WRITEPATH . 'uploads/' . $filename;

        if (is_file($localPath) && filesize($localPath) > 0) {
            return $localPath;
        }

        if ($this->downloadRemoteCsv($localPath, $filename)) {
            return $localPath;
        }

        return null;
    }

    protected function downloadRemoteCsv(string $localPath, string $filename): bool
    {
        $host     = env('REMOTE_CSV_HOST', '');
        $user     = env('REMOTE_CSV_USER', '');
        $root     = env('REMOTE_CSV_ROOT', '');
        $password = env('REMOTE_CSV_PASSWORD', '');

        if (empty($host) || empty($user) || empty($root)) {
            return false;
        }

        if (! is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        $remotePath = rtrim($root, '/') . '/' . $filename;
        $escapedLocal = escapeshellarg($localPath);
        $escapedRemote = escapeshellarg($user . '@' . $host . ':' . $remotePath);

        if ($password !== '') {
            if (! $this->commandExists('sshpass')) {
                return false;
            }
            $escapedPassword = escapeshellarg($password);
            $cmd = "sshpass -p {$escapedPassword} scp -o StrictHostKeyChecking=no {$escapedRemote} {$escapedLocal}";
        } else {
            $cmd = "scp -o StrictHostKeyChecking=no {$escapedRemote} {$escapedLocal}";
        }

        exec($cmd . ' 2>&1', $output, $exitCode);

        return $exitCode === 0 && is_file($localPath) && filesize($localPath) > 0;
    }

    protected function commandExists(string $command): bool
    {
        exec('command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }
}
