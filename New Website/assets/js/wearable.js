document.addEventListener('DOMContentLoaded', () => {
    const startSimulationBtn = document.getElementById('startSimulation');
    const stopSimulationBtn = document.getElementById('stopSimulation');
    const simulationStatus = document.getElementById('simulationStatus');

    let simulationInterval;
    let charts = {};

    // Initialize charts
    initializeCharts();
    
    // Check simulation status on page load
    checkSimulationStatus();

    // Event listeners
    startSimulationBtn.addEventListener('click', startSimulation);
    stopSimulationBtn.addEventListener('click', stopSimulation);

    // Functions
    async function checkSimulationStatus() {
        try {
            const response = await fetch('api/wearable.php?action=get_simulation_status', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                updateSimulationStatus(data.simulation.status);
            }
        } catch (error) {
            console.error('Error checking simulation status:', error);
            updateSimulationStatus('error');
        }
    }

    async function startSimulation() {
        try {
            const response = await fetch('api/wearable.php?action=start_simulation', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                updateSimulationStatus('running');
                startDataPolling();
            }
        } catch (error) {
            console.error('Error starting simulation:', error);
            updateSimulationStatus('error');
        }
    }

    async function stopSimulation() {
        try {
            const response = await fetch('api/wearable.php?action=stop_simulation', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                updateSimulationStatus('stopped');
                stopDataPolling();
            }
        } catch (error) {
            console.error('Error stopping simulation:', error);
            updateSimulationStatus('error');
        }
    }

    async function getMetrics() {
        try {
            const response = await fetch('api/wearable.php?action=get_metrics', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                updateMetrics(data.metrics);
            }
        } catch (error) {
            console.error('Error getting metrics:', error);
        }
    }

    function updateSimulationStatus(status) {
        const statusSpan = simulationStatus.querySelector('span');
        const statusIcon = simulationStatus.querySelector('i');
        
        switch (status) {
            case 'running':
                statusSpan.textContent = 'Running';
                statusIcon.className = 'fas fa-circle-notch fa-spin';
                statusIcon.style.color = '#4CAF50';
                startSimulationBtn.disabled = true;
                stopSimulationBtn.disabled = false;
                break;
            case 'stopped':
                statusSpan.textContent = 'Stopped';
                statusIcon.className = 'fas fa-circle';
                statusIcon.style.color = '#f44336';
                startSimulationBtn.disabled = false;
                stopSimulationBtn.disabled = true;
                break;
            case 'error':
                statusSpan.textContent = 'Error';
                statusIcon.className = 'fas fa-exclamation-circle';
                statusIcon.style.color = '#f44336';
                startSimulationBtn.disabled = false;
                stopSimulationBtn.disabled = true;
                break;
            default:
                statusSpan.textContent = 'Checking...';
                statusIcon.className = 'fas fa-circle-notch fa-spin';
                statusIcon.style.color = '#4CAF50';
                startSimulationBtn.disabled = false;
                stopSimulationBtn.disabled = true;
        }
    }

    function updateMetrics(metrics) {
        // Update metric values
        document.getElementById('heartRate').textContent = `${metrics.heart_rate} bpm`;
        document.getElementById('bloodPressure').textContent = `${metrics.blood_pressure_systolic}/${metrics.blood_pressure_diastolic} mmHg`;
        document.getElementById('oxygenLevel').textContent = `${metrics.oxygen_level} %`;
        document.getElementById('temperature').textContent = `${metrics.temperature} °C`;
        document.getElementById('steps').textContent = `${metrics.step_count} steps`;
        document.getElementById('calories').textContent = `${metrics.calories_burned} calories`;
        document.getElementById('sleep').textContent = `${metrics.sleep_duration} hours`;
        document.getElementById('stress').textContent = `${metrics.stress_level}/5`;

        // Update charts
        updateChart('heartRate', metrics.heart_rate);
        updateChart('bloodPressure', metrics.blood_pressure_systolic);
        updateChart('oxygenLevel', metrics.oxygen_level);
        updateChart('temperature', metrics.temperature);
        updateChart('steps', metrics.step_count);
        updateChart('calories', metrics.calories_burned);
        updateChart('sleep', metrics.sleep_duration);
        updateChart('stress', metrics.stress_level);
    }

    function initializeCharts() {
        const metrics = ['heartRate', 'bloodPressure', 'oxygenLevel', 'temperature', 'steps', 'calories', 'sleep', 'stress'];
        
        metrics.forEach(metric => {
            const ctx = document.getElementById(`${metric}Chart`).getContext('2d');
            charts[metric] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: metric,
                        data: [],
                        borderColor: getChartColor(metric),
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
        });
    }

    function updateChart(metric, value) {
        const chart = charts[metric];
        const now = new Date().toLocaleTimeString();
        
        // Add new data
        chart.data.labels.push(now);
        chart.data.datasets[0].data.push(value);
        
        // Remove old data if too many points
        if (chart.data.labels.length > 20) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
        }
        
        chart.update();
    }

    function getChartColor(metric) {
        const colors = {
            heartRate: '#2196F3',
            bloodPressure: '#4CAF50',
            oxygenLevel: '#FF9800',
            temperature: '#E91E63',
            steps: '#9C27B0',
            calories: '#3F51B5',
            sleep: '#607D8B',
            stress: '#F44336'
        };
        return colors[metric];
    }

    function startDataPolling() {
        simulationInterval = setInterval(getMetrics, 5000); // Poll every 5 seconds
    }

    function stopDataPolling() {
        if (simulationInterval) {
            clearInterval(simulationInterval);
        }
    }
});
