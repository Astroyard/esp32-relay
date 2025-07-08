<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Clé secrète (directe ou via getenv)
$SECRET_KEY = getenv('FIREBASE_SECRET_KEY');
//$SECRET_KEY = "ta_clef_secrete_ici"; // pour debug

$input = file_get_contents("php://input");
$data = json_decode($input, true);

error_log("Données reçues : " . print_r($data, true));
error_log("Clé reçue: '" . ($data['apiKey'] ?? '') . "'");
error_log("Clé attendue: '" . $SECRET_KEY . "'");

if (!$data || !isset($data['deviceId']) || !isset($data['apiKey'])) {
    http_response_code(400);
    echo json_encode(["error" => "Champs manquants ou JSON invalide"]);
    exit;
}

if ($data['apiKey'] !== $SECRET_KEY) {
    error_log("Erreur 403 : clé secrète invalide");
    http_response_code(403);
    echo json_encode(["error" => "Clé secrète invalide"]);
    exit;
}

// URL Cloud Function Firebase
$firebase_url = 'https://us-central1-helpscape-x.cloudfunctions.net/sendData';

// Prépare données pour Firebase
$firebase_data = [
    'deviceId' => $data['deviceId'],
    'latitude' => $data['latitude'] ?? 0,
    'longitude' => $data['longitude'] ?? 0,
    'apiKey' => $SECRET_KEY
];

// Envoi cURL vers Firebase
$ch = curl_init($firebase_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebase_data));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("Réponse Firebase HTTP code : $httpcode");
error_log("Réponse Firebase body : $response");

http_response_code($httpcode);
echo $response;
