<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovementTest</title>

    <link rel="stylesheet" href="../css/movement_test.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
</head>

<body>
    <header class="header" style="background: #FFF5EE;">
        <div class="header__wrapper">
            <h1 class="header__title" style="color: #000; border-bottom: 1px solid #000000;">
                Movement Test 
            </h1>
            <div class="header__text">
             Оценка простой реакции человека: 
                <p class="information" style="font-style: italic; font-size: 20px;">на движущийся объект</p>
            </div>
        </div>
    </header>     

    <div class="section">
        <div class="container">
            <div class="section-0">
                <button id="guideline-button">Guideline</button>
                <div id="popup" class="popup">
                    <div class="popup-content">
                        <span class="close" id="close-button">&times;</span>
                        <p>Это руководство по прохождению теста:</p>        
                        <p>+ Введите контрольное время в поле ниже (допустимо от 2 до 45 минут), затем нажмите клавишу Enter.</p>
                        <p>+ Нажмите клавишу Start(S), чтобы начать. Начнется обратный отсчет времени до 0.</p>
                        <p>+ В течение этого времени попробуйте нажать клавишу Catch(W), когда синий квадрат совпадет с серым квадратом посередине.</p>
                        <p>Чтобы повторить тест, перезагрузите этот сайт</p>
                        <p>Нажмите знак "x" или любое другое место за пределами всплывающего окна, чтобы закрыть учебное пособие.</p>
                    </div>
                </div>
            </div>
            <div class="section-0">
                <input type="text" id="hide-input" placeholder="введите время (от 2 до 45 минут)..." style="opacity: 0.5; margin-bottom: 50px">
            </div>
            <div id="bar-square">
                <div id="bar"></div>
                <div id="square"></div>
            </div>
            <div class="section-0">
                <button id="even-button" style="color:white; border:none">Catch (W)</button>
                <button id="stop" type="button">Stop Game (E)</button>
            </div>
            <div class="section-1">
                <button id="start" type="button">Start Game (S)</button>
                <p id="number-tap" style="color: #000;"></p>
                <p id="result" style="color: #000;"></p>
                <p id="average-result" style="color: #000;"></p>
            </div>
            <div class="section-2" style="padding: 10px 0px 10px 0px;">
                <p style="color: #000;">Остальное время: <span id="timeSet">?</span></p>
            </div>
        </div>
    </div>     

</body>

<script src="../js/Smovement_test.js"></script>

</html>