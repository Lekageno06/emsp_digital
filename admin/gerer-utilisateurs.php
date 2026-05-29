<?php
// Aligne TP CRUD V2 - Modales Bootstrap, DataTables FR & jQuery closest('tr')

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
check_role('admin');

$usersStmt = $conn->prepare(
    "SELECT u.id_utl, u.email, u.role, u.date_creation,
            e.nom AS etu_nom, e.prenom AS etu_prenom, e.id_classe,
            ens.nom AS ens_nom, ens.prenom AS ens_prenom, ens.specialite,
            c.nom AS classe_nom
     FROM utilisateur u
     LEFT JOIN etudiant e ON e.id_utl = u.id_utl
     LEFT JOIN classe c ON c.id_classe = e.id_classe
     LEFT JOIN enseignant ens ON ens.id_utl = u.id_utl
     ORDER BY u.date_creation DESC"
);
$usersStmt->execute();
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$classesStmt = $conn->prepare('SELECT id_classe, nom, niveau FROM classe ORDER BY nom');
$classesStmt->execute();
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gestion utilisateurs - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Gestion des utilisateurs</h1>
            <p class="text-secondary mb-0">CRUD admin avec modales Bootstrap et requetes PDO preparees.</p>
        </div>
        <button type="button" class="btn btn-emsp-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Ajouter</button>
    </div>

    <div id="crudAlert" class="alert d-none" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-striped align-middle datatable-fr">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Nom</th>
                            <th>Prenom</th>
                            <th>Classe/Specialite</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) : ?>
                            <?php
                            $nom = $user['role'] === 'enseignant' ? $user['ens_nom'] : $user['etu_nom'];
                            $prenom = $user['role'] === 'enseignant' ? $user['ens_prenom'] : $user['etu_prenom'];
                            $detail = $user['role'] === 'enseignant' ? $user['specialite'] : $user['classe_nom'];
                            ?>
                            <tr data-id="<?= (int) $user['id_utl']; ?>"
                                data-email="<?= escape($user['email']); ?>"
                                data-role="<?= escape($user['role']); ?>"
                                data-nom="<?= escape($nom ?? ''); ?>"
                                data-prenom="<?= escape($prenom ?? ''); ?>"
                                data-specialite="<?= escape($user['specialite'] ?? ''); ?>"
                                data-id-classe="<?= escape((string) ($user['id_classe'] ?? '')); ?>"
                                data-classe-nom="<?= escape($user['classe_nom'] ?? ''); ?>"
                                data-date="<?= escape($user['date_creation']); ?>">
                                <td><?= (int) $user['id_utl']; ?></td>
                                <td><?= escape($user['email']); ?></td>
                                <td><span class="badge text-bg-primary"><?= escape($user['role']); ?></span></td>
                                <td><?= escape($nom ?? ''); ?></td>
                                <td><?= escape($prenom ?? ''); ?></td>
                                <td><?= escape($detail ?? ''); ?></td>
                                <td><?= escape($user['date_creation']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions utilisateur">
                                        <button type="button" class="btn btn-outline-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal">Modifier</button>
                                        <button type="button" class="btn btn-outline-danger delete-user-btn" data-bs-toggle="modal" data-bs-target="#deleteUserModal">Supprimer</button>
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

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content user-form" data-mode="create">
            <input type="hidden" name="action" value="create_user">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="addUserModalLabel">Ajouter un utilisateur</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="addEmail">Email</label>
                        <input class="form-control" id="addEmail" name="email" type="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="addPassword">Mot de passe</label>
                        <input class="form-control" id="addPassword" name="password" type="password" minlength="6" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="addRole">Role</label>
                        <select class="form-select role-select" id="addRole" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="etudiant">Etudiant</option>
                        </select>
                    </div>
                    <div class="col-md-6 profile-field">
                        <label class="form-label" for="addNom">Nom</label>
                        <input class="form-control" id="addNom" name="nom" type="text" maxlength="50">
                    </div>
                    <div class="col-md-6 profile-field">
                        <label class="form-label" for="addPrenom">Prenom</label>
                        <input class="form-control" id="addPrenom" name="prenom" type="text" maxlength="50">
                    </div>
                    <div class="col-md-6 teacher-field d-none">
                        <label class="form-label" for="addSpecialite">Specialite</label>
                        <input class="form-control" id="addSpecialite" name="specialite" type="text" maxlength="100">
                    </div>
                    <div class="col-md-6 student-field d-none">
                        <label class="form-label" for="addClasse">Classe</label>
                        <select class="form-select" id="addClasse" name="id_classe">
                            <option value="">Choisir</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= (int) $class['id_classe']; ?>"><?= escape($class['nom'] . ' - ' . $class['niveau']); ?></option>
                            <?php endforeach; ?>
                        </select>
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

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content user-form" data-mode="edit">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="id_utl" id="editId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="editUserModalLabel">Modifier un utilisateur</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="editEmail">Email</label>
                        <input class="form-control" id="editEmail" name="email" type="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="editPassword">Nouveau mot de passe</label>
                        <input class="form-control" id="editPassword" name="password" type="password" minlength="6" placeholder="Laisser vide">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="editRole">Role</label>
                        <select class="form-select role-select" id="editRole" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="enseignant">Enseignant</option>
                            <option value="etudiant">Etudiant</option>
                        </select>
                    </div>
                    <div class="col-md-6 profile-field">
                        <label class="form-label" for="editNom">Nom</label>
                        <input class="form-control" id="editNom" name="nom" type="text" maxlength="50">
                    </div>
                    <div class="col-md-6 profile-field">
                        <label class="form-label" for="editPrenom">Prenom</label>
                        <input class="form-control" id="editPrenom" name="prenom" type="text" maxlength="50">
                    </div>
                    <div class="col-md-6 teacher-field d-none">
                        <label class="form-label" for="editSpecialite">Specialite</label>
                        <input class="form-control" id="editSpecialite" name="specialite" type="text" maxlength="100">
                    </div>
                    <div class="col-md-6 student-field d-none">
                        <label class="form-label" for="editClasse">Classe</label>
                        <select class="form-select" id="editClasse" name="id_classe">
                            <option value="">Choisir</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= (int) $class['id_classe']; ?>"><?= escape($class['nom'] . ' - ' . $class['niveau']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-emsp-primary">Modifier</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="deleteUserForm">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="id_utl" id="deleteId">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteUserModalLabel">Confirmer la suppression</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Supprimer <strong id="deleteEmail"></strong> ? Cette action est definitive.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script>
    const classLabels = <?= json_encode(array_column($classes, 'nom', 'id_classe'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    function showCrudAlert(type, message) {
        $('#crudAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    function toggleProfileFields($form) {
        const role = $form.find('.role-select').val();
        $form.find('.profile-field, .teacher-field, .student-field').addClass('d-none');
        $form.find('[name="nom"], [name="prenom"], [name="id_classe"]').prop('required', false);

        if (role === 'enseignant') {
            $form.find('.profile-field, .teacher-field').removeClass('d-none');
            $form.find('[name="nom"], [name="prenom"]').prop('required', true);
        }

        if (role === 'etudiant') {
            $form.find('.profile-field, .student-field').removeClass('d-none');
            $form.find('[name="nom"], [name="prenom"], [name="id_classe"]').prop('required', true);
        }
    }

    function userDetail(user) {
        if (user.role === 'enseignant') {
            return user.specialite || '';
        }

        if (user.role === 'etudiant') {
            return user.classe_nom || classLabels[user.id_classe] || '';
        }

        return '';
    }

    function actionButtons() {
        return '<div class="btn-group btn-group-sm" role="group" aria-label="Actions utilisateur">' +
            '<button type="button" class="btn btn-outline-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal">Modifier</button>' +
            '<button type="button" class="btn btn-outline-danger delete-user-btn" data-bs-toggle="modal" data-bs-target="#deleteUserModal">Supprimer</button>' +
            '</div>';
    }

    function rowData(user) {
        return [
            user.id_utl,
            $('<div>').text(user.email).html(),
            '<span class="badge text-bg-primary">' + $('<div>').text(user.role).html() + '</span>',
            $('<div>').text(user.nom || '').html(),
            $('<div>').text(user.prenom || '').html(),
            $('<div>').text(userDetail(user)).html(),
            $('<div>').text(user.date_creation || '').html(),
            actionButtons()
        ];
    }

    function applyRowDataset($row, user) {
        $row.attr({
            'data-id': user.id_utl,
            'data-email': user.email,
            'data-role': user.role,
            'data-nom': user.nom || '',
            'data-prenom': user.prenom || '',
            'data-specialite': user.specialite || '',
            'data-id-classe': user.id_classe || '',
            'data-classe-nom': user.classe_nom || '',
            'data-date': user.date_creation || ''
        });
    }

    $(function () {
        const usersTable = $('#usersTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json'
            },
            lengthMenu: [5, 10, 25, 50],
            pagingType: 'simple_numbers',
            responsive: true
        });

        $('.role-select').each(function () {
            toggleProfileFields($(this).closest('form'));
        });

        $(document).on('change', '.role-select', function () {
            toggleProfileFields($(this).closest('form'));
        });

        $(document).on('click', '.edit-user-btn', function () {
            const $row = $(this).closest('tr');
            const $form = $('#editUserModal form');

            $('#editId').val($row.data('id'));
            $('#editEmail').val($row.data('email'));
            $('#editPassword').val('');
            $('#editRole').val($row.data('role'));
            $('#editNom').val($row.data('nom'));
            $('#editPrenom').val($row.data('prenom'));
            $('#editSpecialite').val($row.data('specialite'));
            $('#editClasse').val(String($row.data('id-classe') || ''));

            toggleProfileFields($form);
        });

        $(document).on('click', '.delete-user-btn', function () {
            const $row = $(this).closest('tr');
            $('#deleteId').val($row.data('id'));
            $('#deleteEmail').text($row.data('email'));
        });

        $('.user-form').on('submit', function (event) {
            event.preventDefault();

            const form = this;
            const $form = $(form);
            const mode = $form.data('mode');

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (!response.success) {
                    showCrudAlert('danger', response.message);
                    return;
                }

                const user = response.data.user;

                if (mode === 'create') {
                    const rowNode = usersTable.row.add(rowData(user)).draw(false).node();
                    applyRowDataset($(rowNode), user);
                    form.reset();
                    toggleProfileFields($form);
                } else {
                    const rowNode = $('#usersTable tbody tr').filter(function () {
                        return Number($(this).data('id')) === Number(user.id_utl);
                    });
                    usersTable.row(rowNode).data(rowData(user)).draw(false);
                    applyRowDataset(rowNode, user);
                }

                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                showCrudAlert('success', response.message);
            }).fail(function () {
                showCrudAlert('danger', 'Erreur reseau.');
            });
        });

        $('#deleteUserForm').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: '/emsp-digital/code.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json'
            }).done(function (response) {
                if (!response.success) {
                    showCrudAlert('danger', response.message);
                    return;
                }

                const rowNode = $('#usersTable tbody tr').filter(function () {
                    return Number($(this).data('id')) === Number(response.data.id_utl);
                });

                usersTable.row(rowNode).remove().draw(false);
                bootstrap.Modal.getInstance(document.getElementById('deleteUserModal')).hide();
                showCrudAlert('success', response.message);
            }).fail(function () {
                showCrudAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
