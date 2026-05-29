-- =========================================================
-- PROJET EMSP DIGITALISATION - SCRIPT DE CREATION BDD
-- Aligné avec TP Module 2 (PDO, InnoDB, utf8mb4_unicode_ci)
-- =========================================================

CREATE DATABASE IF NOT EXISTS emsp_digital 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE emsp_digital;

-- 1. UTILISATEUR (Authentification unifiée)
CREATE TABLE utilisateur (
  id_utl INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  mdp_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','enseignant','etudiant') NOT NULL DEFAULT 'etudiant',
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ENSEIGNANT
CREATE TABLE enseignant (
  id_ens INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utl INT UNSIGNED NOT NULL,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  specialite VARCHAR(100),
  FOREIGN KEY (id_utl) REFERENCES utilisateur(id_utl) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CLASSE
CREATE TABLE classe (
  id_classe INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL UNIQUE,
  niveau VARCHAR(20) NOT NULL,
  annee_scolaire VARCHAR(9) NOT NULL,
  id_ref_ens INT UNSIGNED,
  FOREIGN KEY (id_ref_ens) REFERENCES enseignant(id_ens) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ETUDIANT
CREATE TABLE etudiant (
  id_etu INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utl INT UNSIGNED NOT NULL,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  id_classe INT UNSIGNED NOT NULL,
  date_inscription DATE DEFAULT (CURRENT_DATE),
  FOREIGN KEY (id_utl) REFERENCES utilisateur(id_utl) ON DELETE CASCADE,
  FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. COURS
CREATE TABLE cours (
  id_cours INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(100) NOT NULL,
  description TEXT,
  niveau VARCHAR(20) NOT NULL,
  duree_totale INT UNSIGNED NOT NULL COMMENT 'en heures',
  id_ens INT UNSIGNED NOT NULL,
  INDEX idx_niveau (niveau),
  FOREIGN KEY (id_ens) REFERENCES enseignant(id_ens) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. INSCRIPTION (Résolution N-N)
CREATE TABLE inscription (
  id_insc INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_etu INT UNSIGNED NOT NULL,
  id_cours INT UNSIGNED NOT NULL,
  date_insc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('en_attente','valide','refuse') DEFAULT 'en_attente',
  UNIQUE KEY uq_etudiant_cours (id_etu, id_cours),
  FOREIGN KEY (id_etu) REFERENCES etudiant(id_etu) ON DELETE CASCADE,
  FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. SEANCE
CREATE TABLE seance (
  id_seance INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cours INT UNSIGNED NOT NULL,
  date_heure DATETIME NOT NULL,
  duree_min INT UNSIGNED NOT NULL,
  type ENUM('presentiel','distanciel') DEFAULT 'presentiel',
  lien_visio VARCHAR(255),
  FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. RESSOURCE
CREATE TABLE ressource (
  id_ress INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cours INT UNSIGNED NOT NULL,
  titre VARCHAR(100) NOT NULL,
  type_fichier ENUM('pdf','video','quiz','document') DEFAULT 'pdf',
  url_stockage VARCHAR(255) NOT NULL,
  date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. EVALUATION
CREATE TABLE evaluation (
  id_eval INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cours INT UNSIGNED NOT NULL,
  titre VARCHAR(100) NOT NULL,
  type ENUM('quiz','projet','examen') DEFAULT 'quiz',
  note_max DECIMAL(4,2) NOT NULL CHECK (note_max > 0),
  date_limite DATETIME NOT NULL,
  FOREIGN KEY (id_cours) REFERENCES cours(id_cours) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. NOTE
CREATE TABLE note (
  id_note INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_eval INT UNSIGNED NOT NULL,
  id_etu INT UNSIGNED NOT NULL,
  valeur DECIMAL(4,2) NOT NULL,
  date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  commentaire TEXT,
  UNIQUE KEY uq_eval_etudiant (id_eval, id_etu),
  FOREIGN KEY (id_eval) REFERENCES evaluation(id_eval) ON DELETE CASCADE,
  FOREIGN KEY (id_etu) REFERENCES etudiant(id_etu) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. CHAT_LOG (IA Qwen - RG-30 & RG-31)
CREATE TABLE chat_log (
  id_log INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utl INT UNSIGNED,
  message TEXT NOT NULL,
  horodatage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_time (id_utl, horodatage),
  FOREIGN KEY (id_utl) REFERENCES utilisateur(id_utl) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;