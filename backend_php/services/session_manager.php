<?php
session_start(); // Démarrer la session PHP

class SessionManager {
    
    // Stocker l'ID utilisateur dans la session lors de la connexion
    public static function setUserSession($userId) {
        $_SESSION['user_id'] = $userId;
    }

    // Récupérer l'ID utilisateur de la session
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    // Détruire la session (déconnexion)
    public static function destroySession() {
        session_unset(); // Supprime toutes les variables de session
        session_destroy(); // Détruit la session
    }

    // Vérifier si l'utilisateur est connecté
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}
?> 