<?php
require_once __DIR__ . '/../api/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// gestione logica qui...
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Gestione Specie - Plantagotchi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- CSS e altro qui -->
</head>
<body>
<!-- corpo della pagina -->
