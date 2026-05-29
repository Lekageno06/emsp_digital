<?php
// Aligne TP CRUD V2 - CRUD cours, seances via modales & DataTables FR

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('enseignant');

$teacherStmt = $conn->prepare('SELECT id_ens FROM enseignant WHERE id_utl = :id_utl LIMIT 1');
$teacherStmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
$teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
$teacherId = $teacher ? (int) $teacher['id_ens'] : 0;

$courses = [];
if ($teacherId > 0) {
    $coursesStmt = $conn->prepare(
        'SELECT c.id_cours, c.titre, c.description, c.niveau, c.duree_totale,
                COUNT(DISTINCT i.id_insc) AS total_inscriptions,
                COUNT(DISTINCT s.id_seance) AS total_seances,
                COALESCE(SUM(s.duree_min), 0) AS total_minutes
         FROM cours c
         LEFT JOIN inscription i ON i.id_cours = c.id_cours
         LEFT JOIN seance s ON s.id_cours = c.id_cours
         WHERE c.id_ens = :id_ens
         GROUP BY c.id_cours, c.titre, c.description, c.niveau, c.duree_totale
         ORDER BY c.id_cours DESC'
    );
    $coursesStmt->execute([':id_ens' => $teacherId]);
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Gestion cours - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Gestion des cours</h1>
            <p class="text-secondary mb-0">Cours, planification des seances et suivi des inscriptions.</p>
        </div>
        <button type="button" class="btn btn-emsp-primary" data-bs-toggle="modal" data-bs-target="#courseModal">Ajouter un cours</button>
    </div>

    <?php if ($teacherId <= 0) : ?>
        <div class="alert alert-warning" role="alert">Votre profil enseignant doit etre cree par un administrateur.</div>
    <?php endif; ?>

    <div id="courseAlert" class="alert d-none" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable-fr">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Niveau</th>
                            <th>Duree</th>
                            <th>Seances</th>
                            <th>Inscriptions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course) : ?>
                            <tr data-id="<?= (int) $course['id_cours']; ?>"
                                data-titre="<?= escape($course['titre']); ?>"
                                data-description="<?= escape($course['description'] ?? ''); ?>"
                                data-niveau="<?= escape($course['niveau']); ?>"
                                data-duree="<?= (int) $course['duree_totale']; ?>">
                                <td><?= escape($course['titre']); ?></td>
                                <td><?= escape($course['niveau']); ?></td>
                                <td><?= (int) $course['duree_totale']; ?> h</td>
                                <td><?= (int) $course['total_seances']; ?> / <?= (int) $course['total_minutes']; ?> min</td>
                                <td><?= (int) $course['total_inscriptions']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions cours">
                                        <button type="button" class="btn btn-outline-success session-btn" data-bs-toggle="modal" data-bs-target="#sessionModal">Seance</button>
                                        <button type="button" class="btn btn-outline-primary edit-course-btn" data-bs-toggle="modal" data-bs-target="#courseModal">Modifier</button>
                                        <button type="button" class="btn btn-outline-danger delete-course-btn" data-bs-toggle="modal" data-bs-target="#deleteCourseModal">Supprimer</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="courseForm">
            <input type="hidden" name="action" id="courseAction" value="create_course">
            <input type="hidden" name="id_cours" id="courseId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="courseModalLabel">Cours</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="courseTitle">Titre</label>
                        <input class="form-control" id="courseTitle" name="titre" type="text" maxlength="100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="courseLevel">Niveau</label>
                        <input class="form-control" id="courseLevel" name="niveau" type="text" maxlength="20" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="courseDuration">Duree totale (h)</label>
                        <input class="form-control" id="courseDuration" name="duree_totale" type="number" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="courseDescription">Description</label>
                        <textarea class="form-control" id="courseDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="sessionModal" tabindex="-1" aria-labelledby="sessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="sessionForm">
            <input type="hidden" name="action" value="create_session">
            <input type="hidden" name="id_cours" id="sessionCourseId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="sessionModalLabel">Planifier une seance</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="sessionDate">Date et heure</label>
                    <input class="form-control" id="sessionDate" name="date_heure" type="datetime-local" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="sessionDuration">Duree (minutes)</label>
                    <input class="form-control" id="sessionDuration" name="duree_min" type="number" min="15" step="15" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="sessionType">Type</label>
                    <select class="form-select" id="sessionType" name="type" required>
                        <option value="presentiel">Presentiel</option>
                        <option value="distanciel">Distanciel</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="sessionLink">Lien visio</label>
                    <input class="form-control" id="sessionLink" name="lien_visio" type="url" maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-success">Planifier</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="deleteCourseForm">
            <input type="hidden" name="action" value="delete_course">
            <input type="hidden" name="id_cours" id="deleteCourseId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteCourseModalLabel">Supprimer le cours</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Supprimer <strong id="deleteCourseTitle"></strong> ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showCourseAlert(type, message) {
        $('#courseAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    $(function () {
        $('#courseModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const isEdit = button.hasClass('edit-course-btn');
            const row = button.closest('tr');

            $('#courseForm')[0].reset();
            $('#courseAction').val(isEdit ? 'update_course' : 'create_course');
            $('#courseModalLabel').text(isEdit ? 'Modifier le cours' : 'Ajouter un cours');

            if (isEdit) {
                $('#courseId').val(row.data('id'));
                $('#courseTitle').val(row.data('titre'));
                $('#courseDescription').val(row.data('description'));
                $('#courseLevel').val(row.data('niveau'));
                $('#courseDuration').val(row.data('duree'));
            } else {
                $('#courseId').val('');
            }
        });

        $('.session-btn').on('click', function () {
            const row = $(this).closest('tr');
            $('#sessionForm')[0].reset();
            $('#sessionCourseId').val(row.data('id'));
            $('#sessionModalLabel').text('Planifier une seance - ' + row.data('titre'));
        });

        $('.delete-course-btn').on('click', function () {
            const row = $(this).closest('tr');
            $('#deleteCourseId').val(row.data('id'));
            $('#deleteCourseTitle').text(row.data('titre'));
        });

        $('#courseForm, #sessionForm, #deleteCourseForm').on('submit', function (event) {
            event.preventDefault();
            const form = this;

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (!response.success) {
                    showCourseAlert('danger', response.message);
                    return;
                }

                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                showCourseAlert('success', response.message);
                window.setTimeout(function () {
                    window.location.reload();
                }, 500);
            }).fail(function () {
                showCourseAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
