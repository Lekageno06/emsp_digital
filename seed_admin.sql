-- Utilisateur admin initial EMSP Digital
-- Email : admin@emsp.local
-- Mot de passe : Admin@123

USE emsp_digital;

INSERT INTO utilisateur (email, mdp_hash, role)
VALUES (
  'admin@emsp.local',
  '$2y$10$cR9HmMd.CJQf.L1quefRueyMiajadWfiLGARSx7JEYAQDJAFZlBi.',
  'admin'
);
