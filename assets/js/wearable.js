document.addEventListener('DOMContentLoaded', function() {
    console.log('Wearable simulation script loaded - running in local simulation mode');
    
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
    let isSimulationRunning = false;
    
    // Mock data generators with more realistic patterns
    function generateMockMetrics() {
        const now = new Date();
        const time = now.getTime();
        
        // Base values with some time-based variation
        const timeOfDay = (now.getHours() * 60 + now.getMinutes()) / (24 * 60); // 0-1 for the day
        const activityLevel = Math.sin(timeOfDay * Math.PI * 2 - Math.PI/2) * 0.5 + 0.5; // 0-1 based on time of day
        
        // Heart rate: higher during the day, lower at night
        const baseHeartRate = 60 + (Math.sin(time / 60000) * 2); // Slight variation
        const heartRate = Math.round(
            baseHeartRate + 
            (activityLevel * 30) + // 30 bpm variation based on activity
            (Math.random() * 5) // Small random variation
        );
        
        // Blood pressure: follows similar pattern to heart rate but less variation
        const baseSystolic = 110 + (Math.sin(time / 90000) * 2);
        const systolic = Math.round(
            baseSystolic + 
            (activityLevel * 10) + // 10 mmHg variation
            (Math.random() * 3)
        );
        
        const baseDiastolic = 70 + (Math.sin(time / 120000) * 1.5);
        const diastolic = Math.round(
            baseDiastolic + 
            (activityLevel * 5) + // 5 mmHg variation
            (Math.random() * 2)
        );
        
        // Oxygen level: high and stable, with very small variations
        const oxygenLevel = 97 + (Math.sin(time / 300000) * 0.5) + (Math.random() * 0.5);
        
        // Temperature: slightly higher during the day
        const baseTemp = 36.5 + (activityLevel * 0.8);
        const temperature = parseFloat((baseTemp + (Math.random() * 0.3)).toFixed(1));
        
        // Steps: accumulate throughout the day
        const steps = Math.floor(
            Math.min(
                15000, // Max steps per day
                (timeOfDay * 12000) + // Base on time of day
                (Math.random() * 3000) // Random variation
            )
        );
        
        // Calories: roughly 0.04 per step + base metabolic rate
        const calories = Math.floor(steps * 0.04 + (timeOfDay * 1000));
        
        // Sleep: more likely to be sleeping at night
        const isSleeping = timeOfDay < 0.2 || timeOfDay > 0.8; // 12am-5am or 7pm-12am
        const sleepDuration = isSleeping ? 
            Math.min(10, (timeOfDay > 0.8 ? timeOfDay - 0.8 : timeOfDay + 0.2) * 24) : 0;
        
        // Stress: lower when sleeping, varies during the day
        const baseStress = isSleeping ? 
            Math.random() * 2 : // Lower stress when sleeping
            1 + (Math.sin(time / 300000) * 0.5 + 0.5) * 4; // 1-5 during the day
        const stressLevel = Math.min(5, Math.max(1, Math.round(baseStress + (Math.random() * 0.8 - 0.4))));
        
        return {
            timestamp: now.toISOString(),
            heart_rate: Math.max(50, Math.min(150, heartRate)), // Keep in safe range
            blood_pressure_systolic: Math.max(90, Math.min(160, systolic)),
            blood_pressure_diastolic: Math.max(60, Math.min(100, diastolic)),
            oxygen_level: Math.max(90, Math.min(100, oxygenLevel)).toFixed(1),
            temperature: Math.max(35, Math.min(40, temperature)).toFixed(1),
            step_count: steps,
            calories_burned: calories,
            sleep_duration: sleepDuration.toFixed(1),
            stress_level: stressLevel
        };
    }

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
        console.log('Starting local simulation...');
        updateSimulationStatus('starting');
        
        try {
            // Simulate API call delay
            await new Promise(resolve => setTimeout(resolve, 500));
            
            isSimulationRunning = true;
            updateSimulationStatus('running');
            startDataPolling();
            toastr.success('Wearable simulation started successfully');
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
        console.log('Stopping local simulation...');
        updateSimulationStatus('stopping');
        
        try {
            // Simulate API call delay
            await new Promise(resolve => setTimeout(resolve, 300));
            
            isSimulationRunning = false;
            updateSimulationStatus('stopped');
            stopDataPolling();
            toastr.success('Wearable simulation stopped successfully');
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
            // For local simulation, just return the current state
            const status = isSimulationRunning ? 'running' : 'stopped';
            updateSimulationStatus(status);
            
            if (status === 'running') {
                startDataPolling();
            }
            return true;
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
        if (!isSimulationRunning) {
            console.warn('Cannot start polling: Simulation is not running');
            return;
        }
        
        console.log('Simulation is running, starting polling...');
        
        // Generate initial data points for a smoother chart experience
        const initialDataPoints = 5;
        const initialDelay = 300; // ms between initial points
        
        // Add initial data points quickly to populate the chart
        for (let i = 0; i < initialDataPoints; i++) {
            setTimeout(() => {
                if (isSimulationRunning) {
                    const mockMetrics = generateMockMetrics();
                    updateMetrics(mockMetrics);
                }
            }, i * initialDelay);
        }
        
        // Initial fetch after a short delay
        setTimeout(() => {
            if (isSimulationRunning) {
                getMetrics().catch(error => {
                    console.error('Error in initial metrics fetch:', error);
                });
            }
        }, initialDataPoints * initialDelay);
        
        // Then poll every 3 seconds for real-time updates
        simulationInterval = setInterval(() => {
            if (isSimulationRunning) {
                console.log('Polling for metrics...');
                getMetrics().catch(error => {
                    console.error('Error in scheduled metrics fetch:', error);
                });
            }
        }, 3000);
        
        console.log('Data polling started with interval ID:', simulationInterval);
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

    // Generate and return mock metrics
    async function getMetrics() {
        if (document.visibilityState !== 'visible') {
            console.log('Tab is not visible, skipping metrics update');
            return;
        }
        
        if (!isSimulationRunning) {
            console.log('Simulation not running, skipping metrics update');
            return;
        }
        
        console.log('Generating mock metrics...');
        
        try {
            // Generate mock metrics
            const mockMetrics = generateMockMetrics();
            console.log('Generated metrics:', mockMetrics);
            
            // Update the UI with mock metrics
            updateMetrics(mockMetrics);
            
            // Simulate network delay (0.5-1.5 seconds)
            const delay = 500 + Math.random() * 1000;
            await new Promise(resolve => setTimeout(resolve, delay));
        } catch (error) {
            console.error('Error in metrics generation:', error);
            toastr.error('Error generating metrics: ' + error.message, 'Error');
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
            // Helper function to safely update metric display
            const updateMetric = (elementId, value, suffix = '') => {
                const element = document.getElementById(elementId);
                if (element) {
                    // Handle different display formats
                    if (value === undefined || value === null || value === '') {
                        element.textContent = '--';
                    } else if (suffix === '%' || suffix === '°C' || suffix === '/5') {
                        // For percentages, temperatures, and stress levels, show one decimal place
                        element.textContent = `${parseFloat(value).toFixed(1)}${suffix}`.trim();
                    } else if (suffix === 'steps' || suffix === 'calories') {
                        // For steps and calories, show as whole numbers
                        element.textContent = `${Math.round(parseFloat(value))} ${suffix}`.trim();
                    } else if (suffix === 'hours') {
                        // For sleep duration, show one decimal place
                        element.textContent = `${parseFloat(value).toFixed(1)} ${suffix}`.trim();
                    } else {
                        // Default display
                        element.textContent = `${value} ${suffix}`.trim();
                    }
                }
            };
            
            // Update each metric with proper formatting
            updateMetric('heartRate', metrics.heart_rate, 'bpm');
            
            // Special handling for blood pressure
            if (metrics.blood_pressure_systolic !== undefined && metrics.blood_pressure_diastolic !== undefined) {
                updateMetric('bloodPressure', 
                    `${Math.round(metrics.blood_pressure_systolic)}/${Math.round(metrics.blood_pressure_diastolic)}`, 
                    'mmHg');
            } else {
                updateMetric('bloodPressure', null, 'mmHg');
            }
            
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
            
            // Update each chart with appropriate values
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
            toastr.error('Failed to update metrics display: ' + error.message, 'Error');
        }
    }

    // Initialize all charts
    function initializeCharts() {
        const chartConfigs = [
            { id: 'heartRateChart', label: 'Heart Rate', color: '#2196F3' },
            { id: 'bloodPressureChart', label: 'Blood Pressure', color: '#4CAF50' },
            { id: 'oxygenLevelChart', label: 'Oxygen Level', color: '#FF9800' },
            { id: 'temperatureChart', label: 'Temperature', color: '#E91E63' },
            { id: 'stepsChart', label: 'Steps', color: '#9C27B0' },
            { id: 'caloriesChart', label: 'Calories', color: '#3F51B5' },
            { id: 'sleepChart', label: 'Sleep', color: '#607D8B' },
            { id: 'stressChart', label: 'Stress Level', color: '#F44336' }
        ];
        
        chartConfigs.forEach(config => {
            const ctx = document.getElementById(config.id);
            if (!ctx) {
                console.warn(`Chart container not found for: ${config.id}`);
                return;
            }
            
            try {
                // Extract the metric name from the ID (remove 'Chart' suffix)
                const metric = config.id.replace('Chart', '');
                
                charts[metric] = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: config.label,
                            data: [],
                            borderColor: config.color,
                            backgroundColor: config.color + '20', // Add opacity to color
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false,
                            pointRadius: 2,
                            pointHoverRadius: 4
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 5
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: { size: 12 },
                                bodyFont: { size: 12 },
                                padding: 10,
                                displayColors: false
                            }
                        }
                    }
                });
                console.log(`Initialized chart: ${config.id}`);
            } catch (error) {
                console.error(`Failed to initialize chart ${config.id}:`, error);
            }
        });
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
            const timeLabel = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // Ensure we have valid data structures
            if (!chart.data || !chart.data.labels || !chart.data.datasets || !chart.data.datasets[0]) {
                console.error('Invalid chart data structure for:', metric);
                return;
            }
            
            // Add new data point
            chart.data.labels.push(timeLabel);
            chart.data.datasets[0].data.push(parseFloat(value));
            
            // Keep only the last 15 data points for better visibility
            const maxDataPoints = 15;
            if (chart.data.labels.length > maxDataPoints) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            
            // Update the chart with smooth animation
            chart.update({
                duration: 300,
                easing: 'easeOutQuart',
                lazy: true
            });
            
            // Update the last updated time (removed as the element doesn't exist in HTML)
            // Commented out to prevent errors
            // const lastUpdatedElement = document.getElementById(`lastUpdated-${metric}`);
            // if (lastUpdatedElement) {
            //     lastUpdatedElement.textContent = `Last updated: ${now.toLocaleTimeString()}`;
            // }
            
        } catch (error) {
            console.error(`Error updating ${metric} chart:`, error);
            // Don't show toastr here to avoid spamming the user
        }
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
