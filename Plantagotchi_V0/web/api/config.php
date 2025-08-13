
<?php
// Plantagotchi - Configurazione DB (NO AUTH)
header('Content-Type: application/json');

$DB_HOST = "localhost";
$DB_NAME = "my_test0000";
$DB_USER = "test0000";
$DB_PASS = "AGPbJB7PyXt7";

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "DB connection failed"]);
  exit;
}
