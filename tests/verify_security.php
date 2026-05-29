<?php
// Aligne TP Module 2 - Audit securite statique PDO, hash & sessions

$root = dirname(__DIR__);
$forbiddenPatterns = [
    'mysqli_' => '/mysqli_/i',
    'md5(' => '/\bmd5\s*\(/i',
    'sha1(' => '/\bsha1\s*\(/i',
    'mysql_query' => '/mysql_query\s*\(/i',
];

$requiredPatterns = [
    'PDO prepare' => '/->prepare\s*\(/',
    'password_hash' => '/password_hash\s*\(/',
    'password_verify' => '/password_verify\s*\(/',
    'session_regenerate_id' => '/session_regenerate_id\s*\(/',
    'htmlspecialchars' => '/htmlspecialchars\s*\(/',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$phpFiles = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        if ($file->getPathname() === __FILE__) {
            continue;
        }

        $phpFiles[] = $file->getPathname();
    }
}

$allCode = '';
$failed = false;

foreach ($phpFiles as $path) {
    $content = file_get_contents($path);
    $allCode .= "\n" . $content;

    foreach ($forbiddenPatterns as $label => $pattern) {
        if (preg_match($pattern, $content)) {
            echo "[ECHEC] {$label} detecte dans {$path}\n";
            $failed = true;
        }
    }
}

foreach ($requiredPatterns as $label => $pattern) {
    if (!preg_match($pattern, $allCode)) {
        echo "[ECHEC] Motif requis absent : {$label}\n";
        $failed = true;
    }
}

if (!$failed) {
    echo "[OK] Audit statique reussi : PDO, hash, sessions et echappement detectes.\n";
    echo "[OK] Aucun mysqli, md5, sha1 ou mysql_query detecte.\n";
}

exit($failed ? 1 : 0);
