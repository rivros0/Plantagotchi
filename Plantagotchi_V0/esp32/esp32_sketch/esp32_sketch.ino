/*
 * Plantagotchi - ESP32 -> HTTP POST (Altervista) - NO AUTH
 * Aggiunta: invia anche 'species' (stringa).
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <Wire.h>
#include <BH1750.h>

// ---- CONFIG ----
const char* WIFI_SSID  = "SSID";
const char* WIFI_PASS  = "PASSWORD";
const char* INGEST_URL = "https://tuoaccount.altervista.org/api/ingest.php";

const char* DEVICE_ID  = "ESP32-AULA-1";
const char* SPECIES    = "Basilico";   // << NUOVO: specie botanica monitorata

#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

#define SOIL_PIN 34
BH1750 lightMeter;

void wifiConnect(){
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status()!=WL_CONNECTED) { delay(500); }
}

String isoTimestamp(){
  time_t now = time(nullptr);
  struct tm *tm_info = gmtime(&now);
  char buf[25];
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%SZ", tm_info);
  return String(buf);
}

void setup(){
  Serial.begin(115200);
  dht.begin();
  Wire.begin();
  lightMeter.begin();
  configTime(0, 0, "pool.ntp.org", "time.nist.gov");
  wifiConnect();
}

void loop(){
  if (WiFi.status() != WL_CONNECTED) wifiConnect();

  float t  = dht.readTemperature();
  float ha = dht.readHumidity();
  int   s  = analogRead(SOIL_PIN);        // calibrare il range del sensore
  float lx = lightMeter.readLightLevel(); // lux

  // JSON payload (include 'device_id' e 'species')
  String payload = "{";
  payload += "\"ts\":\"" + isoTimestamp() + "\",";
  payload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
  payload += "\"species\":\"" + String(SPECIES) + "\",";
  payload += "\"temperatura_c\":" + String(isnan(t)?0:t, 1) + ",";
  payload += "\"umidita_aria\":" + String(isnan(ha)?0:ha, 1) + ",";
  payload += "\"umidita_suolo\":" + String(s) + ",";
  payload += "\"luce_lux\":" + String(isnan(lx)?0:lx, 1);
  payload += "}";

  WiFiClientSecure client;
  client.setInsecure(); // didattico; in produzione caricare il CA

  HTTPClient https;
  if (https.begin(client, INGEST_URL)) {
    https.addHeader("Content-Type", "application/json");
    int code = https.POST(payload);
    String resp = https.getString();
    https.end();
    Serial.printf("POST %d %s\n", code, resp.c_str());
  } else {
    Serial.println("Connessione HTTPS fallita");
  }

  delay(60000); // invio ogni 60s
}
