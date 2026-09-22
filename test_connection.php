<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$target = "http://127.0.0.1:11434/api/tags";

echo "<h2>Ollama Connection Diagnostic</h2>";
echo "Attempting to reach: $target <br><hr>";

if (!function_exists('curl_init')) {
    die("<strong>CRITICAL ERROR:</strong> PHP cURL extension is NOT enabled. Please edit your php.ini and restart Apache.");
}

$ch = curl_init($target);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error_no = curl_errno($ch);
$error_msg = curl_error($ch);
$info = curl_getinfo($ch);

if ($error_no) {
    echo "<strong>Connection Failed!</strong><br>";
    echo "Error Number: " . $error_no . "<br>";
    echo "Error Message: " . $error_msg . "<br>";
    if ($error_no == 7) {
        echo "<p><em>Note: Error 7 (Failed to connect) usually means a Firewall is blocking PHP or the IP is wrong.</em></p>";
    }
} else {
    echo "<strong>Success!</strong> PHP can see Ollama.<br>";
    echo "HTTP Code: " . $info['http_code'] . "<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
}
curl_close($ch);
?>