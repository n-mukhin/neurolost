<?php
session_start();
include '../db-connect.php';

if (isset($_SESSION['user_id']) && isset($_POST['score']) && $_POST['score'] > 0) {
    $user_id = $_SESSION['user_id'];
    $score = $_POST['score'];

    // Найти test_id
    $test_name = "переключаемость"; 
    $test_type = "Оценка внимания"; 

    $query = "SELECT id FROM tests WHERE test_name = ? AND test_type = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $test_name, $test_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $test = $result->fetch_assoc();

    if ($test) {
        $test_id = $test['id'];

        // Сохранить результат теста
        $query = "INSERT INTO test_results (user_id, test_id, result) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("iid", $user_id, $test_id, $score);
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест на переключение внимания</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .container {
            max-width: 800px;
            padding: 20px;
            display: none; /* Initially hidden */
        }

        h1 {
            font-size: 36px;
            text-align: center;
        }

        .box {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            border: 2px solid #ccc;
            margin-bottom: 20px;
        }

        .box-notice {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            height: 200px;
            border: 2px solid #ccc;
            margin-bottom: 20px;
            padding: 20px;
        }

        .box-notice p {
            margin: 10px 0;
            padding: 8px;
            border-bottom: 1px solid #ccc;
        }

        .box-notice p:last-child {
            border-bottom: none;
        }

        .big-letter {
            font-size: 72px;
            font-weight: bold;
        }

        .button-container {
            display: flex;
            justify-content: center;
        }

        button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .modal {
            display: block;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            text-align: center;
        }

        .result {
            font-size: 24px;
            margin-top: 20px;
        }

        .feedback {
            font-size: 24px;
            margin-bottom: 10px;
        }
        #button1 { background-color: #FF0000; }
        #button2 { background-color: #00FF00; }
        #button3 { background-color: #0000FF; }
    </style>
</head>
<body>

<!-- Модальное окно -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <h1>Тест на переключение внимания</h1>
        <h3>На экране будет квадрат, содержащий слова и 3 кнопки, соответствующие 3 цветам. Ячейка слова покажет цвет выражений слов (цвет слова может отличаться от его значения)</h3>
        <h3>Нажмите кнопку, чтобы выбрать цвет слова, не обращая внимания на значение слова</h3>
        <h2>Введите время теста:</h2>
        <input type="number" id="countdownInput" placeholder="Введите время (секунды)...">
        <h2>Выберите уровень сложности:</h2>
        <select id="difficultySelect">
            <option value="easy">Легкий</option>
            <option value="medium">Средний</option>
            <option value="hard">Сложный</option>
            <option value="random">Рандом</option>
            <option value="sequential">Порядок</option>
        </select>
        <button onclick="startCountdown()">Начать</button>
        <button onclick="goBack()">Назад</button>
        <h3>*После ввода времени браузер начнет отсчет от 3 до 0 для начала теста</h3>
    </div>
</div>

<div class="container" id="testContainer">

    <div class="box" id="boxContainer">
        <div class="big-letter" id="bigLetter">Загрузка...</div>
    </div>

    <div class="box-notice" id="boxNoticeContainer">
        <p id="feedback" class="feedback"></p>
        <p id="turn">Количество отвеченных вопросов: 0</p>
        <p id="countdownDisplay">Осталось...</p>
        <p id="correct_streak">Всего правильных ответов: 0</p>
    </div>

    <div class="button-container" id="buttonContainer">
    <button id="button1" onclick="chooseColor('z')" class="color-button">(Z) Красный</button>
<button id="button2" onclick="chooseColor('x')" class="color-button">(X) Зеленый</button>
<button id="button3" onclick="chooseColor('c')" class="color-button">(C) Синий</button>
        <button onclick="cancelTest()">Отменить тест</button>
    </div>

    <div class="result" id="resultDisplay"></div>
</div>

<script>
    let countdown;
    let countdownDisplay = document.getElementById('countdownDisplay');
    let turnDisplay = document.getElementById('turn');
    let correctStreakDisplay = document.getElementById('correct_streak');
    let bigLetter = document.getElementById('bigLetter');
    let feedback = document.getElementById('feedback');
    let resultDisplay = document.getElementById('resultDisplay');
    let correctStreak = 0;
    let turns = 0;
    let colors = ['#FF0000', '#00FF00', '#0000FF'];
    let colorWords = ['Красный', 'Зеленый', 'Синий'];
    let testContainer = document.getElementById('testContainer');
    let modal = document.getElementById('myModal');
    let boxContainer = document.getElementById('boxContainer');
    let boxNoticeContainer = document.getElementById('boxNoticeContainer');
    let buttonContainer = document.getElementById('buttonContainer');
    let currentColorIndex = -1;
    let interval;
    let responseReceived = false;
    let gameStarted = false;
    let difficultyWeights = {
        'easy': 1,
        'medium': 2,
        'hard': 3,
        'random': 1.5,
        'sequential': 2
    };
    let difficulty = 'easy';
    let totalTime;
    let score;

    function startCountdown() {
        let time = document.getElementById('countdownInput').value;
        difficulty = document.getElementById('difficultySelect').value;
        if (!time || time <= 0) {
            alert('Пожалуйста, введите время для теста.');
            return;
        }
        countdown = 3;
        modal.style.display = 'none';
        testContainer.style.display = 'block';
        gameStarted = false;
        let countdownInterval = setInterval(() => {
            bigLetter.textContent = countdown;
            countdown--;
            if (countdown < 0) {
                clearInterval(countdownInterval);
                bigLetter.textContent = 'Начали!';
                setTimeout(() => {
                    startGame(time);
                }, 1000);
            }
        }, 1000);
    }

    function startGame(time) {
        countdown = time;
        totalTime = time;
        score = 0;
        setDifficultyWords();
        gameStarted = true;
        interval = setInterval(function() {
            countdown--;
            countdownDisplay.textContent = 'Осталось ' + countdown + ' секунд';
            if (countdown <= 0) {
                clearInterval(interval);
                displayFinalResult();
            }
        }, 1000);
        changeBigLetterRandomly();
    }

    function setDifficultyWords() {
        switch (difficulty) {
            case 'easy':
                colorWords = ['Красный', 'Зеленый', 'Голубой'];
                break;
            case 'medium':
                colorWords = ['Красный', 'Зеленый', 'Голубой', 'Желтый', 'Фиолетовый', 'Бирюзовый'];
                break;
            case 'hard':
                colorWords = ['Красный', 'Зеленый', 'Голубой', 'Желтый', 'Фиолетовый', 'Бирюзовый', 'Оранжевый', 'Коричневый', 'Серый'];
                break;
        }
    }

    function chooseColor(key) {
        if (!gameStarted || responseReceived || countdown <= 0) {
            return; // Предотвращает прием более одного ответа при смене цвета и во время отсчета времени
        }
        responseReceived = true;
        
        const keyToColorIndex = {
            'z': 0, 'Z': 0, 'я': 0, 'Я': 0, // Красный
            'x': 1, 'X': 1, 'ч': 1, 'Ч': 1, // Зеленый
            'c': 2, 'C': 2, 'с': 2, 'С': 2  // Голубой
        };

        let selectedColorIndex = keyToColorIndex[key];
        if (selectedColorIndex === currentColorIndex) {
            correctStreak++;
            showFeedback(true);
        } else {
            showFeedback(false);
        }

        turns++;
        correctStreakDisplay.textContent = 'Всего правильных ответов: ' + correctStreak;
        turnDisplay.textContent = 'Количество отвеченных вопросов: ' + turns;
    }

    function changeBigLetterRandomly() {
        if (countdown <= 0) {
            return;
        }

        currentColorIndex = Math.floor(Math.random() * colors.length);
        let randomColor = colors[currentColorIndex];
        let randomWord = colorWords[Math.floor(Math.random() * colorWords.length)];

        bigLetter.textContent = randomWord;
        bigLetter.style.color = randomColor;
        feedback.textContent = '';
        responseReceived = false;

        setTimeout(changeBigLetterRandomly, getDisplayTime());
    }

    function getDisplayTime() {
        switch (difficulty) {
            case 'easy':
                return 3000; // 3 seconds
            case 'medium':
                return 2000; // 2 seconds
            case 'hard':
                return 1000; // 1 second
            default:
                return 3000; // Default to easy
        }
    }

    function showFeedback(isCorrect) {
        if (isCorrect) {
            feedback.textContent = 'Правильно';
            feedback.style.color = '#00FF00'; // Зеленый
        } else {
            feedback.textContent = 'Неправильно';
            feedback.style.color = '#FF0000'; // Красный
        }
    }

    function displayFinalResult() {
        score = (correctStreak * difficultyWeights[difficulty]) / totalTime;
        if (score === 0) {
            resultDisplay.innerHTML = 'Вы не прошли тест';
        } else {
            resultDisplay.innerHTML = `Всего правильных ответов: ${correctStreak}<br>Баллы в секунду: ${score.toFixed(2)}`;
            saveResult(score);
        }

        // Скрыть элементы кроме результата и отзыва
        boxContainer.style.display = 'none';
        boxNoticeContainer.style.display = 'none';
        buttonContainer.style.display = 'none';
        
        setTimeout(() => {
            modal.style.display = 'flex';
            resetTest();
        }, 10000); // Показать результаты в течение 10 секунд перед возвратом к модальному окну
    }

    function saveResult(score) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "switch-test.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("score=" + score);
    }

    function resetTest() {
        correctStreak = 0;
        turns = 0;
        correctStreakDisplay.textContent = 'Всего правильных ответов: 0';
        turnDisplay.textContent = 'Количество отвеченных вопросов: 0';
        bigLetter.textContent = 'Загрузка...';
        resultDisplay.innerHTML = '';
        boxContainer.style.display = 'block';
        boxNoticeContainer.style.display = 'block';
        buttonContainer.style.display = 'flex';
        document.getElementById('countdownInput').value = '';
        testContainer.style.display = 'none';
        modal.style.display = 'flex';
    }

    function cancelTest() {
        clearInterval(interval);
        resetTest();
    }

    function goBack() {
        window.location.href = "tests.php";
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'z' || event.key === 'Z' || event.key === 'я' || event.key === 'Я' ||
            event.key === 'x' || event.key === 'X' || event.key === 'ч' || event.key === 'Ч' ||
            event.key === 'c' || event.key === 'C' || event.key === 'с' || event.key === 'С') {
            chooseColor(event.key);
        }
    });

    function program() {
        // Начинаем с отображения модального окна
        modal.style.display = 'flex';
        testContainer.style.display = 'none';
    }

    program();
</script>

</body>
</html>

