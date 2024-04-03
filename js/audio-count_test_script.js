const startButton = document.getElementById('startButton');
const cancelButton = document.getElementById('cancelButton');
const buttonContainer = document.querySelector('.button-container');
const evenButton = document.getElementById('evenButton');
const oddButton = document.getElementById('oddButton');
const resultDisplay = document.getElementById('result');
const timerDisplay = document.getElementById('timer');
const previousReactionTimeDisplay = document.getElementById('previousReactionTime');
const currentReactionTimeDisplay = document.getElementById('currentReactionTime');
const averageReactionTimeDisplay = document.getElementById('averageReactionTime');
let reactionTimes = []; // Массив времен реакции
let questions = []; // Массив вопросов
let countdown;
let currentSum;
let questionsRemaining = 10; // Общее количество вопросов
let isAnsweredCorrectly = false; // Переменная для отслеживания правильности ответа
let isReactionRecorded = false; // Переменная для отслеживания фиксации времени реакции
let questionStartTime; // Переменная для хранения времени начала вопроса

// Функция для генерации вопросов
function generateQuestions() {
    for (let i = 0; i < 10; i++) {
        const num1 = Math.floor(Math.random() * 100);
        const num2 = Math.floor(Math.random() * 100);
        const sum = num1 + num2;
        questions.push({ question: `${num1} плюс ${num2} равно ?`, sum: sum });
    }
}

// Функция для начала теста
// Функция для начала теста
function startTest() {
    startButton.style.display = 'none';
    cancelButton.style.display = 'inline-block';
    resultDisplay.style.display = 'block';
    buttonContainer.style.display = 'flex';
    previousReactionTimeDisplay.style.display = 'block';
    averageReactionTimeDisplay.style.display = 'block';
    currentReactionTimeDisplay.style.display = 'block';
    timerDisplay.style.display = 'block';
    questionsRemaining = 10; // Устанавливаем общее количество вопросов
    updateQuestionsRemaining(); // Обновляем отображение оставшихся примеров

    // Генерируем вопросы
    generateQuestions();

    // Показываем вопросы
    speakNextQuestion();
    countdown = setInterval(speakNextQuestion, 6000); // Установка интервала в 6 секунд
}

// Функция для озвучивания следующего вопроса
// Функция для озвучивания следующего вопроса
function speakNextQuestion() {
    if (questionsRemaining > 0) {
        if (questions.length === 0) {
            generateQuestions();
        }
        const { question, sum } = questions.shift();
        const speechText = speakText(question, 0.05);
        currentSum = sum;
        isAnsweredCorrectly = false;
        isReactionRecorded = false; // Обнуляем флаг при новом вопросе
        questionStartTime = Date.now();

        // Слушаем событие окончания речи
        speechText.addEventListener('end', function() {
            // Фиксируем реакцию только после окончания речи
            const reactionTime = (Date.now() - questionStartTime) / 1000; // Время реакции в секундах
            reactionTimes.push(reactionTime);
            updateReactionTimeDisplays(reactionTime);
            isReactionRecorded = true;
        });

        questionsRemaining--;
        updateQuestionsRemaining();
    } else {
        saveAvgReactionTime();
        endTest();
    }
}

// Функция для озвучивания текста
function speakText(text, volume) {
    const speechSynthesis = window.speechSynthesis;
    const speechText = new SpeechSynthesisUtterance(text);
    speechText.volume = volume;
    speechText.lang = 'ru-RU';
    speechSynthesis.speak(speechText);
    return speechText; // Возвращаем объект SpeechSynthesisUtterance
}

// Обработчики для кнопок
evenButton.addEventListener('click', function() {
    handleButtonClick(true);
});

oddButton.addEventListener('click', function() {
    handleButtonClick(false);
});
// Функция для обработки нажатия кнопки

// Функция для обработки нажатия кнопки
let answeredSums = new Set(); // Множество для хранения уже отвеченных сумм

function handleButtonClick(isEven) {
    const isSumEven = currentSum % 2 === 0;
    const isCorrect = (isEven && isSumEven) || (!isEven && !isSumEven);
    
    // Проверяем, что реакция еще не была зафиксирована и ответ правильный
    if (!isReactionRecorded && isCorrect) {
        const reactionTime = (Date.now() - questionStartTime) / 1000; // Время реакции в секундах
        reactionTimes.push(reactionTime);
        updateReactionTimeDisplays(reactionTime); // Обновляем отображение времени реакции, передавая reactionTime
        isReactionRecorded = true; // Фиксируем реакцию после правильного ответа
    }
    
    isAnsweredCorrectly = isCorrect; // Устанавливаем флаг правильного ответа
}


// Функция для обновления оставшихся вопросов
function updateQuestionsRemaining() {
    timerDisplay.textContent = `Questions remaining: ${questionsRemaining}`;
}

// Функция для обновления отображения времени реакции
function updateReactionTimeDisplays(reactionTime) {
    // Отображаем текущее время реакции
    currentReactionTimeDisplay.textContent = `Current Reaction Time: ${reactionTime !== null ? reactionTime.toFixed(2) : 'не было нажатия'}`;
}

// Функция для завершения теста
function endTest() {
    clearInterval(countdown); // Очищаем интервал
    startButton.style.display = 'block';
    cancelButton.style.display = 'none';
    startButton.style.margin = 'auto'; // Центрируем кнопку "Начать тест"
    timerDisplay.style.display = 'none';
    resultDisplay.style.display = 'none';
    buttonContainer.style.display = 'none';
    previousReactionTimeDisplay.style.display = 'none';
    currentReactionTimeDisplay.style.display = 'none';
    timerDisplay.display = 'none';
    reactionTimes = []; // Очищаем массив времен реакции
    questions = []; // Очищаем массив вопросов
    questionsRemaining = 10; // Возвращаем общее количество вопросов к исходному значению
    isAnsweredCorrectly = false; // Сбрасываем состояние ответа
}

// Функция для сохранения среднего времени реакции
function saveAvgReactionTime() {
    const averageReactionTime = reactionTimes.reduce((acc, val) => acc + val, 0) / reactionTimes.length;
    const formData = new FormData();
    formData.append('avgReactionTime', averageReactionTime); // Отправляем только среднее время реакции

    fetch('save_count_test.php', {
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

// Функция для отмены теста
function cancelTest() {
    clearInterval(countdown); // Останавливаем интервал
    endTest(); // Завершаем тест
}
