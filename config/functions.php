<?php
// Aligne TP Module 2 - Helpers securite session, roles & affichage

function session_secure_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['last_activity'] = time();
        return;
    }

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
        logout_user();
        redirect('/emsp-digital/auth/login.php?timeout=1');
    }

    $_SESSION['last_activity'] = time();
}

function escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header("Location: {$path}");
    exit;
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['id_utl']) && !empty($_SESSION['role']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        redirect('/emsp-digital/auth/login.php');
    }
}

function check_role(array|string $roles): void
{
    require_auth();

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array(current_user_role(), $allowedRoles, true)) {
        redirect('/emsp-digital/index.php?denied=1');
    }
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function dashboard_path_for_role(?string $role): string
{
    return match ($role) {
        'admin' => '/emsp-digital/admin/dashboard.php',
        'enseignant' => '/emsp-digital/enseignant/dashboard.php',
        'etudiant' => '/emsp-digital/etudiant/dashboard.php',
        default => '/emsp-digital/auth/login.php',
    };
}

function log_audit(PDO $conn, int $userId, string $action): void
{
    // RG-30 - Table audit_log a creer si le projet active la journalisation generale.
    $stmt = $conn->prepare(
        'INSERT INTO audit_log (id_utl, action, adresse_ip, horodatage)
         VALUES (:id_utl, :action, :adresse_ip, NOW())'
    );
    $stmt->execute([
        ':id_utl' => $userId,
        ':action' => $action,
        ':adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    ]);
}
