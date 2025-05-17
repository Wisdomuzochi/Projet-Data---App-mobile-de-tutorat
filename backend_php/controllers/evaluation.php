<?php
require_once __DIR__ . '/../config/db.php';

class EvaluationController {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function noterEtudiant($idTuteur, $idEtudiant, $note, $commentaire) {
        $sql = "INSERT INTO Evaluation (idTuteur, idEtudiant, note, commentaire) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$idTuteur, $idEtudiant, $note, $commentaire]);
    }
}
?>
