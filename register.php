<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

// Если пользователь уже вошел в систему, перенаправляем его на домашнюю страницу
if(isset($_SESSION['user_id'])){
    header("Location: home.php");
    exit;
}

// Проверяем, если форма регистрации отправлена
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Проверяем, не занято ли имя пользователя
    $query = "SELECT id FROM users WHERE username='$username'";
    $result = $mysqli->query($query);

    if ($result->num_rows == 0) {
        // Регистрируем нового пользователя
        $query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
        if ($mysqli->query($query) === TRUE) {
            // После успешной регистрации перенаправляем на страницу входа
            header("Location: index.php");
            exit;
        } else {
            $error = "Ошибка при регистрации пользователя: " . $mysqli->error;
        }
    } else {
        $error = "Имя пользователя уже занято";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login-register.css">
    <title>Регистрация</title>
</head>
<body>
    <h2>Регистрация</h2>
    <?php if(isset($error)) echo $error; ?>
    <form method="post" action="">
        <label for="username">Имя пользователя:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br><br>
        <input type="submit" name="register" value="Зарегистрироваться">
    </form>
    <p>Уже зарегистрированы? <a href="index.php">Войдите здесь</a>.</p>
</body>
</html>
