<?php
require_once __DIR__ . '/../config/db.php';

class DocumentController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function partagerDocument($nom, $url, $proprietaireId, $matiere, $filiere) {
        $sql = "INSERT INTO DocumentPartage (nom, url, proprietaireId, matiere, filiere) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nom, $url, $proprietaireId, $matiere, $filiere]);
    }
}
?>
