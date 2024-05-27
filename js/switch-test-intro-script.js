function startGame() {
    var countdownInput = document.getElementById("countdownInput").value;
    if (countdownInput !== "") {
        
        toNewSite(countdownInput);
    } else {
        alert("Please enter the time");
    }
}

c
var modal = document.getElementById('myModal');


function openModal() {
    modal.style.display = 'block';
}


function closeModal() {
    modal.style.display = 'none';
}


function toNewSite(countdownInput) {
    var countdown = 3;
    if (!isNaN(countdownInput) && countdownInput > 0) {
        var timer = countdown;
        var modalContent = modal.querySelector('.modal-content');

      
        modalContent.innerHTML = '';

     
        var countdownDisplay = document.createElement('span');
        countdownDisplay.setAttribute('id', 'countdownDisplay');
        modalContent.appendChild(countdownDisplay);

      
        var countdownInterval = setInterval(function() {
            countdownDisplay.textContent = 'Countdown: ' + timer + ' s.';

            if (timer <= 0) {
                clearInterval(countdownInterval); 
                modalContent.innerHTML = '<h2>The test begins!</h2>'; 
                setTimeout(function() {
                    closeModal(); 
                    window.location.href = `main.html?timeChange=${countdownInput}`; 
                }, 1000);
            }

            timer--;
        }, 1000); 
    } else {
        alert('Please enter a positive integer');
    }
}

window.onload = function() {
    openModal();
};
