<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced movement test</title>
    <link href='https://fonts.googleapis.com/css?family=Lato:100' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="../css/movement_test.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <style>
        .result {
            margin-top: 20px; /* Добавлен отступ сверху */
        }

        .timer {
            margin-top: 50px; /* Добавлен отступ сверху */
        }
    </style>
</head>

<body>
    <div id="time-input" style="padding-top:20px">
        Enter time: <input type="number" id="minutes" min="0" max="45"> minutes
        <input type="number" id="seconds" min="0" max="59"> seconds
    </div>
    <div id="start">
        <button id="start">Start</button>
    </div>
    <div id="container1" class="circle-container">
        <div id="pointer" class="pointer"></div>
        <div id="circle" class="circle">
            <div class="point"></div>
        </div>
        <div id="user-result1" class="result">Result: <span id="result"></span></div>
    </div>
    <div id="end" class="hidden">game over</div>
    <div id="time" class="timer"><span id="timer">00:00</span></div>
    <div class="container">
        <div class="score" style="background: #FFF5EE;">
            <div class="section-0">
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/movement_test.js"></script>
</body>

</html>
