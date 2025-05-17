<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Récupérer un tuteur spécifique
            $stmt = $db->prepare("
                SELECT u.*, n.nom as niveau_nom,
                GROUP_CONCAT(DISTINCT m.nom) as matieres
                FROM utilisateur u
                LEFT JOIN niveau n ON u.niveau_id = n.id
                LEFT JOIN matiere_utilisateur mu ON u.id = mu.utilisateur_id
                LEFT JOIN matiere m ON mu.matiere_id = m.id
                WHERE u.id = ? AND u.role = 'tuteur'
                GROUP BY u.id
            ");
            $stmt->execute([$_GET['id']]);
            $tuteur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tuteur) {
                echo json_encode(['success' => true, 'tuteur' => $tuteur]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Tuteur non trouvé']);
            }
        } else {
            // Récupérer tous les tuteurs disponibles
            $stmt = $db->prepare("
                SELECT u.*, n.nom as niveau_nom,
                GROUP_CONCAT(DISTINCT m.nom) as matieres
                FROM utilisateur u
                LEFT JOIN niveau n ON u.niveau_id = n.id
                LEFT JOIN matiere_utilisateur mu ON u.id = mu.utilisateur_id
                LEFT JOIN matiere m ON mu.matiere_id = m.id
                WHERE u.role = 'tuteur' AND u.est_disponible = true
                GROUP BY u.id
            ");
            $stmt->execute();
            $tuteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tuteurs' => $tuteurs]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Action non spécifiée']);
            exit;
        }

        switch ($data['action']) {
            case 'devenir_tuteur':
                if (!isset($data['utilisateur_id']) || !isset($data['matieres']) || !isset($data['disponibilites'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Données manquantes']);
                    exit;
                }

                try {
                    $db->beginTransaction();

                    // Mettre à jour le rôle de l'utilisateur
                    $stmt = $db->prepare("
                        UPDATE utilisateur 
                        SET role = 'tuteur', 
                            disponibilites = ?,
                            est_disponible = true
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        json_encode($data['disponibilites']),
                        $data['utilisateur_id']
                    ]);

                    // Ajouter les matières du tuteur
                    $stmt = $db->prepare("
                        INSERT INTO matiere_utilisateur (utilisateur_id, matiere_id, type) 
                        VALUES (?, ?, 'enseignement')
                    ");
                    
                    foreach ($data['matieres'] as $matiereId) {
                        $stmt->execute([$data['utilisateur_id'], $matiereId]);
                    }

                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Statut tuteur mis à jour avec succès']);
                } catch (PDOException $e) {
                    $db->rollBack();
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
                }
                break;

            case 'update_disponibilites':
                if (!isset($data['utilisateur_id']) || !isset($data['disponibilites'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Données manquantes']);
        exit;
    }

                try {
                    $stmt = $db->prepare("
                        UPDATE utilisateur 
                        SET disponibilites = ?
                        WHERE id = ? AND role = 'tuteur'
                    ");
                    $stmt->execute([
                        json_encode($data['disponibilites']),
                        $data['utilisateur_id']
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Disponibilités mises à jour']);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
            }
            break;
            }
            break;
}
?>
