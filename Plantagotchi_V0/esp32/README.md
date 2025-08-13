
# ESP32 HTTP Simulator (Plantagotchi → Altervista)

Simula un ESP32 che invia misure via **HTTP POST** all'endpoint `ingest.php` su Altervista.

## Requisiti
- Python 3.8+
- `pip install -r requirements.txt`

## Configurazione rapida
Imposta gli URL/chiavi via CLI o variabili d'ambiente:

```bash
export INGEST_URL="https://TUOACCOUNT.altervista.org/api/ingest.php"
export API_KEY="SOSTITUISCI_CON_API_KEY_64"
export DEVICE_ID="ESP32-SIM-1"
python simulator.py --interval 10 --count 20
```

Oppure con soli argomenti:

```bash
python simulator.py \  --url "https://TUOACCOUNT.altervista.org/api/ingest.php" \  --api-key "SOSTITUISCI_CON_API_KEY_64" \  --device-id "ESP32-SIM-1" \  --interval 10 --count 20
```

## Parametri principali
- `--url`        : endpoint ingest (es. `https://.../api/ingest.php`)
- `--api-key`    : valore header `X-API-Key`
- `--device-id`  : valore header `X-Device-Id`
- `--interval`   : secondi tra un POST e l'altro (default 10)
- `--count`      : numero di misure da inviare (0 = infinito)
- `--seed`       : seed per dati riproducibili
- `--no-cycle`   : disabilita l'andamento giornaliero (sinusoide)

## Output tipico
```
[2025-08-12T16:15:00Z] POST 200 (OK) -> {"status":"ok"}
```

## Note
- I payload sono conformi all'API proposta: `ts`, `temperatura_c`, `umidita_aria`, `umidita_suolo`, `luce_lux`.
- Per test locali senza HTTPS, puoi usare un tunnel o un endpoint di prova.
