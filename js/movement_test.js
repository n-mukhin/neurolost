var startButton = document.getElementById("start");
var buttonPressed = false;
var tm;
const minSpeed = 500; // минимальная скорость вращения
const maxSpeed = 800; // максимальная скорость вращения
var tm2;
var interval;
var interval2;
var results = [];
let test_id = 7;
let correct = 0;

//-----------------------------------------------------------------

// Получить текущий угол поворота
(function($) {
    $.fn.rotationDegrees = function() {
        var matrix = this.css("-webkit-transform") ||
            this.css("-moz-transform") ||
            this.css("-ms-transform") ||
            this.css("-o-transform") ||
            this.css("transform");
        if (typeof matrix === 'string' && matrix !== 'none') {
            var values = matrix.split('(')[1].split(')')[0].split(',');
            var a = values[0];
            var b = values[1];
            var angle = Math.round(Math.atan2(b, a) * (180 / Math.PI));
        } else {
            var angle = 0;
        }
        return angle;
    };
}(jQuery));

jQuery.fn.rotate = function(degrees) {
    $(this).css({
        '-webkit-transform': 'rotate(' + degrees + 'deg)',
        '-moz-transform': 'rotate(' + degrees + 'deg)',
        '-ms-transform': 'rotate(' + degrees + 'deg)',
        'transform': 'rotate(' + degrees + 'deg)'
    });
    return $(this);
};

function updateTimer(timeLeft) {
    // Таймер для обновления каждую секунду
    var timer = setInterval(function() {
        timeLeft--;
        if (timeLeft >= 0) {
            var minutes = Math.floor(timeLeft / 60);
            var seconds = timeLeft % 60;
            var timeString = ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2); // форматировать время в виде "мм:сс"
            $("#timer").text(timeString);
        } else {
            clearInterval(timer);
            endGame();
        }
    }, 1000);
}

function startGame() {
    var minutes = parseInt($("#minutes").val());
    var seconds = parseInt($("#seconds").val());
    if ((isNaN(minutes) || isNaN(seconds)) || (minutes < 0) || (minutes > 45) || (seconds < 0) || (seconds > 59)) {
        alert("Введите время от 1 до 45 минут.");
        buttonPressed = false;
        return;
    }
    $("#start").addClass("hidden");
    startButton.disabled = true;
    buttonPressed = true;
    inaccuracy = 0;
    rotatePoint();
    var timeLeft = minutes * 60 + seconds;
    var timeString = ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2); // форматировать время в виде "мм:сс"
    $("#timer").text(timeString);
    updateTimer(timeLeft);
    $("#circle").rotate(rotationSpeed); // установить начальную скорость вращения
}

function endGame() {
    clearInterval(interval);
    save(results);
    $("#circle").addClass("hidden");
    $("#container > p").addClass("hidden");
    $("#user-result").addClass("hidden");
    $("#end").removeClass("hidden");
}

function checkTime() {
    setTimeout(function() {
        if (!pressed) {
            inaccuracy -= 30;
            rotatePoint();
        } else {
            pressed = false;
            clearTimeout();
        }
    }, tm);
}

function rotatePoint() {
    var speed = Math.floor(Math.random() * (maxSpeed - minSpeed + 1)) + minSpeed + inaccuracy;
    $("#circle").rotate(speed);
    tm = (6000 / (speed - inaccuracy)) * 1000;
    inaccuracy += 360;
    checkTime();
}

function checkAnswer() {
    var angle = $("#circle2").rotationDegrees();
    var unghi = $("#circle").rotationDegrees();
    if (unghi < angle + 30 && unghi > 0) {
        $("#result").text("+" + unghi + "°");
        results.push(unghi);
        inaccuracy -= unghi;
        rotatePoint();
    } else {
        if (unghi > angle - 30 && unghi < 0) {
            $("#result").text(unghi + "°");
            results.push(unghi);
            inaccuracy -= unghi;
            rotatePoint();
        } else {
            if (unghi === 0) {
                $("#result").text(0);
                results.push(0);
                rotatePoint();
            } else if (unghi < 0) {
                inaccuracy -= unghi;
                results.push(unghi);
                $("#result").text("Miss");
                rotatePoint();
            } else {
                inaccuracy += unghi;
                results.push(unghi);
                $("#result").text("Miss");
                rotatePoint();
            }
        }
    }
}

startButton.addEventListener("click", () => {
    startGame();
});

document.addEventListener("keydown", (event) => {
    if (event.code === "KeyS" && !buttonPressed) {
        startButton.click();
    } else if (event.code === "KeyW" && buttonPressed) {
        pressed = true;
        checkAnswer();
    }
});

function save(results) {
    // Вычисляем среднее значение из массива результатов
    const averageResult = calculateAverage(results);
    // Отправляем только среднее значение на сервер
    post('save_movement_test.php', { res: averageResult }, 'post');
}

function calculateAverage(results) {
    if (results.length === 0) {
        return 0; // Если массив результатов пуст, возвращаем 0
    }
    // Суммируем все результаты из массива
    const sum = results.reduce((acc, curr) => acc + curr, 0);
    // Вычисляем среднее значение
    const average = sum / results.length;
    return average;
}


function post(path, params, method='post') {
    const form = document.createElement('form');
    form.method = method;
    form.action = path;
     for (const key in params) {
      if (params.hasOwnProperty(key)) {
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = key;
        hiddenField.value = params[key];
         form.appendChild(hiddenField);
      }
    }
     document.body.appendChild(form);
    const xhr = new XMLHttpRequest();
    xhr.open(method, path);
 
 
    const formData = new FormData();
    for (const key in params) {
    if (params.hasOwnProperty(key)) {
        formData.append(key, params[key]);
    }
    }
    xhr.send(formData);
 }
