<?php
require_once __DIR__ . '/../config/db.php';

class ModerationController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function signalerMessage($idMessage, $modereurId) {
        $sql = "INSERT INTO Moderation (idMessage, modereurId, action) VALUES (?, ?, 'signalement')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idMessage, $modereurId]);
    }

    public function supprimerMessage($idMessage, $modereurId) {
        $sql = "INSERT INTO Moderation (idMessage, modereurId, action) VALUES (?, ?, 'suppression')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idMessage, $modereurId]);
    }
}
?>
