(function() {
    // Создание HTML-структуры виджета
    const widgetContainer = document.createElement('div');
    widgetContainer.className = 'widget';

    const statsContainer = document.createElement('div');
    statsContainer.className = 'stats';

    const currentPulseDiv = document.createElement('div');
    currentPulseDiv.innerHTML = 'Current: <span id="currentPulse">N/A</span>';
    statsContainer.appendChild(currentPulseDiv);

    const maxPulseDiv = document.createElement('div');
    maxPulseDiv.innerHTML = 'Max: <span id="maxPulse">N/A</span>';
    statsContainer.appendChild(maxPulseDiv);

    const minPulseDiv = document.createElement('div');
    minPulseDiv.innerHTML = 'Min: <span id="minPulse">N/A</span>';
    statsContainer.appendChild(minPulseDiv);

    const avgPulseDiv = document.createElement('div');
    avgPulseDiv.innerHTML = 'Avg: <span id="avgPulse">N/A</span>';
    statsContainer.appendChild(avgPulseDiv);

    const timerDiv = document.createElement('div');
    timerDiv.innerHTML = 'Time: <span id="timer">0</span> seconds';
    statsContainer.appendChild(timerDiv);

    widgetContainer.appendChild(statsContainer);

    const canvas = document.createElement('canvas');
    canvas.id = 'pulseChart';
    widgetContainer.appendChild(canvas);

    const controlsContainer = document.createElement('div');
    controlsContainer.className = 'controls';

    const startButton = document.createElement('button');
    startButton.id = 'startButton';
    startButton.textContent = 'Запись';
    controlsContainer.appendChild(startButton);

    const cancelButton = document.createElement('button');
    cancelButton.id = 'cancelButton';
    cancelButton.textContent = 'Отмена';
    controlsContainer.appendChild(cancelButton);

    const saveButton = document.createElement('button');
    saveButton.id = 'saveButton';
    saveButton.textContent = 'Сохранить';
    controlsContainer.appendChild(saveButton);

    widgetContainer.appendChild(controlsContainer);

    document.body.appendChild(widgetContainer);

    // Создание стилей для виджета
    const style = document.createElement('style');
    style.innerHTML = `
    .widget {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 450px;
        height: 300px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px 8px 0 0;
        padding: 10px;
        z-index: 1000;
    }
    .stats {
        font-family: Arial, sans-serif;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        color: black;
    }
    .controls {
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }
    .controls button {
        padding: 5px 10px;
        font-size: 12px;
    }
    #pulseChart {
        width: 100%;
        height: 150px;
    }
    `;
    document.head.appendChild(style);

    // Логика виджета
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = () => {
        const ctx = document.getElementById('pulseChart').getContext('2d');
        const pulseChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Pulse',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Pulse'
                        }
                    }
                }
            }
        });

        const currentPulseElement = document.getElementById('currentPulse');
        const maxPulseElement = document.getElementById('maxPulse');
        const minPulseElement = document.getElementById('minPulse');
        const avgPulseElement = document.getElementById('avgPulse');
        const timerElement = document.getElementById('timer');

        let pulseValues = [];
        let recording = false;
        let intervalId;
        let startTime;
        let elapsedTime = 0;
        let timerIntervalId;

        function updateTimer() {
            elapsedTime = Math.floor((Date.now() - startTime) / 1000);
            timerElement.innerText = elapsedTime;
        }

        async function fetchPulseData() {
            const response = await fetch('../Puppeteer/pulse-data.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            console.log('Fetched data:', data);  // Debugging line

            if (data.pulse !== null && recording) {
                const pulse = parseInt(data.pulse, 10);
                const currentTime = new Date().toLocaleTimeString();

                // Update chart
                pulseChart.data.labels.push(currentTime);
                pulseChart.data.datasets[0].data.push(pulse);
                pulseChart.update();

                // Update pulse values array
                pulseValues.push(pulse);

                // Update stats
                currentPulseElement.innerText = pulse;
                maxPulseElement.innerText = Math.max(...pulseValues);
                minPulseElement.innerText = Math.min(...pulseValues);
                avgPulseElement.innerText = (pulseValues.reduce((a, b) => a + b, 0) / pulseValues.length).toFixed(2);
            } else {
                console.error('No pulse data found');
            }
        }

        startButton.addEventListener('click', () => {
            recording = true;
            startTime = Date.now();
            if (!intervalId) {
                intervalId = setInterval(fetchPulseData, 5000);
                timerIntervalId = setInterval(updateTimer, 1000);
            }
        });

        pauseButton.addEventListener('click', () => {
            recording = false;
            clearInterval(timerIntervalId);
        });

        cancelButton.addEventListener('click', () => {
            recording = false;
            pulseValues = [];
            pulseChart.data.labels = [];
            pulseChart.data.datasets[0].data = [];
            pulseChart.update();
            currentPulseElement.innerText = 'N/A';
            maxPulseElement.innerText = 'N/A';
            minPulseElement.innerText = 'N/A';
            avgPulseElement.innerText = 'N/A';
            clearInterval(timerIntervalId);
            elapsedTime = 0;
            timerElement.innerText = elapsedTime;
        });

        saveButton.addEventListener('click', async () => {
            if (pulseValues.length > 0) {
                const maxPulse = Math.max(...pulseValues);
                const minPulse = Math.min(...pulseValues);
                const avgPulse = (pulseValues.reduce((a, b) => a + b, 0) / pulseValues.length).toFixed(2);

                const response = await fetch('../Puppeteer/save-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ maxPulse, minPulse, avgPulse, timeRecorded: elapsedTime })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Data saved successfully!');
                } else {
                    alert('Failed to save data');
                }
            } else {
                alert('No data to save');
            }
        });

        // Initial fetch to set up the chart
        fetchPulseData();
    };
    document.body.appendChild(script);
})();
