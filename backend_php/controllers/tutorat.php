<?php
require_once __DIR__ . '/../config/db.php';

class TutoratController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    // Un tuteur propose un nouveau tutorat
    public function proposerTutorat($idTuteur, $titre, $description, $matiere, $niveau) {
        $sql = "INSERT INTO Tutorat (idTuteur, titre, description, matiere, niveau) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idTuteur, $titre, $description, $matiere, $niveau]);
    }

    // Consulter toutes les propositions de tutorat (pour les étudiants)
    public function consulterTutoratsDisponibles($matiereFiltre = null, $niveauFiltre = null) {
        $sql = "SELECT t.*, u.nom as nomTuteur 
                FROM Tutorat t 
                JOIN Utilisateur u ON t.idTuteur = u.id";
        $conditions = [];
        $params = [];
        if ($matiereFiltre && $matiereFiltre !== 'Tout') {
            $conditions[] = "t.matiere = ?";
            $params[] = $matiereFiltre;
        }
        if ($niveauFiltre && $niveauFiltre !== 'Tout') {
            $conditions[] = "t.niveau = ?";
            $params[] = $niveauFiltre;
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY t.dateCreation DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Consulter les tutorats proposés par un tuteur spécifique
    public function consulterMesTutorats($idTuteur) {
        $sql = "SELECT * FROM Tutorat WHERE idTuteur = ? ORDER BY dateCreation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idTuteur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Un étudiant demande une session pour un tutorat spécifique
    public function demanderSession($idTutorat, $idEtudiant, $dateHeure) {
        // TODO: Ajouter une vérification pour s'assurer qu'une demande n'existe pas déjà?
        $sql = "INSERT INTO SessionTutorat (idTutorat, idEtudiant, dateHeure, statut) VALUES (?, ?, ?, 'en_attente')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idTutorat, $idEtudiant, $dateHeure]);
    }

    // Un tuteur répond à une demande de session (accepte ou refuse)
    public function repondreSession($idSession, $idTuteur, $statut) {
        // Vérifier que le tuteur a bien le droit de répondre à cette session
        $sqlCheck = "SELECT t.idTuteur FROM SessionTutorat s JOIN Tutorat t ON s.idTutorat = t.id WHERE s.id = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->execute([$idSession]);
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$result || $result['idTuteur'] != $idTuteur) {
            return false; // Non autorisé ou session inexistante
        }

        $sql = "UPDATE SessionTutorat SET statut = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        // S'assurer que le statut est valide ('acceptee' ou 'refusee')
        if ($statut === 'acceptee' || $statut === 'refusee') {
           return $stmt->execute([$statut, $idSession]);
        }
        return false;
    }

    // Voir les sessions (demandes) pour les tutorats d'un tuteur donné
    public function voirSessionsTuteur($idTuteur, $statutFiltre = null) {
        $sql = "SELECT s.*, t.titre as titreTutorat, u.nom as nomEtudiant 
                FROM SessionTutorat s
                JOIN Tutorat t ON s.idTutorat = t.id
                JOIN Utilisateur u ON s.idEtudiant = u.id
                WHERE t.idTuteur = ?";
        $params = [$idTuteur];
        if ($statutFiltre) {
             $sql .= " AND s.statut = ?";
             $params[] = $statutFiltre;
        }
        $sql .= " ORDER BY s.dateDemande DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Voir les sessions demandées par un étudiant donné
    public function voirSessionsEtudiant($idEtudiant, $statutFiltre = null) {
        $sql = "SELECT s.*, t.titre as titreTutorat, tu.nom as nomTuteur
                FROM SessionTutorat s
                JOIN Tutorat t ON s.idTutorat = t.id
                JOIN Utilisateur tu ON t.idTuteur = tu.id
                WHERE s.idEtudiant = ?";
        $params = [$idEtudiant];
         if ($statutFiltre) {
             $sql .= " AND s.statut = ?";
             $params[] = $statutFiltre;
        }
        $sql .= " ORDER BY s.dateDemande DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Point d'entrée API pour TutoratController
header('Content-Type: application/json');
require_once __DIR__ . '/../services/session_manager.php'; // Pour récupérer l'ID utilisateur connecté

$tutoratController = new TutoratController();
$userId = SessionManager::getUserId(); // Récupérer l'ID de l'utilisateur de la session PHP

$action = $_REQUEST['action'] ?? ''; // Utiliser $_REQUEST pour GET et POST

switch ($action) {
    case 'proposer':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
            $titre = $_POST['titre'] ?? '';
            $description = $_POST['description'] ?? '';
            $matiere = $_POST['matiere'] ?? '';
            $niveau = $_POST['niveau'] ?? '';
            // TODO: Vérifier si l'utilisateur est bien un tuteur
            if ($tutoratController->proposerTutorat($userId, $titre, $description, $matiere, $niveau)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la proposition']);
            }
        } else {
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
        }
        break;

    case 'listerDisponibles': // Renommé pour clarté
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
             $matiereFiltre = $_GET['matiere'] ?? null;
             $niveauFiltre = $_GET['niveau'] ?? null;
             $tutorats = $tutoratController->consulterTutoratsDisponibles($matiereFiltre, $niveauFiltre);
             echo json_encode($tutorats);
        } else {
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        }
        break;
        
    case 'listerMesTutorats':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $userId) {
             // TODO: Vérifier si l'utilisateur est bien un tuteur
             $tutorats = $tutoratController->consulterMesTutorats($userId);
             echo json_encode($tutorats);
        } else {
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
        }
        break;

    case 'demanderSession':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
            $idTutorat = $_POST['idTutorat'] ?? null;
            $dateHeure = $_POST['dateHeure'] ?? null; // Format YYYY-MM-DD HH:MM:SS
            if ($idTutorat && $dateHeure && $tutoratController->demanderSession($idTutorat, $userId, $dateHeure)) {
                 echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'error' => 'Erreur lors de la demande de session']);
            }
        } else {
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
        }
        break;
        
    case 'repondreSession':
         if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
            $idSession = $_POST['idSession'] ?? null;
            $statut = $_POST['statut'] ?? null; // 'acceptee' ou 'refusee'
            if ($idSession && $statut && $tutoratController->repondreSession($idSession, $userId, $statut)) {
                echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'error' => 'Erreur ou action non autorisée']);
            }
         } else {
              echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
         }
         break;
         
     case 'voirSessionsTuteur':
         if ($_SERVER['REQUEST_METHOD'] === 'GET' && $userId) {
             $statutFiltre = $_GET['statut'] ?? null;
             $sessions = $tutoratController->voirSessionsTuteur($userId, $statutFiltre);
             echo json_encode($sessions);
         } else {
              echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
         }
         break;
         
    case 'voirSessionsEtudiant':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $userId) {
             $statutFiltre = $_GET['statut'] ?? null;
             $sessions = $tutoratController->voirSessionsEtudiant($userId, $statutFiltre);
             echo json_encode($sessions);
        } else {
             echo json_encode(['success' => false, 'error' => 'Méthode non autorisée ou utilisateur non connecté']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action Tutorat non reconnue']);
}
?>
