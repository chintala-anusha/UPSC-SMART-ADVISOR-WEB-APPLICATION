<?php
require_once __DIR__."/../config.php";

function ask_sarvam($message){

    $url = "https://api.sarvam.ai/v1/chat/completions";

    $data = [
        "model" => "sarvam-m",
        "messages" => [
            [
                "role" => "user",
                "content" => $message
            ]
        ]
    ];

    $headers = [
        "Authorization: Bearer ".SARVAM_API_KEY,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);

    $response = curl_exec($ch);
    $result = json_decode($response,true);

    return $result['choices'][0]['message']['content'] ?? "No response";
}
?>