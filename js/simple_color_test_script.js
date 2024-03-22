const colorBox = document.getElementById('colorBox');
const instruction = document.getElementById('instruction');
const timerDisplay = document.getElementById('reactionTimeDisplay');
const prevReactionTimeDisplay = document.getElementById('prevReactionTimeDisplay');
const avgReactionTimeDisplay = document.getElementById('avgReactionTimeDisplay');
const changeCounterDisplay = document.getElementById('changeCounterDisplay');
const resultText = document.getElementById('resultText');
const header = document.getElementById('header');

let startTime, endTime, prevReactionTime;
let reactionTimes = [];
let changeCount = 0;
let greenAppearances = 0;
let interval;
// Функция для установки зеленого цвета, записи времени начала реакции и установки обработчика клика

// Глобальная переменная для хранения предыдущего времени реакции

// Флаг для проверки первого нажатия на текущий зеленый квадрат
let greenClickHandled = false;

// Глобальная функция для обработки клика
function handleClick() {
    endTime = new Date();
    const reactionTime = (endTime - startTime) / 1000; // Convert to seconds
    timerDisplay.textContent = reactionTime.toFixed(2);

    reactionTimes.push(reactionTime);

    // Обновляем значение prevReactionTime перед вызовом функции updatePrevReactionTime()
    const prevReaction = prevReactionTime; // Сохраняем предыдущее время реакции в отдельной переменной
    updatePrevReactionTime(reactionTime); // Обновляем значение предыдущего времени реакции перед вызовом handleClick()

    // Calculate and display average reaction time
    const avgReactionTime = reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length;
    avgReactionTimeDisplay.textContent = avgReactionTime.toFixed(2);
}

// Функция для установки зеленого цвета, записи времени начала реакции и установки обработчика клика
function setGreenColor() {
    colorBox.style.backgroundColor = 'green';
    startTime = new Date(); // Записываем время начала реакции
    greenClickHandled = false; // Сбрасываем флаг

    // Генерируем случайную задержку от 3 до 5 секунд перед следующим зеленым цветом
    const delay = Math.floor(Math.random() * (5000 - 3000 + 1)) + 3000;
    setTimeout(() => {
        colorBox.style.backgroundColor = 'black'; // Возвращаем цвет квадрата на черный
        greenAppearances++;
        updateChangeCounter(); // Обновляем счетчик появлений зеленого цвета
        if (greenAppearances < 10) {
            setTimeout(alternateColors, 1000); // Устанавливаем задержку перед вызовом alternateColors()
        } else {
            const avgReactionTime = reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length;
            saveReactionTime(avgReactionTime); // Отправить только среднее время реакции на сервер
            resultText.innerHTML = '<br><br>Результат теста: ' + avgReactionTime.toFixed(2); // Отображаем результат теста
            resetTest(); // Сброс теста
        }
    }, delay); // Устанавливаем случайную задержку

    // Устанавливаем обработчик клика только на зеленом квадрате
    colorBox.onclick = function() {
        // Проверяем, что квадрат зеленый и флаг еще не был установлен
        if (colorBox.style.backgroundColor === 'green' && !greenClickHandled) {
            handleClick(); // Вызываем функцию обработки клика
            greenClickHandled = true; // Устанавливаем флаг
        }
    };
}

// Функция для чередования цветов
function alternateColors() {
    if (greenAppearances < 10) {
        setTimeout(() => {
            if (colorBox.style.backgroundColor === 'green') {
                colorBox.style.backgroundColor = 'black'; // Возвращаем цвет квадрата на черный
                greenAppearances++;
                updateChangeCounter(); // Обновляем счетчик появлений зеленого цвета
                setGreenColor(); // Устанавливаем зеленый цвет
            } else {
                setGreenColor(); // Если цвет не зеленый, устанавливаем его заново
            }
            // handleClick() // Не нужно вызывать handleClick() здесь
        }, 1000); // Ждем 1 секунду перед возвратом к черному цвету
    } else {
        const avgReactionTime = reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length;
        saveReactionTime(avgReactionTime); // Отправить только среднее время реакции на сервер
        resultText.innerHTML = '<br><br>Результат теста: ' + avgReactionTime.toFixed(2); // Отображаем результат теста
        resetTest(); // Сброс теста
    }
}


// Функция для обновления счетчика изменений
function updateChangeCounter() {
    changeCounterDisplay.textContent = 10 - greenAppearances; // Обновляем значение счетчика
}


// Глобальная функция для обновления предыдущего времени реакции
function updatePrevReactionTime(reactionTime) {
    // Предыдущее время реакции обновляется только в случае, если уже есть какое-то значение предыдущего времени
    if (prevReactionTime !== undefined) {
        prevReactionTimeDisplay.textContent = prevReactionTime.toFixed(2); // Отображаем его на странице
    }
    prevReactionTime = reactionTime; // Обновляем значение предыдущего времени реакции
}


// Функция для начала теста
function startTest() {
    document.getElementById('startButton').style.display = 'none';
    document.getElementById('test-heading').style.display = 'none';
    document.getElementById('header').style.display = 'none';

    instruction.style.display = 'block';
    colorBox.style.display = 'block';
    timerDisplay.parentElement.style.display = 'block'; // Показать элемент таймера
    prevReactionTimeDisplay.parentElement.style.display = 'block'; // Показать элемент предыдущего времени реакции
    avgReactionTimeDisplay.parentElement.style.display = 'block'; // Показать элемент среднего времени реакции
    changeCounterDisplay.parentElement.style.display = 'block'; // Показать элемент счетчика изменений

    // Устанавливаем обработчик клика на кнопку "Cancel"
    document.getElementById('cancelButton').addEventListener('click', cancelTest);

    // Отображаем кнопку "Cancel"
    document.getElementById('cancelButton').style.display = 'inline-block';

    // Начинаем чередование цветов
    alternateColors();

    // Запускаем интервал и сохраняем его в переменной interval
    interval = setInterval(() => {
        // Отображаем предыдущее время реакции, если оно есть
        if (prevReactionTime !== undefined) {
            prevReactionTimeDisplay.textContent = prevReactionTime.toFixed(2);
        }
    }, 100); // Периодичность обновления - каждые 100 миллисекунд
}

// Функция для отмены теста
// Функция для отмены теста
function cancelTest() {
    clearInterval(interval); // Остановить интервал
    reactionTimes = []; // Очистить массив времен реакции
    resetTest(); // Сброс теста
}

// Функция для сброса теста
function resetTest() {
    document.getElementById('startButton').style.display = 'block';
    document.getElementById('startButton').style.margin = 'auto'; // Центрировать кнопку
    document.getElementById('cancelButton').style.display = 'none';
    document.getElementById('test-heading').style.display = 'block';
    document.getElementById('header').style.display = 'block';
    instruction.style.display = 'none';
    colorBox.style.display = 'none';
    timerDisplay.parentElement.style.display = 'none'; // Скрыть элемент таймера
    prevReactionTimeDisplay.parentElement.style.display = 'none'; // Скрыть элемент предыдущего времени реакции
    avgReactionTimeDisplay.parentElement.style.display = 'none'; // Скрыть элемент среднего времени реакции
    changeCounterDisplay.parentElement.style.display = 'none'; // Скрыть элемент счетчика изменений
    resultText.style.display = 'block';
    resultText.classList.add('result-text'); // Применить стили к элементу результата теста
    reactionTimes = [];
    changeCount = 0;
    greenAppearances = 0; // Сброс счетчика появлений зеленого цвета
    timerDisplay.textContent = '0';
    avgReactionTimeDisplay.textContent = '-';
    changeCounterDisplay.textContent = '10';
}
// Функция для сохранения времени реакции
function saveReactionTime(avgReactionTime) {
    const formData = new FormData();
    formData.append('avgReactionTime', avgReactionTime); // Отправляем только среднее время реакции

    fetch('save_simple_color_test.php', {
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

