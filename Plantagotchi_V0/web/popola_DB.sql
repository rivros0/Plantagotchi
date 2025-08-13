-- Popola 30 giorni (8640 campioni a 5 minuti) per 'Aloe' / 'ESP32-TEST-ALOE'
-- Nessuna CTE: serie numerica 0..8639 generata con cross join di cifre 0..9

INSERT INTO misure_pianta
(ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux, device_id, species, ip_client)
SELECT
  -- Timestamp: 8640*5 minuti nel passato fino ad ora, un punto ogni 5 min
  NOW() - INTERVAL (8640 - n) * 5 MINUTE AS ts,

  -- Temperatura °C: andamento giornaliero + rumore
  ROUND(20 + 5 * SIN((n / 288) * 2 * PI()) + RAND() * 0.5, 1) AS temperatura_c,

  -- Umidità aria %: andamento giornaliero (sfasato) + rumore
  ROUND(50 + 10 * COS((n / 288) * 2 * PI()) + RAND() * 2, 1) AS umidita_aria,

  -- Umidità suolo (ADC): variazione lenta + rumore
  ROUND(500 + 100 * SIN((n / 144) * 2 * PI()) + RAND() * 20) AS umidita_suolo,

  -- Lux: zero di “notte”, picco “diurno” (modello molto semplice)
  ROUND(200 + 800 * GREATEST(SIN(((n % 288) / 288) * PI()), 0) + RAND() * 50, 0) AS luce_lux,

  'ESP32-TEST-ALOE' AS device_id,
  'Aloe'            AS species,
  NULL              AS ip_client
FROM (
  -- Generatore di numeri 0..9999 (noi filtriamo < 8640)
  SELECT
    (u.n + 10*t.n + 100*h.n + 1000*th.n) AS n
  FROM
    (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
           UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
  CROSS JOIN
    (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
           UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t
  CROSS JOIN
    (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
           UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) h
  CROSS JOIN
    (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
           UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) th
) seq
WHERE n < 8640
ORDER BY n;
