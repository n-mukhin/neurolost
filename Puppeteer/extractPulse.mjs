import puppeteer from 'puppeteer';
import fetch from 'node-fetch';

(async () => {
    const url = ' ';
    const serverUrl = 'http://localhost:3000/Puppeteer/pulse-data.php'; // URL вашего PHP скрипта

    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    await page.goto(url, { waitUntil: 'networkidle2' });

    // Функция для извлечения чисел со страницы
    const extractNumbers = async () => {
        return await page.evaluate(() => {
            const bodyText = document.body.innerText;
            const matches = bodyText.match(/\d+/g);
            return matches ? matches.map(Number) : [];
        });
    };

    // Отправка данных на сервер
    const sendDataToServer = async (data) => {
        try {
            await fetch(serverUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
        } catch (error) {
            console.error('Error sending data to server:', error);
        }
    };

    // Повторяем попытку извлечения и отправки данных каждые 5 секунд
    while (true) {
        const pulseData = await extractNumbers();
        if (pulseData.length > 0) {
            await sendDataToServer({ pulse: pulseData[0] });
        }
        await new Promise(resolve => setTimeout(resolve, 5000));
    }

    await browser.close();
})();
