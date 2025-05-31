document.addEventListener('DOMContentLoaded', function() {
    console.log('Wearable simulation script loaded');
    
    // Check if required elements exist
    const simulationStatus = document.getElementById('simulationStatus');
    const simulationContainer = document.getElementById('simulationStatusContainer');
    
    if (!simulationStatus || !simulationContainer) {
        console.warn('Wearable simulation elements not found on this page');
        return; // Exit if this isn't the right page
    }
    
    // Get UI elements
    const startSimulationBtn = document.getElementById('startSimulation');
    const stopSimulationBtn = document.getElementById('stopSimulation');

    // State variables
    let simulationInterval = null;
    const charts = {};
    let isInitialized = false;

    // Initialize the application
    function init() {
        if (isInitialized) return;
        
        console.log('Initializing wearable simulation...');
        
        // Initialize charts
        initializeCharts();
        
        // Check initial simulation status
        checkSimulationStatus().then(() => {
            console.log('Initial simulation status checked');
        }).catch(error => {
            console.error('Error checking initial simulation status:', error);
        });
        
        // Add event listeners
        if (startSimulationBtn) {
            startSimulationBtn.addEventListener('click', startSimulation);
        }
        
        if (stopSimulationBtn) {
            stopSimulationBtn.addEventListener('click', stopSimulation);
        }
        
        isInitialized = true;
        console.log('Wearable simulation initialized');
    }

    // Start the simulation
    async function startSimulation() {
        console.log('Starting simulation...');
        updateSimulationStatus('starting');
        
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }
            
            const response = await fetch('api/wearable.php?action=start_simulation', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store',
                body: JSON.stringify({ timestamp: new Date().toISOString() })
            });
            
            if (response.status === 401) {
                // Token expired or invalid
                console.log('Token expired, attempting to refresh...');
                localStorage.removeItem('token');
                window.location.reload();
                return;
            }
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Start simulation response:', data);
            
            if (data && data.success) {
                updateSimulationStatus('running');
                startDataPolling();
                toastr.success('Wearable simulation started successfully');
            } else {
                throw new Error(data.message || 'Failed to start simulation');
            }
        } catch (error) {
            console.error('Error starting simulation:', error);
            updateSimulationStatus('error');
            
            // Show user-friendly error message
            if (error.message.includes('auth') || error.message.includes('token')) {
                toastr.error('Session expired. Please log in again.', 'Authentication Error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                toastr.error(error.message || 'Failed to start simulation. Please try again.', 'Error');
                setTimeout(() => updateSimulationStatus('stopped'), 2000);
            }
        }
    }

    // Stop the simulation
    async function stopSimulation() {
        console.log('Stopping simulation...');
        updateSimulationStatus('stopping');
        
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }
            
            const response = await fetch('api/wearable.php?action=stop_simulation', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store'
            });
            
            if (response.status === 401) {
                console.log('Token expired, attempting to refresh...');
                localStorage.removeItem('token');
                window.location.reload();
                return;
            }
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Stop simulation response:', data);
            
            if (data && data.success) {
                updateSimulationStatus('stopped');
                stopDataPolling();
                toastr.success('Wearable simulation stopped successfully');
            } else {
                throw new Error(data.message || 'Failed to stop simulation');
            }
        } catch (error) {
            console.error('Error stopping simulation:', error);
            updateSimulationStatus('error');
            
            if (error.message.includes('auth') || error.message.includes('token')) {
                toastr.error('Session expired. Please log in again.', 'Authentication Error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                toastr.error(error.message || 'Failed to stop simulation. Please try again.', 'Error');
                setTimeout(() => updateSimulationStatus('running'), 2000);
            }
        }
    }

    // Check simulation status
    async function checkSimulationStatus() {
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }
            
            const response = await fetch('api/wearable.php?action=get_simulation_status', {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store'
            });
            
            if (response.status === 401) {
                localStorage.removeItem('token');
                window.location.reload();
                return false;
            }
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Simulation status response:', data);
            
            if (data && data.success) {
                updateSimulationStatus(data.simulation.status);
                if (data.simulation.status === 'running') {
                    startDataPolling();
                }
                return true;
            } else {
                throw new Error(data.message || 'Failed to get simulation status');
            }
        } catch (error) {
            console.error('Error checking simulation status:', error);
            updateSimulationStatus('error');
            
            if (error.message.includes('auth') || error.message.includes('token')) {
                toastr.error('Session expired. Please log in again.');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
            return false;
        }
    }

    // Update the UI based on simulation status
    function updateSimulationStatus(status) {
        console.log('Updating simulation status to:', status);
        
        if (!simulationStatus) return;
        
        // Update status text and button states
        switch (status.toLowerCase()) {
            case 'starting':
                simulationStatus.textContent = 'Starting...';
                simulationStatus.className = 'text-warning';
                if (startSimulationBtn) startSimulationBtn.disabled = true;
                if (stopSimulationBtn) stopSimulationBtn.disabled = true;
                break;
                
            case 'running':
                simulationStatus.textContent = 'Running';
                simulationStatus.className = 'text-success';
                if (startSimulationBtn) startSimulationBtn.disabled = true;
                if (stopSimulationBtn) stopSimulationBtn.disabled = false;
                break;
                
            case 'stopping':
                simulationStatus.textContent = 'Stopping...';
                simulationStatus.className = 'text-warning';
                if (startSimulationBtn) startSimulationBtn.disabled = true;
                if (stopSimulationBtn) stopSimulationBtn.disabled = true;
                break;
                
            case 'stopped':
                simulationStatus.textContent = 'Stopped';
                simulationStatus.className = 'text-muted';
                if (startSimulationBtn) startSimulationBtn.disabled = false;
                if (stopSimulationBtn) stopSimulationBtn.disabled = true;
                break;
                
            case 'error':
                simulationStatus.textContent = 'Error';
                simulationStatus.className = 'text-danger';
                if (startSimulationBtn) startSimulationBtn.disabled = false;
                if (stopSimulationBtn) stopSimulationBtn.disabled = true;
                break;
                
            default:
                simulationStatus.textContent = 'Unknown';
                simulationStatus.className = 'text-muted';
                if (startSimulationBtn) startSimulationBtn.disabled = false;
                if (stopSimulationBtn) stopSimulationBtn.disabled = true;
        }
    }

    // Start polling for metrics
    function startDataPolling() {
        console.log('Starting data polling...');
        
        // Clear any existing interval to prevent multiple polling instances
        stopDataPolling();
        
        // Only start polling if simulation is running
        const isRunning = simulationStatus && 
                        simulationStatus.textContent && 
                        simulationStatus.textContent.includes('Running');
        
        if (isRunning) {
            console.log('Simulation is running, starting polling...');
            
            // Initial fetch
            getMetrics().catch(error => {
                console.error('Error in initial metrics fetch:', error);
            });
            
            // Then poll every 5 seconds
            simulationInterval = setInterval(() => {
                console.log('Polling for metrics...');
                getMetrics().catch(error => {
                    console.error('Error in scheduled metrics fetch:', error);
                });
            }, 5000);
            
            console.log('Data polling started with interval ID:', simulationInterval);
        } else {
            console.warn('Cannot start polling: Simulation is not running');
        }
    }

    // Stop polling for metrics
    function stopDataPolling() {
        if (simulationInterval) {
            console.log('Stopping data polling, interval ID:', simulationInterval);
            clearInterval(simulationInterval);
            simulationInterval = null;
            console.log('Data polling stopped');
        } else {
            console.log('No active polling to stop');
        }
    }

    // Fetch metrics from the server
    async function getMetrics() {
        if (document.visibilityState !== 'visible') {
            console.log('Tab is not visible, skipping metrics update');
            return;
        }
        
        console.log('Fetching metrics...');
        
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }
            
            // Add a timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`api/wearable.php?action=get_metrics&_=${timestamp}`, {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                },
                cache: 'no-store'
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Metrics response:', data);
            
            if (data && data.success && data.metrics) {
                console.log('Updating metrics display...');
                updateMetrics(data.metrics);
            } else {
                throw new Error(data.message || 'Failed to get metrics');
            }
        } catch (error) {
            console.error('Error getting metrics:', error);
            
            // If we keep getting errors, stop polling
            if (error.message.includes('token') || error.message.includes('401')) {
                console.error('Authentication error, stopping polling');
                stopDataPolling();
                updateSimulationStatus('error');
                toastr.error('Authentication error. Please log in again.', 'Error');
            }
        }
    }

    // Update the metrics display
    function updateMetrics(metrics) {
        if (!metrics) {
            console.error('No metrics data provided');
            return;
        }
        
        console.log('Updating metrics with data:', metrics);
        
        try {
            // Update metric values with null checks
            const updateMetric = (elementId, value, suffix = '') => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.textContent = value !== undefined && value !== null 
                        ? `${value} ${suffix}`.trim() 
                        : '--';
                }
            };
            
            // Update each metric
            updateMetric('heartRate', metrics.heart_rate, 'bpm');
            updateMetric('bloodPressure', 
                metrics.blood_pressure_systolic && metrics.blood_pressure_diastolic 
                    ? `${metrics.blood_pressure_systolic}/${metrics.blood_pressure_diastolic}`
                    : null, 
                'mmHg');
            updateMetric('oxygenLevel', metrics.oxygen_level, '%');
            updateMetric('temperature', metrics.temperature, '°C');
            updateMetric('steps', metrics.step_count, 'steps');
            updateMetric('calories', metrics.calories_burned, 'calories');
            updateMetric('sleep', metrics.sleep_duration, 'hours');
            updateMetric('stress', metrics.stress_level, '/5');
            
            // Update charts if metrics have valid numeric values
            const updateChartIfValid = (chartId, value) => {
                if (value !== undefined && value !== null && !isNaN(parseFloat(value))) {
                    updateChart(chartId, parseFloat(value));
                }
            };
            
            // Update each chart
            updateChartIfValid('heartRate', metrics.heart_rate);
            updateChartIfValid('bloodPressure', metrics.blood_pressure_systolic);
            updateChartIfValid('oxygenLevel', metrics.oxygen_level);
            updateChartIfValid('temperature', metrics.temperature);
            updateChartIfValid('steps', metrics.step_count);
            updateChartIfValid('calories', metrics.calories_burned);
            updateChartIfValid('sleep', metrics.sleep_duration);
            updateChartIfValid('stress', metrics.stress_level);
            
        } catch (error) {
            console.error('Error updating metrics:', error);
            toastr.error('Failed to update metrics display', 'Error');
        }
    }

    // Update a specific chart with new data
    function updateChart(metric, value) {
        try {
            // Validate inputs
            if (!metric || value === undefined || value === null || isNaN(parseFloat(value))) {
                console.warn(`Invalid chart update for ${metric}:`, value);
                return;
            }
            
            const chart = charts[metric];
            if (!chart) {
                console.warn(`No chart found for metric: ${metric}`);
                return;
            }
            
            // Add new data point with current time
            const now = new Date();
            const timeLabel = now.toLocaleTimeString();
            
            // Ensure we have valid data structures
            if (!chart.data || !chart.data.labels || !chart.data.datasets || !chart.data.datasets[0]) {
                console.error('Invalid chart data structure for:', metric);
                return;
            }
            
            // Add new data point
            chart.data.labels.push(timeLabel);
            chart.data.datasets[0].data.push(parseFloat(value));
            
            // Keep only the last 10 data points for performance
            const maxDataPoints = 10;
            if (chart.data.labels.length > maxDataPoints) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            
            // Update the chart with animation
            chart.update({
                duration: 800,
                easing: 'easeOutQuart',
                lazy: true
            });
            
        } catch (error) {
            console.error(`Error updating ${metric} chart:`, error);
            // Don't show toastr here to avoid spamming the user
        }
    }

    // Initialize all charts
    function initializeCharts() {
        const metrics = ['heartRate', 'bloodPressure', 'oxygenLevel', 'temperature', 'steps', 'calories', 'sleep', 'stress'];
        
        metrics.forEach(metric => {
            const ctx = document.getElementById(`${metric}Chart`);
            if (!ctx) {
                console.warn(`Chart container not found for metric: ${metric}`);
                return;
            }
            
            try {
                charts[metric] = new Chart(ctx.getContext('2d'), {
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
                        animation: {
                            duration: 0 // Disable animation for initial render
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                console.log(`Initialized chart for ${metric}`);
            } catch (error) {
                console.error(`Failed to initialize chart for ${metric}:`, error);
            }
        });
    }

    // Get color for a specific metric's chart
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
        return colors[metric] || '#000000';
    }

    // Start the application
    init();
});
