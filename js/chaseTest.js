const circle = document.querySelector('#circle');
let timerId;
var startButton = document.getElementById("start");
let test_id = 8;
let sumDistance = 0; // Переменная для хранения суммы результатов
let count = 0; // Переменная для подсчета количества результатов

function updateTimer(timeLeft) {
    // Таймер для обновления каждую секунду
    var timer = setInterval(function () {
        timeLeft--;
        if (timeLeft >= 0) {
            var minutes = Math.floor(timeLeft / 60);
            var seconds = timeLeft % 60;
            var timeString = ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2); // форматировать время в виде "мм:сс"
            $("#timer").text(timeString);
        } else {
            circle.style.left = 0; // перемещение в центр
            canMoveCircle = false;
            isMoving = false;
            $("#circle").addClass("hidden");
            $(".green-circle").addClass("hidden");
            $("#message").addClass("hidden");
            $("#end").removeClass("hidden");
            clearInterval(timer);
            save(); // Сохраняем только среднее значение после завершения теста
        }
    }, 1000);
}

function startGame() {
    var minutes = parseInt($("#minutes").val());
    var seconds = parseInt($("#seconds").val());
    if ((isNaN(minutes) || isNaN(seconds)) || (minutes < 0) || (minutes > 45) || (seconds < 0) || (seconds > 59)) {
        alert("Введите время от 2 до 45 минут.");
        return;
    }
    $("#start").addClass("hidden");
    startButton.disabled = true;
    game();
    var timeLeft = minutes * 60 + seconds;
    var timeString = ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2); // форматировать время в виде "мм:сс"
    $("#timer").text(timeString);
    updateTimer(timeLeft);
}

function game() {
    setInterval(() => {
        const randomPosition = Math.random();
        const containerWidth = document.querySelector('#container').offsetWidth;
        const circleWidth = circle.offsetWidth;
        const leftPosition = randomPosition * (containerWidth);
        circle.style.left = leftPosition + 'px';
    }, 1500);

    const greenCircle = document.querySelector('.green-circle');
    document.addEventListener('mousemove', event => {
        const containerWidth = document.querySelector('#container').offsetWidth;
        const circleWidth = greenCircle.offsetWidth;
        const mouseX = event.clientX;
        const leftPosition = mouseX;
        if (leftPosition > 0 && leftPosition < containerWidth) {
            greenCircle.style.left = leftPosition + 'px';
        }
    });

    setInterval(() => {
        const redCircle = document.querySelector('#circle');
        const greenCircle = document.querySelector('.green-circle');
        const distance = Math.abs(redCircle.getBoundingClientRect().left - greenCircle.getBoundingClientRect().left) / 16; // 1 pixel = 0.0625cm
        document.querySelector('#message').innerHTML = `Distance: ${distance.toFixed(2)}cm`;
        sumDistance += distance; // Добавляем результат к сумме
        count++; // Увеличиваем счетчик
    }, 3000);
}

startButton.addEventListener('click', startGame);

function save() {
    const averageDistance = sumDistance / count; // Вычисляем среднее значение
    post('save_chase_results.php', { res: averageDistance.toFixed(2) }, method = 'post'); // Отправляем только среднее значение на сервер
}

function post(path, params, method = 'post') {
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
