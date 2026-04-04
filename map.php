<?php
// filepath: [map.php](http://_vscodecontentref_/1)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Index Performance</title>
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
  <style>
    html, body, #map { height: 100%; margin: 0; }

    .toolbar {
      position: absolute;
      top: 12px;
      left: 12px;
      z-index: 1000;
      background: rgba(17,24,39,.9);
      border: 1px solid rgba(148,163,184,.45);
      border-radius: 8px;
      padding: 6px;
      display: flex;
      gap: 6px;
    }
    .toolbar button {
      background: #1f2937;
      color: #e5e7eb;
      border: 1px solid #374151;
      border-radius: 6px;
      padding: 6px 10px;
      cursor: pointer;
      font: 12px Arial, sans-serif;
    }
    .toolbar button.active {
      background: #2563eb;
      border-color: #3b82f6;
      color: #fff;
    }

    .country-pct-label {
      background: transparent;
      border: 0;
    }
    .country-pct-label span{
      display:inline-block;
      padding:2px 6px;
      border-radius:4px;
      font: 12px/1.2 Arial, sans-serif;
      color:#e5e7eb;
      background: rgba(17,24,39,.75);
      border:1px solid rgba(148,163,184,.45);
      white-space: nowrap;
    }

    .tv-panel {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 1100;
      width: min(42vw, 620px);
      height: calc(100% - 24px);
      background: rgba(15, 23, 42, 0.96);
      border: 1px solid rgba(148,163,184,.45);
      border-radius: 10px;
      box-shadow: 0 12px 28px rgba(0,0,0,.35);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .tv-panel.hidden {
      display: none;
    }
    .tv-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 12px;
      border-bottom: 1px solid rgba(148,163,184,.35);
      color: #e5e7eb;
      font: 600 14px Arial, sans-serif;
    }
    .tv-close {
      border: 1px solid #374151;
      background: #1f2937;
      color: #e5e7eb;
      border-radius: 6px;
      padding: 4px 8px;
      cursor: pointer;
      font: 12px Arial, sans-serif;
    }
    .tv-widget-host {
      flex: 1;
      min-height: 0;
      padding: 8px;
    }
    .tv-empty {
      height: 100%;
      display: grid;
      place-items: center;
      color: #cbd5e1;
      font: 13px Arial, sans-serif;
      text-align: center;
      padding: 12px;
    }

    @media (max-width: 900px) {
      .tv-panel {
        left: 12px;
        right: 12px;
        width: auto;
        height: 56vh;
        top: auto;
        bottom: 12px;
      }
    }
  </style>
</head>
<body>
  <div id="map"></div>

  <div class="toolbar">
    <button data-period="daily">Daily</button>
    <button data-period="weekly" class="active">Weekly</button>
    <button data-period="monthly">Monthly</button>
  </div>

  <div id="tvPanel" class="tv-panel hidden">
    <div class="tv-panel-header">
      <div id="tvTitle">Chart</div>
      <button id="tvClose" class="tv-close" type="button">Close</button>
    </div>
    <div id="tvHost" class="tv-widget-host"></div>
  </div>

  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
  ></script>
  <script>
  const worldBounds = L.latLngBounds(
    L.latLng(-85, -180),
    L.latLng(85, 180)
  );

  const map = L.map('map', {
    maxBounds: worldBounds,
    maxBoundsViscosity: 1.0,
    worldCopyJump: false
  }).setView([20, 0], 2);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd',
    maxZoom: 20,
    noWrap: true
  }).addTo(map);

  function lockMinZoomToBounds() {
    const min = map.getBoundsZoom(worldBounds, true);
    map.setMinZoom(min);
    if (map.getZoom() < min) map.setZoom(min);
  }

  lockMinZoomToBounds();
  map.on('resize', lockMinZoomToBounds);
  window.addEventListener('resize', () => map.invalidateSize());

  function getHeatColor(v) {
    if (v === null || v === undefined || Number.isNaN(v)) return '#374151';
    if (v >= 5) return '#00c853';
    if (v >= 3) return '#2ecc71';
    if (v >= 1) return '#7ed957';
    if (v >= 0) return '#c8ff005c';
    if (v >= -1) return '#ffe082';
    if (v >= -2) return '#ffca28';
    if (v >= -3) return '#ffb74d';
    if (v >= -4) return '#ff9800';
    if (v >= -5) return '#ff7043';
    return '#ff1744';
  }

  let geojsonData = null;
  let countryMeta = {};
  let countryLayer = null;
  let bboxLayer = L.layerGroup().addTo(map);
  let pctLayer = L.layerGroup().addTo(map);

  const tvPanel = document.getElementById('tvPanel');
  const tvHost = document.getElementById('tvHost');
  const tvTitle = document.getElementById('tvTitle');
  const tvClose = document.getElementById('tvClose');

  function flattenCountryMeta(node, out = {}) {
    for (const [key, value] of Object.entries(node || {})) {
      if (value && typeof value === 'object' && typeof value.symbol === 'string') {
        out[key] = {
          symbol: value.symbol,
          isin: typeof value.isin === 'string' ? value.isin.trim() : ''
        };
      } else if (value && typeof value === 'object') {
        flattenCountryMeta(value, out);
      }
    }
    return out;
  }

  function closeTvPanel() {
    tvHost.innerHTML = '';
    tvPanel.classList.add('hidden');
  }

  function openTvPanelForCountry(countryName) {
    const meta = countryMeta[countryName] || {};
    const isin = meta.isin || '';

    tvTitle.textContent = isin ? countryName + '  •  ' + isin : countryName;
    tvPanel.classList.remove('hidden');
    tvHost.innerHTML = '';

    if (!isin) {
      const empty = document.createElement('div');
      empty.className = 'tv-empty';
      empty.textContent = 'No ISIN set for this country in api/countries.json';
      tvHost.appendChild(empty);
      return;
    }

    const container = document.createElement('div');
    container.className = 'tradingview-widget-container';
    container.style.height = '100%';
    container.style.width = '100%';

    const widget = document.createElement('div');
    widget.className = 'tradingview-widget-container__widget';
    widget.style.height = 'calc(100% - 32px)';
    widget.style.width = '100%';

    const copyright = document.createElement('div');
    copyright.className = 'tradingview-widget-copyright';
    copyright.innerHTML =
      '<a href="https://www.tradingview.com/symbols/' + encodeURIComponent(isin) + '/" rel="noopener nofollow" target="_blank">' +
      '<span class="blue-text">' + isin + ' chart</span></a><span class="trademark"> by TradingView</span>';

    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
    script.async = true;
    script.innerHTML = JSON.stringify({
      allow_symbol_change: true,
      calendar: false,
      details: false,
      hide_side_toolbar: true,
      hide_top_toolbar: false,
      hide_legend: false,
      hide_volume: false,
      hotlist: false,
      interval: 'D',
      locale: 'en',
      save_image: true,
      style: '1',
      symbol: isin,
      theme: 'dark',
      timezone: 'Etc/UTC',
      backgroundColor: '#0F0F0F',
      gridColor: 'rgba(242, 242, 242, 0.06)',
      watchlist: [],
      withdateranges: false,
      compareSymbols: [],
      studies: [],
      autosize: true
    });

    container.appendChild(widget);
    container.appendChild(copyright);
    container.appendChild(script);
    tvHost.appendChild(container);
  }

  function periodShortLabel(period) {
    if (period === 'daily') return '1D';
    if (period === 'monthly') return '1M';
    return '1W';
  }

  function setActivePeriodButton(period) {
    document.querySelectorAll('.toolbar button').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.period === period);
    });
  }

  function renderHeatmap(perf, period) {
    if (countryLayer) map.removeLayer(countryLayer);
    bboxLayer.clearLayers();
    pctLayer.clearLayers();

    const pLabel = periodShortLabel(period);

    countryLayer = L.geoJSON(geojsonData, {
      style: (feature) => {
        const name = feature && feature.properties ? feature.properties.name : null;
        const change = perf[name] ?? null;
        return {
          color: '#4fc3f7',
          weight: 1,
          fillColor: getHeatColor(change),
          fillOpacity: change == null ? 0.12 : 0.55
        };
      },
      onEachFeature: (feature, layer) => {
        const name = (feature && feature.properties && feature.properties.name) ? feature.properties.name : 'Unknown country';
        const change = perf[name] ?? null;
        const label = (change == null) ? 'N/A' : (change > 0 ? '+' : '') + Number(change).toFixed(2) + '%';

        const b = layer.getBounds();
        if (!b.isValid()) return;

        const bboxRect = L.rectangle(b, {
          color: '#e2e8f0',
          weight: 2,
          opacity: 0,
          fill: false,
          interactive: false
        }).addTo(bboxLayer);

        L.marker(b.getCenter(), {
          interactive: false,
          icon: L.divIcon({
            className: 'country-pct-label',
            html: '<span>' + label + '</span>',
            iconSize: null
          })
        }).addTo(pctLayer);

        layer.bindPopup('<strong>' + name + '</strong><br>' + label + ' (' + pLabel + ')');

        layer.on({
          mouseover: (e) => {
            e.target.setStyle({ weight: 2, fillOpacity: 0.75, color: '#80d8ff' });
            bboxRect.setStyle({ opacity: 1 });
            bboxRect.bringToFront();
          },
          mouseout: (e) => {
            e.target.setStyle({
              weight: 1,
              fillOpacity: (change == null ? 0.12 : 0.55),
              color: '#4fc3f7'
            });
            bboxRect.setStyle({ opacity: 0 });
          },
          click: () => {
            openTvPanelForCountry(name);
          }
        });
      }
    }).addTo(map);
  }

  async function loadPerformance(period = 'weekly') {
    setActivePeriodButton(period);
    const resp = await fetch('./api/weekly_returns.php?period=' + encodeURIComponent(period));
    const json = await resp.json();
    const perf = json && json.data ? json.data : {};
    renderHeatmap(perf, period);
  }

  tvClose.addEventListener('click', closeTvPanel);

  Promise.all([
    fetch('https://raw.githubusercontent.com/johan/world.geo.json/master/countries.geo.json').then(r => r.json()),
    fetch('./api/countries.json').then(r => r.json())
  ])
  .then(async ([geojson, countries]) => {
    geojsonData = geojson;
    countryMeta = flattenCountryMeta(countries);
    await loadPerformance('weekly');
  })
  .catch(err => console.error('Heatmap load failed:', err));

  document.querySelectorAll('.toolbar button').forEach(btn => {
    btn.addEventListener('click', () => {
      loadPerformance(btn.dataset.period).catch(err => console.error('Period load failed:', err));
    });
  });
  </script>
</body>
</html>