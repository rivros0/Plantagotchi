<?php
// index.php - Punto di ingresso del sito

require_once __DIR__ . '/config/config.php';
session_start();

// Se l'utente è già autenticato, reindirizza alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /public/dashboard.php');
    exit;
}

// Altrimenti mostra la pagina di login
require_once __DIR__ . '/templates/login_form.php';
