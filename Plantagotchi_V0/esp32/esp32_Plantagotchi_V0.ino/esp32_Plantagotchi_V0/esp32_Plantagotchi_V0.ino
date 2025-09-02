/*******************************************************
 * Plantagotchi ESP32 - Versione semplificata senza ArduinoJson e #define
 * Sensori: DHT22 (aria), LDR (fotoresistore), YL-69 (suolo)
 * Invio dati via HTTP POST verso API Altervista ogni 60s
 * Stampa letture su Serial ogni 5s, anche senza WiFi
 * Sincronizzazione oraria con fuso orario di Roma (GMT+1 / GMT+2 DST)
 *******************************************************/

#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <time.h>

// === CONFIGURAZIONE === //

// Struct per contenere SSID e password delle reti conosciute
struct WifiCredential {
  const char* ssid;
  const char* password;
};

// Lista di reti conosciute: aggiungi qui altre coppie ssid/password
WifiCredential wifiList[] = {
  {"ninophone", "sapusapunette"},
  {"Alice-38205073-3", "sapusapunette"},
  {"Alice-38205073-2", "sapusapunette"}
};
const int wifiCount = sizeof(wifiList) / sizeof(wifiList[0]);

// URL API per invio dati
const char* apiUrl   = "https://test0000.altervista.org/api/ingest.php";

// Identificativo del dispositivo (max 40 caratteri) e nome pianta (max 60)
const char* deviceId = "ptg-esp32-01";
const char* species  = "Basilico";

// Pin utilizzati per i sensori
const int dhtPin   = 26;
const int ldrPin   = 35;
const int soilPin  = 34;

// Intervalli temporali
const unsigned long logIntervalMs  = 5000;
const unsigned long postIntervalMs = 60000;
unsigned long lastLog = 0;
unsigned long lastPost = 0;



DHT dht(dhtPin, DHT22);

// === CONNESSIONE AL MIGLIOR WIFI DISPONIBILE === //
void connectToBestWiFi() {
  Serial.println("[WiFi] Scansione reti disponibili...");
  int n = WiFi.scanNetworks();

  for (int i = 0; i < wifiCount; i++) {
    for (int j = 0; j < n; j++) {
      if (WiFi.SSID(j) == wifiList[i].ssid) {
        Serial.printf("[WiFi] Trovata rete conosciuta: %s\n", wifiList[i].ssid);
        WiFi.begin(wifiList[i].ssid, wifiList[i].password);

        unsigned long start = millis();
        while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
          delay(500);
          Serial.print(".");
        }
        Serial.println();
        if (WiFi.status() == WL_CONNECTED) {
          Serial.printf("[WiFi] Connesso a: %s\n", wifiList[i].ssid);
          return;
        } else {
          Serial.printf("[WiFi] Timeout connessione a %s\n", wifiList[i].ssid);
        }
      }
    }
  }
  Serial.println("[WiFi] Nessuna rete conosciuta trovata o connessione fallita.");
}

// === CONFIGURA FUSO ORARIO ITALIANO (Roma) CON GESTIONE DST === //
void configureTime() {
  // Europe/Rome usa il fuso orario CET (Central European Time):
  // - CET = UTC+1 durante l'ora solare
  // - CEST = UTC+2 durante l'ora legale
  //
  // La stringa TZ seguente imposta automaticamente il passaggio da ora solare a legale:
  // "CET-1CEST,M3.5.0/2,M10.5.0/3" significa:
  // - CET è UTC+1 (l'offset è -1 perché è espresso come differenza da UTC)
  // - CEST è l'ora legale estiva
  // - M3.5.0/2 = ultimo domenica di marzo alle 02:00 (inizio ora legale)
  // - M10.5.0/3 = ultima domenica di ottobre alle 03:00 (fine ora legale)
  // Europe/Rome: fuso orario UTC+1 con ora legale (DST) attiva
  const char* timeZone = "CET-1CEST,M3.5.0/2,M10.5.0/3";
  setenv("TZ", timeZone, 1);
  tzset();
  configTime(0, 0, "pool.ntp.org", "time.nist.gov");
  Serial.println("[TIME] Sincronizzazione oraria configurata per fuso Roma");
}

void setup() {
  Serial.begin(115200);
  dht.begin();
  connectToBestWiFi();
  configureTime();
  analogReadResolution(12);
  analogSetPinAttenuation(ldrPin,  ADC_11db);
  analogSetPinAttenuation(soilPin, ADC_11db);
}

String getTimestamp() {
  time_t now = time(nullptr);
  if (now <= 0) return "";
  struct tm t;
  localtime_r(&now, &t);  // usa ora locale (CET/CEST)
  char buf[20];
  strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", &t);
  return String(buf);
}

float readTemperature() { return dht.readTemperature(); }
float readHumidity()    { return dht.readHumidity(); }
float readLux() {
  int raw = analogRead(ldrPin);
  return map(raw, 0, 4095, 100, 0);
}
int readSoilPct() {
  int raw = analogRead(soilPin);
  int pct = map(raw, 3200, 1200, 0, 100);
  return constrain(pct, 0, 100);
}

void logData(float t, float h, float lux, int soil, String ts) {
  Serial.printf("[TS ] %s\n", ts.c_str());
  Serial.printf("[AIR] T=%.2f °C, RH=%.1f %%\n", t, h);
  Serial.printf("[LUX] %.1f lx\n", lux);
  Serial.printf("[SOIL] %d %%\n", soil);
}

void postData(float t, float h, float lux, int soil, String ts) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(apiUrl);
  http.addHeader("Content-Type", "application/json");

  String payload = "{";
  payload += "\"temperatura_c\":" + String(t, 2) + ",";
  payload += "\"umidita_aria\":" + String(h, 1) + ",";
  payload += "\"luce_lux\":" + String(lux, 1) + ",";
  payload += "\"umidita_suolo\":" + String(soil) + ",";
  payload += "\"ts\":\"" + ts + "\",";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"species\":\"" + String(species) + "\"";
  payload += "}";

  int code = http.POST(payload);
  Serial.printf("[HTTP] POST code: %d\n", code);
  if (code > 0) Serial.println(http.getString());
  http.end();
}

void loop() {
  unsigned long now = millis();
  float t = readTemperature();
  float h = readHumidity();
  float lux = readLux();
  int soil = readSoilPct();
  String ts = getTimestamp();

  if (now - lastLog >= logIntervalMs) {
    lastLog = now;
    logData(t, h, lux, soil, ts);
  }
  if (now - lastPost >= postIntervalMs) {
    lastPost = now;
    postData(t, h, lux, soil, ts);
  }
}
