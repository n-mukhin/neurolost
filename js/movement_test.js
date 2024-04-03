document.addEventListener("DOMContentLoaded", function() {
    const catchButton = document.getElementById("even-button");
    const startButton = document.getElementById("start");
    const result = document.getElementById("result");
    const numberTap = document.getElementById("number-tap")
    const averageResult = document.getElementById("average-result")
    const hideInput = document.getElementById("hide-input")
    const bar = document.getElementById("bar");
    const square = document.getElementById("square");
    const timeSet = document.getElementById("timeSet")
    const guidelineButton = document.getElementById("guideline-button");
    const popup = document.getElementById("popup");
    const closeButton = document.getElementById("close-button");
    const stopButton = document.getElementById("stop");

    let countdownTimer

    let barRect;
    let squareRect;
    let overlap;
    let overlapPercentage = 0
    let totalOverlap = 0

    let isEnd = false
    let success = 0
    let buttonPressed = false
    let clickPressed = false
    let stopClick = false


    let timeIn
    let minutesLeft = 0
    let secondsLeft = 0

    let timeUsed  

    function check(){
        
        barRect = bar.getBoundingClientRect();
        squareRect = square.getBoundingClientRect();

        overlap = Math.max(0, Math.min(barRect.right, squareRect.right) - Math.max(barRect.left, squareRect.left));
        overlapPercentage = ((overlap / squareRect.width) * 100).toFixed(2);

        if (overlapPercentage == 0) numberTap.textContent = 'Неудачно!';
        else{
                numberTap.textContent = 'Удачно!';
                success += 1
        } 
        totalOverlap += overlap

        result.textContent = `Последний процент совпадения: ${overlapPercentage}%`;
        averageOverlapPercentage()


        
    }

    function start(){
        isEnd = false; 
        timeUsed = new Date().getTime();
        success = 0; 
        totalOverlap = 0;
        startCountdown(timeIn);
        catchButton.disabled = false;

    }

    function end(){
        if (!isEnd){
            numberTap.textContent = `Количество успешных: ${success}`
            if(success != 0 ){
                let totalOverlapPercentage = ( (totalOverlap / (squareRect.width*success) ) * 100).toFixed(2)
                result.textContent = `Средний процент совпадения: ${totalOverlapPercentage}%`
            }

            let timeUsedEnd = new Date().getTime()
            let intervalUsed = timeUsedEnd - timeUsed
            let minutesUsed = Math.floor((intervalUsed % (1000 * 60 * 60)) / (1000 * 60));
            let secondsUsed = Math.floor((intervalUsed % (1000 * 60)) / 1000);
            averageResult.textContent = `Время: ${minutesUsed} м ${secondsUsed} с`
            
            if (stopClick){
                timeSet.innerHTML = `${minutesLeft} м ${secondsLeft} с. Тест закончен`

            }
            
            catchButton.disabled = true
            startButton.disabled = true;
            stopClick.disabled = true
            isEnd = true
        }
        
    }

    function stopTest() {
            clearInterval(countdownTimer);
            end(); 
            isEnd = true;
            success = 0;
            totalOverlap = 0;
        
    }
    


    function averageOverlapPercentage() {
        let totalOverlapPercentage = ( (totalOverlap / (squareRect.width*success) ) * 100).toFixed(2)
        averageResult.textContent = `Средний процент совпадения: ${totalOverlapPercentage}%`
    }

    startButton.addEventListener("click", () => {
        if (clickPressed) {
            start();
            startButton.disabled = true
            numberTap.textContent = "Начинай тест!";
        } 
    })

    catchButton.addEventListener("click", () => {
        if (clickPressed && startButton.disabled){
            check();
        }

    })
    stopButton.addEventListener("click", () => {
        if (clickPressed && startButton.disabled){
            stopClick = true

            stopTest()
            stopButton.disabled =true
            catchButton.disabled = true 
            hideInput.style.display = "none"
        } 
    });

    guidelineButton.addEventListener("click", function() {
        popup.style.display = "block"; 
    });
    
    closeButton.addEventListener("click", function() {
        popup.style.display = "none"; 
    });
    
    window.addEventListener("click", function(event) {
        if (event.target == popup) {
            popup.style.display = "none"; 
        }
    })

    square.addEventListener("animationiteration", () => {
        if (!isEnd) {
            numberTap.textContent = ''
            result.textContent = ''
        }
        

    })

    document.addEventListener("keydown", (event) => {
        if (buttonPressed) {
            if (event.code == "KeyS"){
                startButton.click()
            } else if (event.code == "KeyW") {
                catchButton.click()
            } else if (event.code == "KeyE") {
                stopButton.click()
            } else {
                numberTap.textContent = "Операция не правильна!"
            }
        }
        if (event.code == "Enter") {
            const inputValue = hideInput.value
            timeIn = parseInt(inputValue)
            if (!isNaN(timeIn) && timeIn >= 2 && timeIn <= 45 ){
                hideInput.style.display = "none"
                buttonPressed = true
                clickPressed = true
                timeSet.textContent = `${timeIn-1} м 59 с`
            }
        } 
        
    })

    function startCountdown(minutes) {
        const currentTime = new Date();
        const endTime = new Date(currentTime.getTime() + minutes * 60000);
        
    
        countdownTimer = setInterval(function() {
            const now = new Date().getTime();
            const timeLeft = endTime - now;
    
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                timeSet.textContent = "0. Тест закончен";
                end()  
            } else {
                minutesLeft = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                secondsLeft = Math.floor((timeLeft % (1000 * 60)) / 1000);
                timeSet.innerHTML =  minutesLeft + " м " + secondsLeft + " с"

            }
            
        }, 1000); 
    }
    

    function save(resultTimes, test_id){
        resultPost = '['
        resultPost += resultTimes.join(',');
        resultPost += ']';
        post('./backend/save_result.php', {res: resultPost, test_id: test_id, correct: null, pulse: null}, method = 'post');
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
     
});
