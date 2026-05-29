<?php
// Aligne TP Module 2 - PDO bindParam() & CRUD Modal | TP CRUD V2

require_once __DIR__ . '/config/dbcon.php';
require_once __DIR__ . '/config/functions.php';

session_secure_start();
require_auth();

function json_response(bool $success, string $message, array $data = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

function request_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function validate_role(string $role): bool
{
    return in_array($role, ['admin', 'enseignant', 'etudiant'], true);
}

function validate_password_policy(string $password): ?string
{
    // RG-03 - Mot de passe min. 6 caracteres, hash avec password_hash().
    $commonPasswords = ['123456', 'password', 'azerty', 'admin', 'admin123', 'qwerty'];

    if (strlen($password) < 6) {
        return 'Le mot de passe doit contenir au moins 6 caracteres.';
    }

    if (in_array(strtolower($password), $commonPasswords, true)) {
        return 'Mot de passe trop courant.';
    }

    return null;
}

function require_action_role(array|string $roles): void
{
    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array(current_user_role(), $allowedRoles, true)) {
        json_response(false, 'Acces refuse.');
    }
}

function current_teacher_id(PDO $conn): int
{
    $stmt = $conn->prepare('SELECT id_ens FROM enseignant WHERE id_utl = :id_utl LIMIT 1');
    $stmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    return $teacher ? (int) $teacher['id_ens'] : 0;
}

function current_student(PDO $conn): ?array
{
    $stmt = $conn->prepare(
        'SELECT e.id_etu, c.niveau
         FROM etudiant e
         INNER JOIN classe c ON c.id_classe = e.id_classe
         WHERE e.id_utl = :id_utl
         LIMIT 1'
    );
    $stmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    return $student ?: null;
}

function fetch_course(PDO $conn, int $courseId): ?array
{
    $stmt = $conn->prepare(
        'SELECT c.id_cours, c.titre, c.description, c.niveau, c.duree_totale, c.id_ens,
                CONCAT(e.prenom, " ", e.nom) AS enseignant
         FROM cours c
         INNER JOIN enseignant e ON e.id_ens = c.id_ens
         WHERE c.id_cours = :id_cours
         LIMIT 1'
    );
    $stmt->execute([':id_cours' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        return null;
    }

    return [
        'id_cours' => (int) $course['id_cours'],
        'titre' => $course['titre'],
        'description' => $course['description'] ?? '',
        'niveau' => $course['niveau'],
        'duree_totale' => (int) $course['duree_totale'],
        'id_ens' => (int) $course['id_ens'],
        'enseignant' => $course['enseignant'],
    ];
}

function current_teacher_course_exists(PDO $conn, int $teacherId, int $courseId): bool
{
    $stmt = $conn->prepare('SELECT id_cours FROM cours WHERE id_cours = :id_cours AND id_ens = :id_ens LIMIT 1');
    $stmt->execute([
        ':id_cours' => $courseId,
        ':id_ens' => $teacherId,
    ]);

    return (bool) $stmt->fetch();
}

function fetch_user(PDO $conn, int $userId): ?array
{
    $stmt = $conn->prepare(
        "SELECT u.id_utl, u.email, u.role, u.date_creation,
                e.nom AS etu_nom, e.prenom AS etu_prenom, e.id_classe,
                ens.nom AS ens_nom, ens.prenom AS ens_prenom, ens.specialite,
                c.nom AS classe_nom
         FROM utilisateur u
         LEFT JOIN etudiant e ON e.id_utl = u.id_utl
         LEFT JOIN classe c ON c.id_classe = e.id_classe
         LEFT JOIN enseignant ens ON ens.id_utl = u.id_utl
         WHERE u.id_utl = :id_utl
         LIMIT 1"
    );
    $stmt->execute([':id_utl' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    $nom = $user['role'] === 'enseignant' ? $user['ens_nom'] : $user['etu_nom'];
    $prenom = $user['role'] === 'enseignant' ? $user['ens_prenom'] : $user['etu_prenom'];

    return [
        'id_utl' => (int) $user['id_utl'],
        'email' => $user['email'],
        'role' => $user['role'],
        'nom' => $nom ?? '',
        'prenom' => $prenom ?? '',
        'specialite' => $user['specialite'] ?? '',
        'id_classe' => $user['id_classe'] !== null ? (int) $user['id_classe'] : '',
        'classe_nom' => $user['classe_nom'] ?? '',
        'date_creation' => $user['date_creation'],
    ];
}

function save_profile(PDO $conn, int $userId, string $role, string $nom, string $prenom, string $specialite, string $idClasse): void
{
    $deleteStudent = $conn->prepare('DELETE FROM etudiant WHERE id_utl = :id_utl');
    $deleteTeacher = $conn->prepare('DELETE FROM enseignant WHERE id_utl = :id_utl');
    $deleteStudent->execute([':id_utl' => $userId]);
    $deleteTeacher->execute([':id_utl' => $userId]);

    if ($role === 'enseignant') {
        $stmt = $conn->prepare(
            'INSERT INTO enseignant (id_utl, nom, prenom, specialite)
             VALUES (:id_utl, :nom, :prenom, :specialite)'
        );
        $stmt->execute([
            ':id_utl' => $userId,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':specialite' => $specialite !== '' ? $specialite : null,
        ]);
    }

    if ($role === 'etudiant') {
        $stmt = $conn->prepare(
            'INSERT INTO etudiant (id_utl, nom, prenom, id_classe)
             VALUES (:id_utl, :nom, :prenom, :id_classe)'
        );
        $stmt->execute([
            ':id_utl' => $userId,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':id_classe' => (int) $idClasse,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Methode non autorisee.');
}

$action = request_value('action');

try {
    if ($action === 'create_user') {
        require_action_role('admin');
        $email = request_value('email');
        $password = (string) ($_POST['password'] ?? '');
        $role = request_value('role');
        $nom = request_value('nom');
        $prenom = request_value('prenom');
        $specialite = request_value('specialite');
        $idClasse = request_value('id_classe');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(false, 'Adresse email invalide.');
        }

        if (!validate_role($role)) {
            json_response(false, 'Role invalide.');
        }

        $passwordError = validate_password_policy($password);
        if ($passwordError !== null) {
            json_response(false, $passwordError);
        }

        if (($role === 'enseignant' || $role === 'etudiant') && ($nom === '' || $prenom === '')) {
            json_response(false, 'Nom et prenom obligatoires.');
        }

        if ($role === 'etudiant' && !ctype_digit($idClasse)) {
            json_response(false, 'Classe obligatoire pour un etudiant.');
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare(
            'INSERT INTO utilisateur (email, mdp_hash, role)
             VALUES (:email, :mdp_hash, :role)'
        );
        $stmt->execute([
            ':email' => $email,
            ':mdp_hash' => password_hash($password, PASSWORD_BCRYPT),
            ':role' => $role,
        ]);

        $userId = (int) $conn->lastInsertId();
        save_profile($conn, $userId, $role, $nom, $prenom, $specialite, $idClasse);
        $conn->commit();

        json_response(true, 'Utilisateur ajoute.', ['user' => fetch_user($conn, $userId)]);
    }

    if ($action === 'update_user') {
        require_action_role('admin');
        $userId = (int) request_value('id_utl');
        $email = request_value('email');
        $password = (string) ($_POST['password'] ?? '');
        $role = request_value('role');
        $nom = request_value('nom');
        $prenom = request_value('prenom');
        $specialite = request_value('specialite');
        $idClasse = request_value('id_classe');

        if ($userId <= 0) {
            json_response(false, 'Utilisateur invalide.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(false, 'Adresse email invalide.');
        }

        if (!validate_role($role)) {
            json_response(false, 'Role invalide.');
        }

        if ($password !== '') {
            $passwordError = validate_password_policy($password);
            if ($passwordError !== null) {
                json_response(false, $passwordError);
            }
        }

        if (($role === 'enseignant' || $role === 'etudiant') && ($nom === '' || $prenom === '')) {
            json_response(false, 'Nom et prenom obligatoires.');
        }

        if ($role === 'etudiant' && !ctype_digit($idClasse)) {
            json_response(false, 'Classe obligatoire pour un etudiant.');
        }

        $conn->beginTransaction();

        if ($password !== '') {
            $stmt = $conn->prepare(
                'UPDATE utilisateur
                 SET email = :email, role = :role, mdp_hash = :mdp_hash
                 WHERE id_utl = :id_utl'
            );
            $stmt->execute([
                ':email' => $email,
                ':role' => $role,
                ':mdp_hash' => password_hash($password, PASSWORD_BCRYPT),
                ':id_utl' => $userId,
            ]);
        } else {
            $stmt = $conn->prepare(
                'UPDATE utilisateur
                 SET email = :email, role = :role
                 WHERE id_utl = :id_utl'
            );
            $stmt->execute([
                ':email' => $email,
                ':role' => $role,
                ':id_utl' => $userId,
            ]);
        }

        save_profile($conn, $userId, $role, $nom, $prenom, $specialite, $idClasse);
        $conn->commit();

        json_response(true, 'Utilisateur modifie.', ['user' => fetch_user($conn, $userId)]);
    }

    if ($action === 'delete_user') {
        require_action_role('admin');
        $userId = (int) request_value('id_utl');

        if ($userId <= 0) {
            json_response(false, 'Utilisateur invalide.');
        }

        if ($userId === (int) ($_SESSION['id_utl'] ?? 0)) {
            json_response(false, 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $stmt = $conn->prepare('DELETE FROM utilisateur WHERE id_utl = :id_utl');
        $stmt->execute([':id_utl' => $userId]);

        json_response(true, 'Utilisateur supprime.', ['id_utl' => $userId]);
    }

    if ($action === 'create_course' || $action === 'update_course') {
        require_action_role('enseignant');

        $teacherId = current_teacher_id($conn);
        if ($teacherId <= 0) {
            json_response(false, 'Profil enseignant introuvable.');
        }

        $courseId = (int) request_value('id_cours');
        $titre = request_value('titre');
        $description = request_value('description');
        $niveau = request_value('niveau');
        $dureeTotale = request_value('duree_totale');

        if ($titre === '' || $niveau === '' || !ctype_digit($dureeTotale) || (int) $dureeTotale <= 0) {
            json_response(false, 'Titre, niveau et duree totale valide sont obligatoires.');
        }

        if ($action === 'create_course') {
            $stmt = $conn->prepare(
                'INSERT INTO cours (titre, description, niveau, duree_totale, id_ens)
                 VALUES (:titre, :description, :niveau, :duree_totale, :id_ens)'
            );
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description !== '' ? $description : null,
                ':niveau' => $niveau,
                ':duree_totale' => (int) $dureeTotale,
                ':id_ens' => $teacherId,
            ]);

            $courseId = (int) $conn->lastInsertId();
            json_response(true, 'Cours ajoute.', ['course' => fetch_course($conn, $courseId)]);
        }

        if ($courseId <= 0) {
            json_response(false, 'Cours invalide.');
        }

        $stmt = $conn->prepare(
            'UPDATE cours
             SET titre = :titre, description = :description, niveau = :niveau, duree_totale = :duree_totale
             WHERE id_cours = :id_cours AND id_ens = :id_ens'
        );
        $stmt->execute([
            ':titre' => $titre,
            ':description' => $description !== '' ? $description : null,
            ':niveau' => $niveau,
            ':duree_totale' => (int) $dureeTotale,
            ':id_cours' => $courseId,
            ':id_ens' => $teacherId,
        ]);

        json_response(true, 'Cours modifie.', ['course' => fetch_course($conn, $courseId)]);
    }

    if ($action === 'delete_course') {
        require_action_role('enseignant');

        $teacherId = current_teacher_id($conn);
        $courseId = (int) request_value('id_cours');

        if ($teacherId <= 0 || $courseId <= 0) {
            json_response(false, 'Cours invalide.');
        }

        $stmt = $conn->prepare('DELETE FROM cours WHERE id_cours = :id_cours AND id_ens = :id_ens');
        $stmt->execute([
            ':id_cours' => $courseId,
            ':id_ens' => $teacherId,
        ]);

        json_response(true, 'Cours supprime.', ['id_cours' => $courseId]);
    }

    if ($action === 'create_session') {
        require_action_role('enseignant');

        $teacherId = current_teacher_id($conn);
        $courseId = (int) request_value('id_cours');
        $dateHeure = request_value('date_heure');
        $dureeMin = request_value('duree_min');
        $type = request_value('type');
        $lienVisio = request_value('lien_visio');

        if ($teacherId <= 0 || $courseId <= 0 || $dateHeure === '' || !ctype_digit($dureeMin)) {
            json_response(false, 'Donnees de seance invalides.');
        }

        if (!in_array($type, ['presentiel', 'distanciel'], true)) {
            json_response(false, 'Type de seance invalide.');
        }

        // RG-10 - Seance planifiee au moins 24h avant.
        $dateHeureSql = str_replace('T', ' ', $dateHeure);
        $sessionTime = DateTime::createFromFormat('Y-m-d H:i', $dateHeureSql);
        if (!$sessionTime) {
            json_response(false, 'Date de seance invalide.');
        }

        $minimumTime = new DateTime('+24 hours');
        if ($sessionTime < $minimumTime) {
            json_response(false, 'La seance doit etre planifiee au moins 24h avant.');
        }

        $courseStmt = $conn->prepare('SELECT duree_totale FROM cours WHERE id_cours = :id_cours AND id_ens = :id_ens LIMIT 1');
        $courseStmt->execute([
            ':id_cours' => $courseId,
            ':id_ens' => $teacherId,
        ]);
        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            json_response(false, 'Cours introuvable.');
        }

        // RG-11 - Duree des seances <= duree totale du cours.
        $sumStmt = $conn->prepare('SELECT COALESCE(SUM(duree_min), 0) AS total_min FROM seance WHERE id_cours = :id_cours');
        $sumStmt->execute([':id_cours' => $courseId]);
        $totalMin = (int) $sumStmt->fetch(PDO::FETCH_ASSOC)['total_min'];
        $maxMin = (int) $course['duree_totale'] * 60;

        if ($totalMin + (int) $dureeMin > $maxMin) {
            json_response(false, 'La duree des seances depasse la duree totale du cours.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO seance (id_cours, date_heure, duree_min, type, lien_visio)
             VALUES (:id_cours, :date_heure, :duree_min, :type, :lien_visio)'
        );
        $stmt->execute([
            ':id_cours' => $courseId,
            ':date_heure' => $dateHeureSql,
            ':duree_min' => (int) $dureeMin,
            ':type' => $type,
            ':lien_visio' => $lienVisio !== '' ? $lienVisio : null,
        ]);

        json_response(true, 'Seance planifiee.');
    }

    if ($action === 'request_enrollment') {
        require_action_role('etudiant');

        $courseId = (int) request_value('id_cours');
        $student = current_student($conn);

        if (!$student || $courseId <= 0) {
            json_response(false, 'Inscription invalide.');
        }

        // RG-01 - Niveau etudiant compatible avec le niveau du cours.
        $courseStmt = $conn->prepare('SELECT id_cours FROM cours WHERE id_cours = :id_cours AND niveau = :niveau LIMIT 1');
        $courseStmt->execute([
            ':id_cours' => $courseId,
            ':niveau' => $student['niveau'],
        ]);

        if (!$courseStmt->fetch()) {
            json_response(false, 'Cours incompatible avec votre niveau.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO inscription (id_etu, id_cours, statut)
             VALUES (:id_etu, :id_cours, :statut)'
        );
        $stmt->execute([
            ':id_etu' => (int) $student['id_etu'],
            ':id_cours' => $courseId,
            ':statut' => 'en_attente',
        ]);

        json_response(true, 'Demande envoyee.', ['id_cours' => $courseId, 'statut' => 'en_attente']);
    }

    if ($action === 'create_evaluation') {
        require_action_role('enseignant');

        $teacherId = current_teacher_id($conn);
        $courseId = (int) request_value('id_cours');
        $titre = request_value('titre');
        $type = request_value('type');
        $noteMax = request_value('note_max');
        $dateLimite = request_value('date_limite');

        if ($teacherId <= 0 || $courseId <= 0 || $titre === '' || !in_array($type, ['quiz', 'projet', 'examen'], true)) {
            json_response(false, 'Donnees evaluation invalides.');
        }

        if (!is_numeric($noteMax) || (float) $noteMax <= 0) {
            json_response(false, 'Note maximale invalide.');
        }

        $dateLimiteSql = str_replace('T', ' ', $dateLimite);
        $deadline = DateTime::createFromFormat('Y-m-d H:i', $dateLimiteSql);
        if (!$deadline) {
            json_response(false, 'Date limite invalide.');
        }

        if (!current_teacher_course_exists($conn, $teacherId, $courseId)) {
            json_response(false, 'Cours introuvable.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO evaluation (id_cours, titre, type, note_max, date_limite)
             VALUES (:id_cours, :titre, :type, :note_max, :date_limite)'
        );
        $stmt->execute([
            ':id_cours' => $courseId,
            ':titre' => $titre,
            ':type' => $type,
            ':note_max' => (float) $noteMax,
            ':date_limite' => $dateLimiteSql,
        ]);

        json_response(true, 'Evaluation creee.');
    }

    if ($action === 'save_grade') {
        require_action_role('enseignant');

        $teacherId = current_teacher_id($conn);
        $evaluationId = (int) request_value('id_eval');
        $studentId = (int) request_value('id_etu');
        $value = request_value('valeur');
        $comment = request_value('commentaire');

        if ($teacherId <= 0 || $evaluationId <= 0 || $studentId <= 0 || !is_numeric($value)) {
            json_response(false, 'Donnees note invalides.');
        }

        $evalStmt = $conn->prepare(
            'SELECT ev.note_max
             FROM evaluation ev
             INNER JOIN cours c ON c.id_cours = ev.id_cours
             WHERE ev.id_eval = :id_eval AND c.id_ens = :id_ens
             LIMIT 1'
        );
        $evalStmt->execute([
            ':id_eval' => $evaluationId,
            ':id_ens' => $teacherId,
        ]);
        $evaluation = $evalStmt->fetch(PDO::FETCH_ASSOC);

        if (!$evaluation) {
            json_response(false, 'Evaluation introuvable.');
        }

        // RG-20 - Note comprise entre 0 et note_max.
        $grade = (float) $value;
        $noteMax = (float) $evaluation['note_max'];
        if ($grade < 0 || $grade > $noteMax) {
            json_response(false, 'La note doit etre comprise entre 0 et la note maximale.');
        }

        $enrollmentStmt = $conn->prepare(
            "SELECT i.id_insc
             FROM inscription i
             INNER JOIN evaluation ev ON ev.id_cours = i.id_cours
             WHERE ev.id_eval = :id_eval AND i.id_etu = :id_etu AND i.statut = 'valide'
             LIMIT 1"
        );
        $enrollmentStmt->execute([
            ':id_eval' => $evaluationId,
            ':id_etu' => $studentId,
        ]);

        if (!$enrollmentStmt->fetch()) {
            json_response(false, 'Etudiant non inscrit ou non valide.');
        }

        // RG-21 - Une note par evaluation et par etudiant, mise a jour preparee si deja presente.
        $stmt = $conn->prepare(
            'INSERT INTO note (id_eval, id_etu, valeur, commentaire)
             VALUES (:id_eval, :id_etu, :valeur, :commentaire)
             ON DUPLICATE KEY UPDATE valeur = :valeur_update, commentaire = :commentaire_update'
        );
        $stmt->execute([
            ':id_eval' => $evaluationId,
            ':id_etu' => $studentId,
            ':valeur' => $grade,
            ':commentaire' => $comment !== '' ? $comment : null,
            ':valeur_update' => $grade,
            ':commentaire_update' => $comment !== '' ? $comment : null,
        ]);

        json_response(true, 'Note enregistree.');
    }

    if ($action === 'submit_evaluation') {
        require_action_role('etudiant');

        $student = current_student($conn);
        $evaluationId = (int) request_value('id_eval');
        $comment = request_value('commentaire');

        if (!$student || $evaluationId <= 0) {
            json_response(false, 'Soumission invalide.');
        }

        $evalStmt = $conn->prepare(
            "SELECT ev.id_eval
             FROM evaluation ev
             INNER JOIN inscription i ON i.id_cours = ev.id_cours
             WHERE ev.id_eval = :id_eval
               AND i.id_etu = :id_etu
               AND i.statut = 'valide'
               AND ev.date_limite >= NOW()
             LIMIT 1"
        );
        $evalStmt->execute([
            ':id_eval' => $evaluationId,
            ':id_etu' => (int) $student['id_etu'],
        ]);

        if (!$evalStmt->fetch()) {
            json_response(false, 'Evaluation indisponible.');
        }

        $existingStmt = $conn->prepare('SELECT id_note FROM note WHERE id_eval = :id_eval AND id_etu = :id_etu LIMIT 1');
        $existingStmt->execute([
            ':id_eval' => $evaluationId,
            ':id_etu' => (int) $student['id_etu'],
        ]);

        // RG-21 - Soumission unique par etudiant et evaluation.
        if ($existingStmt->fetch()) {
            json_response(false, 'Evaluation deja soumise.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO note (id_eval, id_etu, valeur, commentaire)
             VALUES (:id_eval, :id_etu, :valeur, :commentaire)'
        );
        $stmt->execute([
            ':id_eval' => $evaluationId,
            ':id_etu' => (int) $student['id_etu'],
            ':valeur' => 0,
            ':commentaire' => $comment !== '' ? $comment : 'Soumission etudiante en attente de correction',
        ]);

        json_response(true, 'Evaluation soumise.');
    }

    json_response(false, 'Action inconnue.');
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if ($e->getCode() === '23000') {
        json_response(false, 'Email deja utilise ou contrainte de base non respectee.');
    }

    json_response(false, 'Erreur base de donnees.');
} catch (Throwable $e) {
    json_response(false, 'Erreur application.');
}
