<?php
/* =========================================================================
   Plantagotchi – Dashboard multi-pianta (NO AUTH)
   Layout grafici 2 colonne × 2 righe per ogni pianta:
     [1,1] Temp + Umidità aria + Lux (ultime 24h)
     [1,2] Temp + Umidità aria + Lux (ultimo mese)
     [2,1] Umidità suolo (ultime 24h)
     [2,2] Umidità suolo (ultimo mese)
   Nessun auto-refresh. Logica client-side con fetch verso API interne.
   ========================================================================= */
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Plantagotchi – Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    :root { --b:#e4e4e4; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin:20px; }
    h1 { margin-bottom: 14px; }
    .plant { border:1px solid var(--b); border-radius:10px; padding:12px; margin-bottom:16px; }
    .head { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
    .pianta { font-weight:700; }
    .stato  { background:#f6f6f6; border-radius:10px; padding:6px 10px; }
    .grid  { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:8px; margin-top:8px; }
    .card  { border:1px solid var(--b); border-radius:8px; padding:8px; }
    details { margin-top:10px; border:1px dashed var(--b); border-radius:8px; padding:10px; background:#fafafa; }
    summary { cursor:pointer; font-weight:600; }
    .charts2x2 { display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:10px; }
    .chartbox { border:1px solid var(--b); border-radius:8px; padding:8px; }
    .muted { color:#666; font-size:0.9rem; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h1>Plantagotchi – Pannello multi-pianta</h1>
  <div id="plants"></div>

  <p class="muted">Suggerimento: apri “Grafici” per analizzare l’andamento (nessun aggiornamento automatico).</p>

  <script>
    /* -------------------- Utility -------------------- */
    async function jget(u){ try{ const r=await fetch(u); return await r.json(); } catch(e){ console.error(e); return null; } }
    function escId(s){ return String(s||'').replace(/[^a-zA-Z0-9_-]/g,'_'); }
    function fmt(v,unit=""){ if(v===null||v===undefined||isNaN(v))return "—"; const n=+v; return (unit==='lux'?n.toFixed(0):n.toFixed(1))+(unit?(' '+unit):''); }

    // Stato semplice in base alle ultime letture E (se disponibili) ai parametri della specie
    function mood(last, params){
      if (!last || !last.ts) return "—";
      const msgs = [];
      const t = +last.temperatura_c, ha = +last.umidita_aria, s = +last.umidita_suolo, lx = +last.luce_lux;
      // Soglie "grezze"
      if (!isNaN(s) && s < 300) msgs.push("💧 Ho sete");
      if (!isNaN(lx) && lx < 50) msgs.push("🌑 Troppa ombra");
      if (!isNaN(t) && t > 30) msgs.push("🔥 Caldo");
      // Soglie specie (se presenti)
      if (params && params.species){
        if (!isNaN(t) && params.temp_min!==null && t < +params.temp_min) msgs.push("❄️ Troppo freddo");
        if (!isNaN(t) && params.temp_max!==null && t > +params.temp_max) msgs.push("🔥 Troppo caldo");
        if (!isNaN(ha) && params.hum_air_min!==null && ha < +params.hum_air_min) msgs.push("💨 Aria secca");
        if (!isNaN(ha) && params.hum_air_max!==null && ha > +params.hum_air_max) msgs.push("💦 Aria troppo umida");
        if (!isNaN(s) && params.soil_min!==null && s < +params.soil_min) msgs.push("🌱 Suolo troppo secco");
        if (!isNaN(s) && params.soil_max!==null && s > +params.soil_max) msgs.push("🪵 Suolo troppo umido");
        if (!isNaN(lx) && params.lux_min!==null  && lx < +params.lux_min)  msgs.push("🌑 Poca luce");
        if (!isNaN(lx) && params.lux_max!==null  && lx > +params.lux_max)  msgs.push("☀️ Troppa luce");
      }
      return msgs.length ? msgs.join(" · ") : "😊 Sto bene";
    }

    function baseChartOptions(){
      return {
        responsive:true, maintainAspectRatio:false, animation:false,
        interaction:{mode:'index',intersect:false},
        scales:{
          x:{ display:true, grid:{display:true, drawOnChartArea:true}, ticks:{autoSkip:true, maxTicksLimit:8} },
          y:{ grid:{display:true} }
        }
      };
    }

    /* -------------------- DOM per pianta -------------------- */
    function plantSection(deviceId, species){
      const id = escId(deviceId);
      const el = document.createElement('section');
      el.className = 'plant';
      el.id = `plant_${id}`;
      el.innerHTML = `
        <div class="head">
          <div class="pianta">Pianta: <b>${deviceId}</b> <span class="muted">(${species || 'Specie sconosciuta'})</span></div>
          <div class="stato" id="stato_${id}">Stato: …</div>
        </div>

        <div class="grid">
          <div class="card"><b>Ultima misura</b><div id="lastts_${id}">—</div></div>
          <div class="card"><b>Temperatura</b><div id="lastt_${id}">—</div></div>
          <div class="card"><b>Umidità aria</b><div id="lasth_${id}">—</div></div>
          <div class="card"><b>Umidità suolo</b><div id="lasts_${id}">—</div></div>
          <div class="card"><b>Luce</b><div id="lastl_${id}">—</div></div>
        </div>

        <details>
          <summary>Grafici (apri/chiudi)</summary>
          <div class="charts2x2">
            <!-- [1,1] Temp + Umidità aria + Lux (24h) -->
            <div class="chartbox">
              <div class="muted"><b>24 h</b> · Temperatura, Umidità aria, Lux</div>
              <div style="height:220px"><canvas id="c_env24_${id}"></canvas></div>
            </div>
            <!-- [1,2] Temp + Umidità aria + Lux (30gg) -->
            <div class="chartbox">
              <div class="muted"><b>30 giorni</b> · Temperatura, Umidità aria, Lux</div>
              <div style="height:220px"><canvas id="c_env30_${id}"></canvas></div>
            </div>
            <!-- [2,1] Suolo (24h) -->
            <div class="chartbox">
              <div class="muted"><b>24 h</b> · Umidità suolo</div>
              <div style="height:220px"><canvas id="c_soil24_${id}"></canvas></div>
            </div>
            <!-- [2,2] Suolo (30gg) -->
            <div class="chartbox">
              <div class="muted"><b>30 giorni</b> · Umidità suolo</div>
              <div style="height:220px"><canvas id="c_soil30_${id}"></canvas></div>
            </div>
          </div>
        </details>
      `;
      return el;
    }

    /* -------------------- Rendering grafici -------------------- */
    function drawEnv(canvas, hist){
      const labels = hist.map(r=>r.ts);
      const t  = hist.map(r=>+r.temperatura_c);
      const ha = hist.map(r=>+r.umidita_aria);
      const lx = hist.map(r=>+r.luce_lux);
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas,{
        type:'line',
        data:{ labels, datasets:[
          {label:'Temperatura °C', data:t},
          {label:'Umidità aria %', data:ha},
          {label:'Luce (lux)', data:lx},
        ]},
        options: baseChartOptions()
      });
    }
    function drawSoil(canvas, hist){
      const labels = hist.map(r=>r.ts);
      const s = hist.map(r=>+r.umidita_suolo);
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas,{
        type:'line',
        data:{ labels, datasets:[ {label:'Umidità suolo (adc)', data:s} ] },
        options: baseChartOptions()
      });
    }

    /* -------------------- Hydrate singola pianta -------------------- */
    async function hydratePlant(deviceId, species){
      const id = escId(deviceId);
      const q  = encodeURIComponent(deviceId);

      // Ultima lettura
      const last = await jget(`../api/latest.php?device_id=${q}`);

      // Parametri specie (se presenti)
      let params = null;
      if (species) params = await jget(`../api/species_params.php?species=${encodeURIComponent(species)}`);

      // Riempi riepilogo
      document.getElementById(`lastts_${id}`).textContent = last?.ts || '—';
      document.getElementById(`lastt_${id}`).textContent  = fmt(+last?.temperatura_c, '°C');
      document.getElementById(`lasth_${id}`).textContent  = fmt(+last?.umidita_aria, '%');
      document.getElementById(`lasts_${id}`).textContent  = (last?.umidita_suolo ?? '—');
      document.getElementById(`lastl_${id}`).textContent  = fmt(+last?.luce_lux, 'lux');
      document.getElementById(`stato_${id}`).textContent  = "Stato: " + mood(last, params);

      // Storici 24h e 30gg
      const [env24, env30, soil24, soil30] = await Promise.all([
        jget(`../api/history.php?device_id=${q}&hours=24&limit=2000`),
        jget(`../api/history.php?device_id=${q}&days=30&limit=5000`),
        jget(`../api/history.php?device_id=${q}&hours=24&limit=2000`),
        jget(`../api/history.php?device_id=${q}&days=30&limit=5000`),
      ]);

      // Disegna 4 grafici
      const c_env24  = document.getElementById(`c_env24_${id}`);
      const c_env30  = document.getElementById(`c_env30_${id}`);
      const c_soil24 = document.getElementById(`c_soil24_${id}`);
      const c_soil30 = document.getElementById(`c_soil30_${id}`);

      drawEnv(c_env24,  Array.isArray(env24)  ? env24  : []);
      drawEnv(c_env30,  Array.isArray(env30)  ? env30  : []);
      drawSoil(c_soil24,Array.isArray(soil24) ? soil24 : []);
      drawSoil(c_soil30,Array.isArray(soil30) ? soil30 : []);
    }

    /* -------------------- Avvio pagina -------------------- */
    (async () => {
      // Elenco piante (device + specie dall'ultima lettura)
      const devs = await jget('../api/devices.php');
      const container = document.getElementById('plants');

      if (!Array.isArray(devs) || devs.length === 0) {
        container.innerHTML = "<div class='muted'>Nessuna pianta trovata nel database.</div>";
        return;
      }

      // Crea sezioni
      for (const row of devs) {
        const device = row.device_id;
        const species = row.species || '';
        container.appendChild(plantSection(device, species));
      }

      // Popola dati e grafici (una sola volta)
      for (const row of devs) {
        await hydratePlant(row.device_id, row.species || '');
      }
    })();
  </script>
</body>
</html>
