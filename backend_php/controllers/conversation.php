<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/session_manager.php';

class ConversationController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    // Créer une nouvelle conversation ou récupérer une conversation 1-1 existante
    public function creerOuOuvrirConversation($userId, $participantIds, $titre = null) {
        // S'assurer que l'utilisateur courant est inclus dans les participants
        if (!in_array($userId, $participantIds)) {
            $participantIds[] = $userId;
        }
        sort($participantIds); // Trier pour trouver les conversations 1-1 existantes

        // Pour les conversations 1-1, vérifier si elle existe déjà
        if (count($participantIds) == 2) {
            $sqlCheck = "SELECT cp1.conversation_id 
                         FROM Conversation_Participant cp1
                         JOIN Conversation_Participant cp2 ON cp1.conversation_id = cp2.conversation_id
                         WHERE cp1.utilisateur_id = ? AND cp2.utilisateur_id = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$participantIds[0], $participantIds[1]]);
            $existingConv = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($existingConv) {
                return $existingConv['conversation_id']; // Retourner l'ID existant
            }
        }

        // Créer la conversation
        // Générer un titre par défaut si non fourni (surtout pour les groupes)
        if ($titre === null) {
            if (count($participantIds) > 2) {
                $titre = "Groupe de discussion"; // Simple titre par défaut
            } else {
                 // Pour 1-1, on pourrait récupérer le nom de l'autre participant
                 $otherUserId = ($participantIds[0] == $userId) ? $participantIds[1] : $participantIds[0];
                 $sqlNom = "SELECT nom FROM Utilisateur WHERE id = ?";
                 $stmtNom = $this->conn->prepare($sqlNom);
                 $stmtNom->execute([$otherUserId]);
                 $otherUser = $stmtNom->fetch(PDO::FETCH_ASSOC);
                 $titre = $otherUser ? $otherUser['nom'] : "Conversation";
            }
        }
        
        $sqlConv = "INSERT INTO Conversation (titre) VALUES (?)";
        $stmtConv = $this->conn->prepare($sqlConv);
        if ($stmtConv->execute([$titre])) {
            $conversationId = $this->conn->lastInsertId();
            
            // Ajouter les participants
            $sqlPart = "INSERT INTO Conversation_Participant (conversation_id, utilisateur_id) VALUES (?, ?)";
            $stmtPart = $this->conn->prepare($sqlPart);
            foreach ($participantIds as $pId) {
                $stmtPart->execute([$conversationId, $pId]);
            }
            return $conversationId;
        } else {
            return false;
        }
    }

    // Lister les conversations pour l'utilisateur courant
    public function listerConversations($userId) {
        // Récupérer les conversations avec le dernier message et le nom de l'autre participant (pour 1-1)
        $sql = "SELECT 
                    c.id, 
                    c.titre, 
                    c.dateCreation,
                    m.contenu as dernierMessageContenu,
                    m.dateEnvoi as dernierMessageDate,
                    u_exp.nom as dernierMessageExpediteur,
                    GROUP_CONCAT(DISTINCT u_part.nom SEPARATOR ', ') as participantsNoms,
                    GROUP_CONCAT(DISTINCT u_part.id SEPARATOR ',') as participantsIds
                FROM Conversation c
                JOIN Conversation_Participant cp ON c.id = cp.conversation_id
                LEFT JOIN Message m ON c.dernierMessageId = m.id
                LEFT JOIN Utilisateur u_exp ON m.expediteur = u_exp.id
                LEFT JOIN Conversation_Participant cp_other ON c.id = cp_other.conversation_id
                LEFT JOIN Utilisateur u_part ON cp_other.utilisateur_id = u_part.id
                WHERE cp.utilisateur_id = ?
                GROUP BY c.id, c.titre, c.dateCreation, m.contenu, m.dateEnvoi, u_exp.nom
                ORDER BY m.dateEnvoi DESC, c.dateCreation DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajuster le titre pour les conversations 1-1
        foreach ($conversations as &$conv) {
             $participants = explode(',', $conv['participantsIds']);
             if (count($participants) == 2) {
                 $otherUserId = ($participants[0] == $userId) ? $participants[1] : $participants[0];
                 // Trouver le nom correspondant dans participantsNoms
                 $noms = explode(', ', $conv['participantsNoms']);
                 $idNomMap = array_combine($participants, $noms);
                 $conv['titre'] = $idNomMap[$otherUserId] ?? $conv['titre'];
             }
             unset($conv['participantsIds']); // Ne pas renvoyer les IDs ici
             //unset($conv['participantsNoms']); // Garder les noms peut être utile
        }

        return $conversations;
    }
}

// Point d'entrée API pour ConversationController
header('Content-Type: application/json');
$convController = new ConversationController();
$userId = SessionManager::getUserId();

$action = $_REQUEST['action'] ?? '';

if (!$userId && $action !== 'some_public_action') { // Vérifier connexion sauf pour actions publiques
    http_response_code(401); // Non autorisé
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

switch ($action) {
    case 'creerOuOuvrir':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Décoder les IDs depuis la chaîne JSON
            $participantIdsJson = $_POST['participantIds'] ?? '[]'; 
            $participantIds = json_decode($participantIdsJson, true); // true pour tableau associatif/indexé PHP
            $titre = $_POST['titre'] ?? null;
            
            // Vérifier si le décodage a réussi et si c'est bien un tableau
            if (!is_array($participantIds)) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'error' => 'Format participantIds invalide']);
                 exit;
            }
            
            // S'assurer que les IDs sont des entiers
            $participantIds = array_map('intval', $participantIds);
            
            $conversationId = $convController->creerOuOuvrirConversation($userId, $participantIds, $titre);
            if ($conversationId) {
                echo json_encode(['success' => true, 'conversationId' => $conversationId]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création/ouverture de la conversation']);
            }
        } else {
             http_response_code(405);
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        }
        break;

    case 'lister':
         if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $conversations = $convController->listerConversations($userId);
            echo json_encode($conversations);
         } else {
             http_response_code(405);
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
         }
         break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action Conversation non reconnue']);
}
?>
