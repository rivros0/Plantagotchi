import requests
import random
import time
from datetime import datetime
import pytz

# URL dell'API Altervista
URL = "https://test0000.altervista.org/api/ingest.php"

# Lista di piante da simulare
plants = [
    {"device_id": "SYM-esp32-01", "species": "Basilico"},
    {"device_id": "SYM-esp32-02", "species": "Menta"},
    {"device_id": "SYM-esp32-03", "species": "Rosmarino"},
    {"device_id": "SYM-esp32-04", "species": "Timo"}
]

# Funzione per ottenere timestamp con fuso Europe/Rome
def get_timestamp():
    tz = pytz.timezone("Europe/Rome")
    now = datetime.now(tz)
    return now.strftime("%Y-%m-%d %H:%M:%S")

# Funzione per generare dati simulati coerenti con i sensori reali
def get_fake_readings():
    return {
        "temperatura_c": round(random.uniform(18.0, 33.0), 2),
        "umidita_aria": round(random.uniform(35.0, 85.0), 1),
        "luce_lux": round(random.uniform(0, 100), 1),
        "umidita_suolo": random.randint(0, 100)
    }

# Invia i dati di una pianta simulata
def send_data(plant):
    data = get_fake_readings()
    data["ts"] = get_timestamp()
    data["device_id"] = plant["device_id"]
    data["species"] = plant["species"]

    print(f"\n📤 Inviando per {data['species']} ({data['device_id']}): {data}")
    try:
        r = requests.post(URL, json=data, timeout=10)
        print(f"✅ Risposta: {r.status_code} - {r.text}")
    except Exception as e:
        print(f"❌ Errore: {e}")

# Loop principale: invia dati per ogni pianta ogni 60s
def main():
    while True:
        for plant in plants:
            send_data(plant)
            time.sleep(1)  # piccola pausa tra le piante
        print("⏳ Attendo 60 secondi prima del prossimo ciclo...\n")
        time.sleep(60)

if __name__ == "__main__":
    main()
