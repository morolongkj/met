<?php
namespace App\Database\Seeds;

trait RemoteCsvSeederTrait
{
    protected function resolveCsvPath(string $filename): ?string
    {
        $localPath = WRITEPATH . 'uploads/' . $filename;

        if (is_file($localPath) && filesize($localPath) > 0) {
            log_message('info', 'Remote CSV seeder using existing local file: {csv_name} ({csv_path})', [
                'csv_name' => $filename,
                'csv_path' => $localPath,
            ]);

            return $localPath;
        }

        log_message('info', 'Remote CSV seeder local file missing or empty, attempting download: {csv_name}', [
            'csv_name' => $filename,
        ]);

        if ($this->downloadRemoteCsv($localPath, $filename)) {
            log_message('info', 'Remote CSV seeder downloaded file successfully: {csv_name} ({csv_path})', [
                'csv_name' => $filename,
                'csv_path' => $localPath,
            ]);
            return $localPath;
        }

        log_message('error', 'Remote CSV seeder could not resolve file locally or remotely: {csv_name}. Ensure the CSV exists in writable/uploads, install sshpass for password-based SCP, or use SSH key auth.', [
            'csv_name' => $filename,
        ]);

        return null;
    }

    protected function downloadRemoteCsv(string $localPath, string $filename): bool
    {
        $host     = env('REMOTE_CSV_HOST', '');
        $user     = env('REMOTE_CSV_USER', '');
        $root     = env('REMOTE_CSV_ROOT', '');
        $password = env('REMOTE_CSV_PASSWORD', '');

        if (empty($host) || empty($user) || empty($root)) {
            log_message('warning', 'Remote CSV seeder missing remote connection config for file: {csv_name}. Check REMOTE_CSV_HOST, REMOTE_CSV_USER, and REMOTE_CSV_ROOT.', [
                'csv_name' => $filename,
            ]);
            return false;
        }

        if (! is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        $remotePath    = rtrim($root, '/') . '/' . $filename;
        $escapedLocal  = escapeshellarg($localPath);
        $escapedRemote = escapeshellarg($user . '@' . $host . ':' . $remotePath);

        if ($password !== '') {
            if (! $this->commandExists('sshpass')) {
                log_message('warning', 'Remote CSV seeder requires sshpass for password-based SCP but it is unavailable. Falling back to cURL-based SCP/SFTP download. CSV: {csv_name}, host: {host}, user: {user}, root: {root}', [
                    'csv_name' => $filename,
                    'host'     => $host,
                    'user'     => $user,
                    'root'     => $root,
                ]);

                return $this->downloadRemoteCsvWithCurl($localPath, $filename, $host, $user, $password, $remotePath);
            }
            $escapedPassword = escapeshellarg($password);
            $cmd             = "sshpass -p {$escapedPassword} scp -o StrictHostKeyChecking=no {$escapedRemote} {$escapedLocal}";
        } else {
            $cmd = "scp -o StrictHostKeyChecking=no {$escapedRemote} {$escapedLocal}";
        }

        log_message('info', 'Remote CSV seeder downloading {csv_name} from {remote_path} on host {host} as user {user}', [
            'csv_name'    => $filename,
            'remote_path' => $remotePath,
            'host'        => $host,
            'user'        => $user,
        ]);

        exec($cmd . ' 2>&1', $output, $exitCode);

        $downloaded = $exitCode === 0 && is_file($localPath) && filesize($localPath) > 0;

        if (! $downloaded) {
            log_message('error', 'Remote CSV seeder download failed for {csv_name}. Exit code: {exit_code}. Output: {command_output}', [
                'csv_name'       => $filename,
                'exit_code'      => $exitCode,
                'command_output' => implode("\n", $output),
            ]);

            $downloaded = $this->downloadRemoteCsvWithCurl($localPath, $filename, $host, $user, $password, $remotePath);
        }

        return $downloaded;
    }

    protected function downloadRemoteCsvWithCurl(
        string $localPath,
        string $filename,
        string $host,
        string $user,
        string $password,
        string $remotePath
    ): bool {
        if (! function_exists('curl_init')) {
            log_message('error', 'Remote CSV cURL fallback unavailable because cURL is not installed. CSV: {csv_name}', [
                'csv_name' => $filename,
            ]);

            return false;
        }

        foreach (['scp', 'sftp'] as $scheme) {
            if ($this->attemptCurlRemoteDownload($scheme, $localPath, $filename, $host, $user, $password, $remotePath)) {
                return true;
            }
        }

        return false;
    }

    protected function attemptCurlRemoteDownload(
        string $scheme,
        string $localPath,
        string $filename,
        string $host,
        string $user,
        string $password,
        string $remotePath
    ): bool {
        $remoteUrl = sprintf(
            '%s://%s%s',
            $scheme,
            $host,
            str_starts_with($remotePath, '/') ? $remotePath : '/' . $remotePath
        );

        $handle = fopen($localPath, 'w');
        if (! $handle) {
            log_message('error', 'Remote CSV cURL fallback could not open local destination for writing: {csv_path}', [
                'csv_path' => $localPath,
            ]);

            return false;
        }

        $ch = curl_init($remoteUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE            => $handle,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_CONNECTTIMEOUT  => 15,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_USERPWD         => $user . ':' . $password,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
        ]);

        log_message('info', 'Remote CSV seeder trying cURL fallback via {scheme} for {csv_name} from {remote_url}', [
            'scheme'     => $scheme,
            'csv_name'   => $filename,
            'remote_url' => $remoteUrl,
        ]);

        $result   = curl_exec($ch);
        $error    = curl_error($ch);
        $errorNo  = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        fclose($handle);

        $downloaded = $result !== false && is_file($localPath) && filesize($localPath) > 0;

        if ($downloaded) {
            log_message('info', 'Remote CSV cURL fallback succeeded via {scheme} for {csv_name}', [
                'scheme'   => $scheme,
                'csv_name' => $filename,
            ]);

            return true;
        }

        if (is_file($localPath) && filesize($localPath) === 0) {
            unlink($localPath);
        }

        log_message('warning', 'Remote CSV cURL fallback failed via {scheme} for {csv_name}. cURL error {error_no}: {error}. Response code: {response_code}', [
            'scheme'        => $scheme,
            'csv_name'      => $filename,
            'error_no'      => $errorNo,
            'error'         => $error !== '' ? $error : 'n/a',
            'response_code' => $httpCode,
        ]);

        return false;
    }

    protected function commandExists(string $command): bool
    {
        exec('command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }
}

// edited
