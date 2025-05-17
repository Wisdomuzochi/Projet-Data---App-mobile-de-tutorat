<?php
// Configuration de l'environnement
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'entraide_tutorat');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'API
define('API_BASE_URL', 'http://172.20.10.7/backend_php');
define('API_VERSION', 'v1');
define('JWT_SECRET', 'votre_clé_secrète_jwt');
define('JWT_EXPIRATION', 3600); // 1 heure en secondes

// Configuration des uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configuration des erreurs
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuration des rôles
define('ROLE_ETUDIANT', 'etudiant');
define('ROLE_TUTEUR', 'tuteur');

// Configuration du dossier d'upload
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Fonction pour valider les tokens JWT
function validateJWT($token) {
    try {
        $decoded = JWT::decode($token, JWT_SECRET, array('HS256'));
        return $decoded;
    } catch (Exception $e) {
        return false;
    }
}

// Fonction pour générer une réponse JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?> 