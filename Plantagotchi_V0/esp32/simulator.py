
#!/usr/bin/env python3
"""
ESP32 HTTP Simulator (NO AUTH) for Plantagotchi (Altervista API)
- Sends JSON payloads to an ingest endpoint at regular intervals.
- No authentication headers. device_id included in payload.
"""

import os
import sys
import time
import json
import math
import argparse
from datetime import datetime, timezone
import random

URL = "https://test0000.altervista.org/api/ingest.php"
DEVICES =["alloro", "cucuzza"]

try:
    import requests
except ImportError as e:
    print("This script requires the 'requests' package. Install with: pip install requests")
    sys.exit(1)

def iso_utc_now():
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

def clamp(v, lo, hi):
    return max(lo, min(hi, v))

def gen_values(tick, device_id, seed=None, cycle=True):
    rng = random.Random(seed + tick if seed is not None else None)

    base_temp = 24.0
    base_hum  = 55.0
    base_soil = 500
    base_lux  = 300.0

    cyc = 0.0
    if cycle:
        cyc = math.sin(2*math.pi * (tick % 1440) / 1440.0)

    temp = base_temp + 4.0*cyc + rng.uniform(-0.6, 0.6)
    hum  = base_hum  - 5.0*cyc + rng.uniform(-2.0, 2.0)
    soil = base_soil - 80.0*cyc + rng.uniform(-25, 25)
    lux  = base_lux  + 500.0*max(0.0, cyc) + rng.uniform(-30, 30)

    temp = clamp(temp, 10.0, 40.0)
    hum  = clamp(hum,  10.0, 95.0)
    soil = int(clamp(soil,  50,  1000))
    lux  = clamp(lux,  0.0,  5000.0)

    return {
        "ts": iso_utc_now(),
        "device_id": device_id,
        "temperatura_c": round(float(temp), 1),
        "umidita_aria":  round(float(hum), 1),
        "umidita_suolo": int(soil),
        "luce_lux":      round(float(lux), 1),
    }

def main():
    while True:
        for i in range [DEVICES]:

            ap = argparse.ArgumentParser(description="ESP32 HTTP Simulator (NO AUTH) for Plantagotchi (Altervista API)")
            ap.add_argument("--url", default=os.getenv("INGEST_URL", URL), help="Ingest endpoint URL")
            ap.add_argument("--device-id", default=os.getenv("DEVICE_ID", DEVICES[i]), help="Device identifier, included in JSON payload")
            ap.add_argument("--interval", type=float, default=float(os.getenv("INTERVAL", "10")), help="Seconds between posts")
            ap.add_argument("--count", type=int, default=int(os.getenv("COUNT", "0")), help="Number of posts (0 = infinite)")
            ap.add_argument("--timeout", type=float, default=float(os.getenv("TIMEOUT", "8")), help="HTTP timeout seconds")
            ap.add_argument("--seed", type=int, default=None, help="Deterministic seed for reproducible values")
            ap.add_argument("--no-cycle", action="store_true", help="Disable diurnal-like cycle")
            args = ap.parse_args()

            headers = {"Content-Type": "application/json"}

            print(f"Starting simulator -> {args.url}")
            print(f"Device: {args.device_id}  Interval: {args.interval}s  Count: {'∞' if args.count==0 else args.count}")
            print("Press Ctrl+C to stop.\n")

            tick = 0
            sent = 0
            try:
                # while True:
                    payload = gen_values(tick, args.device_id, seed=args.seed, cycle=(not args.no_cycle))
                    try:
                        r = requests.post(args.url, headers=headers, data=json.dumps(payload), timeout=args.timeout)
                        ok = (200 <= r.status_code < 300)
                        print(f"[{payload['ts']}] POST {r.status_code}  ({'OK' if ok else 'ERR'})  -> {r.text[:120]}")
                    except Exception as e:
                        print(f"[{payload['ts']}] POST ERROR: {e}")
                    tick += 1
                    sent += 1
                    if args.count and sent >= args.count:
                        break
                    time.sleep(max(0.1, args.interval))
            except KeyboardInterrupt:
                print("\nStopped by user.")

if __name__ == "__main__":
    main()
