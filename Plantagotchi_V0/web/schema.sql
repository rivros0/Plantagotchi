
-- Plantagotchi - Schema MySQL per Altervista (NO AUTH)
CREATE TABLE IF NOT EXISTS misure_pianta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME NOT NULL,
  temperatura_c FLOAT NULL,
  umidita_aria FLOAT NULL,
  umidita_suolo INT NULL,
  luce_lux FLOAT NULL,
  device_id VARCHAR(40) NOT NULL,
  ip_client VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
