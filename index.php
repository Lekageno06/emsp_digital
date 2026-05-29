<?php
// Aligne TP Module 2 - Routeur simple par role, sans logique metier

require_once __DIR__ . '/config/functions.php';

session_secure_start();

if (!is_logged_in()) {
    redirect('/emsp-digital/auth/login.php');
}

redirect(dashboard_path_for_role(current_user_role()));
