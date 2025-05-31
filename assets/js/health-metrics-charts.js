// Health Metrics Charts
function updateMetricsChart(metrics) {
    if (!Array.isArray(metrics) || metrics.length === 0) {
        console.warn('No metrics data available for charts');
        return;
    }
    
    // Group metrics by type
    const metricsByType = {};
    metrics.forEach(metric => {
        if (!metric || !metric.metric_type) return;
        
        if (!metricsByType[metric.metric_type]) {
            metricsByType[metric.metric_type] = [];
        }
        metricsByType[metric.metric_type].push(metric);
    });
    
    // Update each chart if it exists
    Object.entries(metricsByType).forEach(([metricType, metricData]) => {
        const chartElement = document.getElementById(`${metricType}Chart`);
        if (chartElement) {
            updateChartForMetric(metricType, metricData);
        }
    });
}

// Update a specific chart
function updateChartForMetric(metricType, metricData) {
    const ctx = document.getElementById(`${metricType}Chart`);
    if (!ctx) return;
    
    // Get or create chart instance
    const chart = Chart.getChart(ctx) || new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: metricType.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' '),
                data: [],
                borderColor: getChartColor(metricType),
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Update chart data
    const labels = [];
    const data = [];
    
    metricData.forEach(metric => {
        if (metric.recorded_at && (metric.value1 !== undefined && metric.value1 !== null)) {
            labels.push(new Date(metric.recorded_at).toLocaleTimeString());
            data.push(parseFloat(metric.value1));
        }
    });
    
    // Keep only the last 10 data points
    const maxPoints = 10;
    if (labels.length > maxPoints) {
        labels.splice(0, labels.length - maxPoints);
        data.splice(0, data.length - maxPoints);
    }
    
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
}

// Helper function to get chart colors
function getChartColor(metricType) {
    const colors = {
        heart_rate: '#FF6384',
        blood_pressure: '#36A2EB',
        weight: '#FFCE56',
        glucose: '#4BC0C0',
        temperature: '#9966FF',
        oxygen_level: '#FF9F40'
    };
    return colors[metricType] || '#CCCCCC';
}

// Make functions available globally
window.HealthMetricsCharts = {
    updateMetricsChart,
    updateChartForMetric,
    getChartColor
};
