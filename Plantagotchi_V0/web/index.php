<?php
require_once __DIR__ . '/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $stmt = $db->prepare("SELECT id, password FROM utenti WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($uid, $hash);
        $stmt->fetch();
        if (password_verify($pass, $hash)) {
            $_SESSION['user_id'] = $uid;
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Credenziali non valide";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Login - Plantagotchi</title>
</head>
<body>
  <h1>Plantagotchi - Login</h1>
  <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
  <form method="POST">
    <label>Username: <input type="text" name="username" required></label><br><br>
    <label>Password: <input type="password" name="password" required></label><br><br>
    <button type="submit">Accedi</button>
  </form>
</body>
</html>
