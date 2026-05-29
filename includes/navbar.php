<?php
// Aligne TP Module 2 - Include reutilisable & menu adapte au role

$role = current_user_role();
$brandUrl = is_logged_in() ? dashboard_path_for_role($role) : '/emsp-digital/auth/login.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-emsp">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= escape($brandUrl); ?>">EMSP Digital</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($role === 'admin') : ?>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/admin/gerer-utilisateurs.php">Utilisateurs</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/admin/logs.php">Logs</a></li>
                <?php elseif ($role === 'enseignant') : ?>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/enseignant/dashboard.php">Tableau de bord</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/enseignant/gerer-cours.php">Cours</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/enseignant/gerer-notes.php">Notes</a></li>
                <?php elseif ($role === 'etudiant') : ?>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/etudiant/dashboard.php">Tableau de bord</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/etudiant/mes-cours.php">Mes cours</a></li>
                    <li class="nav-item"><a class="nav-link" href="/emsp-digital/etudiant/evaluations.php">Evaluations</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if (is_logged_in()) : ?>
                    <span class="badge text-bg-light"><?= escape(ucfirst((string) $role)); ?></span>
                    <a class="btn btn-sm btn-outline-light" href="/emsp-digital/auth/profil.php">Profil</a>
                    <a class="btn btn-sm btn-outline-light" href="/emsp-digital/auth/logout.php">Deconnexion</a>
                <?php else : ?>
                    <a class="btn btn-sm btn-outline-light" href="/emsp-digital/auth/login.php">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
