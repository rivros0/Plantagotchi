<?php
require __DIR__ . '/config.php';

/*
Parametri:
- device_id (opzionale)
- hours (opzionale)  es. 24 -> ultimi 24h
- days  (opzionale)  es. 30 -> ultimi 30 giorni
- limit (opzionale)  cap superiore in numero righe
*/
$device = $_GET['device_id'] ?? null;
$hours  = isset($_GET['hours']) ? intval($_GET['hours']) : null;
$days   = isset($_GET['days'])  ? intval($_GET['days'])  : null;
$limit  = max(10, min(5000, intval($_GET['limit'] ?? 1000)));

$where  = [];
$args   = [];

if ($device) { $where[] = "device_id = ?"; $args[] = $device; }
if ($hours)  { $where[] = "ts >= (NOW() - INTERVAL ? HOUR)"; $args[] = $hours; }
if ($days)   { $where[] = "ts >= (NOW() - INTERVAL ? DAY)";  $args[] = $days; }

$sql = "SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux
        FROM misure_pianta";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY ts DESC LIMIT ?";

$args[] = $limit;

$stmt = $pdo->prepare($sql);

/* bindValue semplice rispettando i tipi */
$pos = 1;
foreach ($args as $a) {
  if (is_int($a)) $stmt->bindValue($pos++, $a, PDO::PARAM_INT);
  else            $stmt->bindValue($pos++, $a, PDO::PARAM_STR);
}
$stmt->execute();

$rows = $stmt->fetchAll();
/* Ritorno cronologico ascendente per i grafici */
echo json_encode(array_reverse($rows));
