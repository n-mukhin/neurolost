const urlParams = new URLSearchParams(window.location.search);
const get = urlParams.get('timeChange');

var hiddenBar = document.getElementById("hiddenBar");
var turn = document.getElementById("turn")
var correct_streak = document.getElementById("correct_streak")
var bigLetterElement = document.getElementById('bigLetter');


var startTime = parseInt(get);
var currentTime = startTime;

let letter
let color
let correct = 0
let choosed = 0
let streak = 0
let maxStreak = 0



function getRandomLetter() {
    const letters = ['RED', 'YELLOW', 'GREEN'];
    const randomIndex = Math.floor(Math.random() * letters.length);
    return letters[randomIndex];
}


function getRandomColor() {
    const colors = ['#FF0000', '#FFFF00', '#33FF57']; 
    const randomIndex = Math.floor(Math.random() * colors.length);
    return colors[randomIndex];
}


function changeBigLetterRandomly() {
    letter = getRandomLetter();
    color = getRandomColor();

    bigLetterElement.textContent = letter;
    bigLetterElement.style.color = color;
}


function saveMaxStreak(){
    if (streak > maxStreak) maxStreak = streak
}


function checkColor(colorIn) {
    if(color == colorIn){
        correct += 1
        streak += 1
    } else {
        streak = 0
    }
    saveMaxStreak()
}
function updateAnswer(){
    if (currentTime > 0){
        turn.textContent = "Number of questions answered: " + choosed
        correct_streak.textContent = "Streak of correct answers: " + streak
    } else {  c
        showHiddenBar()  
        bigLetterElement.textContent = "The test is over"
        bigLetterElement.style.color = "initial"; 
        turn.textContent = "Total time used: " + startTime + " seconds"
        countdownDisplay.textContent = "Number of questions answered: " + choosed
        correct_streak.textContent = "The number of questions answered correctly: " + correct
        hiddenBar.textContent = "The maximum streak of correct answers : " + maxStreak
    }   
}


function chooseColor(colorIn) {
    if (currentTime > 0) {
        choosed += 1
        checkColor(colorIn)
        updateAnswer()
        changeBigLetterRandomly()
    } 
}




function countdown() {

    var countdownDisplay = document.getElementById("countdownDisplay");
  
    countdownDisplay.textContent = "The test takes place in " + currentTime + " seconds";

    var countdownInterval = setInterval(function() {
        currentTime--;
 
        countdownDisplay.textContent = "The test takes place in " + currentTime + " seconds";
  
        if (currentTime <= 0) {
            clearInterval(countdownInterval); 
            updateAnswer() 
        }
    }, 1000); 
}

function showHiddenBar() {
    hiddenBar.style.display = "block"; 
}


function goBack() {
    window.location.href = 'index.html';
}


document.addEventListener('keydown', function(event) {
    if (event.key === 'a') { 
        chooseColor('#FF0000'); 
    } else if (event.key === 's') { 
        chooseColor('#008000'); 
    } else if (event.key === 'd') { 
        chooseColor('#FFFF00'); 
    } else if (event.key === 'w') { 
        goBack()
    }
});


function program(){

    countdown();
    changeBigLetterRandomly();

}

program();















