-- Utilisateur etudiant initial EMSP Digital
-- Email : etudiant@emsp.local
-- Mot de passe : Etudiant@123

USE emsp_digital;

INSERT INTO classe (nom, niveau, annee_scolaire)
VALUES ('L2 DSER', 'L2', '2025-2026')
ON DUPLICATE KEY UPDATE
  niveau = VALUES(niveau),
  annee_scolaire = VALUES(annee_scolaire);

INSERT INTO utilisateur (email, mdp_hash, role)
VALUES (
  'etudiant@emsp.local',
  '$2y$10$yd0xSQg1ErBce.HFaKlRM.HVMJrLGVCImW4LgVj/uekJ6h7IiTKoi',
  'etudiant'
);

INSERT INTO etudiant (id_utl, nom, prenom, id_classe)
SELECT u.id_utl, 'Diop', 'Awa', c.id_classe
FROM utilisateur u
INNER JOIN classe c ON c.nom = 'L2 DSER'
WHERE u.email = 'etudiant@emsp.local';
