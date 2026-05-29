# 📘 GUIDE DE DÉVELOPPEMENT IA - Projet EMSP Digital

> Formation DSER Licence 2 | Aligné TP_CRUD V1/V2.pdf & TP_Module2_POO_PDO.pdf

## 🎯 OBJECTIF

Développer progressivement une plateforme de digitalisation des cours en respectant strictement les TPs fournis. L'IA agit comme un assistant d'implémentation, pas comme un générateur autonome.

## 🛑 RÈGLES ABSOLUES (NON NÉGOCIABLES)

1. PDO exclusif. ❌ `mysqli_*` non préparé, concaténation SQL, `md5()`/`sha1()`
2. Sécurité : `password_hash()`, `htmlspecialchars()`, `session_regenerate_id(true)`
3. Architecture : `code.php` centralisé, modales Bootstrap sans rechargement, `includes/` réutilisables
4. Méthode : **1 phase à la fois**. Attendre validation écrite avant de continuer.
5. Traçabilité : Chaque fichier critique doit contenir `// Aligné TP [Nom] - [Fonctionnalité]`

## 📁 ARCHITECTURE ATTENDUE

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

## 🧭 PHASES DE DÉVELOPPEMENT

| Phase                   | Fichiers                                                        | Critère de validation                                |
| ----------------------- | --------------------------------------------------------------- | ---------------------------------------------------- |
| 1. Fondation            | `config/`, `includes/`, `auth/login.php`, `index.php`           | PDO OK, session sécurisée, redirection par rôle      |
| 2. CRUD Utilisateurs    | `code.php`, `admin/gerer-utilisateurs.php`, modales, DataTables | Modales fonctionnelles, `prepare()` partout, i18n FR |
| 3. Cours & Inscriptions | `enseignant/gerer-cours.php`, `etudiant/mes-cours.php`          | FK respectées, statut `en_attente`/`valide`          |
| 4. Évaluations & Notes  | `gerer-notes.php`, `evaluations.php`                            | `BETWEEN 0 AND note_max`, soumission unique          |
| 5. IA Qwen              | `api/qwen-proxy.php`, `qwen-widget.js`, `chat_log`              | Clé masquée, logs RG-30, consentement RG-31          |
| 6. Finalisation         | Audit, accessibilité, documentation                             | Checklist validée, code commenté                     |

## 🤖 PROTOCOLE D'EXÉCUTION IA

1. Génère **UNIQUEMENT** la phase demandée.
2. Fournis le code complet + 3 lignes d'explication + commande de test.
3. Termine par : `✅ Phase terminée. Attends ta validation écrite pour continuer.`
4. Ne génère jamais plus de 3 fichiers par itération.
5. Si une contrainte TP n'est pas respectée, signale-le avant de proposer une correction.

## ✅ CHECKLIST DE VALIDATION (À cocher par l'étudiant)

- [ ] PDO + `ERRMODE_EXCEPTION` fonctionnel
- [ ] 0 concaténation SQL, 100% `prepare()`/`execute()`
- [ ] Modales CRUD sans rechargement (jQuery/Bootstrap)
- [ ] Palette couleurs appliquée + contrastes WCAG AA
- [ ] Journalisation IA + consentement RGPD
- [ ] Code aligné TPs, commenté, prêt soutenance

> 📘 _Note d'intégrité académique_ : Ce guide accompagne votre apprentissage. Documentez chaque choix, testez chaque phase, et citez vos sources dans votre rapport de soutenance.
