<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db_connect.php";

// Получаем ID текущего пользователя
$user_id = $_SESSION['user_id'];

// Проверяем, была ли отправлена форма для обновления профиля
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newUsername']) && isset($_POST['newPassword'])) {
    // Получаем новое имя пользователя и пароль из POST-данных
    $newUsername = $_POST['newUsername'];
    $newPassword = $_POST['newPassword'];

    // Проверяем, что поля не являются пустыми
    if (!empty($newUsername) && !empty($newPassword)) {
        // Подготовка и выполнение запроса на обновление профиля пользователя в базе данных
        $stmt = $mysqli->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newUsername, $newPassword, $user_id);
        if ($stmt->execute()) {
            $_SESSION['updateMessage'] = "Профиль успешно обновлен";
        } else {
            $_SESSION['updateMessage'] = "Ошибка при обновлении профиля: " . $mysqli->error;
        }
        $stmt->close(); // Закрытие подготовленного запроса
    } else {
        $_SESSION['updateMessage'] = "Пожалуйста, заполните все поля";
    }
}

header("Location: account.php"); // Redirect back to account.php
exit;

?>
