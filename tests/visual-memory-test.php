<?php
session_start();
include('../db-connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $finalResult = $_POST['finalResult'];
    $user_id = $_SESSION['user_id'] ?? null;

    // Получение test_id по test_type и test_name
    $sql = "SELECT id FROM tests WHERE test_type = 'Оценка памяти' AND test_name = 'зрительная'";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $test_id = $row['id'];

        // Сохранение результата в test_results
        $stmt = $mysqli->prepare("INSERT INTO test_results (user_id, test_id, result) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $test_id, $finalResult);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Result saved successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error saving result."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Test not found."]);
    }

    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест на память</title>
    <link href='https://fonts.googleapis.com/css?family=Lato:100' rel='stylesheet' type='text/css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <style>
        html {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.5px;
            box-sizing: border-box;
        }

        body {
            background-color: #FFF5EE;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-y: auto;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.5px;
            margin: 0px;
            justify-content: center;
            align-items: center;
            text-align: center;
            -webkit-user-select: none; /* Safari */
            -moz-user-select: none; /* Firefox */
            -ms-user-select: none; /* IE10+/Edge */
            user-select: none; /* Standard */
        }

        .hidden {
            display: none;
        }

        .container {
            margin: 0 auto;
            padding: 30px;
            max-width: 800px;
            padding-bottom: 20px;
            padding-top: 20px;
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        p {
            font-size: 1.2em;
            text-align: center;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        input[type="text"],
        select {
            font-size: 1.2em;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: auto;
            margin-bottom: 20px;
        }

        button {
            background-color: #007bff;
            color: #fff;
            font-size: 1.2em;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none; 
        }
        body a {

    color: white; /* Белый цвет текста */
    text-decoration: none;

        }

        button.hidden {
            display: none;
        }

        p.result {
            font-size: 1.2em;
            margin-top: 20px;
            text-align: center;
        }

        .numbers {
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
        }

        .countdown {
            font-size: 1.2em;
            color: red;
        }
    </style>
    <style>
        /* Disable text selection */
        body {
            -webkit-user-select: none; /* Safari */
            -moz-user-select: none; /* Firefox */
            -ms-user-select: none; /* IE10+/Edge */
            user-select: none; /* Standard */
        }

        /* Disable copying and pasting */
        input, textarea {
            -webkit-user-select: none; /* Safari */
            -moz-user-select: none; /* Firefox */
            -ms-user-select: none; /* IE10+/Edge */
            user-select: none; /* Standard */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Тест на память</h1>
        <p class="text">Описание теста: В этом тесте вы должны будете запомнить и воспроизвести числа.</p>
        <p class="text">Инструкция: Нажмите "Далее", чтобы выбрать уровень сложности и время прохождения теста.</p>
        <button type="button" id="next">Далее</button>
        <br>
        <br>
        <button type="button" id="back"><a href="tests.php" id="back-button">Назад</a></button>

        <form class="hidden">
            <label for="difficulty">Выберите уровень сложности:</label>
            <select id="difficulty" name="difficulty" onchange="updateTimeOptions()">
                <option value="easy">Лёгкий</option>
                <option value="medium">Средний</option>
                <option value="hard">Сложный</option>
                <option value="random">Случайный</option>
                <option value="order">Порядок</option> <!-- Новая сложность -->
            </select>

            <label for="time">Выберите время прохождения:</label>
            <select id="time" name="time">
                <!-- Время будет обновлено на основе выбранной сложности -->
            </select>
            <label for="customTime">Или введите свое время (в секундах):</label>
            <input type="text" id="customTime" name="customTime">
            <br>
            <button type="button" id="start">Начать тест</button>
        </form>

        <div id="test-area" class="hidden">
            <div class="numbers"></div>
            <label for="answer" class="hidden">Введите числа в порядке по умолчанию через пробел.</label>
            <input type="text" id="answer" name="answer" class="hidden" required>
            <button type="button" id="check-answer" class="hidden">Проверить</button>
            <p class="result hidden"></p>
            <p class="correct-count">Всего правильных ответов: <span id="correct">0</span></p>
            <p class="countdown"></p>
            <button type="button" id="cancel">Отменить тест</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const numbersDiv = document.querySelector('.numbers');
        const countdownEl = document.querySelector('.countdown');
        const answerInput = document.querySelector('#answer');
        const resultP = document.querySelector('.result');
        const form = document.querySelector('form');
        const startBtn = document.querySelector('#start');
        const cancelBtn = document.querySelector('#cancel');
        const nextBtn = document.querySelector('#next');
        const backBtn = document.querySelector('#back');
        const testArea = document.getElementById('test-area');
        const correctCount = document.getElementById("correct");
        const checkAnswerBtn = document.querySelector('#check-answer');

        let reverse;
        let timerId;
        let countdownTimer;
        let testDuration;
        let correctAnswers = 0;
        let resultTimes = [];
        let correctRes = [];
        let test_id = 11;
        let testActive = false;
        let answerAccepted = false;

        let displayTime = 5000;
        let difficulty = "easy";
        let startTime;

        const timeOptions = {
            easy: [30000, 60000, 120000],
            medium: [60000, 120000, 180000],
            hard: [120000, 180000, 240000],
            random: [60000, 120000, 240000],
            order: [60000, 120000, 180000]
        };

        const difficultyScores = {
            easy: 1,
            medium: 2,
            hard: 3,
            random: 2, // Adjust score as necessary
            order: 2  // Adjust score as necessary
        };

        // Event listeners for preventing cheating
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && testActive) {
                cancelTest();
                alert('Тест был сброшен из-за смены вкладки или окна.');
            }
        });

        window.addEventListener('blur', () => {
            if (testActive) {
                cancelTest();
                alert('Тест был сброшен из-за перехода на другую вкладку.');
            }
        });

        window.addEventListener('focus', () => {
            // Optionally, handle return to focus if needed
        });

        function getRandomNumber() {
            return Math.random() < 0.5 ? 0 : 1;
        }

        function generateNumbers() {
            const numbers = [];
            let count;
            if (difficulty === "easy") {
                count = 2;
                while (numbers.length < count) {
                    numbers.push(Math.floor(Math.random() * 9) + 1); // Однозначные числа
                }
            } else if (difficulty === "medium") {
                count = 2;
                let randomChoice = Math.random();
                if (randomChoice < 0.5) {
                    numbers.push(Math.floor(Math.random() * 9) + 1); // Однозначное число
                    numbers.push(Math.floor(Math.random() * 90) + 10); // Двузначное число
                } else {
                    while (numbers.length < count) {
                        numbers.push(Math.floor(Math.random() * 90) + 10); // Двузначные числа
                    }
                }
            } else if (difficulty === "hard") {
                count = 3;
                while (numbers.length < count) {
                    numbers.push(Math.floor(Math.random() * 90) + 10); // Двузначные числа
                }
            } else if (difficulty === "order") {
                count = Math.floor(Math.random() * 3) + 1; // 1, 2 или 3 числа
                for (let i = 0; i < count; i++) {
                    const randomNum = Math.random() < 0.5 ? Math.floor(Math.random() * 9) + 1 : Math.floor(Math.random() * 90) + 10;
                    numbers.push(randomNum);
                }
            } else if (difficulty === "random") {
                count = Math.floor(Math.random() * 3) + 2;
                while (numbers.length < count) {
                    numbers.push(Math.floor(Math.random() * 100));
                }
            }
            return numbers;
        }

        function displayNumbers(numbers) {
            let numbersText = numbers.join(' ');
            numbersDiv.textContent = numbersText;
        }

        function updateTimeOptions() {
            difficulty = document.getElementById("difficulty").value;
            const timeSelect = document.getElementById("time");
            timeSelect.innerHTML = '';

            timeOptions[difficulty].forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = `${time / 1000} секунд`;
                timeSelect.appendChild(option);
            });

            displayTime = parseInt(timeSelect.value);
        }

        function startCountdown(duration, display, callback) {
            let timer = duration,
                minutes, seconds;
            countdownTimer = setInterval(() => {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(countdownTimer);
                    callback();
                }
            }, 1000);
        }

        function startLearnNumbers() {
            if (!testActive) return;
            let numbers = generateNumbers();
            displayNumbers(numbers);
            answerAccepted = false;

            numbersDiv.style.display = 'block';
            answerInput.classList.add('hidden');
            checkAnswerBtn.classList.add('hidden');
            document.querySelector('label[for="answer"]').classList.add('hidden');
            clearTimeout(timerId);
            timerId = setTimeout(() => {
                startGeneralTest();
            }, 5000);
        }

        function startGeneralTest() {
            if (!testActive) return;
            numbersDiv.style.display = 'none';
            answerInput.classList.remove('hidden');
            checkAnswerBtn.classList.remove('hidden');
            document.querySelector('label[for="answer"]').classList.remove('hidden');
            countdownEl.textContent = "";
            startTime = Date.now();

            reverse = getRandomNumber();
            let label = document.querySelector('label[for="answer"]');

            if (reverse === 0) {
                label.textContent = "Введите числа в порядке по умолчанию через пробел.";
            } else {
                label.textContent = "Введите числа в обратном порядке через пробел.";
            }

            answerInput.value = '';
            answerInput.focus();

            clearTimeout(timerId);
            timerId = setTimeout(() => {
                checkAnswer();
            }, 5000);
        }

        function checkAnswer() {
            if (!testActive || answerAccepted) return;
            answerAccepted = true;
            clearTimeout(timerId);
            const answer = answerInput.value.trim();
            let numbers = (reverse === 0) ? numbersDiv.textContent.split(' ').map(n => parseInt(n)) : numbersDiv.textContent.split(' ').map(n => parseInt(n)).reverse();
            let correct = numbers.every((num, index) => num === parseInt(answer.split(' ')[index]));
            const time = Date.now() - startTime;

            if (correct) {
                resultP.textContent = `Верно. Вы решили задание за ${time} мс.`;
                resultP.style.color = 'green';
                correctAnswers++;
            } else {
                resultP.textContent = `Неверно.`;
                resultP.style.color = 'red';
            }
            correctCount.textContent = correctAnswers;
            resultTimes.push(time);
            correctRes.push(correct ? 1 : 0);
            resultP.classList.remove('hidden'); // Убрать класс hidden здесь
            if (testActive && difficulty !== "order") {
                setTimeout(startLearnNumbers, 1000);
            }
        }

        function cancelTest() {
            clearTimeout(timerId);
            clearTimeout(countdownTimer);
            testActive = false;
            resetTest();
            document.querySelectorAll('.text').forEach(el => el.classList.remove('hidden')); // Show text after canceling test
        }

        function resetTest() {
            startBtn.classList.remove('hidden');
            form.classList.add('hidden');
            testArea.classList.add('hidden');
            nextBtn.classList.remove('hidden');
            backBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden'); // Make sure cancel button is visible
            correctAnswers = 0;
            correctCount.textContent = correctAnswers;
            countdownEl.textContent = "";
            resultP.textContent = ""; // Reset the result text
            clearTimeout(timerId);
            clearTimeout(countdownTimer);
        }

        function displayFinalResults() {
            // Скрыть все элементы внутри test-area, кроме result и correct-count
            const elementsToHide = testArea.querySelectorAll(':scope > :not(.result):not(.correct-count)');
            elementsToHide.forEach(element => {
                element.classList.add('hidden');
            });

            // Отобразить результат
            resultP.classList.remove('hidden');
            numbersDiv.style.display = 'none';
        }

        function calculateFinalScore() {
            const baseScore = correctAnswers * difficultyScores[difficulty];
            return (baseScore / (testDuration / 1000)).toFixed(2);
        }

        function displayFinalScore() {
            const score = calculateFinalScore();
            if (score > 0) {
                resultP.textContent = `Ваш финальный результат: ${score} баллов в секунду.`;
                resultP.style.color = 'blue';
                save(score);
            } else {
                resultP.textContent = `Вы не прошли тест`;
                resultP.style.color = 'red';
            }
            displayFinalResults();

            setTimeout(() => {
                document.querySelectorAll('.text').forEach(el => el.classList.remove('hidden'));
                resetTest();
            }, 10000);
        }

        function displayResultsDuringTest() {
            document.querySelector('.result').classList.remove('hidden');
            document.querySelector('.countdown').classList.remove('hidden');
        }

        nextBtn.addEventListener('click', () => {
            document.querySelectorAll('.text').forEach(el => el.classList.add('hidden'));
            nextBtn.classList.add('hidden');
            backBtn.classList.add('hidden');
            form.classList.remove('hidden');
        });

        startBtn.addEventListener('click', () => {
            difficulty = document.getElementById("difficulty").value;
            let customTime = document.getElementById("customTime").value;
            testDuration = customTime ? parseInt(customTime) * 1000 : parseInt(document.getElementById("time").value);
            correctAnswers = 0;
            correctCount.textContent = correctAnswers;
            form.classList.add('hidden');
            testArea.classList.remove('hidden');
            cancelBtn.classList.remove('hidden'); // Ensure cancel button is visible at start of test
            displayResultsDuringTest();
            testActive = true;
            startLearnNumbers();
            startCountdown(testDuration / 1000, countdownEl, () => {
                testActive = false;
                displayFinalScore();
            });
        });

        cancelBtn.addEventListener('click', cancelTest);

        checkAnswerBtn.addEventListener('click', (event) => {
            event.preventDefault();
            checkAnswer();
        });

        answerInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                checkAnswer();
            }
        });

        function save(score) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);  // POST запрос на тот же URL
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (this.status >= 200 && this.status < 300) {
                    console.log('Result saved successfully:', this.responseText);
                } else {
                    console.log('Failed to save result:', this.statusText);
                }
            };
            xhr.onerror = function () {
                console.log('Network error.');
            };
            xhr.send("finalResult=" + encodeURIComponent(score));
        }

        updateTimeOptions();
    </script>
</body>

</html>
