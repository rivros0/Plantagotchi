/*******************************************************
 * Plantagotchi ESP32 → HTTP POST (Altervista)
 * Sensori: DHT22 (aria), LDR (fotoresistore), YL-69 (suolo)
 * - Log Serial ogni 5s (anche senza Wi‑Fi)
 * - POST verso API ogni 60s con payload coerente con PHP
 *   Campi richiesti: temperatura_c, umidita_aria, umidita_suolo, luce_lux,
 *                    ts, device_id, species
 *******************************************************/
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>
#include <time.h>
#include <math.h>

/* ================== CONFIG ================== */
#define DEVICE_ID "ptg-esp32-01"
#define SPECIES   "Basilico"   // obbligatorio per l'API

const char* WIFI_SSID = "ninophone";
const char* WIFI_PASS = "sapusapunette";
const char* API_URL   = "http://test0000.altervista.org/api/ingest.php";
const char* API_KEY   = "";   // opzionale

// Intervalli
const unsigned long LOG_INTERVAL_MS  = 5000;   // stampa seriale
const unsigned long POST_INTERVAL_MS = 60000;  // POST verso API
const uint8_t  HTTP_MAX_RETRIES      = 3;
const unsigned long HTTP_RETRY_DELAY_MS = 1500;

// Pin sensori
#define PIN_DHT      26
#define PIN_LDR_ADC  35
#define PIN_SOIL_ADC 34
#define PIN_LED      2
#define DHTTYPE      DHT22

// Calibrazioni
int   LDR_RAW_DARK    = 300;       // RAW in buio
int   LDR_RAW_BRIGHT  = 3500;      // RAW in piena luce
float LUX_DARK        = 1.0f;      // lux approssimati al buio
float LUX_BRIGHT      = 10000.0f;  // lux in piena luce (regolabile)

int SOIL_RAW_DRY = 3200;  // suolo secco (RAW)
int SOIL_RAW_WET = 1200;  // suolo bagnato (RAW)

/* ================== GLOBALI ================== */
struct AirData  { float t=NAN, h=NAN; bool ok=false; };
struct LightData{ int raw=-1; float lux=NAN; bool ok=false; };
struct SoilData { int raw=-1; int pct=0;   bool ok=false; };

DHT dht(PIN_DHT, DHTTYPE);
unsigned long lastLog=0, lastPost=0;

/* ================== UTILS ================== */
static inline float clampf(float v,float lo,float hi){ return v<lo?lo:(v>hi?hi:v); }
static inline float fmap(float x,float in_min,float in_max,float out_min,float out_max){
  if(in_max==in_min) return NAN; return (x-in_min)*(out_max-out_min)/(in_max-in_min)+out_min;
}

float ldrRawToLux(int raw){
  float lux = fmap((float)raw,(float)LDR_RAW_DARK,(float)LDR_RAW_BRIGHT,LUX_DARK,LUX_BRIGHT);
  return lux<0?0:lux;
}

int soilRawToPct(int raw){
  float pct = 100.0f * ((float)(SOIL_RAW_DRY - raw) / (float)(SOIL_RAW_DRY - SOIL_RAW_WET));
  pct = clampf(pct,0.0f,100.0f); return (int)lroundf(pct);
}

void blink(int n){ pinMode(PIN_LED,OUTPUT); while(n--){digitalWrite(PIN_LED,HIGH);delay(60);digitalWrite(PIN_LED,LOW);delay(80);} }

/* ================== RETE & TEMPO ================== */
void ensureWiFi(){
  if(WiFi.status()==WL_CONNECTED) return;
  WiFi.mode(WIFI_STA); WiFi.begin(WIFI_SSID,WIFI_PASS);
  unsigned long t0=millis();
  while(WiFi.status()!=WL_CONNECTED && millis()-t0<15000){ delay(300); }
}

String nowTimestamp(){
  time_t now = time(nullptr);
  if(now<=0) return String("");
  struct tm tm_now; gmtime_r(&now, &tm_now);
  char buf[20];
  strftime(buf,sizeof(buf),"%Y-%m-%d %H:%M:%S", &tm_now);
  return String(buf);
}

/* ================== SENSORI ================== */
AirData  readAir(){ AirData a; a.h=dht.readHumidity(); a.t=dht.readTemperature(); a.ok=!isnan(a.h)&&!isnan(a.t); return a; }
LightData readLight(){ LightData l; l.raw=analogRead(PIN_LDR_ADC); l.lux=ldrRawToLux(l.raw); l.ok=l.raw>=0; return l; }
SoilData  readSoil(){ SoilData s; s.raw=analogRead(PIN_SOIL_ADC); s.pct=soilRawToPct(s.raw); s.ok=s.raw>=0; return s; }

/* ================== POST ================== */
bool postTelemetry(float temperatura_c, float umidita_aria, int umidita_suolo, float luce_lux, const String& ts_str){
  if(WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http; http.begin(API_URL);
  http.addHeader("Content-Type","application/json");
  if(API_KEY && strlen(API_KEY)>0){ http.addHeader("X-API-KEY",API_KEY); }

  StaticJsonDocument<256> doc;
  doc["temperatura_c"] = temperatura_c;
  doc["umidita_aria"]  = umidita_aria;
  doc["umidita_suolo"] = umidita_suolo;
  doc["luce_lux"]      = luce_lux;
  doc["ts"]            = ts_str;
  doc["device_id"]     = DEVICE_ID;
  doc["species"]       = SPECIES;

  String payload; serializeJson(doc,payload);
  for(uint8_t i=0;i<HTTP_MAX_RETRIES;i++){
    int code = http.POST(payload);
    Serial.printf("[HTTP] POST attempt %u, code=%d\n", i+1, code);
    if(code==200||code==201){ Serial.println(http.getString()); http.end(); return true; }
    delay(HTTP_RETRY_DELAY_MS);
  }
  http.end(); return false;
}

/* ================== SETUP/LOOP ================== */
void setup(){
  Serial.begin(115200);
  pinMode(PIN_LED,OUTPUT); digitalWrite(PIN_LED,LOW);
  analogReadResolution(12);
  analogSetPinAttenuation(PIN_LDR_ADC,  ADC_11db);
  analogSetPinAttenuation(PIN_SOIL_ADC, ADC_11db);
  dht.begin();

  ensureWiFi();
  configTime(0, 0, "pool.ntp.org", "time.nist.gov");
  blink(2);
}

void loop(){
  if(WiFi.status()!=WL_CONNECTED) ensureWiFi();
  unsigned long nowMs = millis();

  AirData air = readAir();
  LightData lgt = readLight();
  SoilData sol = readSoil();

  if(nowMs - lastLog >= LOG_INTERVAL_MS){
    lastLog = nowMs;
    String ts = nowTimestamp();
    Serial.printf("[TS ] %s\n", ts.length()? ts.c_str():"(no-ntp)");
    Serial.printf("[AIR] T=%.2f °C  RH=%.1f %%  ok=%d\n", air.t, air.h, air.ok);
    Serial.printf("[LGT] RAW=%d  LUX=%.1f\n", lgt.raw, lgt.lux);
    Serial.printf("[SOL] RAW=%d  PCT=%d %%\n", sol.raw, sol.pct);
  }

  if(nowMs - lastPost >= POST_INTERVAL_MS){
    lastPost = nowMs;
    String ts = nowTimestamp();
    bool ok = postTelemetry(
      air.ok?air.t:NAN,
      air.ok?air.h:NAN,
      sol.pct,
      lgt.lux,
      ts
    );
    if(!ok) Serial.println(F("[HTTP] Invio fallito (ritento al prossimo ciclo)"));
  }
}
