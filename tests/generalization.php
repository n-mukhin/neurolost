<?php
session_start();
require '../db-connect.php';

// Проверка и обработка AJAX запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalScore'])) {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        echo "Ошибка: идентификатор пользователя не установлен.";
        exit;
    }
    $finalScore = floatval($_POST['finalScore']);  // Получаем и конвертируем finalScore из строки в число
    if ($finalScore != 0) {
        saveResult($userId, $finalScore);
    } else {
        echo "Вы не прошли тест.";
    }
    exit;  // Завершаем скрипт после обработки AJAX запроса
}

function getTestId($testType, $testName) {
    require '../db-connect.php';
    $stmt = $mysqli->prepare("SELECT id FROM tests WHERE test_type = ? AND test_name = ?");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }
    $stmt->bind_param("ss", $testType, $testName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id'];
    } else {
        $stmt->close();
        return null; // Вернуть null, если не найдено совпадений
    }
}

function saveResult($userId, $finalScore) {
    require '../db-connect.php';
    $testType = "Оценка мышления";
    $testName = "Обобщение";
    
    $testId = getTestId($testType, $testName);
    if ($testId !== null) {
        $stmt = $mysqli->prepare("INSERT INTO test_results (user_id, test_id, result) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $stmt->bind_param("iid", $userId, $testId, $finalScore);
        if ($stmt->execute()) {
            echo "Результат успешно сохранен.";
        } else {
            echo "Ошибка при сохранении результата: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Test ID не найден для '$testType', '$testName'";
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тест на обобщение</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        button {
            padding: 10px;
            margin: 10px;
            cursor: pointer;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 id="testTitle">Тест на обобщение</h1>
        <p id="testDescription">Выберите ответы, которые лучше всего соответствуют заданным вопросам.</p>
        <div id="taskContainer" class="hidden"></div>
        <div id="correctAnswersContainer" class="hidden">Правильных ответов: 0</div>
        <div id="timerContainer" class="hidden"></div>
        <div id="resultContainer" class="hidden"></div>
        <button onclick="startTest()">Начать тест</button>
        <div id="statusMessage" class="hidden"></div>
        <button onclick="cancelTest()" class="hidden" style="background-color: red;">Отмена теста</button>
    </div>
    <script>
        let tasks = [
            // Easy level tasks
            { level: 'easy', question: 'Какие фрукты обычно кладут в пирог?', options: ['Яблоки', 'Бананы'], answer: 'Яблоки', score: 1 },
            { level: 'easy', question: 'Какие из этих животных являются домашними?', options: ['Кошки', 'Лисы'], answer: 'Кошки', score: 1 },
            { level: 'easy', question: 'Какое из этих растений можно есть?', options: ['Мухомор', 'Картофель'], answer: 'Картофель', score: 1 },
            { level: 'easy', question: 'Какой из этих предметов тяжелее?', options: ['Пух', 'Железо'], answer: 'Железо', score: 1 },
            { level: 'easy', question: 'Какое из этих средств передвижения быстрее?', options: ['Велосипед', 'Скутер'], answer: 'Скутер', score: 1 },

            // Medium level tasks
            { level: 'medium', question: 'Что общего у всех видов транспорта?', options: ['Перемещают людей и грузы', 'Имеют колеса', 'Используют двигатель'], answer: 'Перемещают людей и грузы', score: 2 },
            { level: 'medium', question: 'Что общего у всех праздников?', options: ['Подарки', 'Торжества', 'Праздничный стол'], answer: 'Торжества', score: 2 },
            { level: 'medium', question: 'Что общего у всех электронных устройств?', options: ['Используют электричество', 'Могут летать', 'Имеют экран'], answer: 'Используют электричество', score: 2 },
            { level: 'medium', question: 'Что общего у всех офисных работ?', options: ['Требуют компьютер', 'Требуют физической силы', 'Требуют офисные принадлежности'], answer: 'Требуют компьютер', score: 2 },
            { level: 'medium', question: 'Что общего у всех школ?', options: ['Учат плавать', 'Учат математике', 'Имеют учебники'], answer: 'Учат математике', score: 2 },

            // Hard level tasks
            { level: 'hard', question: 'Какое общее свойство у всех материалов, используемых для изготовления одежды?', options: ['Ткань', 'Цвет', 'Марка', 'Состав'], answer: 'Ткань', score: 3 },
            { level: 'hard', question: 'Что общего между книгой и фильмом?', options: ['Сюжет', 'Страницы', 'Автор', 'Жанр'], answer: 'Сюжет', score: 3 },
            { level: 'hard', question: 'Чем общее у всех приложений для смартфонов?', options: ['Могут звонить', 'Могут устанавливаться', 'Могут копировать текст', 'Могут обновляться'], answer: 'Могут устанавливаться', score: 3 },
            { level: 'hard', question: 'Что общего у всех спортивных игр?', options: ['Используют мяч', 'Имеют правила', 'Требуют команду', 'Тренируют физическую выносливость'], answer: 'Имеют правила', score: 3 },
            { level: 'hard', question: 'Что общего у всех источников энергии?', options: ['Производят тепло', 'Производят энергию', 'Имеют батарейки', 'Могут загрязнять окружающую среду'], answer: 'Производят энергию', score: 3 }
        ];
        let currentTaskIndex = 0;
        let correctAnswers = 0;
        let totalScore = 0;
        let startTime, endTime, timeLimit, interval;

        function startTest() {
            document.querySelector('button[onclick="startTest()"]').classList.add('hidden');
            showDifficultySelector();
        }

        function showDifficultySelector() {
            let selectorHTML = '<h2>Выберите уровень сложности:</h2>';
            ['easy', 'medium', 'hard', 'random'].forEach(level => {
                selectorHTML += `<button onclick="setDifficulty('${level}')">${level}</button>`;
            });
            document.getElementById('taskContainer').innerHTML = selectorHTML;
            document.getElementById('taskContainer').classList.remove('hidden');
        }

        function setDifficulty(level) {
            selectedTasks = level === 'random' ? tasks.sort(() => 0.5 - Math.random()) : tasks.filter(task => task.level === level);
            let timeOptions = level === 'easy' ? [30, 45, 60] : level === 'medium' ? [60, 90, 120] : [120, 150, 180];
            showTimeOptions(timeOptions);
        }

        function showTimeOptions(timeOptions) {
            let timeSelectorHTML = '<h2>Выберите время на выполнение теста:</h2>';
            timeOptions.forEach(time => {
                timeSelectorHTML += `<button onclick="setTimeLimit(${time})">${time} секунд</button>`;
            });
            document.getElementById('taskContainer').innerHTML = timeSelectorHTML;
        }

        function setTimeLimit(seconds) {
            timeLimit = seconds;
            startTasks();
        }

        function startTasks() {
            document.getElementById('testTitle').classList.add('hidden');
            document.getElementById('testDescription').classList.add('hidden');
            startTime = new Date();
            endTime = new Date(startTime.getTime() + timeLimit * 1000);
            document.getElementById('taskContainer').classList.add('hidden');
            document.getElementById('correctAnswersContainer').classList.remove('hidden');
            document.getElementById('resultContainer').classList.remove('hidden');
            document.querySelector('button[onclick="cancelTest()"]').classList.remove('hidden');
            startTimer();
            loadTask();
        }

        function startTimer() {
            interval = setInterval(() => {
                const now = new Date();
                const remaining = Math.round((endTime - now) / 1000);
                document.getElementById('timerContainer').innerHTML = `Осталось времени: ${remaining} секунд`;
                document.getElementById('timerContainer').classList.remove('hidden');
                if (remaining <= 0) {
                    clearInterval(interval);
                    endTest();
                }
            }, 1000);
        }

        function loadTask() {
            if (currentTaskIndex < selectedTasks.length) {
                const task = selectedTasks[currentTaskIndex];
                const optionsHtml = task.options.map(option => `<button onclick="submitAnswer('${option}', '${task.answer}', '${task.score}')">${option}</button>`).join('');
                document.getElementById('taskContainer').innerHTML = `<h2>${task.question}</h2>${optionsHtml}`;
                document.getElementById('taskContainer').classList.remove('hidden');
            } else {
                endTest();
            }
        }

        function submitAnswer(userAnswer, correctAnswer, score) {
            document.getElementById('taskContainer').classList.add('hidden');
            if (userAnswer === correctAnswer) {
                correctAnswers++;
                totalScore += parseInt(score); // Ensure 'score' is treated as a number
                document.getElementById('correctAnswersContainer').innerHTML = `Правильных ответов: ${correctAnswers}`;
                document.getElementById('resultContainer').innerHTML = 'Правильно!';
                currentTaskIndex++;
                if (currentTaskIndex < selectedTasks.length) {
                    loadTask();
                } else {
                    endTest();
                }
            } else {
                document.getElementById('resultContainer').innerHTML = 'Неправильно! Следующий вопрос...';
                currentTaskIndex++;
                if (currentTaskIndex < selectedTasks.length) {
                    setTimeout(loadTask, 1000);
                } else {
                    endTest();
                }
            }
        }

        function saveResult(finalScore) {
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
            xhr.send("finalScore=" + encodeURIComponent(finalScore));
        }

        function endTest() {
            clearInterval(interval);
            const now = new Date();
            const timeTaken = (now - startTime) / 1000; // Время в секундах
            const finalScore = totalScore / timeTaken; // Рассчитываем итоговый результат, учитывая сумму баллов

            document.getElementById('taskContainer').innerHTML = `<h2>Тест завершен</h2>
                <p>Ваш итоговый результат: ${finalScore.toFixed(2)} баллов в секунду.</p>
                <p>Затраченное время: ${timeTaken} секунд.</p>`;
            document.getElementById('taskContainer').classList.remove('hidden');
            document.getElementById('correctAnswersContainer').classList.add('hidden');
            document.getElementById('timerContainer').classList.add('hidden');
            document.getElementById('resultContainer').classList.add('hidden');
            document.getElementById('statusMessage').classList.add('hidden');
            document.querySelector('button[onclick="cancelTest()"]').classList.add('hidden');
            document.querySelector('button[onclick="startTest()"]').classList.remove('hidden');

            // Проверяем, есть ли набранные баллы перед сохранением результатов
            if (totalScore > 0) {
                saveResult(finalScore);
            } else {
                document.getElementById('taskContainer').innerHTML += "<p>Вы не прошли тест.</p>";
                console.log('No points scored. Result not saved.');
            }
        }
        // Защита от копирования, скриншотов и смены вкладок
document.addEventListener('copy', (e) => e.preventDefault());
document.addEventListener('cut', (e) => e.preventDefault());
document.addEventListener('paste', (e) => e.preventDefault());

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        cancelTest();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'PrintScreen') {
        e.preventDefault();
        alert('Скриншоты отключены.');
    }
});

window.addEventListener('blur', () => {
    alert('Не меняйте вкладки во время теста.');
    cancelTest();
});

function cancelTest() {
        clearInterval(interval);
        document.getElementById('testTitle').classList.remove('hidden');
        document.getElementById('testDescription').classList.remove('hidden');
        document.getElementById('taskContainer').classList.add('hidden');
        document.getElementById('correctAnswersContainer').classList.add('hidden');
        document.getElementById('timerContainer').classList.add('hidden');
        document.getElementById('resultContainer').classList.add('hidden');
        document.getElementById('statusMessage').classList.add('hidden');
        document.querySelector('button[onclick="cancelTest()"]').classList.add('hidden');
        document.querySelector('button[onclick="startTest()"]').classList.remove('hidden');
        document.getElementById('statusMessage').innerHTML = 'Тест отменен.';
        document.getElementById('statusMessage').classList.remove('hidden');

}
    </script>
</body>
</html>
