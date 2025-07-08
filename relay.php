<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 🔐 Clé secrète partagée entre ton serveur PHP et Firebase (apiKey attendue dans le JSON)
$SECRET_KEY = getenv('FIREBASE_SECRET_KEY');

// 🔗 URL de ta Firebase Cloud Function (remplace bien par la tienne)
$firebase_url = 'https://us-central1-helpscape-x.cloudfunctions.net/sendData';

// Récupère les données JSON brutes
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Vérifie les champs nécessaires
if (!$data || !isset($data['deviceId'])) {
    http_response_code(400);
    echo json_encode(["error" => "Champs manquants ou JSON invalide"]);
    exit;
}

// Ajoute la clé API dans la requête vers Firebase
$data['apiKey'] = $SECRET_KEY;

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

// Retourne la réponse Firebase au client (ESP32)
http_response_code($httpcode);
echo $response;
