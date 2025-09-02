// Lettura sensore di umidità del suolo su ESP32 (pin 34)
// Stampa su seriale il valore grezzo e la percentuale mappata
// Baud rate: 115200

const int SOIL_PIN = 34;   // Pin analogico collegato al sensore

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("Avvio lettura sensore umidità (pin 34)...");
}

void loop() {
  // Lettura analogica grezza (0–4095 a 12 bit)
  int raw = analogRead(SOIL_PIN);

  // Conversione in percentuale (da tarare secondo il tuo sensore)
  // Nota: invertendo i parametri di map() cambi il verso (secco=0%, bagnato=100%)
  int perc = map(raw, 0, 4095, 0, 100);

  // Stampa dei dati
  Serial.print("Raw: ");
  Serial.print(raw);
  Serial.print("  ->  Umidita': ");
  Serial.print(perc);
  Serial.println("%");

  delay(500); // mezzo secondo tra le letture
}
