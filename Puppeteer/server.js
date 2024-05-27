const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

const wss = new WebSocket.Server({ port: 8080 });

wss.on('connection', ws => {
    console.log('Client connected');

    const interval = setInterval(() => {
        fs.readFile('pulse_data.json', 'utf8', (err, data) => {
            if (err) {
                console.error('Error reading pulse data:', err);
                return;
            }
            const pulseData = JSON.parse(data);
            if (pulseData.pulse !== null) {
                ws.send(JSON.stringify(pulseData));
            }
        });
    }, 5000);

    ws.on('close', () => {
        console.log('Client disconnected');
        clearInterval(interval);
    });
});

console.log('WebSocket server started on ws://localhost:8080');
