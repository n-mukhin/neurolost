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
    saveResult($userId, $finalScore);
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
    $testName = "классификация";
    
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
    <title>Тест на классификацию</title>
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
        <h1 id="testTitle">Тест на  классификацию</h1>
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
    { level: 'easy', question: 'Какой из перечисленных является фруктом?', options: ['Яблоко', 'Морковь'], answer: 'Яблоко', score: 1 },
    { level: 'easy', question: 'Какой из перечисленных является мебелью?', options: ['Стул', 'Ковер'], answer: 'Стул', score: 1 },
    { level: 'easy', question: 'Какой из перечисленных является видом транспорта?', options: ['Автомобиль', 'Каляска'], answer: 'Автомобиль', score: 1 },
    { level: 'easy', question: 'Какой из перечисленных является цветком?', options: ['Роза', 'Ива'], answer: 'Роза', score: 1 },

    // Medium level tasks
    { level: 'medium', question: 'Какой из перечисленных является видом спорта?', options: ['Футбол', 'Соревнование', 'Спор'], answer: 'Футбол', score: 2 },
    { level: 'medium', question: 'Какой из перечисленных является видом мебели?', options: ['Стол', 'Сейф', 'Сигнализация'], answer: 'Стол', score: 2 },
    { level: 'medium', question: 'Какой из перечисленных является видом фрукта?', options: ['Апельсин', 'Томат', 'Арбуз'], answer: 'Апельсин', score: 2 },
    { level: 'medium', question: 'Какой из перечисленных является видом музыкального инструмента?', options: ['Гитара', 'Струна', 'Мелодия'], answer: 'Гитара', score: 2 },

    // Hard level tasks
    { level: 'hard', question: 'Какие две эпохи истории Европы можно считать наиболее значимыми для формирования современной культуры? ', options: ['Античность и Средневековье', 'Средневековье и Возрождение', 'Возрождение и Просвещение', 'Просвещение и Новое время'], answer: 'Средневековье и Возрождение', score: 3 },
{ level: 'hard', question: 'Какие два процесса играют ключевую роль в формировании климата на Земле? ', options: ['Ветер и океанские течения', 'Солнечное излучение и географическое положение', 'Парниковый эффект и дыхание растений', 'Распределение воды и воздуха'], answer: 'Солнечное излучение и географическое положение', score: 3 },
{ level: 'hard', question: 'Какие два политических строя были преобладающими в древнем мире? ', options: ['Демократия и монархия', 'Теократия и диктатура', 'Олигархия и тоталитаризм', 'Федерализм и коммунизм'], answer: 'Демократия и монархия', score: 3 },
{ level: 'hard', question: 'Какие две страны были ключевыми участниками в Холодной войне? ', options: ['США и Германия', 'СССР и Япония', 'Франция и Китай', 'Великобритания и Италия'], answer: 'США и Германия', score: 3 },
{ level: 'hard', question: 'Какие две философские школы были наиболее влиятельными в античном мире? ', options: ['Стократики и эпикурейцы', 'Стойки и платоники', 'Софисты и киники', 'Аристотелевцы и пифагорейцы'], answer: 'Стойки и платоники', score: 3 }
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
