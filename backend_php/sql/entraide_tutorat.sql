-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 11 avr. 2025 à 15:15
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `entraide_tutorat`
--

-- Suppression des tables si elles existent
DROP TABLE IF EXISTS `matiere_utilisateur`;
DROP TABLE IF EXISTS `message_salon`;
DROP TABLE IF EXISTS `document_salon`;
DROP TABLE IF EXISTS `demande_aide`;
DROP TABLE IF EXISTS `salon_entraide`;
DROP TABLE IF EXISTS `matiere`;
DROP TABLE IF EXISTS `utilisateur`;
DROP TABLE IF EXISTS `niveau`;

-- Création de la table niveau
CREATE TABLE `niveau` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des niveaux
INSERT INTO `niveau` (`nom`) VALUES
('Licence 1'),
('Licence 2'),
('Licence 3'),
('Master 1'),
('Master 2');

-- Création de la table matiere
CREATE TABLE `matiere` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `niveau_id` (`niveau_id`),
  CONSTRAINT `matiere_ibfk_1` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des matières
INSERT INTO `matiere` (`nom`, `niveau_id`) VALUES
('Mathématiques', 1),
('Physique', 1),
('Chimie', 1),
('Informatique', 1),
('Biologie', 1),
('Géologie', 1),
('Économie', 1),
('Gestion', 1),
('Droit', 1),
('Langues', 1),
('Philosophie', 1),
('Histoire', 1),
('Mathématiques', 2),
('Physique', 2),
('Chimie', 2),
('Informatique', 2),
('Biologie', 2),
('Géologie', 2),
('Économie', 2),
('Gestion', 2),
('Droit', 2),
('Langues', 2),
('Philosophie', 2),
('Histoire', 2),
('Mathématiques', 3),
('Physique', 3),
('Chimie', 3),
('Informatique', 3),
('Biologie', 3),
('Géologie', 3),
('Économie', 3),
('Gestion', 3),
('Droit', 3),
('Langues', 3),
('Philosophie', 3),
('Histoire', 3),
('Mathématiques Appliquées', 4),
('Physique Quantique', 4),
('Chimie Organique', 4),
('Informatique Avancée', 4),
('Biologie Moléculaire', 4),
('Géologie Structurale', 4),
('Économie Internationale', 4),
('Gestion de Projet', 4),
('Droit des Affaires', 4),
('Langues Étrangères', 4),
('Philosophie des Sciences', 4),
('Histoire Contemporaine', 4),
('Mathématiques Fondamentales', 5),
('Physique Théorique', 5),
('Chimie Analytique', 5),
('Intelligence Artificielle', 5),
('Génétique', 5),
('Géologie Appliquée', 5),
('Économie Financière', 5),
('Gestion Stratégique', 5),
('Droit International', 5),
('Traduction', 5),
('Épistémologie', 5),
('Histoire des Sciences', 5);

-- Création de la table utilisateur
CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('etudiant','tuteur') NOT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  `disponibilites` JSON DEFAULT NULL,
  `est_disponible` BOOLEAN DEFAULT FALSE,
  `date_inscription` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `niveau_id` (`niveau_id`),
  CONSTRAINT `utilisateur_ibfk_1` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des utilisateurs de test
INSERT INTO `utilisateur` (`nom`, `prenom`, `email`, `mot_de_passe`, `role`, `niveau_id`, `disponibilites`, `est_disponible`) VALUES
('Dupont', 'Jean', 'jean.dupont@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tuteur', 1, '{"lundi":["09:00-12:00","14:00-17:00"],"mercredi":["09:00-12:00"],"vendredi":["14:00-17:00"]}', true),
('Martin', 'Sophie', 'sophie.martin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tuteur', 2, '{"mardi":["09:00-12:00","14:00-17:00"],"jeudi":["09:00-12:00"]}', true),
('Dubois', 'Marie', 'marie.dubois@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant', 1, NULL, false);

-- Création de la table salon_entraide
CREATE TABLE `salon_entraide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  `matiere_id` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `niveau_id` (`niveau_id`),
  KEY `matiere_id` (`matiere_id`),
  CONSTRAINT `salon_entraide_ibfk_1` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`),
  CONSTRAINT `salon_entraide_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matiere` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des salons d'entraide
INSERT INTO `salon_entraide` (`nom`, `niveau_id`, `matiere_id`) VALUES
('Salon Mathématiques L1', 1, 1),
('Salon Physique L1', 1, 2),
('Salon Chimie L1', 1, 3),
('Salon Informatique L1', 1, 4),
('Salon Mathématiques L2', 2, 13),
('Salon Physique L2', 2, 14),
('Salon Chimie L2', 2, 15),
('Salon Informatique L2', 2, 16),
('Salon Mathématiques L3', 3, 25),
('Salon Physique L3', 3, 26),
('Salon Chimie L3', 3, 27),
('Salon Informatique L3', 3, 28),
('Salon Mathématiques M1', 4, 37),
('Salon Physique M1', 4, 38),
('Salon Chimie M1', 4, 39),
('Salon Informatique M1', 4, 40),
('Salon Mathématiques M2', 5, 49),
('Salon Physique M2', 5, 50),
('Salon Chimie M2', 5, 51),
('Salon Informatique M2', 5, 52);

--
-- Structure de la table `demande_aide`
--

CREATE TABLE `demande_aide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) DEFAULT NULL,
  `matiere_id` int(11) DEFAULT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'en_attente',
  `tuteur_id` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `tuteur_id` (`tuteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `document_salon`
--

CREATE TABLE `document_salon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salon_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `nom_fichier` varchar(255) DEFAULT NULL,
  `chemin_fichier` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `salon_id` (`salon_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `matiere_utilisateur`
--

CREATE TABLE `matiere_utilisateur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `type` enum('aide','enseignement') NOT NULL,
  `date_ajout` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `matiere_id` (`matiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `message_salon`
--

CREATE TABLE `message_salon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salon_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `salon_id` (`salon_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `demande_aide`
--
ALTER TABLE `demande_aide`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `document_salon`
--
ALTER TABLE `document_salon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matiere`
--
ALTER TABLE `matiere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `matiere_utilisateur`
--
ALTER TABLE `matiere_utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message_salon`
--
ALTER TABLE `message_salon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `salon_entraide`
--
ALTER TABLE `salon_entraide`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `demande_aide`
--
ALTER TABLE `demande_aide`
  ADD CONSTRAINT `demande_aide_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`),
  ADD CONSTRAINT `demande_aide_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matiere` (`id`),
  ADD CONSTRAINT `demande_aide_ibfk_3` FOREIGN KEY (`tuteur_id`) REFERENCES `utilisateur` (`id`);

--
-- Contraintes pour la table `document_salon`
--
ALTER TABLE `document_salon`
  ADD CONSTRAINT `document_salon_ibfk_1` FOREIGN KEY (`salon_id`) REFERENCES `salon_entraide` (`id`),
  ADD CONSTRAINT `document_salon_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);

--
-- Contraintes pour la table `matiere_utilisateur`
--
ALTER TABLE `matiere_utilisateur`
  ADD CONSTRAINT `matiere_utilisateur_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`),
  ADD CONSTRAINT `matiere_utilisateur_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matiere` (`id`);

--
-- Contraintes pour la table `message_salon`
--
ALTER TABLE `message_salon`
  ADD CONSTRAINT `message_salon_ibfk_1` FOREIGN KEY (`salon_id`) REFERENCES `salon_entraide` (`id`),
  ADD CONSTRAINT `message_salon_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
