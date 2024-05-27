<?php
session_start();



// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db-connect.php";

// Получаем информацию о текущем пользователе из базы данных
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = $mysqli->query($query);

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
} else {
    echo "Ошибка: Пользователь не найден.";
    exit;
}
// Проверяем наличие сообщения об обновлении профиля
$updateMessage = isset($_SESSION['updateMessage']) ? $_SESSION['updateMessage'] : null;
unset($_SESSION['updateMessage']); // Очищаем сообщение из сессии


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="css/background.css">
    <title>Личный кабинет</title>
</head>
<body>
<div class="background"></div>
<header>
        <p><a href="index.php">Домой</a></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="logout.php">Выйти</a></p>
        <?php endif; ?>
        </header>
    <div class="container">
    <h2>Личный кабинет</h2>
    <p><strong>Имя пользователя:</strong> <?php echo $user['username']; ?></p>
        <p><strong>Роль:</strong> <?php echo $user['role']; ?></p>

        <form action="update_profile.php" method="post">
            <label for="newUsername">Новое имя пользователя:</label>
            <input type="text" id="newUsername" name="newUsername"><br>

            <label for="newPassword">Новый пароль:</label>
            <input type="password" id="newPassword" name="newPassword"><br>

            <input type="submit" value="Обновить профиль">
        </form>

        <?php if (isset($updateMessage)): ?>
            <p><?php echo $updateMessage; ?></p>
        <?php endif; ?>

</body>
</html>
