import requests
import random
import time
from datetime import datetime
import pytz

# Lista di domini Altervista (aggiungine quanti vuoi)
URLS = [
    "https://test0000.altervista.org/api/ingest.php",
   # "https://test1111.altervista.org/api/ingest.php",
    #"https://test2222.altervista.org/api/ingest.php"
]

# Lista di piante da simulare
plants = [
    {"device_id": "SYM-esp32-01", "species": "Basilico"},
    {"device_id": "SYM-esp32-02", "species": "Menta"},
    {"device_id": "SYM-esp32-03", "species": "Rosmarino"},
    {"device_id": "SYM-esp32-04", "species": "Timo"}
]

# Definizione di Min e Max per i dati da inviare
temp_min = 18.0
temp_max = 40.0
hum_air_min = 10.0
hum_air_max = 90.0
soil_min = 0
soil_max = 100
lux_min = 0 
lux_max = 100

# Definizione del tempo di attesa tra gli invii in secondi
refreshRate = 60

# Funzione per ottenere timestamp con fuso Europe/Rome
def get_timestamp():
    tz = pytz.timezone("Europe/Rome")
    now = datetime.now(tz)
    return now.strftime("%Y-%m-%d %H:%M:%S")

# Funzione per generare dati simulati coerenti con i sensori reali
def get_fake_readings():
    return {
        "temperatura_c": round(random.uniform(temp_min, temp_max), 2),
        "umidita_aria": round(random.uniform(hum_air_min, hum_air_max), 1),
        "luce_lux": round(random.uniform(lux_min, lux_max), 1),
        "umidita_suolo": random.randint(soil_min, soil_max)
    }

# Invia i dati di una pianta simulata verso tutti i domini
def send_data(plant):
    data = get_fake_readings()
    data["ts"] = get_timestamp()
    data["device_id"] = plant["device_id"]
    data["species"] = plant["species"]

    print(f"\n📤 Inviando per {data['species']} ({data['device_id']}): {data}")
    for url in URLS:
        try:
            r = requests.post(url, json=data, timeout=10)
            print(f"  🌍 {url} → {r.status_code} - {r.text}")
        except Exception as e:
            print(f"  ❌ Errore verso {url}: {e}")

# Loop principale: invia dati per ogni pianta ogni 60s
def main():
    while True:
        for plant in plants:
            send_data(plant)
            time.sleep(1)  # piccola pausa tra le piante
        print("\n ⏳ Attendo " + str(refreshRate) + " secondi prima del prossimo ciclo...\n")
        time.sleep(refreshRate)

if __name__ == "__main__":
    main()
