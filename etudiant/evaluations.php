<?php
// Aligne TP Module 2 - Evaluations et soumission unique RG-20/RG-21

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('etudiant');

$studentStmt = $conn->prepare('SELECT id_etu FROM etudiant WHERE id_utl = :id_utl LIMIT 1');
$studentStmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

$evaluations = [];
if ($student) {
    $evalStmt = $conn->prepare(
        "SELECT ev.id_eval, ev.titre, ev.type, ev.note_max, ev.date_limite,
                c.titre AS cours_titre, n.valeur, n.commentaire, n.date_soumission
         FROM inscription i
         INNER JOIN cours c ON c.id_cours = i.id_cours
         INNER JOIN evaluation ev ON ev.id_cours = c.id_cours
         LEFT JOIN note n ON n.id_eval = ev.id_eval AND n.id_etu = i.id_etu
         WHERE i.id_etu = :id_etu AND i.statut = 'valide'
         ORDER BY ev.date_limite DESC"
    );
    $evalStmt->execute([':id_etu' => (int) $student['id_etu']]);
    $evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Evaluations - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Evaluations</h1>
        <p class="text-secondary mb-0">Soumissions uniques et consultation des notes.</p>
    </div>

    <?php if (!$student) : ?>
        <div class="alert alert-warning" role="alert">Votre profil etudiant doit etre cree par un administrateur.</div>
    <?php endif; ?>

    <div id="submitAlert" class="alert d-none" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable-fr">
                    <thead>
                        <tr>
                            <th>Cours</th>
                            <th>Evaluation</th>
                            <th>Type</th>
                            <th>Note max</th>
                            <th>Date limite</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $evaluation) : ?>
                            <?php
                            $submitted = $evaluation['date_soumission'] !== null;
                            $expired = strtotime($evaluation['date_limite']) < time();
                            ?>
                            <tr data-id-eval="<?= (int) $evaluation['id_eval']; ?>"
                                data-title="<?= escape($evaluation['titre']); ?>">
                                <td><?= escape($evaluation['cours_titre']); ?></td>
                                <td><?= escape($evaluation['titre']); ?></td>
                                <td><?= escape($evaluation['type']); ?></td>
                                <td><?= escape((string) $evaluation['note_max']); ?></td>
                                <td><?= escape($evaluation['date_limite']); ?></td>
                                <td>
                                    <?php if ($submitted) : ?>
                                        <?= escape((string) $evaluation['valeur']); ?> / <?= escape((string) $evaluation['note_max']); ?>
                                    <?php else : ?>
                                        Non soumise
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$submitted && !$expired) : ?>
                                        <button type="button" class="btn btn-sm btn-emsp-primary submit-eval-btn" data-bs-toggle="modal" data-bs-target="#submitModal">Soumettre</button>
                                    <?php elseif ($submitted) : ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Deja soumise</button>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Expiree</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="submitEvalForm">
            <input type="hidden" name="action" value="submit_evaluation">
            <input type="hidden" name="id_eval" id="submitEvalId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="submitModalLabel">Soumettre une evaluation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="submitComment">Reponse / commentaire</label>
                <textarea class="form-control" id="submitComment" name="commentaire" rows="5" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-primary">Soumettre</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showSubmitAlert(type, message) {
        $('#submitAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    $(function () {
        $('.submit-eval-btn').on('click', function () {
            const row = $(this).closest('tr');
            $('#submitEvalId').val(row.data('id-eval'));
            $('#submitComment').val('');
            $('#submitModalLabel').text('Soumettre - ' + row.data('title'));
        });

        $('#submitEvalForm').on('submit', function (event) {
            event.preventDefault();
            const form = this;

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                data: $(form).serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (!response.success) {
                    showSubmitAlert('danger', response.message);
                    return;
                }

                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                showSubmitAlert('success', response.message);
                window.setTimeout(function () {
                    window.location.reload();
                }, 500);
            }).fail(function () {
                showSubmitAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
