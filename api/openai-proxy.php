<?php
// Aligne TP Module 2 - Proxy OpenAI securise, PDO prepare() & RG-30/RG-31

require_once __DIR__ . '/../config/dbcon.php';
require_once __DIR__ . '/../config/functions.php';

$openaiLocalConfig = __DIR__ . '/../config/openai.local.php';
if (is_file($openaiLocalConfig)) {
    require_once $openaiLocalConfig;
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
        'provider' => 'openai',
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

function extract_openai_text(array $response): string
{
    if (!empty($response['output_text']) && is_string($response['output_text'])) {
        return trim($response['output_text']);
    }

    foreach (($response['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                return trim((string) $content['text']);
            }
        }
    }

    return '';
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

$apiKey = getenv('OPENAI_API_KEY') ?: (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
$apiUrl = getenv('OPENAI_API_URL') ?: (defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1/responses');
$model = getenv('OPENAI_MODEL') ?: (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4.1-mini');
$caBundle = getenv('OPENAI_CA_BUNDLE') ?: (defined('OPENAI_CA_BUNDLE') ? OPENAI_CA_BUNDLE : '');

if ($apiKey === '' || $apiKey === 'REMPLACE_PAR_TA_CLE_OPENAI') {
    api_response(false, 'Cle API OpenAI non configuree cote serveur.');
}

$instructions = 'Tu es un assistant pedagogique pour la plateforme EMSP Digital. Reponds en francais, clairement, de facon courte et utile pour des etudiants DSER. Si la question concerne le projet, aide sur PHP, MySQL, PDO, Bootstrap, DataTables, securite et bonnes pratiques academiques.';

$payload = [
    'model' => $model,
    'instructions' => $instructions,
    'input' => $message,
    'max_output_tokens' => 700,
];

try {
    log_chat($conn, $userId, 'question', $message);

    $curl = curl_init($apiUrl);
    $curlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ];

    if ($caBundle !== '' && is_file($caBundle)) {
        $curlOptions[CURLOPT_CAINFO] = $caBundle;
    }

    curl_setopt_array($curl, $curlOptions);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($responseBody === false || $curlError !== '') {
        api_response(false, 'Erreur de communication avec OpenAI : ' . $curlError);
    }

    $response = json_decode($responseBody, true);
    if (!is_array($response)) {
        api_response(false, 'Reponse OpenAI invalide.');
    }

    $answer = extract_openai_text($response);

    if ($statusCode < 200 || $statusCode >= 300 || $answer === '') {
        api_response(false, $response['error']['message'] ?? 'Reponse IA indisponible.');
    }

    log_chat($conn, $userId, 'answer', $answer);
    api_response(true, 'Reponse recue.', ['answer' => $answer]);
} catch (Throwable $e) {
    api_response(false, 'Erreur IA.');
}
