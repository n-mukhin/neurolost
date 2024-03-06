<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

// Если пользователь уже вошел в систему, перенаправляем его на домашнюю страницу
if(isset($_SESSION['user_id'])){
    header("Location: home.php");
    exit;
}

// Проверяем, если форма входа отправлена
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ищем пользователя в базе данных
    $query = "SELECT id, username FROM users WHERE username='$username' AND password='$password'";
    $result = $mysqli->query($query);

    if ($result->num_rows == 1) {
        // Если пользователь найден, устанавливаем сессию и перенаправляем на домашнюю страницу
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: home.php");
        exit;
    } else {
        $error = "Неправильное имя пользователя или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login-register.css">
    <title>Вход</title>
</head>
<body>
    <h2>Вход</h2>
    <?php if(isset($error)) echo $error; ?>
    <form method="post" action="">
        <label for="username">Имя пользователя:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br><br>
        <input type="submit" name="login" value="Войти">
    </form>
    <p>Еще не зарегистрированы? <a href="register.php">Зарегистрируйтесь здесь</a>.</p>
</body>
</html>
