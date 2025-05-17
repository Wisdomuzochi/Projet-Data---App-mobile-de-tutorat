<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['note_id']) || !isset($_POST['utilisateur_id']) || !isset($_POST['contenu'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO note_cours_commentaire (note_cours_id, utilisateur_id, contenu)
            VALUES (:note_id, :utilisateur_id, :contenu)
        ");
        $stmt->execute([
            ':note_id' => $_POST['note_id'],
            ':utilisateur_id' => $_POST['utilisateur_id'],
            ':contenu' => $_POST['contenu']
        ]);

        $commentaireId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("
            SELECT c.*, u.nom as nom_utilisateur
            FROM note_cours_commentaire c
            JOIN utilisateur u ON c.utilisateur_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $commentaireId]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 