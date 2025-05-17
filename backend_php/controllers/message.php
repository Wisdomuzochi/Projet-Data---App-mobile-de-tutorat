<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/session_manager.php';

class MessageController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    // Envoyer un message dans une conversation
    public function envoyerMessage($idConversation, $expediteurId, $contenu) {
        // Vérifier si l'expéditeur participe bien à la conversation
        $sqlCheck = "SELECT 1 FROM Conversation_Participant WHERE conversation_id = ? AND utilisateur_id = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->execute([$idConversation, $expediteurId]);
        if (!$stmtCheck->fetch()) {
            return false; // Non autorisé
        }

        $this->conn->beginTransaction();
        try {
            // Insérer le message
            $sqlMsg = "INSERT INTO Message (contenu, expediteur, idConversation) VALUES (?, ?, ?)";
            $stmtMsg = $this->conn->prepare($sqlMsg);
            if (!$stmtMsg->execute([$contenu, $expediteurId, $idConversation])) {
                 throw new Exception("Erreur insertion message");
            }
            $messageId = $this->conn->lastInsertId();

            // Mettre à jour le dernier message de la conversation
            $sqlConv = "UPDATE Conversation SET dernierMessageId = ? WHERE id = ?";
            $stmtConv = $this->conn->prepare($sqlConv);
             if (!$stmtConv->execute([$messageId, $idConversation])) {
                 throw new Exception("Erreur MAJ conversation");
            }

            $this->conn->commit();
            // Retourner le message complet (utile pour l'UI)
            return $this->getMessageById($messageId);

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur envoi message: " . $e->getMessage());
            return false;
        }
    }

    // Lister les messages d'une conversation donnée
    public function listerMessages($idConversation, $userId, $limit = 50, $beforeMessageId = null) {
         // Vérifier si l'utilisateur participe bien à la conversation
         $sqlCheck = "SELECT 1 FROM Conversation_Participant WHERE conversation_id = ? AND utilisateur_id = ?";
         $stmtCheck = $this->conn->prepare($sqlCheck);
         $stmtCheck->execute([$idConversation, $userId]);
         if (!$stmtCheck->fetch()) {
             return []; // Non autorisé ou conversation inexistante
         }
         
         $sql = "SELECT m.*, u.nom as nomExpediteur 
                 FROM Message m
                 JOIN Utilisateur u ON m.expediteur = u.id
                 WHERE m.idConversation = ?";
         $params = [$idConversation];
         
         if ($beforeMessageId) {
             $sql .= " AND m.id < ?";
             $params[] = $beforeMessageId;
         }
         
         $sql .= " ORDER BY m.dateEnvoi DESC LIMIT ?";
         $params[] = $limit;
         
         $stmt = $this->conn->prepare($sql);
         $stmt->execute($params);
         // Retourner les messages du plus ancien au plus récent pour affichage
         return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)); 
    }
    
    // Récupérer un message par son ID (utilisé en interne après envoi)
    private function getMessageById($messageId) {
        $sql = "SELECT m.*, u.nom as nomExpediteur 
                FROM Message m
                JOIN Utilisateur u ON m.expediteur = u.id
                WHERE m.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$messageId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Point d'entrée API pour MessageController
header('Content-Type: application/json');
$msgController = new MessageController();
$userId = SessionManager::getUserId();

$action = $_REQUEST['action'] ?? '';

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

switch ($action) {
    case 'envoyer':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idConversation = $_POST['idConversation'] ?? null;
            $contenu = $_POST['contenu'] ?? '';
            
            if ($idConversation && !empty($contenu)) {
                 $newMessage = $msgController->envoyerMessage($idConversation, $userId, $contenu);
                 if ($newMessage) {
                     echo json_encode(['success' => true, 'message' => $newMessage]);
                 } else {
                     echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi du message']);
                 }
            } else {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'error' => 'Données manquantes (idConversation, contenu)']);
            }
        } else {
             http_response_code(405);
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        }
        break;

    case 'lister':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $idConversation = $_GET['idConversation'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $beforeMessageId = $_GET['beforeMessageId'] ?? null;
            
            if ($idConversation) {
                $messages = $msgController->listerMessages($idConversation, $userId, intval($limit), $beforeMessageId);
                echo json_encode($messages);
            } else {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'error' => 'ID de conversation manquant']);
            }
        } else {
             http_response_code(405);
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action Message non reconnue']);
}
?>
