<?php
session_start();

// Подключение к базе данных
require_once "db-connect.php";

// Если пользователь уже вошел в систему, перенаправляем его на домашнюю страницу
if(isset($_SESSION['user_id'])){
    header("Location: home.php");
    exit;
}

// Проверяем, если форма регистрации отправлена
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $code = $_POST['code']; // Добавляем код из формы

    // Хеширование пароля
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Проверяем, не занято ли имя пользователя
    $query = "SELECT id FROM users WHERE username=?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param("s", $username);
    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows == 0) {
        // Регистрируем нового пользователя
        if(empty($code)){
            $query = "INSERT INTO users (username, password, expert_id) VALUES (?, ?, NULL)";
            $statement = $mysqli->prepare($query);
            $statement->bind_param("ss", $username, $hashed_password);
        } else {
            // Проверяем код в таблице experts
            $code_query = "SELECT id FROM experts WHERE code=?";
            $statement = $mysqli->prepare($code_query);
            $statement->bind_param("s", $code);
            $statement->execute();
            $code_result = $statement->get_result();

            if ($code_result->num_rows == 1) {
                $row = $code_result->fetch_assoc();
                $expert_id = $row['id'];

                // Регистрируем нового пользователя с привязкой к эксперту
                $role = 'expert';
                $query = "INSERT INTO users (username, password, expert_id, role) VALUES (?, ?, ?, ?)";
                $statement = $mysqli->prepare($query);
                $statement->bind_param("ssis", $username, $hashed_password, $expert_id, $role);
            } else {
                $error = "Неправильный код эксперта.";
            }
        }

        if ($statement->execute()) {
            // После успешной регистрации перенаправляем на страницу входа
            header("Location: login.php");
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
    <link rel="stylesheet" href="css/reglog.css">
    <link rel="stylesheet" href="css/background.css">
    <title>Регистрация</title>
</head>
<body>
<div class="background"></div>
<div class="container">
    <h2>Регистрация</h2>
    <?php if(isset($error)) echo $error; ?>
    <form method="post" action="">
        <label for="username">Имя пользователя:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Пароль:</label><br>
        <input type="password" id="password" name="password"><br>
        <label for="code">Код эксперта(если есть):</label><br> <!-- Добавляем поле для ввода кода -->
        <input type="text" id="code" name="code"><br><br>
        <input type="submit" name="register" value="Зарегистрироваться">
    </form>
    <p>Уже зарегистрированы? <br><br><a href="login.php">Войдите здесь</a>.</p>
    </div>
</body>
</html>
