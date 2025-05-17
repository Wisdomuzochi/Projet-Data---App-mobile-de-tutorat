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

    $sql = "SELECT * FROM matiere";
    $params = [];

    if (isset($_GET['niveau_id'])) {
        $sql .= " WHERE niveau_id = :niveau_id";
        $params[':niveau_id'] = $_GET['niveau_id'];
    }

    $sql .= " ORDER BY nom";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'matieres' => $matieres
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 