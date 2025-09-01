<?php
// Error Debugging
ini_set('display_errors', 1); // 0  or 1
error_reporting(E_ALL); // E_ERROR & 0

//.env load function
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Environment file not found: $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
// Load .env only from the same folder as this file
loadEnv(__DIR__ . DIRECTORY_SEPARATOR . '.env');
//MySQL Connection
$mysqli = new mysqli($_ENV['DB_HOST'],$_ENV['DB_USER'],$_ENV['DB_PASS'],$_ENV['DB_NAME']);

// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}

//Curl Function to get scrape data
function callCurl($url,$headers,$postData = null) {
   
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => '', // allows decoding gzip, deflate, etc.
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => $postData ? true : false,
        CURLOPT_POSTFIELDS => $postData ? http_build_query($postData) : null,
    ]);
    curl_setopt($ch, CURLOPT_COOKIE, "_ga=GA1.1.243730864.1752823086; _ga_GMXJG5SBQP=GS2.1.s1752834916\$o2\$g1\$t1752835854\$j49\$l0\$h0");

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch); 
    
    // Try to decode as JSON
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    } else {
        return $response;
    }
}

function safe_json_decode($raw) {
    $raw = trim($raw);
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // strip BOM
    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode failed: " . json_last_error_msg();
        echo "\nRaw input:\n$raw\n";
        return null;
    }

    return $decoded;
}
