<?php
// Aligne TP Module 2 - Proxy IA Qwen securise, PDO prepare() & RG-30/RG-31

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

$qwenLocalConfig = __DIR__ . '/../config/qwen.local.php';
if (is_file($qwenLocalConfig)) {
    require_once $qwenLocalConfig;
}

session_secure_start();
require_auth();

header('Content-Type: application/json; charset=utf-8');

function api_response(bool $success, string $message, array $data = []): never
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

function log_chat(PDO $conn, int $userId, string $kind, string $content): void
{
    // RG-30 - Journalisation IA avec timestamp BDD et IP dans le contenu journalise.
    $payload = json_encode([
        'type' => $kind,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'content' => $content,
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare(
        'INSERT INTO chat_log (id_utl, message)
         VALUES (:id_utl, :message)'
    );
    $stmt->execute([
        ':id_utl' => $userId,
        ':message' => $payload,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(false, 'Methode non autorisee.');
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '{}', true);

if (!is_array($input)) {
    api_response(false, 'Requete JSON invalide.');
}

$action = (string) ($input['action'] ?? 'chat');
$userId = (int) ($_SESSION['id_utl'] ?? 0);

if ($action === 'delete_history') {
    // RG-31 - Droit a l'oubli : suppression de l'historique IA utilisateur.
    $stmt = $conn->prepare('DELETE FROM chat_log WHERE id_utl = :id_utl');
    $stmt->execute([':id_utl' => $userId]);
    api_response(true, 'Historique supprime.');
}

if ($action !== 'chat') {
    api_response(false, 'Action inconnue.');
}

if (empty($input['consent'])) {
    api_response(false, 'Consentement obligatoire avant utilisation IA.');
}

$message = trim((string) ($input['message'] ?? ''));

if ($message === '' || mb_strlen($message) > 1500) {
    api_response(false, 'Message invalide ou trop long.');
}

$apiKey = getenv('QWEN_API_KEY') ?: (defined('QWEN_API_KEY') ? QWEN_API_KEY : '');
$apiUrl = getenv('QWEN_API_URL') ?: (defined('QWEN_API_URL') ? QWEN_API_URL : 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions');
$model = getenv('QWEN_MODEL') ?: (defined('QWEN_MODEL') ? QWEN_MODEL : 'qwen-plus');

if ($apiKey === '') {
    api_response(false, 'Cle API Qwen non configuree cote serveur.');
}

$systemPrompt = 'Tu es un assistant pedagogique pour la plateforme EMSP Digital. Reponds en francais, clairement, avec des explications courtes et utiles pour des etudiants DSER.';

$payload = [
    'model' => $model,
    'temperature' => 0.3,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message],
    ],
];

try {
    log_chat($conn, $userId, 'question', $message);

    $curl = curl_init($apiUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($responseBody === false || $curlError !== '') {
        api_response(false, 'Erreur de communication avec Qwen.');
    }

    $response = json_decode($responseBody, true);
    $answer = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

    if ($statusCode < 200 || $statusCode >= 300 || $answer === '') {
        api_response(false, 'Reponse IA indisponible.');
    }

    log_chat($conn, $userId, 'answer', $answer);
    api_response(true, 'Reponse recue.', ['answer' => $answer]);
} catch (Throwable $e) {
    api_response(false, 'Erreur IA.');
}
