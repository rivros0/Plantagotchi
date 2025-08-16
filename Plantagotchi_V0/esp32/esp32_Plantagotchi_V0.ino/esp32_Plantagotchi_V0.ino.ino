/*******************************************************
 * Plantagotchi ESP32 → HTTP POST (Altervista)
 * Sensori: DHT22 (aria), LDR (fotoresistore modulo LM393), YL-69 (umidità suolo)
 * Invia JSON via HTTP(S) POST all'endpoint REST del sito.
 *******************************************************/
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

/* ============ CONFIGURAZIONE ============ */
// Identità
#define DEVICE_ID  "ptg-esp32-01"
#define PLANT_ID   "basilico-1"

// Wi‑Fi
const char* WIFI_SSID = "ninophone";
const char* WIFI_PASS = "sapusapunette";

// API Altervista
// Usa l'endpoint effettivo in produzione (qui esempio "ingest.php")
const char* API_URL = "https://test0000.altervista.org/api/ingest.php";
const char* API_KEY = ""; // se non usi auth, lascia vuoto

// Intervalli
const unsigned long MEASURE_INTERVAL_MS = 60UL * 1000UL; // 60 s
const uint8_t       HTTP_MAX_RETRIES    = 3;
const unsigned long HTTP_RETRY_DELAY_MS = 1500;

// Pin
#define PIN_DHT        26      // DHT22 data
#define PIN_LDR_ADC    35      // uscita analogica modulo LDR → GPIO35 (ADC1)
#define PIN_SOIL_ADC   34      // uscita analogica modulo YL‑69 → GPIO34 (ADC1)
#define PIN_LED        2
#define DHTTYPE        DHT22

// Calibrazioni (aggiorna con i tuoi RAW misurati)
int LDR_RAW_DARK   = 300;   // buio
int LDR_RAW_BRIGHT = 3500;  // piena luce
int SOIL_RAW_DRY   = 3200;  // secco
int SOIL_RAW_WET   = 1200;  // bagnato

/* ============ STRUTTURE & GLOBALI ============ */
struct AirData  { float temp_c=NAN, hum_pct=NAN; bool ok=false; };
struct LightData{ int   raw=-1; float pct=NAN;  bool ok=false; };
struct SoilData { int   raw=-1; float pct=NAN;  bool ok=false; };

WiFiUDP ntpUDP;                      // timestamp
NTPClient ntp(ntpUDP, "pool.ntp.org", 0, 3600000);
DHT dht(PIN_DHT, DHTTYPE);
unsigned long lastMeasure = 0;

/* ============ UTILS ============ */
static inline float clampf(float v, float lo, float hi){return v<lo?lo:(v>hi?hi:v);} 
static inline float mapPct(int raw,int lo,int hi,bool asc){
  if(hi==lo) return NAN; 
  float pct = asc ? 100.0f*( (raw-lo)/(float)(hi-lo) )
                  : 100.0f*( (lo-raw)/(float)(lo-hi) );
  return clampf(pct,0.0f,100.0f);
}
static inline void blink(uint8_t n,int onMs=60,int offMs=80){pinMode(PIN_LED,OUTPUT);while(n--){digitalWrite(PIN_LED,HIGH);delay(onMs);digitalWrite(PIN_LED,LOW);delay(offMs);}}

/* ============ Wi‑Fi ============ */
void ensureWiFi(){
  if(WiFi.status()==WL_CONNECTED) return;
  WiFi.mode(WIFI_STA); WiFi.begin(WIFI_SSID,WIFI_PASS);
  unsigned long t0=millis(); Serial.print(F("WiFi:"));
  while(WiFi.status()!=WL_CONNECTED && millis()-t0<15000){Serial.print('.');delay(300);} Serial.println();
  if(WiFi.status()==WL_CONNECTED){Serial.print(F("IP: "));Serial.println(WiFi.localIP()); blink(2);} else {Serial.println(F("Wi‑Fi FAIL"));}
}

/* ============ SENSORI ============ */
AirData  readAir(){ AirData a; a.hum_pct=dht.readHumidity(); a.temp_c=dht.readTemperature(); a.ok=!(isnan(a.hum_pct)||isnan(a.temp_c)); return a; }
LightData readLight(){ LightData l; l.raw=analogRead(PIN_LDR_ADC); l.pct=mapPct(l.raw,LDR_RAW_DARK,LDR_RAW_BRIGHT,true); l.ok=(l.raw>=0)&&!isnan(l.pct); return l; }
SoilData  readSoil(){ SoilData s; s.raw=analogRead(PIN_SOIL_ADC); s.pct=mapPct(s.raw,SOIL_RAW_DRY,SOIL_RAW_WET,false); s.ok=(s.raw>=0)&&!isnan(s.pct); return s; }

/* ============ POST ============ */
bool postTelemetry(const String& json){
  if(WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http; http.setUserAgent(String("Plantagotchi-ESP32/1.0 (")+DEVICE_ID+")"); http.begin(API_URL);
  http.addHeader("Content-Type","application/json");
  if(API_KEY && strlen(API_KEY)>0){ http.addHeader("X-API-KEY",API_KEY); http.addHeader("Authorization",String("Bearer ")+API_KEY);} 
  for(uint8_t att=1; att<=HTTP_MAX_RETRIES; ++att){
    int code = http.POST((uint8_t*)json.c_str(), json.length());
    Serial.printf("[HTTP] POST attempt %u, code=%d
", att, code);
    if(code>0){
      if(code==HTTP_CODE_OK||code==HTTP_CODE_CREATED||code==HTTP_CODE_ACCEPTED){ Serial.println(http.getString()); http.end(); blink(1); return true; }
      else { Serial.println(http.getString()); }
    } else {
      Serial.printf("[HTTP] conn err: %s
", http.errorToString(code).c_str());
    }
    delay(HTTP_RETRY_DELAY_MS);
  }
  http.end(); return false;
}

/* ============ SETUP ============ */
void setup(){
  pinMode(PIN_LED,OUTPUT); digitalWrite(PIN_LED,LOW);
  Serial.begin(115200); delay(100);
  analogReadResolution(12); // 0..4095
  analogSetPinAttenuation(PIN_LDR_ADC, ADC_11db);
  analogSetPinAttenuation(PIN_SOIL_ADC,ADC_11db);
  dht.begin(); ensureWiFi(); ntp.begin(); ntp.update(); blink(3);
}

/* ============ LOOP ============ */
void loop(){
  if(WiFi.status()!=WL_CONNECTED) ensureWiFi();
  unsigned long now=millis();
  if(now-lastMeasure>=MEASURE_INTERVAL_MS){
    lastMeasure=now; ntp.update(); unsigned long epoch=ntp.getEpochTime();

    AirData air=readAir(); LightData lgt=readLight(); SoilData sol=readSoil();

    // --- Serial monitor: riepilogo misure ---
    Serial.printf("[TS] %lu s
", epoch);
    Serial.printf("[AIR] T=%.2f °C, RH=%.1f %% (ok=%d)
", air.temp_c, air.hum_pct, air.ok);
    Serial.printf("[LGT] RAW=%d, PCT=%.1f %% (ok=%d)
", lgt.raw, lgt.pct, lgt.ok);
    Serial.printf("[SOIL] RAW=%d, PCT=%.1f %% (ok=%d)
", sol.raw, sol.pct, sol.ok);

    StaticJsonDocument<384> doc;
    doc["device_id"]=DEVICE_ID; doc["plant_id"]=PLANT_ID; doc["ts"]=epoch;
    JsonObject airJ=doc.createNestedObject("air"); airJ["temp_c"]= air.ok?air.temp_c:nullptr; airJ["hum_pct"]= air.ok?air.hum_pct:nullptr;
    JsonObject lJ=doc.createNestedObject("light"); lJ["raw"]=lgt.raw; lJ["pct"]= lgt.ok?lgt.pct:nullptr;
    JsonObject sJ=doc.createNestedObject("soil");  sJ["raw"]=sol.raw; sJ["pct"]= sol.ok?sol.pct:nullptr;

    char buf[512]; size_t n=serializeJson(doc,buf,sizeof(buf)); String payload(String(buf,n));
    Serial.println(payload); // JSON inviato

    if(!postTelemetry(payload)) Serial.println(F("Invio fallito (retry al prossimo ciclo)"));
  }
  delay(5);
}
/*******************************************************
 * Plantagotchi ESP32 → HTTP POST (Altervista)
 * Sensori: DHT22 (aria), LDR (fotoresistore modulo LM393), YL-69 (umidità suolo)
 * Invia JSON via HTTP(S) POST all'endpoint REST del sito.
 *******************************************************/
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

/* ============ CONFIGURAZIONE ============ */
// Identità
#define DEVICE_ID  "ptg-esp32-01"
#define PLANT_ID   "basilico-1"

// Wi‑Fi
const char* WIFI_SSID = "ninophone";
const char* WIFI_PASS = "sapusapunette";

// API Altervista
// Usa l'endpoint effettivo in produzione (qui esempio "ingest.php")
const char* API_URL = "https://test0000.altervista.org/api/ingest.php";
const char* API_KEY = ""; // se non usi auth, lascia vuoto

// Intervalli
const unsigned long MEASURE_INTERVAL_MS = 60UL * 1000UL; // 60 s
const uint8_t       HTTP_MAX_RETRIES    = 3;
const unsigned long HTTP_RETRY_DELAY_MS = 1500;

// Pin
#define PIN_DHT        26      // DHT22 data
#define PIN_LDR_ADC    35      // uscita analogica modulo LDR → GPIO35 (ADC1)
#define PIN_SOIL_ADC   34      // uscita analogica modulo YL‑69 → GPIO34 (ADC1)
#define PIN_LED        2
#define DHTTYPE        DHT22

// Calibrazioni (aggiorna con i tuoi RAW misurati)
int LDR_RAW_DARK   = 300;   // buio
int LDR_RAW_BRIGHT = 3500;  // piena luce
int SOIL_RAW_DRY   = 3200;  // secco
int SOIL_RAW_WET   = 1200;  // bagnato

/* ============ STRUTTURE & GLOBALI ============ */
struct AirData  { float temp_c=NAN, hum_pct=NAN; bool ok=false; };
struct LightData{ int   raw=-1; float pct=NAN;  bool ok=false; };
struct SoilData { int   raw=-1; float pct=NAN;  bool ok=false; };

WiFiUDP ntpUDP;                      // timestamp
NTPClient ntp(ntpUDP, "pool.ntp.org", 0, 3600000);
DHT dht(PIN_DHT, DHTTYPE);
unsigned long lastMeasure = 0;

/* ============ UTILS ============ */
static inline float clampf(float v, float lo, float hi){return v<lo?lo:(v>hi?hi:v);} 
static inline float mapPct(int raw,int lo,int hi,bool asc){
  if(hi==lo) return NAN; 
  float pct = asc ? 100.0f*( (raw-lo)/(float)(hi-lo) )
                  : 100.0f*( (lo-raw)/(float)(lo-hi) );
  return clampf(pct,0.0f,100.0f);
}
static inline void blink(uint8_t n,int onMs=60,int offMs=80){pinMode(PIN_LED,OUTPUT);while(n--){digitalWrite(PIN_LED,HIGH);delay(onMs);digitalWrite(PIN_LED,LOW);delay(offMs);}}

/* ============ Wi‑Fi ============ */
void ensureWiFi(){
  if(WiFi.status()==WL_CONNECTED) return;
  WiFi.mode(WIFI_STA); WiFi.begin(WIFI_SSID,WIFI_PASS);
  unsigned long t0=millis(); Serial.print(F("WiFi:"));
  while(WiFi.status()!=WL_CONNECTED && millis()-t0<15000){Serial.print('.');delay(300);} Serial.println();
  if(WiFi.status()==WL_CONNECTED){Serial.print(F("IP: "));Serial.println(WiFi.localIP()); blink(2);} else {Serial.println(F("Wi‑Fi FAIL"));}
}

/* ============ SENSORI ============ */
AirData  readAir(){ AirData a; a.hum_pct=dht.readHumidity(); a.temp_c=dht.readTemperature(); a.ok=!(isnan(a.hum_pct)||isnan(a.temp_c)); return a; }
LightData readLight(){ LightData l; l.raw=analogRead(PIN_LDR_ADC); l.pct=mapPct(l.raw,LDR_RAW_DARK,LDR_RAW_BRIGHT,true); l.ok=(l.raw>=0)&&!isnan(l.pct); return l; }
SoilData  readSoil(){ SoilData s; s.raw=analogRead(PIN_SOIL_ADC); s.pct=mapPct(s.raw,SOIL_RAW_DRY,SOIL_RAW_WET,false); s.ok=(s.raw>=0)&&!isnan(s.pct); return s; }

/* ============ POST ============ */
bool postTelemetry(const String& json){
  if(WiFi.status()!=WL_CONNECTED) return false;
  HTTPClient http; http.setUserAgent(String("Plantagotchi-ESP32/1.0 (")+DEVICE_ID+")"); http.begin(API_URL);
  http.addHeader("Content-Type","application/json");
  if(API_KEY && strlen(API_KEY)>0){ http.addHeader("X-API-KEY",API_KEY); http.addHeader("Authorization",String("Bearer ")+API_KEY);} 
  for(uint8_t att=1; att<=HTTP_MAX_RETRIES; ++att){
    int code = http.POST((uint8_t*)json.c_str(), json.length());
    Serial.printf("[HTTP] POST attempt %u, code=%d
", att, code);
    if(code>0){
      if(code==HTTP_CODE_OK||code==HTTP_CODE_CREATED||code==HTTP_CODE_ACCEPTED){ Serial.println(http.getString()); http.end(); blink(1); return true; }
      else { Serial.println(http.getString()); }
    } else {
      Serial.printf("[HTTP] conn err: %s
", http.errorToString(code).c_str());
    }
    delay(HTTP_RETRY_DELAY_MS);
  }
  http.end(); return false;
}

/* ============ SETUP ============ */
void setup(){
  pinMode(PIN_LED,OUTPUT); digitalWrite(PIN_LED,LOW);
  Serial.begin(115200); delay(100);
  analogReadResolution(12); // 0..4095
  analogSetPinAttenuation(PIN_LDR_ADC, ADC_11db);
  analogSetPinAttenuation(PIN_SOIL_ADC,ADC_11db);
  dht.begin(); ensureWiFi(); ntp.begin(); ntp.update(); blink(3);
}

/* ============ LOOP ============ */
void loop(){
  if(WiFi.status()!=WL_CONNECTED) ensureWiFi();
  unsigned long now=millis();
  if(now-lastMeasure>=MEASURE_INTERVAL_MS){
    lastMeasure=now; ntp.update(); unsigned long epoch=ntp.getEpochTime();

    AirData air=readAir(); LightData lgt=readLight(); SoilData sol=readSoil();

    // --- Serial monitor: riepilogo misure ---
    Serial.printf("[TS] %lu s
", epoch);
    Serial.printf("[AIR] T=%.2f °C, RH=%.1f %% (ok=%d)
", air.temp_c, air.hum_pct, air.ok);
    Serial.printf("[LGT] RAW=%d, PCT=%.1f %% (ok=%d)
", lgt.raw, lgt.pct, lgt.ok);
    Serial.printf("[SOIL] RAW=%d, PCT=%.1f %% (ok=%d)
", sol.raw, sol.pct, sol.ok);

    StaticJsonDocument<384> doc;
    doc["device_id"]=DEVICE_ID; doc["plant_id"]=PLANT_ID; doc["ts"]=epoch;
    JsonObject airJ=doc.createNestedObject("air"); airJ["temp_c"]= air.ok?air.temp_c:nullptr; airJ["hum_pct"]= air.ok?air.hum_pct:nullptr;
    JsonObject lJ=doc.createNestedObject("light"); lJ["raw"]=lgt.raw; lJ["pct"]= lgt.ok?lgt.pct:nullptr;
    JsonObject sJ=doc.createNestedObject("soil");  sJ["raw"]=sol.raw; sJ["pct"]= sol.ok?sol.pct:nullptr;

    char buf[512]; size_t n=serializeJson(doc,buf,sizeof(buf)); String payload(String(buf,n));
    Serial.println(payload); // JSON inviato

    if(!postTelemetry(payload)) Serial.println(F("Invio fallito (retry al prossimo ciclo)"));
  }
  delay(5);
}
