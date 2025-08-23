/*******************************************************
 * Plantagotchi ESP32
 * Sensori: DHT22 (aria), LDR (fotoresistore), YL-69 (suolo)
 * Invio dati via HTTP POST verso API Altervista ogni 60s
 * Stampa letture su Serial ogni 5s, anche senza WiFi
 *******************************************************/

#include <WiFi.h>         // Libreria per gestione WiFi
#include <HTTPClient.h>   // Libreria per richieste HTTP
#include <DHT.h>          // Libreria per sensore temperatura/umidità DHT22
#include <time.h>         // Libreria per sincronizzazione oraria via NTP

// === CONFIGURAZIONE === //

// Dati WiFi
const char* wifiSsid = "ninophone";
const char* wifiPass = "sapusapunette";

// URL API per invio dati
const char* apiUrl   = "https://test0000.altervista.org/api/ingest.php";

// Identificativo del dispositivo (max 40 caratteri) e nome pianta (max 60)
const char* deviceId = "ptg-esp32-01";
const char* species  = "Basilico";  // es. basilico

// Pin utilizzati per i sensori
const int dhtPin   = 26;  // Temperatura e umidità dell'aria
const int ldrPin   = 35;  // Sensore di luce (fotoresistore)
const int soilPin  = 34;  // Sensore umidità suolo YL-69

// Intervalli temporali per operazioni periodiche
const unsigned long logIntervalMs  = 5000;   // Ogni 5 secondi: stampa seriale
const unsigned long postIntervalMs = 60000;  // Ogni 60 secondi: invio POST

// Variabili di stato per calcolare gli intervalli
unsigned long lastLog = 0;
unsigned long lastPost = 0;

// Inizializzazione oggetto DHT per sensore aria
DHT dht(dhtPin, DHT22);

// === SETUP INIZIALE === //
void setup() {
  Serial.begin(115200);      // Comunicazione seriale per debug
  dht.begin();               // Avvia sensore DHT22

  WiFi.begin(wifiSsid, wifiPass);   // Connessione alla rete WiFi

  // Sincronizzazione oraria via server NTP (UTC)
  configTime(0, 0, "pool.ntp.org", "time.nist.gov");

  // Configura ADC per letture analogiche precise (0-4095)
  analogReadResolution(12);
  analogSetPinAttenuation(ldrPin,  ADC_11db);  // massima attenuazione
  analogSetPinAttenuation(soilPin, ADC_11db);
}

// === OTTIENI TIMESTAMP UTC CORRENTE === //
String getTimestamp() {
  time_t now = time(nullptr);
  if (now <= 0) return "";  // ritorna stringa vuota se orario non disponibile
  struct tm t;
  gmtime_r(&now, &t);       // converte orario corrente in formato UTC
  char buf[20];
  strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", &t); // formato ISO 8601
  return String(buf);
}

// === FUNZIONI DI LETTURA SENSORI === //

// Temperatura ambiente in gradi Celsius
float readTemperature() {
  return dht.readTemperature();
}

// Umidità relativa dell'aria in percentuale
float readHumidity() {
  return dht.readHumidity();
}

// Intensità luminosa in lux (stima approssimativa)
float readLux() {
  int raw = analogRead(ldrPin);                   // valore ADC grezzo
  return map(raw, 300, 3500, 1, 10000);           // mappa valore su scala 1-10000 lux
}

// Umidità del suolo in percentuale 0–100%
int readSoilPct() {
  int raw = analogRead(soilPin);                 // valore ADC grezzo
  int pct = map(raw, 3200, 1200, 0, 100);        // calibrazione secco-bagnato
  if (pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  return pct;
}

// === STAMPA DATI SU MONITOR SERIALE === //
void logData(float t, float h, float lux, int soil, String ts) {
  Serial.printf("[TS ] %s\n", ts.c_str());
  Serial.printf("[AIR] T=%.2f °C, RH=%.1f %%\n", t, h);
  Serial.printf("[LUX] %.1f lx\n", lux);
  Serial.printf("[SOIL] %d %%\n", soil);
}

// === INVIA I DATI VIA HTTP POST ALL'API PHP === //
void postData(float t, float h, float lux, int soil, String ts) {
  if (WiFi.status() != WL_CONNECTED) return;  // se non connesso, esce

  HTTPClient http;
  http.begin(apiUrl);  // URL dell'endpoint PHP
  http.addHeader("Content-Type", "application/json");

  // Costruzione manuale del JSON da inviare
  String payload = "{";
  payload += "\"temperatura_c\":" + String(t, 2) + ",";
  payload += "\"umidita_aria\":" + String(h, 1) + ",";
  payload += "\"luce_lux\":" + String(lux, 1) + ",";
  payload += "\"umidita_suolo\":" + String(soil) + ",";
  payload += "\"ts\":\"" + ts + "\",";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"species\":\"" + String(species) + "\"";
  payload += "}";

  // Invio del payload e risposta
  int code = http.POST(payload);
  Serial.printf("[HTTP] POST code: %d\n", code);
  if (code > 0) Serial.println(http.getString());
  http.end();
}

// === CICLO PRINCIPALE DI ACQUISIZIONE E INVIO === //
void loop() {
  unsigned long now = millis();

  // Lettura valori correnti dai sensori
  float t = readTemperature();
  float h = readHumidity();
  float lux = readLux();
  int soil = readSoilPct();
  String ts = getTimestamp();

  // Log seriale ogni 5 secondi
  if (now - lastLog >= logIntervalMs) {
    lastLog = now;
    logData(t, h, lux, soil, ts);
  }

  // Invio dati ogni 60 secondi
  if (now - lastPost >= postIntervalMs) {
    lastPost = now;
    postData(t, h, lux, soil, ts);
  }
} // end loop
