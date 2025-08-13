#!/usr/bin/env python3
"""
ESP32 HTTP Simulator (NO AUTH) – Multi-Device con specie
Definisce staticamente elenco di device e specie, invia JSON a ingest.php.
"""

import time, json, math, random, requests
from datetime import datetime, timezone

# --------- CONFIG ---------
URL = "https://test0000.altervista.org/api/ingest.php"

# Elenco statico device -> specie
PLANTS = [
    {"device_id": "ESP32-AULA-1", "species": "Basilico"},
    {"device_id": "ESP32-AULA-2", "species": "Aloe"},
    {"device_id": "ESP32-SERRA-1", "species": "Basilico"},
]

MODE = "roundrobin"   # "roundrobin" | "random"
INTERVAL = 2          # secondi tra invii
COUNT = 0             # 0=infinito (complessivo)
PER_DEVICE = 0        # se >0: invii per pianta (override COUNT)
TIMEOUT = 8
SEED = None
USE_CYCLE = True
# --------------------------

def iso_utc_now(): return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
def clamp(v,lo,hi): return max(lo, min(hi, v))

def gen_values(tick, seed=None, cycle=True):
    rng = random.Random((seed or 0) + tick)
    base_temp, base_hum, base_soil, base_lux = 24.0, 55.0, 500, 300.0
    cyc = math.sin(2*math.pi*(tick%1440)/1440.0) if cycle else 0.0
    temp = clamp(base_temp + 4.0*cyc + rng.uniform(-0.6,0.6), 10.0, 40.0)
    hum  = clamp(base_hum  - 5.0*cyc + rng.uniform(-2.0,2.0), 10.0, 95.0)
    soil = int(clamp(base_soil - 80.0*cyc + rng.uniform(-25,25), 50, 1000))
    lux  = clamp(base_lux  + 500.0*max(0.0,cyc) + rng.uniform(-30,30), 0.0, 5000.0)
    return {"ts": iso_utc_now(), "temperatura_c": round(temp,1), "umidita_aria": round(hum,1), "umidita_suolo": soil, "luce_lux": round(lux,1)}

def main():
    rng = random.Random(SEED)
    idx, tick, total = 0, 0, 0
    sent = {p["device_id"]:0 for p in PLANTS}
    headers = {"Content-Type":"application/json"}

    def stop():
        if PER_DEVICE>0: return all(sent[p["device_id"]]>=PER_DEVICE for p in PLANTS)
        if COUNT>0: return total>=COUNT
        return False

    print(f"Simulatore -> {URL}\nDispositivi: {[p['device_id']+'('+p['species']+')' for p in PLANTS]}")
    try:
        while True:
            plant = PLANTS[idx % len(PLANTS)] if MODE=="roundrobin" else rng.choice(PLANTS)
            payload = gen_values(tick, seed=SEED, cycle=USE_CYCLE)
            payload["device_id"] = plant["device_id"]
            payload["species"]   = plant["species"]   # << invia specie
            try:
                r = requests.post(URL, headers=headers, data=json.dumps(payload), timeout=TIMEOUT)
                print(f"[{payload['ts']}] {plant['device_id']}({plant['species']}) -> {r.status_code}")
            except Exception as e:
                print(f"[{payload['ts']}] ERRORE: {e}")

            sent[plant["device_id"]]+=1; total+=1; tick+=1; idx+=1
            if stop(): break
            time.sleep(max(0.1, INTERVAL))
    except KeyboardInterrupt:
        pass

    print("\nRiepilogo:")
    for d,c in sent.items(): print(f" - {d}: {c}")

if __name__=="__main__":
    main()
