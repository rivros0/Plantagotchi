<?php
require __DIR__ . '/config.php';

/* Se passato ?species=Nome, restituisce solo quella riga; altrimenti l'elenco */
$species = $_GET['species'] ?? null;

if ($species) {
  $stmt = $pdo->prepare("SELECT * FROM species_params WHERE species = ?");
  $stmt->execute([$species]);
  $row = $stmt->fetch();
  echo json_encode($row ?: []);
  exit;
}

$stmt = $pdo->query("SELECT * FROM species_params ORDER BY species ASC");
echo json_encode($stmt->fetchAll() ?: []);
