# 📘 README - Instructions pour Agent IA Codex (Projet EMSP Digital)

## 🎯 Objectif

Guider un agent IA dans le développement **étape par étape** d'une plateforme de digitalisation des cours pour l'EMSP. Le code doit respecter scrupuleusement les standards des TP de la formation DSER (Licence 2), notamment :

- `TP_CRUD V2.pdf` : Architecture modale, Bootstrap 5, DataTables, CRUD sans rechargement
- `TP_Module2_POO et PDO_V1.pdf` : PDO exclusif, `try/catch`, `prepare()`/`execute()`, `bindParam()`, séparation `includes/`, `config/`, `code.php`

---

## 🛠️ Stack Technique & Contraintes Académiques

| Couche              | Technologie                               | Règle stricte (issue des TP)                                                                                           |
| ------------------- | ----------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| **Backend**         | PHP 8.x                                   | Orienté objet ou procédural clair. Fonctions obsolètes interdites.                                                     |
| **Base de données** | MySQL 8.x                                 | Moteur `InnoDB`, `utf8mb4_unicode_ci`, FK, INDEX.                                                                      |
| **Driver**          | PDO exclusivement                         | ❌ Interdit : `mysqli_*` sans préparation. ✅ Obligatoire : `prepare()` + `execute()` ou `bindParam()` (TP Mod2, TP7). |
| **Frontend**        | Bootstrap 5.3, jQuery 3.7, DataTables 2.x | Modales CRUD, DataTables i18n FR, responsive mobile-first (TP CRUD V2, TP7).                                           |
| **Architecture**    | MVC simplifié + `code.php` centralisé     | Pas de fichiers `ajout.php`/`edit.php` séparés. Logique CRUD regroupée (TP Mod2, TP2/TP8).                             |
| **IA**              | Qwen via proxy PHP                        | Jamais de clé API côté client. Logs conformes RG-30/RG-31.                                                             |

---

## 📁 Architecture Attendue

emsp-digital/
├── config/
│ ├── dbcon.php # Connexion PDO (try/catch, ERRMODE_EXCEPTION) → TP Mod2 TP1
│ └── functions.php # Helpers (validation, session, hash, redirection)
├── includes/ # header.php, navbar.php, footer.php → TP Mod2 TP8
├── auth/ # login.php, logout.php
├── etudiant/ # dashboard.php, mes-cours.php, evaluations.php
├── enseignant/ # dashboard.php, gerer-cours.php, gerer-notes.php
├── admin/ # dashboard.php, gerer-utilisateurs.php, logs.php
├── api/ # qwen-proxy.php (sécurisé, journalisation)
├── assets/
│ ├── css/emsp-theme.css # Variables couleurs (bleu, vert, jaune, blanc)
│ └── js/main.js # Init DataTables, modales CRUD, validation client
├── code.php # Centralisation CRUD (pattern TP Module 2)
└── index.php # Routeur simple / redirection par rôle

---

## 🗄️ Base de Données & Connexion

- Utiliser le script SQL fourni (11 tables, `ON DELETE CASCADE/RESTRICT`, `INDEX`)
- `config/dbcon.php` **doit** suivre ce modèle (TP Module 2, TP1) :

```php
<?php
$servername = "localhost";
$username   = "root"; // Remplacer par utilisateur spécifique en production
$password   = "";
$database   = "emsp_digital";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("❌ Échec de connexion : " . $e->getMessage());
}
?>
Règle absolue : Aucune variable utilisateur ne doit apparaître dans une chaîne SQL sans marqueur :nom ou ?.

🔒 Sécurité & Conformité (Aligné TP)
Règle
Implémentation
Référence TP
Injection SQL
prepare() + execute($data) ou bindParam()
TP Mod2 TP7
Mots de passe
password_hash() (Argon2ID/Bcrypt), min. 6 chars
RG-03
XSS
htmlspecialchars($var, ENT_QUOTES, 'UTF-8') à chaque affichage
TP1 Sécurité
Session
session_start(), session_regenerate_id(true), timeout 30 min
TP Mod2 TP2
Journalisation
Actions sensibles dans chat_log / audit_log avec timestamp + IP
RG-30
RGPD/IA
Consentement explicite + bouton "Supprimer historique" → DELETE
RG-31

🎨 UI/UX & Design System
Palette (assets/css/emsp-theme.css) :
:root {
  --emsp-blue: #2563EB; --emsp-green: #10B981;
  --emsp-yellow: #F59E0B; --emsp-white: #FFFFFF;
  --emsp-gray-50: #F8FAFC; --emsp-text: #0F172A;
}
Composants : Cards, Modales Bootstrap (ajout/édition/suppression sans rechargement), DataTables FR
Accessibilité : Contraste WCAG AA, aria-* sur modales, required sur inputs, focus visible

🧭 Plan de Développement (Phasé pour l'IA)
⚠️ L'agent doit valider chaque phase avant de passer à la suivante. Ne jamais générer plus de 3 fichiers par itération.
Phase
Fichiers à générer
Critère de validation
1. Fondation
config/dbcon.php, config/functions.php, `includes/header
navbar
2. CRUD Utilisateurs
code.php (INSERT/UPDATE/DELETE), admin/gerer-utilisateurs.php, modales CRUD, DataTables
Modales fonctionnelles, prepare() partout, i18n FR, pas d'erreur PHP
3. Cours & Inscriptions
enseignant/gerer-cours.php, etudiant/mes-cours.php, validation RG-04
FK respectées, statut en_attente/valide, affichage conditionnel par rôle
4. Évaluations & Notes
enseignant/gerer-notes.php, etudiant/evaluations.php, RG-20/21
note BETWEEN 0 AND note_max, soumission unique, affichage notes
5. IA Qwen
api/qwen-proxy.php, assets/js/qwen-widget.js, chat_log table, consentement
Clé API masquée, logs RG-30, bouton suppression historique, réponse pédagogique stable
6. Finalisation
Tests, accessibilité, documentation rapport, README.md
Checklist validée, code commenté, prêt soutenance

🤖 Instructions Opérationnelles pour l'Agent IA
Format de réponse : Générer uniquement le code demandé + explication courte (max 5 lignes) + commande de test.
Référence TP obligatoire : Ajouter un commentaire dans chaque fichier critique :
// Aligné TP Module 2 - PDO bindParam() & CRUD Modal | TP CRUD V2.pdf
Validation intermédiaire : Demander à l'utilisateur de tester chaque phase avant de continuer.
Interdits formels :
❌ mysqli_connect, mysqli_query, concaténation SQL
❌ Stockage mot de passe en clair ou via md5()/sha1()
❌ Clé API Qwen exposée dans le HTML/JS
❌ Fichiers dispersés sans logique CRUD centralisée (code.php)
❌ Rechargement de page pour les opérations CRUD (utiliser les modales Bootstrap)

✅ Checklist de Validation (à cocher par l'utilisateur)
Connexion PDO fonctionnelle + ERRMODE_EXCEPTION
Session sécurisée + redirection par rôle (admin/enseignant/etudiant)
CRUD complet avec modales Bootstrap + DataTables FR
0 requête SQL non préparée
Palette couleurs appliquée + contrastes WCAG AA
Widget IA fonctionnel + logs chat_log + consentement RGPD
Code commenté, structuré, aligné avec les TPs fournis

```

## 📋 RÈGLES DE GESTION (RG) & MAPPING TECHNIQUE

> Chaque règle est associée à une phase, un fichier cible et une contrainte PDO/Sécurité issue des TPs.

| Code      | Règle Métier                                                      | Phase | Fichier(s) concerné(s)                       | Contrainte Technique (TP)                                                | Critère de Validation                     |
| --------- | ----------------------------------------------------------------- | ----- | -------------------------------------------- | ------------------------------------------------------------------------ | ----------------------------------------- |
| **RG-01** | Un étudiant ne s'inscrit qu'à un cours compatible avec son niveau | 3     | `code.php`, `etudiant/mes-cours.php`         | `SELECT ... WHERE niveau = :niveau` (prepare)                            | 0 cours incompatible affiché              |
| **RG-02** | Un enseignant ne peut animer 2 cours sur le même créneau          | 3     | `enseignant/gerer-cours.php`                 | `CHECK` ou `SELECT COUNT` avant `INSERT`                                 | Alert si conflit horaire                  |
| **RG-03** | Mot de passe : min. 6 caractères, hashé                           | 1     | `auth/login.php`, `code.php`                 | `password_hash()` + `password_verify()`                                  | Stockage jamais en clair                  |
| **RG-04** | Accès ressources uniquement après inscription validée             | 3     | `etudiant/mes-cours.php`                     | `WHERE statut = 'valide'` dans requête préparée                          | Bouton actif/inactif conditionnel         |
| **RG-10** | Séance planifiée ≥ 24h avant                                      | 3     | `enseignant/gerer-cours.php`                 | `IF(date_heure < NOW() + INTERVAL 24 HOUR)`                              | Rejet formulaire si < 24h                 |
| **RG-11** | Durée séances ≤ durée_totale du cours                             | 3     | `code.php`                                   | `SUM(duree_min) <= :duree_totale`                                        | Message d'erreur si dépassement           |
| **RG-20** | Note ∈ [0 ; note_max]                                             | 4     | `enseignant/gerer-notes.php`, `code.php`     | `CHECK(valeur BETWEEN 0 AND note_max)`                                   | Rejet si hors plage                       |
| **RG-21** | Soumission évaluation unique par étudiant                         | 4     | `code.php`, `etudiant/evaluations.php`       | `UNIQUE(id_eval, id_etu)` + vérif PHP avant `INSERT`                     | Bouton désactivé après soumission         |
| **RG-30** | Journalisation actions sensibles (connexion, suppression, IA)     | 1, 5  | `config/functions.php`, `api/qwen-proxy.php` | `INSERT INTO chat_log/audit_log` avec `NOW()`, `$_SERVER['REMOTE_ADDR']` | Table remplie, IP tracée                  |
| **RG-31** | Conformité RGPD / IA : consentement + droit à l'oubli             | 5     | `assets/js/qwen-widget.js`, `admin/logs.php` | Checkbox obligatoire + `DELETE WHERE id_utl = :uid`                      | Bouton "Supprimer historique" fonctionnel |

### 🧠 Instructions IA pour l'application des RG

1. **Ne jamais ignorer une RG** : si une contrainte bloque le flux, proposer une alternative sécurisée et commenter le choix.
2. **Valider côté serveur ET côté client** : HTML5 `required`/`pattern` + PHP `filter_var()` + PDO `prepare()`.
3. **Traçabilité** : ajouter un commentaire `// RG-XX` dans le code à chaque implémentation.
4. **Test implicite** : chaque génération de phase doit inclure une vérification manuelle ou un script de test pour la RG associée.
