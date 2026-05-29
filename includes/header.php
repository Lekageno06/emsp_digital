<?php
// Aligne TP CRUD V2 - Bootstrap 5.3, DataTables & palette EMSP

require_once __DIR__ . '/../config/functions.php';
session_secure_start();

$pageTitle = $pageTitle ?? 'EMSP Digital';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="/emsp-digital/assets/css/emsp-ui.css" rel="stylesheet">
    <link href="/emsp-digital/assets/css/emsp-audit.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body data-auth="<?= is_logged_in() ? '1' : '0'; ?>">
