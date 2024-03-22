<!DOCTYPE html>
<html>
<head>
  <title>Оценка скорости реакции на разные цвета</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <link rel="stylesheet" href="../css/color-test.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/background.css">
</head>
<body>
<div class="background"></div>
<header id="header">
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
<div class="container">
  <h2 id="test-heading">Оценка скорости реакции на разные цвета</h2>
  <br>
    
  
  <button id="startButton" onclick="startTest()">Начать тест</button>
  <div id="test-section" style="display: none;"> <!-- Скрываем секцию теста по умолчанию -->
    <div class="colorbox" id="colorBox"></div>
    <div id="counter">Оставшиеся смены цвета: <span id="changeCounter">10</span></div>
    <br>
 
    <button id="purple-button" onclick="checkColor('#A324DB')">(Z) Фиолетовый</button>
    <button id="orange-button" onclick="checkColor('#DBA324')">(X) Оранжевый</button>
    <button id="cyan-button" onclick="checkColor('#24DBA3')">(C) Бирюзовый</button>
    </div>
    <br>
    <br>
    
  <div id="result"></div>
    <button id="cancelButton" onclick="cancelTest()" style="display: none;">Отменить тест</button>
    <br>
    <br>
    <a href="tests.php">Вернутся</a>
</div>
<script>
  var colors = ['#A324DB', '#DBA324', '#24DBA3'];
  var remainingChanges = 10;
  var totalReactionTime = 0;
  var correctColor;
  var startTime;
  var previousReactionTime = 0;
  var colorChanged = false;
  var resultDisplayed = false;
  var isBlack = true;
  var testActive = false;

  $(document).on('keydown', function(event) {
  if (testActive && (
    (event.key === 'z' || event.key === 'я' || event.key === 'Z' || event.key === 'Я') ||
    (event.key === 'x' || event.key === 'ч' || event.key === 'X' || event.key === 'Ч') ||
    (event.key === 'c' || event.key === 'с' || event.key === 'C' || event.key === 'С')
  )) {
    event.preventDefault(); // Отменяем действие по умолчанию
    var buttonKey = '';
    if (event.key === 'z' || event.key === 'я' || event.key === 'Z' || event.key === 'Я') {
      buttonKey = 'z';
    } else if (event.key === 'x' || event.key === 'ч' || event.key === 'X' || event.key === 'Ч') {
      buttonKey = 'x';
    } else if (event.key === 'c' || event.key === 'с' || event.key === 'C' || event.key === 'С') {
      buttonKey = 'c';
    }
    $('#' + buttonKey + '-button').focus(); // Фокусировка на кнопке соответствующей нажатой клавише
    checkColorByKey(buttonKey);
  }
});



  function startTest() {
    testActive = true;
    $('#test-heading').hide();
    $('#header').hide();
    $('#startButton').hide();
    $('#cancelButton').show();
    $('#test-section').show();
    remainingChanges = 10;
    totalReactionTime = 0;
    startTime = null;
    correctColor = null;
    $('#result').html('');
    $('#changeCounter').text(remainingChanges);
    resultDisplayed = false;
    changeColor();
  }

  function cancelTest() {
    testActive = false;
    $('#test-heading').show();
    $('#startButton').show();
    $('#cancelButton').hide();
    $('#test-section').hide();
    remainingChanges = 10;
    $('#result').html('');
    $('#startButton').focus();
    }

  function checkColor(color) {
    if (color === correctColor && colorChanged) {
      registerReaction();
      colorChanged = false;
    }
  }

  function checkColorByKey(key) {
    if (key === 'z') {
      checkColor('#A324DB');
    } else if (key === 'x') {
      checkColor('#DBA324');
    } else if (key === 'c') {
      checkColor('#24DBA3');
    }
  }
  function saveReactionTime(averageReactionTime) {
    const formData = new FormData();
    formData.append('averageReactionTime', averageReactionTime); // Отправляем только среднее время реакции

    fetch('save_advanced_color_test.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(data => {
        console.log(data); // Журнал ответа сервера
    })
    .catch(error => {
        console.error('Проблема с операцией fetch:', error);
    });
}

function registerReaction() {
    var endTime = new Date().getTime();
    var reactionTime = (endTime - startTime) / 1000;
    totalReactionTime += reactionTime;
    $('#result').html(
        'Текущая реакция: ' + reactionTime.toFixed(2) + ' секунд<br>' +
        'Предыдущее время реакции: ' + previousReactionTime.toFixed(2) + ' секунд<br>' +
        'Среднее время реакции за тест: ' + (totalReactionTime / (10 - remainingChanges)).toFixed(2) + ' секунд<br><br>');
    previousReactionTime = reactionTime;

}

  function changeColor() {
    if (remainingChanges > 0 && testActive) {
      var delay;
      if (isBlack) {
        delay = 3000;
      } else {
        delay = Math.floor(Math.random() * (5000 - 3000 + 1)) + 3000;
      }
      setTimeout(function() {
        var randomColor;
        if (isBlack) {
          randomColor = 'black';
        } else {
          randomColor = colors[Math.floor(Math.random() * colors.length)];
        }
        $('#colorBox').css('background-color', randomColor);
        correctColor = randomColor;
        isBlack = !isBlack;
        if (correctColor !== 'black') {
          colorChanged = true;
          startTime = new Date().getTime();
          remainingChanges--;
          $('#changeCounter').text(remainingChanges);
        }
        $('#' + correctColor.substring(1, 7) + '-button').focus(); // Установка фокуса на кнопке с соответствующим цветом
        changeColor();
      }, delay);
    } else {
      $('#cancelButton').hide();
      $('#test-section').hide();
      $('#startButton').show();
      $('#test-heading').show();
      testActive = false;
      showResult();
    }
  }
  function showResult(averageReactionTime) {
    if (remainingChanges == 0) {
        var averageReactionTime = totalReactionTime / 10;
        saveReactionTime(averageReactionTime); // Отправка среднего времени реакции на сервер
        $('#result').html('<br>Среднее время реакции за тест: ' + averageReactionTime.toFixed(2) + ' секунд<br>');
        $('#result').show(); // Показываем элемент с результатом}
        $('#header').show();
    }
}

</script>
</body>
</html>
