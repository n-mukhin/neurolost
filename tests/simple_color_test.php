<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/color-test.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/background.css">
    <title>Оценка скорости реакции на цвет</title>
</head>
<body>
    <div class="background"></div>
<header id="header">
        <p><a href="../index.php">Домой</a></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="../account.php">Личный кабинет</a></p>
        <?php endif; ?>
    </header>
    <div class = "container">
    <h2 id="test-heading">Оценка скорости реакции на цвет</h2>
    <button id="startButton" onclick="startTest()">Начать</button>
    <p id="instruction" style="display: none;">Нажмите когда цвет изменится</p>
    <div class="colorbox" id="colorBox" style="display: none;"></div>
    <p id="changeCounter" style="display: none;">Смен цветов осталось <span id="changeCounterDisplay">10</span></p>
    <p id="timer"style="display: none;">Время реакции: <span id="reactionTimeDisplay">0</span> секунд(ы)</p>
    <p id="prevReactionTime"style="display: none;">Прошлое время реакции: <span id="prevReactionTimeDisplay">-</span> секунд(ы)</p>
    <p id="avgReactionTime" style="display: none;">Среднее время реакции: <span id="avgReactionTimeDisplay">-</span> секунд(ы)</p>
    <button id="cancelButton" onclick="cancelTest()" style="display: none;">Остановить тест</button>
    <p id="resultText" class="result-text"></p>
    <br>
    <a href="tests.php">Вернутся</a>
    <br>

    </div>
</body>
<script src="../js/simple_color_test_script.js"></script>
</html>

