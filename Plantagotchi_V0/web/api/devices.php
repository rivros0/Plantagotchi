<?php
require __DIR__ . '/config.php';

/* Ricava, per ogni device, l'ultima riga (così otteniamo anche la specie corrente) */
$sql = "
  SELECT m.device_id, m.species, m.ts AS last_ts
  FROM misure_pianta m
  INNER JOIN (
    SELECT device_id, MAX(ts) AS ts
    FROM misure_pianta
    GROUP BY device_id
  ) t ON t.device_id = m.device_id AND t.ts = m.ts
  ORDER BY m.device_id ASC
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();
echo json_encode($rows ?: []);
