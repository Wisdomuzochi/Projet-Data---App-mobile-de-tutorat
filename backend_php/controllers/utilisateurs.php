// backend_php/controllers/utilisateurs.php
<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Récupérer les données du corps de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Si les données ne sont pas en JSON, essayer de les récupérer depuis $_POST
        if ($data === null) {
            $data = $_POST;
        }

        // Vérifier l'action
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'register':
                    // Vérifier que tous les champs requis sont présents
                    if (!isset($data['email']) || !isset($data['motDePasse']) || 
                        !isset($data['nom']) || !isset($data['prenom']) || !isset($data['role'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Tous les champs sont requis']);
                        exit;
                    }

                    // Vérifier que le rôle est valide
                    if (!in_array($data['role'], ['etudiant', 'tuteur'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Rôle invalide']);
                        exit;
                    }

                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        // Vérifier si l'email existe déjà
                        $stmt = $db->prepare("SELECT id FROM utilisateur WHERE email = ?");
                        $stmt->execute([$data['email']]);
                        if ($stmt->rowCount() > 0) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Cet email est déjà utilisé']);
                            exit;
                        }

                        // Hacher le mot de passe
                        $hashedPassword = password_hash($data['motDePasse'], PASSWORD_DEFAULT);

                        // Insérer le nouvel utilisateur
                        $stmt = $db->prepare("
                            INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, role) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $data['email'],
                            $hashedPassword,
                            $data['nom'],
                            $data['prenom'],
                            $data['role']
                        ]);

                        $userId = $db->lastInsertId();

                        // Récupérer l'utilisateur créé
                        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Ne pas renvoyer le mot de passe
                        unset($user['mot_de_passe']);

                        echo json_encode([
                            'success' => true,
                            'user' => $user
                        ]);

                    } catch (PDOException $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de l\'inscription: ' . $e->getMessage()]);
                    }
                    break;

                case 'login':
                    if (!isset($data['email']) || !isset($data['motDePasse'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Email et mot de passe requis']);
                        exit;
                    }

                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        $stmt = $db->prepare("SELECT * FROM utilisateur WHERE email = ?");
                        $stmt->execute([$data['email']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$user || !password_verify($data['motDePasse'], $user['mot_de_passe'])) {
                            http_response_code(401);
                            echo json_encode(['error' => 'Email ou mot de passe incorrect']);
                            exit;
                        }

                        // Ne pas renvoyer le mot de passe
                        unset($user['mot_de_passe']);

                        echo json_encode([
                            'success' => true,
                            'user' => $user
                        ]);

                    } catch (PDOException $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de la connexion: ' . $e->getMessage()]);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Action non spécifiée']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?>