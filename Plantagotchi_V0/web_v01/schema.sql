-- Se è un'installazione nuova:
CREATE TABLE IF NOT EXISTS misure_pianta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME NOT NULL,
  temperatura_c FLOAT NULL,
  umidita_aria FLOAT NULL,
  umidita_suolo INT NULL,
  luce_lux FLOAT NULL,
  device_id VARCHAR(40) NOT NULL,
  species VARCHAR(60) NOT NULL,           -- << NUOVO: specie della pianta
  ip_client VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Se la tabella esiste già senza 'species':
-- ALTER TABLE misure_pianta ADD COLUMN species VARCHAR(60) NOT NULL DEFAULT 'Sconosciuta' AFTER device_id;

-- Parametri di riferimento per specie botanica
CREATE TABLE IF NOT EXISTS species_params (
  id INT AUTO_INCREMENT PRIMARY KEY,
  species VARCHAR(60) NOT NULL UNIQUE,      -- Nome specie (chiave logica)
  temp_min FLOAT NULL,
  temp_max FLOAT NULL,
  hum_air_min FLOAT NULL,
  hum_air_max FLOAT NULL,
  soil_min INT NULL,
  soil_max INT NULL,
  lux_min FLOAT NULL,
  lux_max FLOAT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Esempi (personalizzare):
INSERT INTO species_params (species, temp_min, temp_max, hum_air_min, hum_air_max, soil_min, soil_max, lux_min, lux_max) VALUES
('Basilico', 18, 30, 40, 80, 300, 800, 300, 5000),
('Aloe',     12, 28, 30, 60, 200, 700, 200, 5000);
