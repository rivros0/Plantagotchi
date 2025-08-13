
# Plantagotchi (ESP32 -> HTTP POST -> Altervista) - NO AUTH

## Struttura
- /api/config.php       : Configurazione DB (PDO)
- /api/ingest.php       : Endpoint POST per ricevere JSON e salvare su MySQL (senza API key)
- /api/latest.php       : Ultima misura (opz. filtro ?device_id=...)
- /api/history.php      : Storico (opz. filtro ?device_id=...)
- /public/index.php     : Dashboard HTML con Chart.js
- schema.sql            : Creazione tabella MySQL

## Istruzioni rapide
1. Creare DB su Altervista e importare `schema.sql` (phpMyAdmin).
2. Modificare `/api/config.php` con credenziali DB.
3. Caricare via FTP/SFTP il contenuto mantenendo la struttura cartelle.
4. Verificare `https://TUOACCOUNT.altervista.org/public/`

## Sicurezza
- Questa versione **non** include autenticazione: chiunque conosca l'URL `ingest.php` può inviare dati.
- Per uso pubblico, valutare almeno un token segreto o restrizioni IP.
