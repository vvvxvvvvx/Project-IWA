const stationChart = document.getElementById('stationTemperatureChart');
if (stationChart) {
    new Chart(stationChart, {
        type: 'line',
        data: {
            labels: stationDetailLabels,
            datasets: [{
                data: stationDetailValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                tension: 0.3,
                borderWidth: 3,
                pointRadius: 2,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#eef2f7' } }
            }
        }
    });
}
