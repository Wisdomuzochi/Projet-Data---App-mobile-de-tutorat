<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class DocumentSalonController {
    private $conn;
    private $uploadDir = '../uploads/';

    public function __construct($db) {
        $this->conn = $db;
        // Créer le dossier d'upload s'il n'existe pas
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    // Récupérer les documents d'un salon
    public function getDocumentsBySalon($salonId) {
        try {
            $query = "SELECT d.*, u.nom as nom_utilisateur 
                     FROM document_salon d 
                     JOIN utilisateur u ON d.utilisateur_id = u.id 
                     WHERE d.salon_id = :salon_id 
                     ORDER BY d.date_upload DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salon_id', $salonId);
            $stmt->execute();

            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($documents);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Uploader un document
    public function uploadDocument($data) {
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception("Aucun fichier n'a été uploadé");
            }

            $file = $_FILES['file'];
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetPath = $this->uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $query = "INSERT INTO document_salon (salon_id, utilisateur_id, nom_fichier, chemin_fichier) 
                         VALUES (:salon_id, :utilisateur_id, :nom_fichier, :chemin_fichier)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':salon_id', $data['salon_id']);
                $stmt->bindParam(':utilisateur_id', $data['utilisateur_id']);
                $stmt->bindParam(':nom_fichier', $data['nom_fichier']);
                $stmt->bindParam(':chemin_fichier', $fileName);
                
                if($stmt->execute()) {
                    $id = $this->conn->lastInsertId();
                    
                    // Récupérer le document complet avec le nom de l'utilisateur
                    $query = "SELECT d.*, u.nom as nom_utilisateur 
                             FROM document_salon d 
                             JOIN utilisateur u ON d.utilisateur_id = u.id 
                             WHERE d.id = :id";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    
                    $document = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($document);
                } else {
                    throw new Exception("Erreur lors de l'enregistrement du document");
                }
            } else {
                throw new Exception("Erreur lors de l'upload du fichier");
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Télécharger un document
    public function downloadDocument($documentId) {
        try {
            $query = "SELECT * FROM document_salon WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $documentId);
            $stmt->execute();

            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($document) {
                $filePath = $this->uploadDir . $document['chemin_fichier'];
                
                if (file_exists($filePath)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $document['nom_fichier'] . '"');
                    header('Content-Length: ' . filesize($filePath));
                    readfile($filePath);
                    exit;
                } else {
                    throw new Exception("Fichier non trouvé");
                }
            } else {
                throw new Exception("Document non trouvé");
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// Gestion des requêtes
$database = new Database();
$db = $database->getConnection();
$controller = new DocumentSalonController($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['salon_id'])) {
            $controller->getDocumentsBySalon($_GET['salon_id']);
        } elseif(isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['document_id'])) {
            $controller->downloadDocument($_GET['document_id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres invalides']);
        }
        break;
        
    case 'POST':
        $controller->uploadDocument($_POST);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 