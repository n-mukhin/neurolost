<?php
session_start();

// Подключение к базе данных
require_once "db-connect.php";

// Если пользователь уже вошел в систему, перенаправляем его на домашнюю страницу
if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// Проверяем, если форма входа отправлена
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ищем пользователя в базе данных
    $query = "SELECT id, username, password FROM users WHERE username=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param("s", $username);
    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows == 1) {
        // Если пользователь найден, проверяем пароль
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Если пароль верен, устанавливаем сессию и перенаправляем на домашнюю страницу
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Неправильное имя пользователя или пароль";
        }
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
    <link rel="stylesheet" href="../css/reglog.css">
    <link rel="stylesheet" href="../css/background.css">
    <title>Вход</title>
</head>
<body>
    <div class="background"></div>
    <div class="container">
    <h2>Вход</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="">
        <label for="username">Имя пользователя:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br><br>
        <input type="submit" name="login" value="Войти">
    </form>
    <p>Еще не зарегистрированы? <br><br><a href="register.php">Зарегистрируйтесь здесь</a>.</p>
    </div>
</body>
</html>
