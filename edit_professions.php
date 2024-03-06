<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db_connect.php";

// Проверяем, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && isset($_POST['description'])) {
    $name = $_POST['name'];
    $group = $_POST['description'];

    // Подготавливаем запрос для добавления профессии
    $query = "INSERT INTO professions (name, description) VALUES (?, ?)";
    $statement = $mysqli->prepare($query);

    // Привязываем параметры
    $statement->bind_param("ss", $name, $group);

    // Выполняем запрос
    if ($statement->execute()) {
        // Эксперт успешно добавлен, перенаправляем обратно на страницу списка экспертов
        header("Location: professions.php");
        exit;
    } else {
        // Ошибка при добавлении профессии
        echo "Ошибка при добавлении эксперта: " . $mysqli->error;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete']) && isset($_POST['profession_id'])) {
    $expert_id = $_POST['profession_id'];

    // Подготавливаем запрос для удаления профессии
    $query = "DELETE FROM professions WHERE id = ?";
    $statement = $mysqli->prepare($query);

    // Привязываем параметр
    $statement->bind_param("i", $expert_id);

    // Выполняем запрос
    if ($statement->execute()) {
        // Эксперт успешно удален, перенаправляем обратно на страницу списка профессий
        header("Location: professions.php");
        exit;
    } else {
        // Ошибка при удалении профессии
        echo "Ошибка при удалении эксперта: " . $mysqli->error;
    }
}
?>

