/* ==========================================================================
   IWA Weerdashboard — app.js
   Verantwoordelijk voor: tab-navigatie, Chart.js grafieken, zoekfilter
   ========================================================================== */

(function () {
    'use strict';

    /* -----------------------------------------------------------------------
       Hulpfuncties
    ----------------------------------------------------------------------- */

    /** Geeft de dashboard-data terug die vanuit Blade is meegestuurd. */
    function getData() {
        return window.dashboardBootstrap || {};
    }

    /** Maakt een CSS-kleurpalet voor Chart.js aan op basis van een basiskleur. */
    function palette(count) {
        const base = [
            '#1d4ed8', '#0284c7', '#0891b2', '#059669',
            '#d97706', '#dc2626', '#7c3aed', '#db2777',
        ];
        const result = [];
        for (let i = 0; i < count; i++) {
            result.push(base[i % base.length]);
        }
        return result;
    }

    /* -----------------------------------------------------------------------
       Tab-navigatie
    ----------------------------------------------------------------------- */
    function initTabs() {
        const buttons = document.querySelectorAll('.tab-button');
        const panels  = document.querySelectorAll('.tab-panel');

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = btn.dataset.tabTarget;

                buttons.forEach(function (b) { b.classList.remove('active'); });
                panels.forEach(function (p)  { p.classList.remove('active'); });

                btn.classList.add('active');
                const panel = document.getElementById(target);
                if (panel) panel.classList.add('active');
            });
        });
    }

    /* -----------------------------------------------------------------------
       Temperatuurgrafiek (lijn) — Overzicht tab
    ----------------------------------------------------------------------- */
    function initTemperatureChart() {
        const canvas = document.getElementById('temperatureChart');
        if (!canvas) return;

        const points = getData().chart_points || [];

        const labels      = points.map(function (p) { return p.date; });
        const corrected   = points.map(function (p) { return p.avg_temp; });
        const original    = points.map(function (p) { return p.avg_original; });

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gecorrigeerde temp (°C)',
                        data: corrected,
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, .08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: true,
                        tension: .35,
                    },
                    {
                        label: 'Originele temp (°C)',
                        data: original,
                        borderColor: '#dc2626',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        borderDash: [5, 4],
                        pointRadius: 2,
                        fill: false,
                        tension: .35,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 11 }, color: '#475569' },
                    },
                    tooltip: { backgroundColor: '#0f172a', titleFont: { size: 11 } },
                },
                scales: {
                    x: {
                        ticks: { font: { size: 10 }, color: '#94a3b8' },
                        grid:  { color: '#f1f5f9' },
                    },
                    y: {
                        ticks: { font: { size: 10 }, color: '#94a3b8' },
                        grid:  { color: '#f1f5f9' },
                    },
                },
            },
        });
    }

    /* -----------------------------------------------------------------------
       Top-stations donut — Overzicht tab
    ----------------------------------------------------------------------- */
    function initStationDonut() {
        const canvas = document.getElementById('stationChart');
        if (!canvas) return;

        const stations = getData().top_stations || [];
        const labels   = stations.map(function (s) { return s.name; });
        const values   = stations.map(function (s) { return s.reading_count; });
        const colors   = palette(stations.length);

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString('nl-NL') + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
    }

    /* -----------------------------------------------------------------------
       API-activiteitsgrafiek (horizontale balk) — Activiteit tab
    ----------------------------------------------------------------------- */
    function initActivityChart() {
        const canvas = document.getElementById('activityChart');
        if (!canvas) return;

        const activity = getData().api_activity || [];
        const labels   = activity.map(function (a) {
            /* Haal de laatste twee segmenten van het pad op voor leesbaarheid */
            const parts = a.endpoint_used.replace(/^\/+/, '').split('/');
            return parts.slice(-2).join('/');
        });
        const calls        = activity.map(function (a) { return a.calls; });
        const unauthorized = activity.map(function (a) { return a.unauthorized || 0; });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Aanroepen (totaal)',
                        data: calls,
                        backgroundColor: 'rgba(29, 78, 216, .75)',
                        borderColor: '#1d4ed8',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Ongeautoriseerd',
                        data: unauthorized,
                        backgroundColor: 'rgba(220, 38, 38, .65)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 11 }, color: '#475569' },
                    },
                    tooltip: { backgroundColor: '#0f172a' },
                },
                scales: {
                    x: {
                        ticks: { font: { size: 10 }, color: '#94a3b8', maxRotation: 30 },
                        grid:  { display: false },
                    },
                    y: {
                        ticks: { font: { size: 10 }, color: '#94a3b8' },
                        grid:  { color: '#f1f5f9' },
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    /* -----------------------------------------------------------------------
       Live-indicator puls (herstel bij hidden tab)
    ----------------------------------------------------------------------- */
    function initLiveIndicator() {
        const pill = document.getElementById('liveIndicator');
        if (!pill) return;
        /* De animatie loopt al via CSS; hier eventueel uitbreiden met WebSocket */
    }

    /* -----------------------------------------------------------------------
       Zoekfilter stationentabel
    ----------------------------------------------------------------------- */
    function initStationSearch() {
        const input = document.getElementById('stationSearch');
        const table = document.getElementById('stationsTable');
        if (!input || !table) return;

        input.addEventListener('input', function () {
            const query = input.value.toLowerCase().trim();
            const rows  = table.querySelectorAll('tbody tr');

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    /* -----------------------------------------------------------------------
       Bootstrap — alles initialiseren na het laden van de DOM
    ----------------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        initTabs();
        initTemperatureChart();
        initStationDonut();
        initActivityChart();
        initLiveIndicator();
        initStationSearch();
    });

}());
