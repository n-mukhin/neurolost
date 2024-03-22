const startButton = document.getElementById('startButton');
        const cancelButton = document.getElementById('cancelButton');
        const questionDisplay = document.getElementById('question');
        const buttonContainer = document.querySelector('.button-container');
        const evenButton = document.getElementById('evenButton');
        const oddButton = document.getElementById('oddButton');
        const resultDisplay = document.getElementById('result');
        const timerDisplay = document.getElementById('timer');
        let correctCount = 0;
        let totalCount = 0;
        let timeLeft;
        let countdown;
        let currentSum;

        function generateQuestion() {
            const num1 = Math.floor(Math.random() * 100);
            const num2 = Math.floor(Math.random() * 100);
            currentSum = num1 + num2;
            questionDisplay.textContent = `${num1} + ${num2} = ?`;
        }

        function startTest() {
            startButton.style.display = 'none';
            cancelButton.style.display = 'inline-block';
            questionDisplay.style.display = 'block';
            buttonContainer.style.display = 'flex';
            resultDisplay.style.display = 'block';
            timerDisplay.style.display = 'block';
            timeLeft = 20;
            timerDisplay.textContent = `Time left: ${timeLeft} sec`;
            countdown = setInterval(() => {
                timeLeft--;
                timerDisplay.textContent = `Time left: ${timeLeft} sec`;
                if (timeLeft <= 0) {
                    saveAvgAccuracy(); // Вызываем функцию сохранения результатов
                    clearInterval(countdown);
                    endTest();
                }
            }, 1000);
            generateQuestion(); // Генерируем вопрос только один раз при начале теста
        }

        function cancelTest() {
            clearInterval(countdown);
            startButton.style.display = 'block';
            cancelButton.style.display = 'none';
            timerDisplay.style.display = 'none';
            questionDisplay.style.display = 'none';
            buttonContainer.style.display = 'none';
            resultDisplay.style.display = 'none';
            correctCount = 0;
            totalCount = 0;
        }

        function saveAvgAccuracy() {
            const avgAccuracy = (correctCount / totalCount) * 100;
            const formData = new FormData();
            formData.append('avgAccuracy', avgAccuracy); // Отправляем только среднюю точность

            fetch('save_cunt_test.php', {
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

        function checkAnswer(isEven) {
            const isSumEven = currentSum % 2 === 0;
            const isCorrect = isEven === isSumEven;
            if (isCorrect) {
                correctCount++;
            }
            totalCount++;
            const accuracy = (correctCount / totalCount) * 100; // Вычисляем точность на текущем этапе
            resultDisplay.textContent = `Correct: ${isCorrect ? 'Yes' : 'No'} | Accuracy: ${accuracy.toFixed(2)}%`;
            generateQuestion(); // Генерируем новый вопрос после проверки текущего ответа
        }

        function endTest() {
            startButton.style.display = 'block';
            cancelButton.style.display = 'none';
            startButton.style.margin = 'auto'; // Центрируем кнопку "Начать тест"
            timerDisplay.style.display = 'none';
            questionDisplay.style.display = 'none';
            buttonContainer.style.display = 'none';
            resultDisplay.textContent = `Final Accuracy: ${(correctCount / totalCount * 100).toFixed(2)}%`;
            correctCount = 0;
            totalCount = 0;
        }