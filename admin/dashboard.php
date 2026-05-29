<?php
// Aligne TP Module 2 - Dashboard admin protege par role

require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('admin');

$pageTitle = 'Dashboard admin - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tableau de bord admin</h1>
            <p class="text-secondary mb-0">Gestion de la plateforme EMSP Digital.</p>
        </div>
        <a class="btn btn-emsp-primary" href="/emsp-digital/admin/gerer-utilisateurs.php">Gerer les utilisateurs</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Utilisateurs</h2>
                    <p class="text-secondary mb-0">Creation, modification et suppression via modales CRUD.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Journalisation</h2>
                    <p class="text-secondary mb-0">Suivi des actions sensibles selon RG-30.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Securite</h2>
                    <p class="text-secondary mb-0">Sessions protegees et acces controles par role.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
