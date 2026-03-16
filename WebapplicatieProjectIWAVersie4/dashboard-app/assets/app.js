let temperatureChart = null;
let stationChart = null;
const bootstrap = window.dashboardBootstrap || {};

function formatValue(value) {
    return value === null || value === undefined || value === '' ? '-' : String(value);
}
function formatStatusBadge(row) {
    if (Number(row.has_missing_data || 0) === 1) return '<span class="status-badge warning">Missing data</span>';
    if (Number(row.is_temp_peak || 0) === 1) return '<span class="status-badge info">Peak</span>';
    return '<span class="status-badge success">OK</span>';
}
function renderLine(points) {
    const element = document.getElementById('temperatureChart'); if (!element) return;
    const labels = points.map((p) => (p.bucket || '').replace('T', ' ').slice(0, 16));
    const hasCorrectionSeries = points.some((p) => p.original_temp !== undefined || p.corrected_temp !== undefined);
    const datasets = hasCorrectionSeries
        ? [
            { label: 'Origineel', data: points.map((p) => p.original_temp === null || p.original_temp === undefined ? null : Number(p.original_temp)), borderColor: '#d91e2e', backgroundColor: 'rgba(217,30,46,.08)', tension: .32, fill: false, borderWidth: 3, pointRadius: 2 },
            { label: 'Gecorrigeerd', data: points.map((p) => p.corrected_temp === null || p.corrected_temp === undefined ? null : Number(p.corrected_temp)), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', tension: .32, fill: false, borderWidth: 3, pointRadius: 2 },
        ]
        : [{ label: 'Gemiddelde temp', data: points.map((p) => Number(p.average_temp)), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', tension: .32, fill: false, borderWidth: 3, pointRadius: 2 }];
    if (temperatureChart) { temperatureChart.data.labels = labels; temperatureChart.data.datasets = datasets; temperatureChart.update(); return; }
    temperatureChart = new Chart(element, { type: 'line', data: { labels, datasets }, options: { responsive: true, plugins: { legend: { display: hasCorrectionSeries } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#eef2f7' } } } } });
}
function renderDonut(topStations) {
    const element = document.getElementById('stationChart'); if (!element) return;
    const labels = topStations.map((s) => s.stn); const values = topStations.map((s) => Number(s.reading_count));
    if (stationChart) { stationChart.data.labels = labels; stationChart.data.datasets[0].data = values; stationChart.update(); return; }
    stationChart = new Chart(element, { type: 'doughnut', data: { labels, datasets: [{ data: values, backgroundColor: ['#1d4ed8','#60a5fa','#67d1db','#c7d2fe','#dbeafe'], borderWidth: 0 }]}, options: { cutout: '72%', plugins: { legend: { display: false } } } });
}
function setText(id, value) { const el = document.getElementById(id); if (el) el.textContent = formatValue(value); }
function updateMetrics(overview) {
    setText('metricStationCount', overview.station_count); setText('metricReadingCount', overview.reading_count); setText('metricAverageTemp', overview.average_temp); setText('metricMinTemp', overview.min_temp); setText('metricMaxTemp', overview.max_temp); setText('metricPeakCount', overview.peak_count); setText('metricMissingCount', overview.missing_count);
    const stationCount = Math.max(Number(overview.station_count || 0), 1), readingCount = Math.max(Number(overview.reading_count || 0), 1), missing = Number(overview.missing_count || 0), peaks = Number(overview.peak_count || 0);
    const health = Math.max(0, 100 - Math.min(100, missing * 2)), coverage = Math.min(100, Math.round((readingCount / Math.max(1, stationCount * 10)) * 100)), stability = Math.max(0, 100 - Math.min(100, peaks * 5));
    [['kpiHealthValue','kpiHealthBar',health],['kpiCoverageValue','kpiCoverageBar',coverage],['kpiStabilityValue','kpiStabilityBar',stability]].forEach(([valueId, barId, value])=>{ setText(valueId, `${value}%`); const bar=document.getElementById(barId); if (bar) bar.style.width=`${value}%`; });
}
function rowsToHtml(rows, type) {
    if (type === 'latest') return rows.map((row) => `<tr><td><a href="/stations/${row.stn}">${row.name}</a></td><td>${formatValue(row.location_label)}</td><td>${formatValue((row.measured_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td><td>${formatValue(row.temp)}</td><td>${formatValue(row.dewp)}</td><td>${formatValue(row.wdsp)}</td><td>${formatValue(row.visib)}</td><td>${formatValue(row.prcp)}</td><td>${formatStatusBadge(row)}</td></tr>`).join('');
    if (type === 'stations') return rows.map((row) => `<tr><td><a href="/stations/${row.stn}">${row.stn}</a></td><td>${row.name}</td><td>${formatValue(row.location_label)}</td><td>${row.lat != null ? `${row.lat}, ${row.lon}` : '-' }</td><td>${formatValue((row.measured_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td><td>${formatValue(row.temp)}</td><td>${formatValue(row.avg_temp)}</td><td>${formatValue(row.visib)}</td><td>${formatValue(row.wdsp)}</td><td>${formatValue(row.prcp)}</td><td>${formatValue(row.reading_count)}</td><td>${formatStatusBadge(row)}</td></tr>`).join('');
    if (type === 'batches') return rows.map((row)=>`<tr><td>${formatValue((row.received_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td><td>${formatValue(row.station_count)}</td><td>${formatValue(row.correction_count)}</td><td>${formatValue(row.peak_count)}</td><td>${formatValue(row.missing_value_count)}</td><td>${formatValue(row.average_temp)}</td></tr>`).join('');
    if (type === 'flagged') return rows.map((row)=>`<tr><td><a href="/stations/${row.stn}">${row.name}</a></td><td>${formatValue((row.measured_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td><td>${formatValue(row.temp)}</td><td>${Number(row.has_missing_data||0)===1?'<span class="status-badge warning">Missing data</span>':'<span class="status-badge info">Temp peak</span>'}</td></tr>`).join('');
    if (type === 'corrections') return rows.map((row)=>`<tr><td>${row.stn}</td><td>${row.field}</td><td>${row.reason}</td><td>${formatValue(row.original_value)}</td><td>${formatValue(row.corrected_value)}</td><td>${formatValue((row.created_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td></tr>`).join('');
    if (type === 'originals') return rows.map((row)=>`<tr><td>${row.stn}</td><td>${row.field}</td><td>${formatValue(row.original_value)}</td><td>${formatValue(row.corrected_value)}</td><td>${formatValue((row.created_at || '').replace('T',' ').replace('+00:00',' UTC'))}</td></tr>`).join('');
    return '';
}
function updateSection(id, html) { const el = document.getElementById(id); if (el) el.innerHTML = html; }
function updateTopList(rows) { const list = document.getElementById('topStationsList'); if (list) list.innerHTML = rows.map((s)=>`<li><span class="source-name">${s.name}</span><span class="source-value">${s.reading_count}</span></li>`).join(''); }
function renderDashboard(data) {
    updateMetrics(data.overview || {}); renderLine(data.chart_points || []); renderDonut(data.top_stations || []); updateTopList(data.top_stations || []);
    updateSection('latestReadingsBody', rowsToHtml(data.latest_readings || [], 'latest')); updateSection('stationsTableBody', rowsToHtml(data.stations || [], 'stations')); updateSection('batchTableBody', rowsToHtml(data.latest_batches || [], 'batches')); updateSection('flaggedReadingsBody', rowsToHtml(data.flagged_readings || [], 'flagged')); updateSection('correctionsBody', rowsToHtml(data.recent_corrections || [], 'corrections')); updateSection('originalsBody', rowsToHtml(data.recent_originals || [], 'originals'));
}
function setupTabs() { const buttons=document.querySelectorAll('[data-tab-target]'); const panels=document.querySelectorAll('.tab-panel'); buttons.forEach((button)=>button.addEventListener('click',()=>{const targetId=button.getAttribute('data-tab-target'); buttons.forEach((b)=>b.classList.remove('active')); panels.forEach((p)=>p.classList.remove('active')); button.classList.add('active'); const panel=document.getElementById(targetId); if (panel) panel.classList.add('active');})); }
async function refreshDashboard() { try { const response = await fetch('/api/overview', {headers:{Accept:'application/json'}}); if(!response.ok) throw new Error(`HTTP ${response.status}`); const payload=await response.json(); renderDashboard(payload); const i=document.getElementById('liveIndicator'); if(i){i.textContent='Live'; i.classList.remove('warning-state');}} catch (e){const i=document.getElementById('liveIndicator'); if(i){i.textContent='Verbinding controleren'; i.classList.add('warning-state');} console.error(e);} }
setupTabs(); renderDashboard(bootstrap); window.setInterval(refreshDashboard, 5000);
