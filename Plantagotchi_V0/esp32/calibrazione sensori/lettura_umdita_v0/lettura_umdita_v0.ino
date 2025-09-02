// Lettura LDR su ESP32 dal pin 35 e stampa su seriale
// Baud: 115200, campionamento ~5 Hz

#include <Arduino.h>

constexpr int LDR_PIN      = 35;       // GPIO 35 (ADC1_CHANNEL_7)
constexpr uint32_t BAUD    = 115200;   // Velocità seriale
constexpr uint32_t PERIODO = 200;      // ms tra letture


void setup() {
  Serial.begin(BAUD);
  // Impostazioni ADC dell'ESP32
  analogReadResolution(12);                          // 0..4095
  analogSetPinAttenuation(LDR_PIN, ADC_11db);        // ~0..3.6 V (sicuro per 0..3.3 V)
  // Attendere (facoltativo) l'apertura del monitor seriale
  uint32_t t0 = millis();
  while (!Serial && (millis() - t0 < 2000)) { /*noop*/ }

  Serial.println(F("Avvio lettura LDR su GPIO 35"));
  Serial.println(F("Formato: t_ms, raw, V"));
}

void loop() {
  static uint32_t t_last = 0;
  uint32_t now = millis();
  if (now - t_last >= PERIODO) {
    t_last = now;

    int raw = analogRead(LDR_PIN);                   // 0..4095
    // Stima tensione (rif. 3.3 V). L'ADC reale non è perfettamente lineare.
    float volts = raw * (3.3f / 4095.0f);

    Serial.printf("%lu,\t %d, %d,\t %.3f\n", (unsigned long)now, raw, map(raw, 0, 4095, 100, 0), volts);
    // Serial.printf("\t\t %d", map(raw, 0, 4095, 100, 0));

  }
}
