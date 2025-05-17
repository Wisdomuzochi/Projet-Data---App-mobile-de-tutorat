<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class MessageSalonController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer les messages d'un salon
    public function getMessagesBySalon($salonId) {
        try {
            $query = "SELECT m.*, u.nom as nom_utilisateur 
                     FROM message_salon m 
                     JOIN utilisateur u ON m.utilisateur_id = u.id 
                     WHERE m.salon_id = :salon_id 
                     ORDER BY m.date_envoi ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salon_id', $salonId);
            $stmt->execute();

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($messages);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Envoyer un message dans un salon
    public function sendMessage($data) {
        try {
            $query = "INSERT INTO message_salon (salon_id, utilisateur_id, contenu) 
                     VALUES (:salon_id, :utilisateur_id, :contenu)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salon_id', $data['salon_id']);
            $stmt->bindParam(':utilisateur_id', $data['utilisateur_id']);
            $stmt->bindParam(':contenu', $data['contenu']);
            
            if($stmt->execute()) {
                $id = $this->conn->lastInsertId();
                
                // Récupérer le message complet avec le nom de l'utilisateur
                $query = "SELECT m.*, u.nom as nom_utilisateur 
                         FROM message_salon m 
                         JOIN utilisateur u ON m.utilisateur_id = u.id 
                         WHERE m.id = :id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                $message = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($message);
            } else {
                throw new Exception("Erreur lors de l'envoi du message");
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// Gestion des requêtes
$database = new Database();
$db = $database->getConnection();
$controller = new MessageSalonController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['salon_id'])) {
            $controller->getMessagesBySalon($_GET['salon_id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'salon_id parameter is required']);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $controller->sendMessage($data);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 