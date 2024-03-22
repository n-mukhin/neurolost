<?php
session_start();
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];

    // Получаем user_id из сессии
    $user_id = $_SESSION['user_id'];

    // Подготовка запроса с использованием подготовленных выражений
    $query = "INSERT INTO respondents (user_id, name, gender, age) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("isss", $user_id, $name, $gender, $age);

    if ($stmt->execute()) {
        $_SESSION['respondent_id'] = $mysqli->insert_id;
        header("Location: tests/tests.html");
        exit;
    } else {
        echo "Ошибка при регистрации респондента: " . $mysqli->error;
    }
    $stmt->close(); // Закрытие подготовленного выражения
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация респондента</title>
</head>
<body>
    <h2>Регистрация респондента</h2>
    <form method="post" action="">
        <label for="name">Имя:</label><br>
        <input type="text" id="name" name="name" required><br>
        <label for="gender">Пол:</label><br>
        <select id="gender" name="gender">
            <option value="Male">Мужской</option>
            <option value="Female">Женский</option>
            <option value="Other">Другой</option>
        </select><br>
        <label for="age">Возраст:</label><br>
        <input type="number" id="age" name="age" required><br><br>
        <input type="submit" name="register" value="Зарегистрироваться">
    </form>
</body>
</html>