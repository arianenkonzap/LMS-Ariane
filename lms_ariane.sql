-- ============================================================
-- LMS-ARIANE - Base de données
-- ============================================================

CREATE DATABASE IF NOT EXISTS lms_ariane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lms_ariane;

-- ------------------------------------------------------------
-- Table : utilisateurs
-- ------------------------------------------------------------
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'enseignant', 'promoteur') NOT NULL DEFAULT 'etudiant',
    avatar VARCHAR(255) DEFAULT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table : modules (créés par le promoteur)
-- ------------------------------------------------------------
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    promoteur_id INT NOT NULL,
    seuil_validation INT NOT NULL DEFAULT 60, -- % requis pour valider le module
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promoteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : cours (rattaché à un module, créé par un enseignant)
-- ------------------------------------------------------------
CREATE TABLE cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    enseignant_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : lecons (PDF ou vidéo, ordonnées dans un cours)
-- ------------------------------------------------------------
CREATE TABLE lecons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cours_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    type_contenu ENUM('pdf', 'video') NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    ordre INT NOT NULL DEFAULT 1,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : evaluations (une par leçon)
-- ------------------------------------------------------------
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecon_id INT NOT NULL UNIQUE,
    titre VARCHAR(150) NOT NULL DEFAULT 'Évaluation',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecon_id) REFERENCES lecons(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : questions (QCM)
-- ------------------------------------------------------------
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    enonce TEXT NOT NULL,
    points INT NOT NULL DEFAULT 1,
    ordre INT NOT NULL DEFAULT 1,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : reponses (propositions de chaque question)
-- ------------------------------------------------------------
CREATE TABLE reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    texte VARCHAR(255) NOT NULL,
    est_correcte TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table : progression (résultats d'un étudiant à une évaluation)
-- ------------------------------------------------------------
CREATE TABLE progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    lecon_id INT NOT NULL,
    evaluation_id INT NOT NULL,
    note DECIMAL(5,2) NOT NULL,        -- note obtenue sur 100 (%)
    statut ENUM('en_cours', 'termine') NOT NULL DEFAULT 'termine',
    date_passage DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (lecon_id) REFERENCES lecons(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_etudiant_lecon (etudiant_id, lecon_id)
);

-- ------------------------------------------------------------
-- Table : certificats
-- ------------------------------------------------------------
CREATE TABLE certificats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    module_id INT NOT NULL,
    code_certificat VARCHAR(50) NOT NULL UNIQUE,
    score_final DECIMAL(5,2) NOT NULL,
    date_obtention DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_etudiant_module (etudiant_id, module_id)
);

-- ------------------------------------------------------------
-- Table : inscriptions (étudiant <-> module)
-- ------------------------------------------------------------
CREATE TABLE inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    module_id INT NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_inscription (etudiant_id, module_id)
);

-- ------------------------------------------------------------
-- Données de démonstration
-- ------------------------------------------------------------

-- Mot de passe en clair pour tous : "password123"
-- Hash généré avec password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Mballa', 'Jean', 'promoteur@ariane.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudJUiP8wjF/IfzCsTaFEMA9N2nkVii', 'promoteur'),
('Nguema', 'Sarah', 'enseignant@ariane.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudJUiP8wjF/IfzCsTaFEMA9N2nkVii', 'enseignant'),
('Tchoumi', 'Paul', 'etudiant@ariane.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYudJUiP8wjF/IfzCsTaFEMA9N2nkVii', 'etudiant');

INSERT INTO modules (titre, description, promoteur_id, seuil_validation) VALUES
('Développement Web Fondamental', 'Apprenez les bases du développement web : HTML, CSS, JavaScript, PHP et bases de données.', 1, 60);

INSERT INTO cours (module_id, enseignant_id, titre, description) VALUES
(1, 2, 'Introduction au HTML & CSS', 'Découvrez les fondamentaux de la structuration et du style des pages web.');

INSERT INTO lecons (cours_id, titre, type_contenu, chemin_fichier, ordre) VALUES
(1, 'Leçon 1 : Structure HTML', 'pdf', 'uploads/pdf/lecon1_html.pdf', 1),
(1, 'Leçon 2 : Mise en forme CSS', 'video', 'uploads/video/lecon2_css.mp4', 2);

INSERT INTO evaluations (lecon_id, titre) VALUES
(1, 'Quiz : Structure HTML'),
(2, 'Quiz : Mise en forme CSS');

INSERT INTO questions (evaluation_id, enonce, points, ordre) VALUES
(1, 'Quelle balise définit le titre principal d''une page HTML ?', 1, 1),
(1, 'Quelle balise permet de créer un lien hypertexte ?', 1, 2),
(2, 'Quelle propriété CSS modifie la couleur du texte ?', 1, 1);

INSERT INTO reponses (question_id, texte, est_correcte) VALUES
(1, '<h1>', 1),
(1, '<p>', 0),
(1, '<div>', 0),
(2, '<a>', 1),
(2, '<link>', 0),
(2, '<href>', 0),
(3, 'color', 1),
(3, 'font-size', 0),
(3, 'text-style', 0);
