<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['niveau_id'])) {
            // Récupérer les salons pour un niveau spécifique
            $stmt = $db->prepare("
                SELECT s.*, m.nom as matiere_nom, n.nom as niveau_nom 
                FROM salon_entraide s
                JOIN matiere m ON s.matiere_id = m.id
                JOIN niveau n ON s.niveau_id = n.id
                WHERE s.niveau_id = ?
                ORDER BY m.nom
            ");
            $stmt->execute([$_GET['niveau_id']]);
        } else {
            // Récupérer tous les salons
            $stmt = $db->prepare("
                SELECT s.*, m.nom as matiere_nom, n.nom as niveau_nom 
                FROM salon_entraide s
                JOIN matiere m ON s.matiere_id = m.id
                JOIN niveau n ON s.niveau_id = n.id
                ORDER BY n.id, m.nom
            ");
            $stmt->execute();
        }
        
        $salons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'salons' => $salons]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['niveau_id']) || !isset($data['matiere_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Niveau et matière requis']);
            exit;
        }

        try {
            // Vérifier si le salon existe déjà
            $stmt = $db->prepare("
                SELECT id FROM salon_entraide 
                WHERE niveau_id = ? AND matiere_id = ?
            ");
            $stmt->execute([$data['niveau_id'], $data['matiere_id']]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Ce salon existe déjà']);
                exit;
            }

            // Créer le salon
            $stmt = $db->prepare("
                INSERT INTO salon_entraide (nom, niveau_id, matiere_id) 
                VALUES (?, ?, ?)
            ");
            
            $nom = "Salon " . $data['matiere_nom'] . " " . $data['niveau_code'];
            $stmt->execute([$nom, $data['niveau_id'], $data['matiere_id']]);
            
            $salonId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'salon_id' => $salonId,
                'message' => 'Salon créé avec succès'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la création du salon: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 