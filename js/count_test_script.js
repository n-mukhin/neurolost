const startButton = document.getElementById('startButton');
const cancelButton = document.getElementById('cancelButton');
const questionDisplay = document.getElementById('question');
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
let questionTimeout;
let questionsRemaining = 10; // Общее количество вопросов
let isAnsweredCorrectly = false; // Переменная для отслеживания правильности ответа
let isReactionRecorded = false; // Переменная для отслеживания фиксации времени реакции
let questionStartTime; // Переменная для хранения времени начала вопроса

function generateQuestions() {
    for (let i = 0; i < 10; i++) {
        const num1 = Math.floor(Math.random() * 100);
        const num2 = Math.floor(Math.random() * 100);
        const sum = num1 + num2;
        questions.push(`${num1} + ${num2} = ?`);
    }
}

function startTest() {
    startButton.style.display = 'none';
    cancelButton.style.display = 'inline-block';
    questionDisplay.style.display = 'block';
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
    showNextQuestion();
    countdown = setInterval(showNextQuestion, Math.floor(Math.random() * (5000 - 3000 + 1)) + 3000);
}

function handleButtonClick(isEven) {
    if (!isReactionRecorded && !isAnsweredCorrectly) { // Проверяем, не была ли уже записана реакция и не был ли дан правильный ответ ранее
        const isSumEven = currentSum % 2 === 0;
        const isCorrect = isEven === isSumEven;
        if (isCorrect) {
            // Вычисляем и записываем время реакции только при правильном ответе
            const reactionTime = (Date.now() - questionStartTime) / 1000; // Время реакции в секундах
            reactionTimes.push(reactionTime);
            updateReactionTimeDisplays(reactionTime); // Обновляем отображение времени реакции, передавая reactionTime
            isAnsweredCorrectly = true; // Устанавливаем флаг, что данный вопрос был правильно отвечен
        }
    }
    // Сбрасываем флаг после нажатия кнопки
    isReactionRecorded = true;
}

evenButton.onclick = () => handleButtonClick(true);
oddButton.onclick = () => handleButtonClick(false);

function showNextQuestion() {
    // Очищаем предыдущий таймаут перед установкой нового
    clearTimeout(questionTimeout);

    if (questionsRemaining > 0) {
        if (questions.length === 0) {
            generateQuestions(); // Генерируем новые вопросы, если текущие закончились
        }
        const question = questions.shift();
        questionDisplay.textContent = question;
        currentSum = parseInt(question.split('=')[1].trim(), 10);
        isAnsweredCorrectly = false;
        isReactionRecorded = false; // Сбрасываем флаг

        questionStartTime = Date.now(); // Устанавливаем значение questionStartTime

        // Устанавливаем задержку перед показом следующего примера
        questionTimeout = setTimeout(() => {
            if (!isReactionRecorded) {
                reactionTimes.push("не было нажатия"); // Присваиваем "не было нажатия" в случае, если не было нажатия
                updateReactionTimeDisplays(null); // Передаем null вместо времени реакции
            }
            showNextQuestion();
        }, Math.floor(Math.random() * (5000 - 3000 + 1)) + 3000);

        questionsRemaining--;
        updateQuestionsRemaining();
    } else {
        saveAvgReactionTime();
        endTest();
    }
}

function updateQuestionsRemaining() {
    timerDisplay.textContent = `Questions remaining: ${questionsRemaining}`;
}

let previousReactionTime = null; // Переменная для хранения предыдущего времени реакции

function updateReactionTimeDisplays(reactionTime) {
    if (reactionTime !== null) { // Проверяем, передано ли время реакции
        // Отображаем предыдущее время реакции
        previousReactionTimeDisplay.textContent = previousReactionTime !== null ? `Previous Reaction Time: ${previousReactionTime.toFixed(2)} s` : 'Previous Reaction Time: -';
        previousReactionTime = reactionTime; // Обновляем предыдущее время реакции
    } else {
        // Если не передано время реакции, очищаем отображение
        previousReactionTimeDisplay.textContent = 'Previous Reaction Time: -';
    }

    if (reactionTimes.length > 0) {
        // Отображаем среднее время реакции
        let totalReactionTime = 0;
        let validReactionCount = 0;
        for (const reaction of reactionTimes) {
            if (typeof reaction === "number") {
                totalReactionTime += reaction;
                validReactionCount++;
            }
        }
        const averageReactionTime = totalReactionTime / validReactionCount;
        averageReactionTimeDisplay.textContent = `Average Reaction Time: ${averageReactionTime.toFixed(2)} s`;
    } else {
        // Если нет реакций, выводим сообщение об отсутствии данных
        averageReactionTimeDisplay.textContent = 'Average Reaction Time: -';
    }

    // Отображаем текущее время реакции
    currentReactionTimeDisplay.textContent = `Current Reaction Time: ${reactionTime !== null ? reactionTime.toFixed(2) : 'не было нажатия'}`;
}


function checkAnswer(isEven) {
    if (!isAnsweredCorrectly) {
        const isSumEven = currentSum % 2 === 0;
        const isCorrect = isEven === isSumEven;
        resultDisplay.textContent = `Correct: ${isCorrect ? 'Yes' : 'No'}`;
        if (isCorrect) {
            isAnsweredCorrectly = true;
            const reactionTime = (Date.now() - questionStartTime) / 1000; // Время реакции в секундах
            updateReactionTimeDisplays(reactionTime); // Передаем время реакции в функцию updateReactionTimeDisplays
        }
    }
}

function handleOddButtonClick() {
    checkAnswer(false);
}

function endTest() {
    clearInterval(countdown); // Очищаем интервал
    clearTimeout(questionTimeout); // Очищаем таймаут
    startButton.style.display = 'block';
    cancelButton.style.display = 'none';
    startButton.style.margin = 'auto'; // Центрируем кнопку "Начать тест"
    timerDisplay.style.display = 'none';
    questionDisplay.style.display = 'none';
    buttonContainer.style.display = 'none';
    previousReactionTimeDisplay.style.display = 'none';
    currentReactionTimeDisplay.style.display = 'none';
    timerDisplay.display = 'none';
    reactionTimes = []; // Очищаем массив времен реакции
    questions = []; // Очищаем массив вопросов
    questionsRemaining = 10; // Возвращаем общее количество вопросов к исходному значению
    isAnsweredCorrectly = false; // Сбрасываем состояние ответа
}

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

function cancelTest() {
    clearInterval(countdown); // Останавливаем интервал
    endTest(); // Завершаем тест
}
