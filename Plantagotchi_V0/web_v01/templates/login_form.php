<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Plantagotchi</title>
  <link rel="stylesheet" href="/public/style.css">
</head>
<body>

  <header>
    <h1>🌱 Plantagotchi - Login</h1>
  </header>

  <main class="container">
    <section class="box" style="max-width: 400px; margin: 0 auto;">
      <form method="post" action="/src/auth.php">
        <div class="form-group">
          <label for="username">Nome utente</label>
          <input type="text" name="username" id="username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" required>
        </div>

        <div class="form-group" style="text-align: center;">
          <button type="submit" name="login">Accedi</button>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
