<?php
session_start();
include '../db-connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$accuracy = isset($_POST['accuracy']) ? round($_POST['accuracy'], 2) : null; // Round accuracy to 2 decimal places
$test_type = 'Оценка внимания'; // Specify the test type
$test_name = 'объем'; // Specify the test name

if ($user_id !== null && $accuracy !== null) {
    $stmt = $mysqli->prepare("SELECT id FROM tests WHERE test_type = ? AND test_name = ?");
    $stmt->bind_param("ss", $test_type, $test_name);
    $stmt->execute();
    $stmt->bind_result($test_id);
    $stmt->fetch();
    $stmt->close();

    if ($test_id !== null) {
        $stmt = $mysqli->prepare("INSERT INTO test_results (user_id, test_id, result) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $user_id, $test_id, $accuracy);
        $stmt->execute();
        $stmt->close();
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Тест на объем</title>
<style>
    body {
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f0f0f0;
        font-family: Arial, sans-serif;
        user-select: none; /* Prevent text selection */
        -webkit-user-select: none;
        -ms-user-select: none;
    }

    #canvas {
        display: none;
        border: 2px solid #000;
        border-radius: 50%;
    }

    #result-section {
        display: none;
        text-align: center;
    }

    #countdown {
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .hidden {
        display: none;
    }

    button {
        padding: 10px 20px;
        margin: 5px;
        font-size: 1rem;
        cursor: pointer;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
    }

    button:hover {
        background-color: #0056b3;
    }

    input {
        padding: 10px;
        margin: 5px;
        font-size: 1rem;
    }

    label {
        margin: 5px;
        font-size: 1rem;
    }

    .back-button {

        background-color: #dc3545;
    }

    .cancel-button {
        display: none;
        margin-top: 20px;
        background-color: #ffc107;
    }
</style>
</head>
<body>
    <div id="collisions-count"></div>
    <div id="instructions">
        <h1>Как устроен тест?</h1>
        <p>Считай количество касаний красных шариков</p>
    </div>
    <div id="settings-section">
        <div>
            <label for="time-input">Введите время (в секундах):</label>
            <input type="number" id="time-input" min="1">
        </div>
        <div>
            <label for="difficulty-select">Выберите сложность:</label>
            <select id="difficulty-select">
                <option value="1">Легко</option>
                <option value="2">Средне</option>
                <option value="3">Сложно</option>
            </select>
        </div>
    </div>
    <canvas id="canvas" width="400" height="400"></canvas>
    <button id="start-button">Старт</button>
    <button class="back-button" onclick="window.location.href='test.php'">Назад</button>
    <button id="cancel-button" class="cancel-button" onclick="cancelTest()">Отмена</button>
    <div id="countdown"></div>
    <div id="result-section">
        <label for="user-input" id="user-input-label">Введите количество касаний:</label>
        <input type="number" id="user-input" min="0">
        <button id="submit-button">Отправить</button>
        <p id="accuracy-result"></p>
        <p id="actual-collisions"></p>
        <p id="user-collisions"></p>
    </div>

<script>
    const canvas = document.getElementById('canvas');
    const collisionsCountElement = document.getElementById('collisions-count');
    const startButton = document.getElementById('start-button');
    const timeInput = document.getElementById('time-input');
    const difficultySelect = document.getElementById('difficulty-select');
    const settingsSection = document.getElementById('settings-section');
    const resultSection = document.getElementById('result-section');
    const userInput = document.getElementById('user-input');
    const userInputLabel = document.getElementById('user-input-label');
    const submitButton = document.getElementById('submit-button');
    const accuracyResult = document.getElementById('accuracy-result');
    const actualCollisions = document.getElementById('actual-collisions');
    const userCollisions = document.getElementById('user-collisions');
    const instructions = document.getElementById('instructions');
    const countdown = document.getElementById('countdown');
    const backButton = document.querySelector('.back-button');
    const cancelButton = document.getElementById('cancel-button');

    const ctx = canvas.getContext('2d');
    const bigRadius = 150; // Decreased size of the big circle
    const smallRadius = 15;
    const minSpeed = 0.5;
    const maxSpeed = 2;
    const balls = [];
    let ballsCount = 1;
    let intervalId;
    let countdownId;
    let collisionsCount = 0;
    let boundaryCollisionsCount = 0;
    let testOngoing = false; // Flag to track if the test is ongoing

    // Function to draw a ball
    function drawBall(x, y, radius, color) {
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.closePath();
    }

    // Function to draw the big circle and small circles
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawBall(canvas.width / 2, canvas.height / 2, bigRadius, 'rgba(0, 0, 0, 0.1)');
        for (const ball of balls) {
            drawBall(ball.x, ball.y, smallRadius, 'red');
        }
    }

    function handleCollision() {
        for (let i = 0; i < balls.length; i++) {
            const ball = balls[i];
            ball.x += ball.dx;
            ball.y += ball.dy;

            // Ensure balls don't stop moving
            if (Math.abs(ball.dx) < minSpeed) ball.dx = (Math.random() < 0.5 ? -1 : 1) * minSpeed;
            if (Math.abs(ball.dy) < minSpeed) ball.dy = (Math.random() < 0.5 ? -1 : 1) * minSpeed;

            // Randomly change speed
            if (Math.random() < 0.01) {
                ball.dx *= (Math.random() < 0.5 ? 0.5 : 1.5);
                ball.dy *= (Math.random() < 0.5 ? 0.5 : 1.5);
                ball.dx = Math.min(maxSpeed, Math.max(minSpeed, ball.dx));
                ball.dy = Math.min(maxSpeed, Math.max(minSpeed, ball.dy));
            }

            const distanceFromCenter = Math.sqrt((ball.x - canvas.width / 2) ** 2 + (ball.y - canvas.height / 2) ** 2);
            if (distanceFromCenter >= bigRadius - smallRadius) {
                const normalX = (ball.x - canvas.width / 2) / distanceFromCenter;
                const normalY = (ball.y - canvas.height / 2) / distanceFromCenter;
                const dotProduct = ball.dx * normalX + ball.dy * normalY;
                ball.dx -= 2 * dotProduct * normalX;
                ball.dy -= 2 * dotProduct * normalY;
                boundaryCollisionsCount++;
            }

            // Check for collisions with other balls
            for (let j = i + 1; j < balls.length; j++) {
                const otherBall = balls[j];
                const dx = otherBall.x - ball.x;
                const dy = otherBall.y - ball.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < smallRadius * 2) {
                    // Simple collision response by swapping velocities
                    const tempDx = ball.dx;
                    const tempDy = ball.dy;
                    ball.dx = otherBall.dx;
                    ball.dy = otherBall.dy;
                    otherBall.dx = tempDx;
                    otherBall.dy = tempDy;
                    collisionsCount++;
                }
            }
        }
    }

    function startAnimation() {
        intervalId = setInterval(function() {
            draw();
            handleCollision();
        }, 1000 / 60);
    }

    function resetTest() {
        collisionsCount = 0;
        boundaryCollisionsCount = 0;
        initializeBalls();
        draw();
    }

    function startCountdown(duration) {
        let timeLeft = duration;
        countdown.textContent = `Осталось времени: ${timeLeft}с`;
        countdownId = setInterval(function() {
            timeLeft--;
            countdown.textContent = `Осталось времени: ${timeLeft}с`;
            if (timeLeft <= 0) {
                clearInterval(countdownId);
                countdown.textContent = '';
            }
        }, 1000);
    }

    function promptCollisions() {
        resultSection.style.display = 'block';
        canvas.style.display = 'none';
        instructions.style.display = 'none';
        startButton.style.display = 'none';
        cancelButton.style.display = 'none';
        clearInterval(intervalId);
        clearInterval(countdownId);
        testOngoing = false;
    }

    function calculateAccuracy(userInput, correctCollisions) {
        let accuracy = (1 - Math.abs(userInput - correctCollisions) / correctCollisions).toFixed(2);
        return Math.max(0, accuracy); // Ensure accuracy is never negative
    }

    submitButton.addEventListener('click', function() {
        const userAnswer = parseInt(userInput.value);
        if (isNaN(userAnswer) || userAnswer === '') {
            alert('Пожалуйста, введите корректное количество касаний.');
            return;
        }
        const accuracy = calculateAccuracy(userAnswer, boundaryCollisionsCount);
        accuracyResult.textContent = `Точность: ${accuracy}`;
        actualCollisions.textContent = `Реальное количество столкновений: ${boundaryCollisionsCount}`;
        userCollisions.textContent = `Введенное количество столкновений: ${userAnswer}`;

        // Send accuracy and user_id to PHP for saving
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log(xhr.responseText);
            }
        };
        xhr.send("accuracy=" + accuracy);

        userInput.style.display = 'none';
        userInputLabel.style.display = 'none';
        submitButton.style.display = 'none';
        accuracyResult.style.display = 'block';
        actualCollisions.style.display = 'block';
        userCollisions.style.display = 'block';

        setTimeout(function() {
            accuracyResult.style.display = 'none';
            actualCollisions.style.display = 'none';
            userCollisions.style.display = 'none';
            settingsSection.style.display = 'block';
            instructions.style.display = 'block';
            startButton.style.display = 'block';
            backButton.style.display = 'block';
            userInput.value = '';
        }, 10000);
    });

    function initializeBalls() {
        balls.length = 0;
        for (let i = 0; i < ballsCount; i++) {
            const angle = Math.random() * 2 * Math.PI;
            const distance = (Math.random() * (bigRadius - smallRadius));
            let x = canvas.width / 2 + distance * Math.cos(angle);
            let y = canvas.height / 2 + distance * Math.sin(angle);
            let dx = (Math.random() < 0.5 ? -1 : 1) * (Math.random() * (maxSpeed - minSpeed) + minSpeed);
            let dy = (Math.random() < 0.5 ? -1 : 1) * (Math.random() * (maxSpeed - minSpeed) + minSpeed);

            balls.push({ x, y, dx, dy });
        }
    }

    startButton.addEventListener('click', function() {
        const selectedTime = parseInt(timeInput.value) * 1000;
        if (isNaN(selectedTime) || selectedTime <= 0) {
            alert('Пожалуйста, введите корректное время.');
            return;
        }
        canvas.style.display = 'block';
        settingsSection.style.display = 'none';
        backButton.style.display = 'none';
        cancelButton.style.display = 'block';
        ballsCount = difficultySelect.value;
        resetTest();
        startAnimation();
        startCountdown(selectedTime / 1000);
        testOngoing = true;
        setTimeout(promptCollisions, selectedTime);
    });

    function cancelTest() {
        clearInterval(intervalId);
        clearInterval(countdownId);
        countdown.textContent = '';
        if (testOngoing) {
            alert('Тест отменен.');
        }
        resetTest();
        canvas.style.display = 'none';
        resultSection.style.display = 'none';
        settingsSection.style.display = 'block';
        instructions.style.display = 'block';
        startButton.style.display = 'block';
        backButton.style.display = 'block';
        cancelButton.style.display = 'none';
        testOngoing = false;
    }

    // Prevent copy, screenshot, and tab switch
    document.addEventListener('copy', (e) => e.preventDefault());
    document.addEventListener('cut', (e) => e.preventDefault());
    document.addEventListener('paste', (e) => e.preventDefault());
    document.addEventListener('contextmenu', (e) => e.preventDefault());

    window.addEventListener('blur', () => {
        if (testOngoing) {
            cancelTest();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.keyCode == 44) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>
