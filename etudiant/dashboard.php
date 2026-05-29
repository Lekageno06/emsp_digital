<?php
// Aligne TP Module 2 - Dashboard etudiant protege par role

require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('etudiant');

$pageTitle = 'Dashboard etudiant - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tableau de bord etudiant</h1>
            <p class="text-secondary mb-0">Acces aux cours, ressources et evaluations valides.</p>
        </div>
        <a class="btn btn-emsp-primary" href="/emsp-digital/etudiant/mes-cours.php"><i data-lucide="book-open-check"></i> Voir mes cours</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Mes cours</h2>
                    <p class="text-secondary mb-0">Consultation des cours accessibles apres validation.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Evaluations</h2>
                    <p class="text-secondary mb-0">Soumissions et resultats selon les regles de la plateforme.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
