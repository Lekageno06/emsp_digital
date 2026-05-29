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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="/emsp-digital/assets/css/emsp-audit.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        :root {
            --emsp-blue: #2563EB;
            --emsp-green: #10B981;
            --emsp-yellow: #F59E0B;
            --emsp-white: #FFFFFF;
            --emsp-gray-50: #F8FAFC;
            --emsp-text: #0F172A;
        }

        body {
            background: var(--emsp-gray-50);
            color: var(--emsp-text);
        }

        .navbar-emsp {
            background: var(--emsp-blue);
        }

        .btn-emsp-primary {
            --bs-btn-bg: var(--emsp-blue);
            --bs-btn-border-color: var(--emsp-blue);
            --bs-btn-color: var(--emsp-white);
            --bs-btn-hover-bg: #1D4ED8;
            --bs-btn-hover-border-color: #1D4ED8;
            --bs-btn-hover-color: var(--emsp-white);
        }

        .btn-emsp-success {
            --bs-btn-bg: var(--emsp-green);
            --bs-btn-border-color: var(--emsp-green);
            --bs-btn-color: var(--emsp-white);
            --bs-btn-hover-bg: #059669;
            --bs-btn-hover-border-color: #059669;
            --bs-btn-hover-color: var(--emsp-white);
        }

        :focus-visible {
            outline: 3px solid var(--emsp-yellow);
            outline-offset: 2px;
        }
    </style>
</head>
<body data-auth="<?= is_logged_in() ? '1' : '0'; ?>">
