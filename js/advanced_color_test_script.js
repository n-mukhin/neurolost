document.addEventListener("DOMContentLoaded", function() {
    const startButton = document.getElementById('startButton');
    const cancelButton = document.getElementById('cancelButton');
    const questionDisplay = document.getElementById('question');
    const buttonContainer = document.querySelector('.button-container');
    const resultDisplay = document.getElementById('result');
    const colorBox = document.getElementById('colorBox');
    const timerDisplay = document.getElementById('reactionTimeDisplay');
    const prevReactionTimeDisplay = document.getElementById('prevReactionTimeDisplay');
    const avgReactionTimeDisplay = document.getElementById('avgReactionTimeDisplay');
    const colors = ['red', 'green', 'blue'];

    let correctCount = 0;
    let totalCount = 0;
    let colorsRemaining = 10;
    let currentColor;
    let reactionTimes = [];
    let startTime;

    function generateQuestion() {
        colorsRemaining--;
        if (colorsRemaining < 0) {
            endTest();
            return;
        }
    
        const randomIndex = Math.floor(Math.random() * colors.length);
        currentColor = colors[randomIndex];
        colorBox.style.backgroundColor = currentColor;
    
        // Проверяем, был ли уже установлен текущий цвет до начала отсчета времени
        if (startTime) {
            // Если цвет уже установлен, фиксируем время начала реакции
            startTime = performance.now();
        }
    
        // Задержка перед следующим цветом
        setTimeout(() => {
            colorBox.style.backgroundColor = 'black';
            if (!startTime) {
                // Если цвет еще не был установлен, устанавливаем время начала реакции здесь
                startTime = performance.now();
            }
        }, Math.floor(Math.random() * 3000) + 2000); // Случайная задержка от 2 до 5 секунд
    }

    function handleButtonClick(event) {
        if (event.target.classList.contains('color-button')) {
            totalCount++;
            const endTime = performance.now(); 
            const reactionTime = endTime - startTime;
            
            if (event.target.id === currentColor + 'Button') {
                reactionTimes.push(reactionTime);
                timerDisplay.textContent = `Reaction Time: ${reactionTime.toFixed(2)} milliseconds`;
                prevReactionTimeDisplay.textContent = `Previous Reaction Time: ${reactionTime.toFixed(2)} milliseconds`;
    
                correctCount++;
                resultDisplay.textContent = 'Correct!';
            } else {
                resultDisplay.textContent = 'Incorrect!';
            }
    
            // Генерируем следующий вопрос
            generateQuestion();
        }
    }
    function sendDataToServer(averageReactionTime) {
        const formData = new FormData();
        formData.append('avgReactionTime', averageReactionTime);

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

    function startTest() {
        document.getElementById('startButton').style.display = 'none';
        document.getElementById('cancelButton').style.display = 'inline-block';
        document.getElementById('question').style.display = 'block';
        document.querySelector('.button-container').style.display = 'flex';
        document.getElementById('result').style.display = 'block';
        document.getElementById('colorBox').style.display = 'block';
        document.getElementById('timer').style.display = 'block';
        document.getElementById('prevReactionTime').style.display = 'block';
        document.getElementById('avgReactionTime').style.display = 'block';
        document.getElementById('changeCounterDisplay').style.display = 'block';
        
        colorsRemaining = 10;
        reactionTimes = []; // Обнуляем массив времен реакции
        generateQuestion();
    }
    function cancelTest() {
        startButton.style.display = 'block';
        cancelButton.style.display = 'none';
        timerDisplay.style.display = 'none';
        prevReactionTimeDisplay.style.display = 'none';
        avgReactionTimeDisplay.style.display = 'none';
        questionDisplay.style.display = 'none';
        colorBox.style.display = 'none';
        buttonContainer.style.display = 'none';
        resultDisplay.style.display = 'none';
        correctCount = 0;
        totalCount = 0;
    }
    
    function endTest() {
        const averageReactionTime = reactionTimes.reduce((acc, val) => acc + val, 0) / reactionTimes.length;
        resultDisplay.textContent = `Average Reaction Time: ${averageReactionTime.toFixed(2)} milliseconds`;
        avgReactionTimeDisplay.textContent = `Average Reaction Time: ${averageReactionTime.toFixed(2)} milliseconds`;
        sendDataToServer(averageReactionTime); // Отправляем данные на сервер
        startButton.style.display = 'block';
        cancelButton.style.display = 'none';
        questionDisplay.style.display = 'none';
        buttonContainer.style.display = 'none';
        timerDisplay.style.display = 'none';
        prevReactionTimeDisplay.style.display = 'none';
        avgReactionTimeDisplay.style.display = 'none';
        colorBox.style.display = 'none';
        correctCount = 0;
        totalCount = 0;
    }

startButton.addEventListener('click', startTest);
cancelButton.addEventListener('click', cancelTest);
});
