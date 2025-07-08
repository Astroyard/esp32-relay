<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 🔐 Clé secrète partagée entre ton serveur PHP et Firebase
$SECRET_KEY = getenv('FIREBASE_SECRET_KEY');

// Log des headers reçus pour debug
error_log("Headers reçus : " . print_r(getallheaders(), true));

// Recherche case-insensitive du header Authorization
$headers = getallheaders();
$authHeader = null;
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $authHeader = $value;
        break;
    }
}

// Log de la clé reçue dans Authorization
error_log("Authorization header reçu : " . var_export($authHeader, true));
error_log("Clé secrète attendue : " . var_export($SECRET_KEY, true));

if ($authHeader !== $SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Clé secrète invalide ou absente']);
    error_log("Erreur 403 : clé secrète invalide");
    exit();
}

// 🔗 URL de ta Firebase Cloud Function (remplace bien par la tienne)
$firebase_url = 'https://us-central1-helpscape-x.cloudfunctions.net/sendData';

// Récupère les données JSON brutes
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Vérifie les champs nécessaires
if (!$data || !isset($data['deviceId'])) {
    http_response_code(400);
    echo json_encode(["error" => "Champs manquants ou JSON invalide"]);
    error_log("Erreur 400 : JSON invalide ou champs manquants");
    exit;
}

// Ajoute la clé secrète dans la requête vers Firebase
$data['auth'] = $SECRET_KEY;

// Prépare la requête cURL vers Firebase
$ch = curl_init($firebase_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
    error_log("Erreur cURL : " . curl_error($ch));
    exit;
}

$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log de la réponse de Firebase
error_log("Réponse Firebase HTTP code : " . $httpcode);
error_log("Réponse Firebase body : " . $response);

// Retourne la réponse Firebase au client (ESP32)
http_response_code($httpcode);
echo $response;
