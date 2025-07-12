<?php
header('Content-Type: application/json');

// Récupération de la clé API Google via variable d'environnement
$googleApiKey = getenv("GOOGLE_API_KEY");

if (!$googleApiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Clé API manquante. Définissez GOOGLE_API_KEY."]);
    exit;
}

// Vérifie que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée. Utilisez POST."]);
    exit;
}

// Lire le contenu brut JSON
$rawInput = file_get_contents("php://input");
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(["error" => "Aucune donnée reçue."]);
    exit;
}

// Vérifie que c'est du JSON valide
$data = json_decode($rawInput, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "JSON invalide."]);
    exit;
}

// Requête vers l'API Google
$url = "https://www.googleapis.com/geolocation/v1/geolocate?key=" . urlencode($googleApiKey);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// En cas d'erreur cURL
if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur cURL : $error"]);
    exit;
}

// Réponse brute de Google
http_response_code($httpCode);
echo $response;
?>
