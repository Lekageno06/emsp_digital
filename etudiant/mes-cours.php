<?php
// Aligne TP Module 2 - Cours compatibles, inscriptions preparees & RG-04

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('etudiant');

$studentStmt = $conn->prepare(
    'SELECT e.id_etu, c.niveau, c.nom AS classe_nom
     FROM etudiant e
     INNER JOIN classe c ON c.id_classe = e.id_classe
     WHERE e.id_utl = :id_utl
     LIMIT 1'
);
$studentStmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

$availableCourses = [];
$validatedCourses = [];

if ($student) {
    // RG-01 - L'etudiant ne voit que les cours compatibles avec son niveau.
    $availableStmt = $conn->prepare(
        'SELECT c.id_cours, c.titre, c.description, c.niveau, c.duree_totale,
                CONCAT(ens.prenom, " ", ens.nom) AS enseignant,
                i.statut
         FROM cours c
         INNER JOIN enseignant ens ON ens.id_ens = c.id_ens
         LEFT JOIN inscription i ON i.id_cours = c.id_cours AND i.id_etu = :id_etu
         WHERE c.niveau = :niveau
         ORDER BY c.titre'
    );
    $availableStmt->execute([
        ':id_etu' => (int) $student['id_etu'],
        ':niveau' => $student['niveau'],
    ]);
    $availableCourses = $availableStmt->fetchAll(PDO::FETCH_ASSOC);

    // RG-04 - Acces ressources uniquement apres inscription validee.
    $validatedStmt = $conn->prepare(
        "SELECT c.id_cours, c.titre, c.description, c.niveau,
                GROUP_CONCAT(CONCAT(r.titre, '::', r.type_fichier, '::', r.url_stockage) SEPARATOR '||') AS ressources
         FROM inscription i
         INNER JOIN cours c ON c.id_cours = i.id_cours
         LEFT JOIN ressource r ON r.id_cours = c.id_cours
         WHERE i.id_etu = :id_etu AND i.statut = 'valide'
         GROUP BY c.id_cours, c.titre, c.description, c.niveau
         ORDER BY c.titre"
    );
    $validatedStmt->execute([':id_etu' => (int) $student['id_etu']]);
    $validatedCourses = $validatedStmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Mes cours - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Mes cours</h1>
        <p class="text-secondary mb-0">Inscriptions et ressources accessibles selon validation.</p>
    </div>

    <?php if (!$student) : ?>
        <div class="alert alert-warning" role="alert">Votre profil etudiant doit etre cree par un administrateur.</div>
    <?php else : ?>
        <div class="alert alert-info" role="alert">Classe : <?= escape($student['classe_nom']); ?> | Niveau : <?= escape($student['niveau']); ?></div>
    <?php endif; ?>

    <div id="enrollmentAlert" class="alert d-none" role="alert"></div>

    <section class="mb-4">
        <h2 class="h5 mb-3">Cours compatibles</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle datatable-fr">
                        <thead>
                            <tr>
                                <th>Cours</th>
                                <th>Enseignant</th>
                                <th>Niveau</th>
                                <th>Duree</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableCourses as $course) : ?>
                                <tr data-id="<?= (int) $course['id_cours']; ?>">
                                    <td>
                                        <strong><?= escape($course['titre']); ?></strong>
                                        <div class="text-secondary small"><?= escape($course['description'] ?? ''); ?></div>
                                    </td>
                                    <td><?= escape($course['enseignant']); ?></td>
                                    <td><?= escape($course['niveau']); ?></td>
                                    <td><?= (int) $course['duree_totale']; ?> h</td>
                                    <td class="status-cell"><?= escape($course['statut'] ?? 'non inscrit'); ?></td>
                                    <td>
                                        <?php if ($course['statut'] === null) : ?>
                                            <button type="button" class="btn btn-sm btn-emsp-primary enroll-btn">Demander</button>
                                        <?php else : ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Deja traite</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h5 mb-3">Cours valides et ressources</h2>
        <div class="row g-3">
            <?php foreach ($validatedCourses as $course) : ?>
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h6"><?= escape($course['titre']); ?></h3>
                            <p class="text-secondary"><?= escape($course['description'] ?? ''); ?></p>
                            <?php if (!empty($course['ressources'])) : ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach (explode('||', $course['ressources']) as $resource) : ?>
                                        <?php [$title, $type, $url] = array_pad(explode('::', $resource), 3, ''); ?>
                                        <li class="list-group-item px-0">
                                            <a href="<?= escape($url); ?>" target="_blank" rel="noopener"><?= escape($title); ?></a>
                                            <span class="badge text-bg-light"><?= escape($type); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="text-secondary mb-0">Aucune ressource disponible.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($validatedCourses === []) : ?>
                <div class="col-12">
                    <div class="alert alert-secondary" role="alert">Aucun cours valide pour le moment.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    function showEnrollmentAlert(type, message) {
        $('#enrollmentAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    $(function () {
        $('.enroll-btn').on('click', function () {
            const button = $(this);
            const row = button.closest('tr');

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'request_enrollment',
                    id_cours: row.data('id')
                }
            }).done(function (response) {
                if (!response.success) {
                    showEnrollmentAlert('danger', response.message);
                    return;
                }

                row.find('.status-cell').text(response.data.statut);
                button.removeClass('btn-emsp-primary enroll-btn')
                    .addClass('btn-outline-secondary')
                    .prop('disabled', true)
                    .text('Deja traite');
                showEnrollmentAlert('success', response.message);
            }).fail(function () {
                showEnrollmentAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
