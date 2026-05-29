<?php
// Aligne TP Module 2 - Profil utilisateur & suppression historique IA RG-31

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

session_secure_start();
require_auth();

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM chat_log WHERE id_utl = :id_utl');
$countStmt->execute([':id_utl' => (int) $_SESSION['id_utl']]);
$logCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

$pageTitle = 'Profil - EMSP Digital';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Profil</h1>
        <p class="text-secondary mb-0">Compte connecte : <?= escape($_SESSION['email'] ?? ''); ?></p>
    </div>

    <div id="profileAlert" class="alert d-none" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Historique IA</h2>
            <p class="text-secondary">Messages journalises : <strong id="chatLogCount"><?= $logCount; ?></strong></p>
            <button type="button" class="btn btn-danger" id="deleteHistoryBtn">Supprimer mon historique</button>
        </div>
    </div>
</main>

<script>
    function showProfileAlert(type, message) {
        $('#profileAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .text(message);
    }

    $(function () {
        $('#deleteHistoryBtn').on('click', function () {
            fetch('/emsp-digital/api/openai-proxy.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'delete_history'})
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    showProfileAlert('danger', payload.message);
                    return;
                }

                $('#chatLogCount').text('0');
                showProfileAlert('success', payload.message);
            }).catch(function () {
                showProfileAlert('danger', 'Erreur reseau.');
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
