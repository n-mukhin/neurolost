<?php
session_start();

// Подключение к базе данных
require_once "../db_connect.php";

// Проверяем, была ли отправлена форма для сохранения результатов теста
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['avgReactionTime'])) {
    // Получаем среднее время реакций из POST-данных
    $avgReactionTime = $_POST['avgReactionTime'];

    // Отсутствие записи в таблицу sound_reaction_test
    echo "Нет записи в таблицу sound_reaction_test";
} else {

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/sound-test.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/background.css">
    <title>Sound Reaction Test</title>
</head>
<body>
<div class="background"></div>
<header>
    <p><a href="index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
<div class = "container">
<h2>Sound Reaction Test</h2>
<br>
<button id="startButton" onclick="startTest()">Start Test</button>
<p id="instruction" style="display: none;">Click the box when you hear the sound</p>
<button id="soundButton" style="display: none;">Услышал</button>
<audio id="sound" src="audio.mp3"></audio>
<p id="timer" style="display: none;">Reaction time: <span id="reactionTimeDisplay">0</span> seconds</p>
<p id="prevReactionTime" style="display: none;">Previous reaction time: <span id="prevReactionTimeDisplay">-</span> seconds</p>
<p id="avgReactionTime" style="display: none;">Average reaction time: <span id="avgReactionTimeDisplay">-</span> seconds</p>
<p id="changeCounter" style="display: none;">Sound plays left: <span id="changeCounterDisplay">10</span></p>
<button id="cancelButton" onclick="cancelTest()" style="display: none;">Cancel</button>
<p id="resultText"></p>
<br>
<a href="tests.php">Назад</a>
<br>
<br>
<a href="home.php">Домой</a>
</div>
</body>
<script src="../js/sound_reaction_test_script.js"></script>
</html>
