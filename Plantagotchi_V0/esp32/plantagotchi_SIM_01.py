#!/usr/bin/env python3
"""
ESP32 HTTP Simulator (NO AUTH) – Multi-Device
Versione con array statico di piante definito all'inizio.
"""

import sys
import time
import json
import math
import random
import requests
from datetime import datetime, timezone

# ----------------- CONFIGURAZIONE -----------------
# Endpoint Altervista
URL = "https://test0000.altervista.org/api/ingest.php"

# Array statico di piante
DEVICES = [
    "ESP32-AULA-1",
    "ESP32-AULA-2",
    "ESP32-SERRA-1"
]

MODE = "roundrobin"     # "roundrobin" o "random"
INTERVAL = 5            # secondi tra richieste
COUNT = 0               # 0 = infinito, >0 = numero totale invii
PER_DEVICE = 0          # Se >0, invii per ogni pianta (ignora COUNT)
TIMEOUT = 8             # Timeout HTTP
SEED = None             # Per riproducibilità valori
USE_CYCLE = True        # Ciclo diurno ON/OFF
# ---------------------------------------------------

def iso_utc_now():
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

def clamp(v, lo, hi):
    return max(lo, min(hi, v))

def gen_values(tick: int, seed=None, cycle=True):
    rng = random.Random((seed or 0) + tick)
    base_temp = 20.0
    base_hum  = 55.0
    base_soil = 1000
    base_lux  = 3000.0

    cyc = math.sin(2*math.pi * (tick % 1440) / 1440.0) if cycle else 0.0

    temp = base_temp + 4.0*cyc + rng.uniform(-0.6, 0.6)
    hum  = base_hum  - 5.0*cyc + rng.uniform(-2.0, 2.0)
    soil = base_soil - 80.0*cyc + rng.uniform(-25, 25)
    lux  = base_lux  + 500.0*max(0.0, cyc) + rng.uniform(-30, 30)

    return {
        "ts": iso_utc_now(),
        "temperatura_c": round(clamp(temp, 10.0, 40.0), 1),
        "umidita_aria":  round(clamp(hum, 10.0, 95.0), 1),
        "umidita_suolo": int(clamp(soil, 50, 1000)),
        "luce_lux":      round(clamp(lux, 500.0, 5000.0), 1),
    }

def main():
    rng = random.Random(SEED)
    tick = 0
    total_sent = 0
    sent_by_device = {d: 0 for d in DEVICES}
    idx = 0
    headers = {"Content-Type": "application/json"}

    print(f"Simulatore multi-pianta -> {URL}")
    print(f"Devices: {DEVICES}")
    print(f"Modalità: {MODE} | Intervallo: {INTERVAL}s | Ciclo diurno: {'ON' if USE_CYCLE else 'OFF'}")
    print("Ctrl+C per interrompere.\n")

    def should_stop():
        if PER_DEVICE > 0:
            return all(sent_by_device[d] >= PER_DEVICE for d in DEVICES)
        if COUNT > 0:
            return total_sent >= COUNT
        return False

    try:
        while True:
            if MODE == "roundrobin":
                device = DEVICES[idx % len(DEVICES)]
                idx += 1
            else:
                device = rng.choice(DEVICES)

            payload = gen_values(tick, seed=SEED, cycle=USE_CYCLE)
            payload["device_id"] = device

            try:
                r = requests.post(URL, headers=headers, data=json.dumps(payload), timeout=TIMEOUT)
                ok = (200 <= r.status_code < 300)
                print(f"[{payload['ts']}] {device} POST {r.status_code} ({'OK' if ok else 'ERR'})")
            except Exception as e:
                print(f"[{payload['ts']}] {device} POST ERROR: {e}")

            total_sent += 1
            sent_by_device[device] += 1
            tick += 1

            if should_stop():
                break
            time.sleep(max(0.1, INTERVAL))

    except KeyboardInterrupt:
        print("\nInterrotto dall'utente.")

    print("\nRiepilogo invii per device:")
    for d in DEVICES:
        print(f" - {d}: {sent_by_device[d]} richieste")
    print("Fatto.")

if __name__ == "__main__":
    main()
