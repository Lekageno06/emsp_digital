<?php
// Aligne TP Module 2 - Authentification PDO prepare() & password_verify()

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();

if (is_logged_in()) {
    redirect(dashboard_path_for_role(current_user_role()));
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    if ($password === '') {
        $errors[] = 'Mot de passe obligatoire.';
    }

    if ($errors === []) {
        $stmt = $conn->prepare(
            'SELECT id_utl, email, mdp_hash, role
             FROM utilisateur
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->mdp_hash)) {
            session_regenerate_id(true);
            $_SESSION['id_utl'] = (int) $user->id_utl;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;
            $_SESSION['last_activity'] = time();

            redirect(dashboard_path_for_role($user->role));
        }

        $errors[] = 'Identifiants incorrects.';
    }
}

$pageTitle = 'Connexion - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4">Connexion</h1>

                    <?php if (isset($_GET['timeout'])) : ?>
                        <div class="alert alert-warning" role="alert">Session expiree. Reconnectez-vous.</div>
                    <?php endif; ?>

                    <?php if ($errors !== []) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?php foreach ($errors as $error) : ?>
                                <div><?= escape($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= escape($email); ?>" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-emsp-primary w-100"><i data-lucide="log-in"></i> Se connecter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
