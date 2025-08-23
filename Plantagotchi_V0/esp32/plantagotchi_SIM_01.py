#!/usr/bin/env python3
"""
Simulatore ESP32 per invio dati Plantagotchi
Invia dati JSON via HTTP POST a ingest.php imitando uno sketch ESP32 reale.
"""

import time, json, math, random, requests
from datetime import datetime, timezone

# === CONFIG ===
URL = "https://test0000.altervista.org/api/ingest.php"

# Elenco statico di dispositivi e specie associate
PLANTS = [
    {"device_id": "ESP32-AULA-1", "species": "Basilico"},
    {"device_id": "ESP32-AULA-2", "species": "Aloe"},
    #{"device_id": "ESP32-SERRA-1", "species": "Ocimum basilicum"},
]

MODE = "roundrobin"   # roundrobin o random
INTERVAL = 5          # secondi tra invii
COUNT = 0             # 0 = infinito
PER_DEVICE = 0        # se >0, numero massimo invii per dispositivo
SEED = 42             # seed casuale riproducibile
USE_CYCLE = True      # alternanza giorno/notte simulata
TIMEOUT = 10
# =================

def iso_ts():
    """Restituisce timestamp in formato YYYY-MM-DD HH:MM:SS (UTC)."""
    return datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")

def clamp(v, lo, hi):
    """Limita v all'intervallo [lo, hi]."""
    return max(lo, min(hi, v))

def genera_valori(tick, seed=None, ciclo=True):
    rng = random.Random((seed or 0) + tick)
    base_t, base_h, base_soil, base_lux = 24.0, 55.0, 500, 300.0
    ora = (tick % 1440) / 1440.0
    variazione = math.sin(2 * math.pi * ora) if ciclo else 0

    temp = clamp(base_t + 4 * variazione + rng.uniform(-0.6, 0.6), 10.0, 40.0)
    umid = clamp(base_h - 5 * variazione + rng.uniform(-2.0, 2.0), 10.0, 95.0)
    soil = int(clamp(base_soil - 80 * variazione + rng.uniform(-25, 25), 50, 1000))
    lux = clamp(base_lux + 500 * max(0.0, variazione) + rng.uniform(-30, 30), 0.0, 10000.0)

    return {
        "ts": iso_ts(),
        "temperatura_c": round(temp, 2),
        "umidita_aria": round(umid, 1),
        "umidita_suolo": soil,
        "luce_lux": round(lux, 1)
    }

def main():
    rng = random.Random(SEED)
    idx = tick = total = 0
    sent = {p["device_id"]: 0 for p in PLANTS}
    headers = {"Content-Type": "application/json"}

    def stop():
        if PER_DEVICE > 0:
            return all(sent[p["device_id"]] >= PER_DEVICE for p in PLANTS)
        if COUNT > 0:
            return total >= COUNT
        return False

    print(f"[INFO] Simulazione avviata verso {URL}")
    print("Dispositivi attivi:")
    for p in PLANTS:
        print(f" - {p['device_id']} ({p['species']})")

    try:
        while not stop():
            plant = PLANTS[idx % len(PLANTS)] if MODE == "roundrobin" else rng.choice(PLANTS)
            payload = genera_valori(tick, SEED, USE_CYCLE)
            payload["device_id"] = plant["device_id"]
            payload["species"] = plant["species"]

            try:
                r = requests.post(URL, headers=headers, data=json.dumps(payload), timeout=TIMEOUT)
                status = f"{r.status_code} {r.reason}" if r.ok else f"ERRORE {r.status_code}"
                print(f"[{payload['ts']}] {plant['device_id']} -> {status}")
            except Exception as e:
                print(f"[{iso_ts()}] ERRORE DI RETE: {e}")

            sent[plant["device_id"]] += 1
            total += 1
            tick += 1
            idx += 1
            time.sleep(INTERVAL)
    except KeyboardInterrupt:
        print("\n[INTERRUZIONE MANUALE]")

    print("\n[SIMULAZIONE TERMINATA]")
    for d, c in sent.items():
        print(f" - {d}: {c} invii")

if __name__ == "__main__":
    main()
