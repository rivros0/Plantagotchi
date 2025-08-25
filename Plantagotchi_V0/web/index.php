<?php
require_once 'api/config.php';
session_start();

// Verifica invio dati via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    // Prepara ed esegue la query
    $stmt = mysqli_prepare($conn, "SELECT id, password FROM utenti WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    // Verifica che l'utente esista
    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $uid, $db_pass);
        mysqli_stmt_fetch($stmt);

        // Verifica corrispondenza password (senza hash)
        if ($pass === $db_pass) {
            $_SESSION['user_id'] = $uid;
            header("Location: public/dashboard.php");
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Plantagotchi</title>
  <link rel="stylesheet" href="public/style.css">
</head>
<body>

  <div class="centered-box">
    <h1>Accesso a Plantagotchi</h1>

    <?php if (!empty($errore)): ?>
      <p class="errore"><?= htmlspecialchars($errore) ?></p>
    <?php endif; ?>

    <form method="POST" action="index.php" autocomplete="on">
      <label>
        Utente:
        <input type="text" name="username" autocomplete="username" required>
      </label>

      <label>
        Password:
        <input type="password" name="password" autocomplete="current-password" required>
      </label>

      <button type="submit">Entra</button>
    </form>
  </div>

</body>
</html>
