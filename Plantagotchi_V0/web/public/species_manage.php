<?php
require_once __DIR__ . '/../api/config.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Gestione specie</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: sans-serif; margin: 2em; background: #f8f8f8; }
    h1 { margin-bottom: 0.5em; }
    #toolbar button { margin-right: 1em; padding: 0.5em 1em; }
    .hidden { display: none; }
    form label { display: block; margin-top: 0.5em; }
    form input { width: 10em; }
    table { border-collapse: collapse; margin-top: 2em; width: 100%; }
    th, td { padding: 0.5em; border: 1px solid #ccc; text-align: center; }
    select { margin-top: 1em; }
  </style>
</head>
<body>

<h1>Gestione specie</h1>

<div id="toolbar">
  <button onclick="showForm('add')">Nuova specie</button>
  <button onclick="showSelector()">Modifica</button>
  <button onclick="deleteSpecie()">Elimina</button>
</div>

<!-- Selettore specie per modifica -->
<div id="speciesSelector" class="hidden">
  <label for="speciesSelect">Seleziona specie:</label>
  <select id="speciesSelect" onchange="loadSpecie(this.value)">
    <option value="">-- Seleziona --</option>
  </select>
</div>

<!-- Form inserimento/modifica -->
<form id="specieForm" class="hidden" onsubmit="submitForm(event)">
  <input type="hidden" id="formMode" value="add">
  <label>Specie: <input type="text" id="species" required></label>
  <label>Temp min: <input type="number" id="temp_min" step="0.1"></label>
  <label>Temp max: <input type="number" id="temp_max" step="0.1"></label>
  <label>Umidità aria min: <input type="number" id="hum_air_min" step="0.1"></label>
  <label>Umidità aria max: <input type="number" id="hum_air_max" step="0.1"></label>
  <label>Suolo min: <input type="number" id="soil_min" step="1"></label>
  <label>Suolo max: <input type="number" id="soil_max" step="1"></label>
  <label>Luce min: <input type="number" id="lux_min" step="1"></label>
  <label>Luce max: <input type="number" id="lux_max" step="1"></label>
  <br><br>
  <button type="submit">Salva</button>
</form>

<!-- Elenco specie -->
<h2>Specie presenti nel database</h2>
<table id="speciesTable">
  <thead>
    <tr>
      <th>Specie</th><th>Tmin</th><th>Tmax</th><th>Hmin</th><th>Hmax</th>
      <th>Smin</th><th>Smax</th><th>Lmin</th><th>Lmax</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<script>
let allSpecies = [];

function fetchSpecies() {
  fetch('/api/species_params.php')
    .then(res => res.json())
    .then(data => {
      allSpecies = data;
      updateSpeciesTable();
      updateSpeciesSelect();
    });
}

function updateSpeciesTable() {
  const tbody = document.querySelector("#speciesTable tbody");
  tbody.innerHTML = "";
  allSpecies.forEach(s => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${s.species}</td><td>${s.temp_min ?? ''}</td><td>${s.temp_max ?? ''}</td>
      <td>${s.hum_air_min ?? ''}</td><td>${s.hum_air_max ?? ''}</td>
      <td>${s.soil_min ?? ''}</td><td>${s.soil_max ?? ''}</td>
      <td>${s.lux_min ?? ''}</td><td>${s.lux_max ?? ''}</td>
    `;
    tbody.appendChild(tr);
  });
}

function updateSpeciesSelect() {
  const sel = document.getElementById("speciesSelect");
  sel.innerHTML = '<option value="">-- Seleziona --</option>';
  allSpecies.forEach(s => {
    const opt = document.createElement("option");
    opt.value = s.species;
    opt.textContent = s.species;
    sel.appendChild(opt);
  });
}

function showForm(mode) {
  document.getElementById("formMode").value = mode;
  document.getElementById("species").readOnly = (mode === "edit");
  document.getElementById("speciesSelector").classList.add("hidden");
  document.getElementById("specieForm").reset();
  document.getElementById("specieForm").classList.remove("hidden");
}

function showSelector() {
  document.getElementById("speciesSelector").classList.remove("hidden");
  document.getElementById("specieForm").classList.add("hidden");
}

function loadSpecie(name) {
  const s = allSpecies.find(x => x.species === name);
  if (!s) return;
  showForm("edit");
  document.getElementById("species").value = s.species;
  document.getElementById("temp_min").value = s.temp_min ?? '';
  document.getElementById("temp_max").value = s.temp_max ?? '';
  document.getElementById("hum_air_min").value = s.hum_air_min ?? '';
  document.getElementById("hum_air_max").value = s.hum_air_max ?? '';
  document.getElementById("soil_min").value = s.soil_min ?? '';
  document.getElementById("soil_max").value = s.soil_max ?? '';
  document.getElementById("lux_min").value = s.lux_min ?? '';
  document.getElementById("lux_max").value = s.lux_max ?? '';
}

function submitForm(e) {
  e.preventDefault();
  const mode = document.getElementById("formMode").value;
  const payload = {
    species: document.getElementById("species").value.trim(),
    temp_min: +document.getElementById("temp_min").value || null,
    temp_max: +document.getElementById("temp_max").value || null,
    hum_air_min: +document.getElementById("hum_air_min").value || null,
    hum_air_max: +document.getElementById("hum_air_max").value || null,
    soil_min: +document.getElementById("soil_min").value || null,
    soil_max: +document.getElementById("soil_max").value || null,
    lux_min: +document.getElementById("lux_min").value || null,
    lux_max: +document.getElementById("lux_max").value || null
  };

  fetch('/api/species_params.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({mode, ...payload})
  })
  .then(res => res.json())
  .then(() => {
    fetchSpecies();
    document.getElementById("specieForm").classList.add("hidden");
    document.getElementById("speciesSelector").classList.add("hidden");
  });
}

function deleteSpecie() {
  const sel = document.getElementById("speciesSelect");
  const specie = sel.value;
  if (!specie || !confirm(`Eliminare la specie "${specie}"?`)) return;

  fetch('/api/species_params.php', {
    method: 'DELETE',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({species: specie})
  })
  .then(res => res.json())
  .then(() => {
    fetchSpecies();
    sel.value = "";
  });
}

fetchSpecies();
</script>

</body>
</html>
