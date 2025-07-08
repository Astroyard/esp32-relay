<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Clé secrète attendue
$SECRET_KEY = getenv('FIREBASE_SECRET_KEY');

// Récupère les données JSON brutes
$input = file_get_contents("php://input");
$data = json_decode($input, true);

error_log("Données reçues : " . print_r($data, true)); // Log pour debug

// Vérifie la clé apiKey dans le JSON reçu
if (!isset($data['apiKey']) || $data['apiKey'] !== $SECRET_KEY) {
    error_log("Erreur 403 : clé secrète invalide");
    http_response_code(403);
    echo json_encode(["error" => "Clé secrète invalide"]);
    exit;
}

// Enlève la clé API avant d’envoyer à Firebase
unset($data['apiKey']);

// URL de la Cloud Function Firebase
$firebase_url = 'https://us-central1-helpscape-x.cloudfunctions.net/sendData';

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
    exit;
}

$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpcode);
echo $response;
