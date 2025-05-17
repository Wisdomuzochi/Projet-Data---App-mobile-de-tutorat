<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "SELECT nc.*, m.nom as nom_matiere, u.nom as nom_utilisateur,
                         (SELECT AVG(note) FROM note_cours_evaluation WHERE note_cours_id = nc.id) as note_moyenne,
                         (SELECT COUNT(*) FROM note_cours_vue WHERE note_cours_id = nc.id) as nombre_vues
                  FROM note_cours nc
                  JOIN matiere m ON nc.matiere_id = m.id
                  JOIN utilisateur u ON nc.utilisateur_id = u.id";

        $params = [];
        if (isset($_GET['matiere_id'])) {
            $query .= " WHERE nc.matiere_id = :matiere_id";
            $params[':matiere_id'] = $_GET['matiere_id'];
        }
        if (isset($_GET['niveau_id'])) {
            $query .= isset($_GET['matiere_id']) ? " AND" : " WHERE";
            $query .= " m.niveau_id = :niveau_id";
            $params[':niveau_id'] = $_GET['niveau_id'];
        }

        $query .= " ORDER BY nc.date_creation DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les commentaires pour chaque note
        foreach ($notes as &$note) {
            $stmt = $pdo->prepare("
                SELECT c.*, u.nom as nom_utilisateur
                FROM note_cours_commentaire c
                JOIN utilisateur u ON c.utilisateur_id = u.id
                WHERE c.note_cours_id = :note_id
                ORDER BY c.date_creation DESC
            ");
            $stmt->execute([':note_id' => $note['id']]);
            $note['commentaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($notes);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'noter':
                    if (!isset($_POST['note_id']) || !isset($_POST['note'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Paramètres manquants']);
                        exit;
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO note_cours_evaluation (note_cours_id, utilisateur_id, note)
                        VALUES (:note_id, :utilisateur_id, :note)
                        ON DUPLICATE KEY UPDATE note = :note
                    ");
                    $stmt->execute([
                        ':note_id' => $_POST['note_id'],
                        ':utilisateur_id' => $_POST['utilisateur_id'],
                        ':note' => $_POST['note']
                    ]);
                    echo json_encode(['success' => true]);
                    break;

                case 'incrementer_vues':
                    if (!isset($_POST['note_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID de note manquant']);
                        exit;
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO note_cours_vue (note_cours_id, utilisateur_id)
                        VALUES (:note_id, :utilisateur_id)
                    ");
                    $stmt->execute([
                        ':note_id' => $_POST['note_id'],
                        ':utilisateur_id' => $_POST['utilisateur_id']
                    ]);
                    echo json_encode(['success' => true);
                    break;

                default:
                    // Upload de fichier
                    if (!isset($_FILES['fichier'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Aucun fichier uploadé']);
                        exit;
                    }

                    $uploadDir = '../uploads/notes/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = uniqid() . '_' . basename($_FILES['fichier']['name']);
                    $uploadFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadFile)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO note_cours (utilisateur_id, matiere_id, titre, description, chemin_fichier)
                            VALUES (:utilisateur_id, :matiere_id, :titre, :description, :chemin_fichier)
                        ");
                        $stmt->execute([
                            ':utilisateur_id' => $_POST['utilisateur_id'],
                            ':matiere_id' => $_POST['matiere_id'],
                            ':titre' => $_POST['titre'],
                            ':description' => $_POST['description'],
                            ':chemin_fichier' => $uploadFile
                        ]);

                        $noteId = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("
                            SELECT nc.*, m.nom as nom_matiere, u.nom as nom_utilisateur
                            FROM note_cours nc
                            JOIN matiere m ON nc.matiere_id = m.id
                            JOIN utilisateur u ON nc.utilisateur_id = u.id
                            WHERE nc.id = :id
                        ");
                        $stmt->execute([':id' => $noteId]);
                        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de l\'upload du fichier']);
                    }
                    break;
            }
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 