const soundButton = document.getElementById('soundButton');
const instruction = document.getElementById('instruction');
const timerDisplay = document.getElementById('reactionTimeDisplay');
const prevReactionTimeDisplay = document.getElementById('prevReactionTimeDisplay');
const avgReactionTimeDisplay = document.getElementById('avgReactionTimeDisplay');
const changeCounterDisplay = document.getElementById('changeCounterDisplay');
const resultText = document.getElementById('resultText'); // Добавлено
let startTime, endTime, prevReactionTime;
let reactionTimes = [];
let changeCount = 0;
let interval;

// Предварительная загрузка аудиофайла
const audio = document.getElementById('sound');
audio.volume = 0.05; // Установка громкости в 5%
audio.load();

function playSound() {
    audio.play();
}

// Глобальная функция для обработки клика
function handleClick() {
    soundButton.removeEventListener('click', handleClick);
    endTime = new Date();
    const reactionTime = (endTime - startTime) / 1000; // Convert to seconds and compensate for 50ms delay
    
    // Calculate and display average reaction time
    reactionTimes.push(reactionTime);
    const avgReactionTime = reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length;
    avgReactionTimeDisplay.textContent = avgReactionTime.toFixed(2);

    // Set previous reaction time display
    prevReactionTimeDisplay.textContent = prevReactionTime.toFixed(2);

    // Удаляем обработчик клика после первого срабатывания
    soundButton.onclick = null;

    if (changeCount >= 9) {
        saveReactionTime(avgReactionTime); // Отправить только среднее время реакции на сервер
        resultText.innerText = 'Результат теста: ' + avgReactionTime.toFixed(2); // Отображаем результат теста
        setTimeout(() => { // Задержка перед сбросом теста
            resetTest(); // Сброс теста
        }, 1000);
    } else {
        changeCount++;
        changeCounterDisplay.textContent = 10 - changeCount;
        setTimeout(setRandomSound, getRandomInterval()); // Случайный интервал между звуками
    }

    // Convert to seconds
    timerDisplay.textContent = reactionTime.toFixed(2);
}

function setRandomSound() {
    startTime = new Date();
    playSound();

    // Сохраняем предыдущее время реакции
    prevReactionTime = parseFloat(timerDisplay.textContent);
    if (!isNaN(prevReactionTime)) {
        prevReactionTimeDisplay.textContent = prevReactionTime.toFixed(2);
    }

    // Определяем обработчик клика
    soundButton.onclick = handleClick;
}

function saveReactionTime(avgReactionTime) {
    const formData = new FormData();
    formData.append('avgReactionTime', avgReactionTime); // Отправляем только среднее время реакции

    fetch('save_sound_reaction_test.php', {
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

function getRandomInterval() {
    // Генерируем случайный интервал между 2.5 и 5 секундами
    return Math.floor(Math.random() * (5000 - 2500) + 2500);
}

function startTest() {
    document.getElementById('startButton').style.display = 'none';
    
    instruction.style.display = 'block';
    soundButton.style.display = 'block';
    timerDisplay.parentElement.style.display = 'block'; // Показать элемент таймера
    prevReactionTimeDisplay.parentElement.style.display = 'block'; // Показать элемент предыдущего времени реакции
    avgReactionTimeDisplay.parentElement.style.display = 'block'; // Показать элемент среднего времени реакции
    changeCounterDisplay.parentElement.style.display = 'block'; // Показать элемент счетчика изменений

    // Отображаем кнопку "Cancel"
    document.getElementById('cancelButton').style.display = 'inline-block';

    setTimeout(setRandomSound, 3000); // Задержка в 3 секунды перед началом теста
}

function cancelTest() {
    clearInterval(interval); // Остановить интервал
    soundButton.onclick = null; // Удаляем обработчик клика на элементе soundButton
    resetTest(); // Сброс теста
}

function resetTest() {
    document.getElementById('startButton').style.display = 'block';
    document.getElementById('startButton').style.margin = 'auto'; // Центрировать кнопку
    instruction.style.display = 'none';
    soundButton.style.display = 'none';
    timerDisplay.parentElement.style.display = 'none'; // Скрыть элемент таймера
    prevReactionTimeDisplay.parentElement.style.display = 'none'; // Скрыть элемент предыдущего времени реакции
    avgReactionTimeDisplay.parentElement.style.display = 'none'; // Скрыть элемент среднего времени реакции
    changeCounterDisplay.parentElement.style.display = 'none'; // Скрыть элемент счетчика изменений
    document.getElementById('cancelButton').style.display = 'none'; // Скрываем кнопку "Cancel"
    reactionTimes = [];
    changeCount = 0;
    timerDisplay.textContent = '0';
    prevReactionTimeDisplay.textContent = '-';
    avgReactionTimeDisplay.textContent = '-';
    changeCounterDisplay.textContent = '10';
}
