<?php
/* =========================================================================
   Plantagotchi – Dashboard multi-pianta (layout 2x3)
   - Colonna sx: ultime 24h | Colonna dx: ultimo mese
   - Riga 1: Temperatura + Umidità aria
   - Riga 2: Esposizione solare (lux)
   - Riga 3: Umidità suolo (ADC)
   - Grafici nascosti di default (details chiuso)
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
    .params { display:grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap:8px; margin-top:10px; }
    .param  { border:1px solid var(--b); border-radius:8px; padding:8px; background:#fafafa; }
    details { margin-top:10px; border:1px dashed var(--b); border-radius:8px; padding:10px; background:#fbfbfb; }
    summary { cursor:pointer; font-weight:600; }
    /* Nuovo layout: 2 colonne x 3 righe (6 grafici) */
    .charts2x3 { display:grid; grid-template-columns: 1fr 1fr; grid-auto-rows: auto; gap:16px; margin-top:8px; }
    .chartbox { border:1px solid var(--b); border-radius:8px; padding:8px; }
    .muted { color:#666; font-size:0.9rem; }
    small.dim { display:block; color:#666; margin-top:4px; }
  </style>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h1>Plantagotchi – Pannello multi-pianta</h1>
  <div id="plants"></div>

  <script>
    /* ====================== Utility base ====================== */
    async function jget(url){ try{ const r=await fetch(url); return await r.json(); }catch(e){ console.error("Fetch error:",e,url); return null; } }
    function escId(s){ return String(s||'').replace(/[^a-zA-Z0-9_-]/g,'_'); }
    function fmt(v,unit=""){ if(v===null||v===undefined||isNaN(v))return "—"; const n=+v; const lux=unit.toLowerCase()==="lux"; return (lux?n.toFixed(0):n.toFixed(1))+(unit?(" "+unit):""); }
    function mean(arr,key){ if(!Array.isArray(arr)||!arr.length)return null; let s=0,c=0; for(const x of arr){ const v=+x[key]; if(!isNaN(v)){s+=v;c++;} } return c? s/c : null; }
    function mood(last){ if(!last||!last.ts) return "—"; const msgs=[]; const t=+last.temperatura_c, ha=+last.umidita_aria, s=+last.umidita_suolo, lx=+last.luce_lux; if(!isNaN(s)&&s<300)msgs.push("💧 Ho sete"); if(!isNaN(lx)&&lx<50)msgs.push("🌑 Troppa ombra"); if(!isNaN(t)&&t>30)msgs.push("🔥 Caldo"); return msgs.length?msgs.join(" · "):"😊 Sto bene"; }
    function baseChartOptions(){ return { responsive:true, maintainAspectRatio:false, animation:false, interaction:{mode:'index',intersect:false},
      scales:{ x:{ display:true, grid:{display:true}, ticks:{autoSkip:true,maxTicksLimit:8} }, y:{ grid:{display:true} } }, plugins:{ legend:{display:true} } }; }

    /* ====================== Sezione pianta ====================== */
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

        <!-- Riepilogo ultima misura + medie 24h -->
        <div class="grid">
          <div class="card"><b>Ultima misura</b><div id="lastts_${id}">—</div></div>
          <div class="card"><b>Temperatura</b>
            <div id="lastt_${id}">—</div><small class="dim" id="avg_t_${id}">Media 24h: —</small>
          </div>
          <div class="card"><b>Umidità aria</b>
            <div id="lasth_${id}">—</div><small class="dim" id="avg_h_${id}">Media 24h: —</small>
          </div>
          <div class="card"><b>Umidità suolo</b>
            <div id="lasts_${id}">—</div><small class="dim" id="avg_s_${id}">Media 24h: —</small>
          </div>
          <div class="card"><b>Luce</b>
            <div id="lastl_${id}">—</div><small class="dim" id="avg_l_${id}">Media 24h: —</small>
          </div>
        </div>

        <!-- Parametri specie (solo testuale) -->
        <div class="params" id="params_${id}">
          <div class="param"><b>Parametri specie</b><div class="muted">in caricamento…</div></div>
        </div>

        <!-- Grafici, nascosti di default -->
        <details>
          <summary>Mostra grafici</summary>
          <div class="charts2x3">
            <!-- Riga 1: Temp + Umidità aria -->
            <div class="chartbox">
              <div class="muted"><b>24 h</b> · Temperatura & Umidità aria</div>
              <div style="height:220px"><canvas id="c_tempHum24_${id}"></canvas></div>
            </div>
            <div class="chartbox">
              <div class="muted"><b>30 giorni</b> · Temperatura & Umidità aria</div>
              <div style="height:220px"><canvas id="c_tempHum30_${id}"></canvas></div>
            </div>

            <!-- Riga 2: Lux -->
            <div class="chartbox">
              <div class="muted"><b>24 h</b> · Esposizione solare (lux)</div>
              <div style="height:220px"><canvas id="c_lux24_${id}"></canvas></div>
            </div>
            <div class="chartbox">
              <div class="muted"><b>30 giorni</b> · Esposizione solare (lux)</div>
              <div style="height:220px"><canvas id="c_lux30_${id}"></canvas></div>
            </div>

            <!-- Riga 3: Suolo -->
            <div class="chartbox">
              <div class="muted"><b>24 h</b> · Umidità suolo</div>
              <div style="height:220px"><canvas id="c_soil24_${id}"></canvas></div>
            </div>
            <div class="chartbox">
              <div class="muted"><b>30 giorni</b> · Umidità suolo</div>
              <div style="height:220px"><canvas id="c_soil30_${id}"></canvas></div>
            </div>
          </div>
        </details>
      `;
      return el;
    }

    /* ====================== Disegno grafici ====================== */
    function drawTempHum(canvas, hist){
      const labels = hist.map(r=>r.ts);
      const t  = hist.map(r=>+r.temperatura_c);
      const h  = hist.map(r=>+r.umidita_aria);
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas,{ type:'line',
        data:{ labels, datasets:[ {label:'Temperatura °C', data:t}, {label:'Umidità aria %', data:h} ] },
        options: baseChartOptions()
      });
    }
    function drawLux(canvas, hist){
      const labels = hist.map(r=>r.ts);
      const l = hist.map(r=>+r.luce_lux);
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas,{ type:'line',
        data:{ labels, datasets:[ {label:'Luce (lux)', data:l} ] },
        options: baseChartOptions()
      });
    }
    function drawSoil(canvas, hist){
      const labels = hist.map(r=>r.ts);
      const s = hist.map(r=>+r.umidita_suolo);
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas,{ type:'line',
        data:{ labels, datasets:[ {label:'Umidità suolo (adc)', data:s} ] },
        options: baseChartOptions()
      });
    }

    /* ====================== Hydration per pianta ====================== */
    async function hydratePlant(deviceId, species){
      const id = escId(deviceId);
      const q  = encodeURIComponent(deviceId);

      // Dati principali
      const last = await jget(`/api/latest.php?device_id=${q}`);
      const params = species ? await jget(`/api/species_params.php?species=${encodeURIComponent(species)}`) : null;

      // Serie 24h e 30gg (riusate per i 6 grafici)
      const [hist24, hist30] = await Promise.all([
        jget(`/api/history.php?device_id=${q}&hours=24&limit=5000`),
        jget(`/api/history.php?device_id=${q}&days=30&limit=5000`)
      ]);

      // Riepiloghi e medie 24h
      document.getElementById(`lastts_${id}`).textContent = last?.ts || '—';
      document.getElementById(`lastt_${id}`).textContent  = fmt(+last?.temperatura_c, '°C');
      document.getElementById(`lasth_${id}`).textContent  = fmt(+last?.umidita_aria,  '%');
      document.getElementById(`lasts_${id}`).textContent  = (last?.umidita_suolo ?? '—');
      document.getElementById(`lastl_${id}`).textContent  = fmt(+last?.luce_lux, 'lux');
      document.getElementById(`avg_t_${id}`).textContent  = `Media 24h: ${fmt(mean(hist24,'temperatura_c'),'°C')}`;
      document.getElementById(`avg_h_${id}`).textContent  = `Media 24h: ${fmt(mean(hist24,'umidita_aria'),'%')}`;
      document.getElementById(`avg_s_${id}`).textContent  = `Media 24h: ${Math.round(mean(hist24,'umidita_suolo')||0)}`;
      document.getElementById(`avg_l_${id}`).textContent  = `Media 24h: ${fmt(mean(hist24,'luce_lux'),'lux')}`;
      document.getElementById(`stato_${id}`).textContent  = "Stato: " + mood(last);

      // Parametri specie (solo testo)
      if (params && params.species){
        document.getElementById(`params_${id}`).innerHTML = `
          <div class="param"><b>Specie</b><div>${params.species}</div></div>
          <div class="param"><b>Temperatura (°C)</b><div>Min: ${params.temp_min ?? '—'} · Max: ${params.temp_max ?? '—'}</div></div>
          <div class="param"><b>Umidità aria (%)</b><div>Min: ${params.hum_air_min ?? '—'} · Max: ${params.hum_air_max ?? '—'}</div></div>
          <div class="param"><b>Umidità suolo (adc)</b><div>Min: ${params.soil_min ?? '—'} · Max: ${params.soil_max ?? '—'}</div></div>
          <div class="param"><b>Luce (lux)</b><div>Min: ${params.lux_min ?? '—'} · Max: ${params.lux_max ?? '—'}</div></div>
        `;
      }

      // Disegno dei 6 grafici
      drawTempHum(document.getElementById(`c_tempHum24_${id}`), hist24);
      drawTempHum(document.getElementById(`c_tempHum30_${id}`), hist30);
      drawLux    (document.getElementById(`c_lux24_${id}`),     hist24);
      drawLux    (document.getElementById(`c_lux30_${id}`),     hist30);
      drawSoil   (document.getElementById(`c_soil24_${id}`),    hist24);
      drawSoil   (document.getElementById(`c_soil30_${id}`),    hist30);
    }

    /* ====================== Avvio pagina ====================== */
    (async () => {
      const devs = await jget('/api/devices.php');
      const container = document.getElementById('plants');

      if (!Array.isArray(devs) || devs.length === 0) {
        container.innerHTML = "<div class='muted'>Nessuna pianta trovata nel database.</div>";
        return;
      }

      // Crea sezioni
      for (const row of devs) {
        container.appendChild(plantSection(row.device_id, row.species || ''));
      }
      // Popola dati (una sola volta)
      for (const row of devs) {
        await hydratePlant(row.device_id, row.species || '');
      }
    })();
  </script>
</body>
</html>
