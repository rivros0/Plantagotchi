<?php
// Plantagotchi - Configurazione database (procedurale, senza oggetti)

// Definizione costanti per la connessione al database
define('DB_HOST', 'localhost');          // Host del database (di solito 'localhost' su Altervista)
define('DB_NAME', 'my_test0000');        // Nome del database
define('DB_USER', 'test0000');           // Nome utente del database
define('DB_PASS', 'AGPbJB7PyXt7');       // Password del database

// Connessione al database MySQL in modalità procedurale
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verifica della connessione
if (!$conn) {
    // In caso di errore, termina lo script e mostra il messaggio
    die("Errore connessione al database: " . mysqli_connect_error());
}
?>
