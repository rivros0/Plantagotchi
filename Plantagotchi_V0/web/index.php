<?php
require_once 'api/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, password FROM utenti WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $uid, $db_pass);
        mysqli_stmt_fetch($stmt);

        if ($pass === $db_pass) {
            $_SESSION['user_id'] = $uid;
            header("Location: ../public/dashboard.php");
            exit;
        }
    }

    $errore = "Credenziali non valide.";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Plantagotchi</title>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: #f0f0f0;
    }

    .login-box {
      background: #fff;
      padding: 20px 30px;
      border: 1px solid #ccc;
      box-shadow: 0 0 10px #ccc;
      text-align: center;
    }

    input {
      padding: 5px;
      width: 200px;
    }

    button {
      padding: 5px 10px;
      margin-top: 10px;
    }

    p {
      color: red;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>Accesso a Plantagotchi</h1>

    <?php if (!empty($errore)) echo "<p>" . htmlspecialchars($errore) . "</p>"; ?>

    <form method="POST" action="index.php" autocomplete="on">
      <div>
        <label>
          Utente:<br>
          <input type="text" name="username" autocomplete="username" required>
        </label>
      </div><br>

      <div>
        <label>
          Password:<br>
          <input type="password" name="password" autocomplete="current-password" required>
        </label>
      </div><br>

      <button type="submit">Entra</button>
    </form>
  </div>
</body>
</html>
