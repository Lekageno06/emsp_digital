<?php
// Aligne TP CRUD V2 - Evaluations, notes via modales & RG-20/RG-21

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('enseignant');

$teacherStmt = $conn->prepare('SELECT id_ens FROM enseignant WHERE id_utl = :id_utl LIMIT 1');
$teacherStmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
$teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
$teacherId = $teacher ? (int) $teacher['id_ens'] : 0;

$courses = [];
$evaluations = [];
$students = [];

if ($teacherId > 0) {
    $coursesStmt = $conn->prepare('SELECT id_cours, titre FROM cours WHERE id_ens = :id_ens ORDER BY titre');
    $coursesStmt->execute([':id_ens' => $teacherId]);
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

    $evalStmt = $conn->prepare(
        'SELECT ev.id_eval, ev.titre, ev.type, ev.note_max, ev.date_limite, c.titre AS cours_titre
         FROM evaluation ev
         INNER JOIN cours c ON c.id_cours = ev.id_cours
         WHERE c.id_ens = :id_ens
         ORDER BY ev.date_limite DESC'
    );
    $evalStmt->execute([':id_ens' => $teacherId]);
    $evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

    $studentsStmt = $conn->prepare(
        "SELECT ev.id_eval, etu.id_etu, etu.nom, etu.prenom, n.valeur, n.commentaire
         FROM evaluation ev
         INNER JOIN cours c ON c.id_cours = ev.id_cours
         INNER JOIN inscription i ON i.id_cours = c.id_cours AND i.statut = 'valide'
         INNER JOIN etudiant etu ON etu.id_etu = i.id_etu
         LEFT JOIN note n ON n.id_eval = ev.id_eval AND n.id_etu = etu.id_etu
         WHERE c.id_ens = :id_ens
         ORDER BY ev.id_eval DESC, etu.nom, etu.prenom"
    );
    $studentsStmt->execute([':id_ens' => $teacherId]);
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Evaluations et notes - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Evaluations et notes</h1>
            <p class="text-secondary mb-0">Creation des evaluations et saisie des notes.</p>
        </div>
        <button type="button" class="btn btn-emsp-primary" data-bs-toggle="modal" data-bs-target="#evaluationModal">Ajouter une evaluation</button>
    </div>

    <?php if ($teacherId <= 0) : ?>
        <div class="alert alert-warning" role="alert">Votre profil enseignant doit etre cree par un administrateur.</div>
    <?php endif; ?>

    <div id="gradeAlert" class="alert d-none" role="alert"></div>

    <section class="mb-4">
        <h2 class="h5 mb-3">Evaluations</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle datatable-fr">
                        <thead>
                            <tr>
                                <th>Cours</th>
                                <th>Titre</th>
                                <th>Type</th>
                                <th>Note max</th>
                                <th>Date limite</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $evaluation) : ?>
                                <tr>
                                    <td><?= escape($evaluation['cours_titre']); ?></td>
                                    <td><?= escape($evaluation['titre']); ?></td>
                                    <td><?= escape($evaluation['type']); ?></td>
                                    <td><?= escape((string) $evaluation['note_max']); ?></td>
                                    <td><?= escape($evaluation['date_limite']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h5 mb-3">Saisie des notes</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle datatable-fr">
                        <thead>
                            <tr>
                                <th>Evaluation</th>
                                <th>Etudiant</th>
                                <th>Note</th>
                                <th>Commentaire</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student) : ?>
                                <tr data-id-eval="<?= (int) $student['id_eval']; ?>"
                                    data-id-etu="<?= (int) $student['id_etu']; ?>"
                                    data-etudiant="<?= escape($student['prenom'] . ' ' . $student['nom']); ?>"
                                    data-valeur="<?= escape((string) ($student['valeur'] ?? '')); ?>"
                                    data-commentaire="<?= escape($student['commentaire'] ?? ''); ?>">
                                    <td>#<?= (int) $student['id_eval']; ?></td>
                                    <td><?= escape($student['prenom'] . ' ' . $student['nom']); ?></td>
                                    <td><?= escape((string) ($student['valeur'] ?? 'Non notee')); ?></td>
                                    <td><?= escape($student['commentaire'] ?? ''); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary grade-btn" data-bs-toggle="modal" data-bs-target="#gradeModal">Noter</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="evaluationForm">
            <input type="hidden" name="action" value="create_evaluation">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="evaluationModalLabel">Ajouter une evaluation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="evalCourse">Cours</label>
                    <select class="form-select" id="evalCourse" name="id_cours" required>
                        <option value="">Choisir</option>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?= (int) $course['id_cours']; ?>"><?= escape($course['titre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="evalTitle">Titre</label>
                    <input class="form-control" id="evalTitle" name="titre" type="text" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="evalType">Type</label>
                    <select class="form-select" id="evalType" name="type" required>
                        <option value="quiz">Quiz</option>
                        <option value="projet">Projet</option>
                        <option value="examen">Examen</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="evalMax">Note max</label>
                    <input class="form-control" id="evalMax" name="note_max" type="number" min="1" step="0.25" required>
                </div>
                <div>
                    <label class="form-label" for="evalDeadline">Date limite</label>
                    <input class="form-control" id="evalDeadline" name="date_limite" type="datetime-local" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="gradeForm">
            <input type="hidden" name="action" value="save_grade">
            <input type="hidden" name="id_eval" id="gradeEvalId">
            <input type="hidden" name="id_etu" id="gradeStudentId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="gradeModalLabel">Saisir une note</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="gradeValue">Note</label>
                    <input class="form-control" id="gradeValue" name="valeur" type="number" min="0" step="0.25" required>
                </div>
                <div>
                    <label class="form-label" for="gradeComment">Commentaire</label>
                    <textarea class="form-control" id="gradeComment" name="commentaire" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-success">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showGradeAlert(type, message) {
        $('#gradeAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    $(function () {
        $('.grade-btn').on('click', function () {
            const row = $(this).closest('tr');
            $('#gradeEvalId').val(row.data('id-eval'));
            $('#gradeStudentId').val(row.data('id-etu'));
            $('#gradeValue').val(row.data('valeur'));
            $('#gradeComment').val(row.data('commentaire'));
            $('#gradeModalLabel').text('Saisir une note - ' + row.data('etudiant'));
        });

        $('#evaluationForm, #gradeForm').on('submit', function (event) {
            event.preventDefault();
            const form = this;

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (!response.success) {
                    showGradeAlert('danger', response.message);
                    return;
                }

                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                showGradeAlert('success', response.message);
                window.setTimeout(function () {
                    window.location.reload();
                }, 500);
            }).fail(function () {
                showGradeAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
