<?php

// Récupérer la clé API Google Geolocation à partir de la variable d'environnement
$googleApiKey = getenv("GOOGLE_API_KEY");

if (!$googleApiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Clé API Google manquante."]);
    exit;
}

// Lire le corps brut de la requête POST (JSON)
$rawPostData = file_get_contents("php://input");

if (!$rawPostData) {
    http_response_code(400);
    echo json_encode(["error" => "Aucune donnée reçue."]);
    exit;
}

// Préparer la requête vers l'API Google Geolocation
$googleUrl = "https://www.googleapis.com/geolocation/v1/geolocate?key=" . urlencode($googleApiKey);

$ch = curl_init($googleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $rawPostData);

// Exécuter la requête vers Google
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Vérifier les erreurs de cURL
if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur cURL : $curlError"]);
    exit;
}

// Retourner la réponse Google à l'ESP32
http_response_code($httpCode);
header("Content-Type: application/json");
echo $response;
