<?php
/* ========================================================================
   Plantagotchi – Dashboard multi-pianta (NO AUTH) – versione semplificata
   - NIENTE aggiornamento automatico periodico
   - PHP in stile procedurale (nessuna notazione a oggetti)
   - Per ogni pianta:
       • intestazione con nome e "stato"
       • riquadri con ultima misura
       • sezione "Grafici" (chiusa di default) con 2 grafici:
           (1) Umidità & Lux  (umidità aria %, umidità suolo ADC, luce lux)
           (2) Temperatura & Umidità (temperatura °C, umidità aria %)
   - Dipendenze:
       • Chart.js via CDN
       • Endpoint API lato server già disponibili
   ======================================================================== */
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Plantagotchi – Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Stili minimi, leggibili e senza framework
       Nota: possono essere adattati alle necessità didattiche -->
  <style>
    :root { --b: #e4e4e4; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 20px; }
    h1 { margin-bottom: 14px; }
    .plant { border:1px solid var(--b); border-radius:10px; padding:12px; margin-bottom:14px; }
    .head { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
    .pianta { font-weight:700; }
    .stato  { background:#f6f6f6; border-radius:10px; padding:6px 10px; }
    .grid  { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:8px; margin-top:8px; }
    .card  { border:1px solid var(--b); border-radius:8px; padding:8px; }
    details { margin-top:10px; border:1px dashed var(--b); border-radius:8px; padding:10px; background:#fafafa; }
    summary { cursor:pointer; font-weight:600; }
    .muted { color:#666; font-size:0.9rem; }
    .chartbox { margin-top:10px; }
  </style>

  <!-- Chart.js: libreria grafici (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h1>Plantagotchi – Pannello multi-pianta (demo semplice)</h1>
  <div id="plants"><!-- Le sezioni pianta verranno inserite via JS --></div>

  <p class="muted">
    Nota: questa pagina non si aggiorna in automatico. Per vedere nuovi dati,
    ricaricare la pagina o chiudere/aprire la sezione "Grafici".
  </p>

  <script>
    /* ==============================================================
       Utility JavaScript (semplici e commentate)
       ============================================================== */

    // Richiesta JSON con fetch; se fallisce ritorna null
    async function jget(url){
      try { const r = await fetch(url); return await r.json(); }
      catch(e){ console.error("Errore fetch:", e); return null; }
    }

    // Sanifica una stringa per usarla come parte di un id HTML
    function escId(s){ return String(s || '').replace(/[^a-zA-Z0-9_-]/g, '_'); }

    // Valuta uno "stato" semplice in stile Tamagotchi con regole didattiche
    function computeMood(last){
      if (!last || !last.ts) return "—";
      const msgs = [];
      const soil = Number(last.umidita_suolo);
      const lux  = Number(last.luce_lux);
      const t    = Number(last.temperatura_c);
      if (!isNaN(soil) && soil < 300) msgs.push("💧 Ho sete");
      if (!isNaN(lux)  && lux  < 50)  msgs.push("🌑 Troppa ombra");
      if (!isNaN(t)    && t    > 30)  msgs.push("🔥 Caldo");
      return msgs.length ? msgs.join(" · ") : "😊 Sto bene";
    }

    // Format semplice per numeri (mostra "—" se non valido)
    function fmt(v, unit=""){
      if (v === null || v === undefined || isNaN(v)) return "—";
      const isLux = (unit.toLowerCase() === "lux");
      const n = Number(v);
      return (isLux ? n.toFixed(0) : n.toFixed(1)) + (unit ? " " + unit : "");
    }

    // Opzioni comuni dei grafici: griglia asse X visibile
    function baseChartOptions(){
      return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
          x: {
            display: true,        // mostro etichette X (timestamp)
            grid: { display: true, drawOnChartArea: true },
            ticks: { autoSkip: true, maxTicksLimit: 8 }
          },
          y: { grid: { display: true } }
        }
      };
    }

    // Crea il blocco DOM per UNA pianta (intestazione + carte + grafici)
    function createPlantSection(deviceId){
      const id = escId(deviceId);
      const wrap = document.createElement('section');
      wrap.className = 'plant';
      wrap.id = `plant_${id}`;

      // HTML minimale con commenti didattici
      wrap.innerHTML = `
        <div class="head">
          <!-- Nome pianta -->
          <div class="pianta">Pianta: <b>${deviceId}</b></div>
          <!-- Stato calcolato lato client (verrà riempito dopo la fetch) -->
          <div class="stato" id="stato_${id}">Stato: …</div>
        </div>

        <!-- Ultima misura (valori "istantanei") -->
        <div class="grid">
          <div class="card"><b>Ultima misura</b><div id="lastts_${id}">—</div></div>
          <div class="card"><b>Temperatura</b><div id="lastt_${id}">—</div></div>
          <div class="card"><b>Umidità aria</b><div id="lasth_${id}">—</div></div>
          <div class="card"><b>Umidità suolo</b><div id="lasts_${id}">—</div></div>
          <div class="card"><b>Luce</b><div id="lastl_${id}">—</div></div>
        </div>

        <!-- Grafici: la sezione è CHIUSA di default (details/summary) -->
        <details>
          <summary>Grafici (apri/chiudi)</summary>

          <!-- Grafico 1: Umidità aria / Umidità suolo / Luce -->
          <div class="chartbox">
            <div class="muted"><b>Umidità & Lux</b> — umidità aria (%), umidità suolo (ADC), luce (lux)</div>
            <div style="height:220px">
              <canvas id="chart_env_${id}"></canvas>
            </div>
          </div>

          <!-- Grafico 2: Temperatura / Umidità aria -->
          <div class="chartbox">
            <div class="muted"><b>Temperatura & Umidità</b> — temperatura (°C), umidità aria (%)</div>
            <div style="height:220px">
              <canvas id="chart_climate_${id}"></canvas>
            </div>
          </div>
        </details>
      `;
      return wrap;
    }

    // Riempie i riquadri "Ultima misura" e lo stato
    function fillLast(deviceId, last){
      const id = escId(deviceId);
      document.getElementById(`stato_${id}`).textContent  = "Stato: " + computeMood(last);
      document.getElementById(`lastts_${id}`).textContent = last && last.ts ? last.ts : "—";
      document.getElementById(`lastt_${id}`).textContent  = fmt(Number(last?.temperatura_c), "°C");
      document.getElementById(`lasth_${id}`).textContent  = fmt(Number(last?.umidita_aria), "%");
      document.getElementById(`lasts_${id}`).textContent  = (last?.umidita_suolo ?? "—");
      document.getElementById(`lastl_${id}`).textContent  = fmt(Number(last?.luce_lux), "lux");
    }

    // Disegna grafico 1 (Umidità & Lux) con griglia X visibile
    function drawChartEnv(deviceId, hist){
      const id  = escId(deviceId);
      const el  = document.getElementById(`chart_env_${id}`);
      if (!el) return;
      const lbl = hist.map(r => r.ts);
      const a   = hist.map(r => Number(r.umidita_aria));
      const s   = hist.map(r => Number(r.umidita_suolo));
      const l   = hist.map(r => Number(r.luce_lux));
      // Distrugge l’eventuale grafico precedente (se si ricarica dinamicamente)
      if (el._chart) el._chart.destroy();
      el._chart = new Chart(el, {
        type: 'line',
        data: {
          labels: lbl,
          datasets: [
            { label: 'Umidità aria %',   data: a },
            { label: 'Umidità suolo (adc)', data: s },
            { label: 'Luce (lux)',       data: l }
          ]
        },
        options: baseChartOptions()
      });
    }

    // Disegna grafico 2 (Temperatura & Umidità aria) con griglia X visibile
    function drawChartClimate(deviceId, hist){
      const id  = escId(deviceId);
      const el  = document.getElementById(`chart_climate_${id}`);
      if (!el) return;
      const lbl = hist.map(r => r.ts);
      const t   = hist.map(r => Number(r.temperatura_c));
      const a   = hist.map(r => Number(r.umidita_aria));
      if (el._chart) el._chart.destroy();
      el._chart = new Chart(el, {
        type: 'line',
        data: {
          labels: lbl,
          datasets: [
            { label: 'Temperatura °C', data: t },
            { label: 'Umidità aria %', data: a }
          ]
        },
        options: baseChartOptions()
      });
    }

    // Carica i dati per UNA pianta e popola la sezione
    async function hydratePlant(deviceId){
      const q = encodeURIComponent(deviceId);
      const last = await jget(`../api/latest.php?device_id=${q}`);   // ultimo record
      const hist = await jget(`../api/history.php?device_id=${q}&limit=144`); // storico (ultimi N)
      fillLast(deviceId, last && last.ts ? last : null);
      const series = Array.isArray(hist) ? hist : [];
      drawChartEnv(deviceId, series);
      drawChartClimate(deviceId, series);
    }

    // Avvio: elenca i device e costruisce la pagina una tantum (Niente auto-refresh)
    (async () => {
      const container = document.getElementById('plants');

      // 1) Richiede l’elenco delle piante (device_id distinti)
      const devs = await jget('../api/devices.php');

      if (!Array.isArray(devs) || devs.length === 0) {
        container.innerHTML = "<div class='muted'>Nessuna pianta presente nel database.</div>";
        return;
      }

      // 2) Crea il DOM per ciascuna pianta
      for (const row of devs) {
        const deviceId = row.device_id;
        container.appendChild(createPlantSection(deviceId));
      }

      // 3) Popola dati e disegna grafici (una sola volta)
      for (const row of devs) {
        await hydratePlant(row.device_id);
      }
    })();
  </script>
</body>
</html>
