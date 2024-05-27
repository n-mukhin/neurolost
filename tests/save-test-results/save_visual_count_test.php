<?php
session_start();

// Проверяем, была ли отправлена форма для сохранения результатов теста
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['avgAccuracy'])) {
    // Получаем среднюю точность из POST-данных
    $avgAccuracy = $_POST['avgAccuracy'];

    // Проверяем, авторизован ли пользователь
    if (isset($_SESSION['user_id'])) {
        // Пользователь авторизован, сохраняем результаты в базу данных

        // Подключение к базе данных
        require_once "../db_connect.php"; // Замените это на ваше соединение с базой данных

        // Получаем user_id из сессии
        $user_id = $_SESSION['user_id'];

        // Устанавливаем test_type и test_name
        $test_type = "reaction";
        $test_name = "sound-test";

        // Подготовка и выполнение запроса на вставку результатов теста в базу данных
        $stmt = $mysqli->prepare("INSERT INTO test_results (user_id, test_type, test_name, result) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $test_type, $test_name, $avgAccuracy); // 'isss' представляет собой типы данных: integer, string, string, double/float
        if ($stmt->execute()) {
            echo "Результаты успешно сохранены";
        } else {
            echo "Ошибка при сохранении результатов: " . $mysqli->error;
        }
        $stmt->close(); // Закрытие подготовленного запроса
    } else {
        // Пользователь не авторизован, сохраняем среднюю точность в сессию
        $_SESSION['guest_avg_accuracy_sound'] = $avgAccuracy;
        echo "Результаты успешно сохранены в сессии";
    }
} else {
    echo "Нет данных о точности";
}
?>
