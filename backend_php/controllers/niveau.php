<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT * FROM Niveau ORDER BY id");
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($niveaux);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 