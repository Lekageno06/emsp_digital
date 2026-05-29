<?php
// Aligne TP Module 2 - Dashboard enseignant protege par role

require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('enseignant');

$pageTitle = 'Dashboard enseignant - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tableau de bord enseignant</h1>
            <p class="text-secondary mb-0">Organisation des cours, seances et evaluations.</p>
        </div>
        <a class="btn btn-emsp-primary" href="/emsp-digital/enseignant/gerer-cours.php">Gerer les cours</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Cours</h2>
                    <p class="text-secondary mb-0">Creation et suivi des cours avec modales Bootstrap.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Notes</h2>
                    <p class="text-secondary mb-0">Saisie controlee des notes selon les regles RG-20 et RG-21.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
