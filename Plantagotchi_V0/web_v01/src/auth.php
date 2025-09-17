<?php
// Avvio sessione
session_start();

//

// Inclusione configurazione database
require_once __DIR__ . '/../config/config.php';

// Verifica che il form sia stato inviato correttamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    
    // Sanificazione input utente
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));

    // Query di selezione utente (senza hash password)
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    // Se trovato esattamente un utente valido
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Salva ID utente nella sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Reindirizza alla dashboard
        header("Location: /public/dashboard.php");
        exit;
    } else {
        // Login fallito → reindirizza a index con errore
        header("Location: /index.php?errore=1");
        exit;
    }

} else {
    // Accesso diretto a questa pagina senza POST → redirect
    header("Location: /index.php");
    exit;
}
