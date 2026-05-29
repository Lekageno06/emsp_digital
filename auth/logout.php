<?php
// Aligne TP Module 2 - Fermeture de session securisee

require_once __DIR__ . '/../config/functions.php';

session_secure_start();
logout_user();

redirect('/emsp-digital/auth/login.php');
