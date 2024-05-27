<?php
session_start();
require_once "db-connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];

    // Получаем user_id из сессии
    $user_id = $_SESSION['user_id'];

    // Проверяем, существует ли уже анкета для данного пользователя
    $query_check = "SELECT id FROM respondents WHERE user_id = ?";
    $stmt_check = $mysqli->prepare($query_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Если анкета уже существует, обновляем её
        $query_update = "UPDATE respondents SET name = ?, gender = ?, age = ? WHERE user_id = ?";
        $stmt_update = $mysqli->prepare($query_update);
        $stmt_update->bind_param("sssi", $name, $gender, $age, $user_id);
        
        if ($stmt_update->execute()) {
            // После успешного обновления анкеты, присваиваем роль "respondent" пользователю
            $query_assign_role = "UPDATE users SET role = 'respondent' WHERE id = ?";
            $stmt_assign_role = $mysqli->prepare($query_assign_role);
            $stmt_assign_role->bind_param("i", $user_id);
            $stmt_assign_role->execute();
            $stmt_assign_role->close();

            header("Location: tests/tests.php");
            exit;
        } else {
            echo "Ошибка при обновлении данных респондента: " . $mysqli->error;
        }
        $stmt_update->close();
    } else {
        $query_insert = "INSERT INTO respondents (user_id, name, gender, age) VALUES (?, ?, ?, ?)";
$stmt_insert = $mysqli->prepare($query_insert);
$stmt_insert->bind_param("isss", $user_id, $name, $gender, $age);

if ($stmt_insert->execute()) {
    $respondent_id = $stmt_insert->insert_id; // Получаем respondent_id после вставки

    // После успешной вставки новой анкеты, присваиваем роль "respondent" пользователю
    $query_assign_role = "UPDATE users SET role = 'respondent', respondent_id = ? WHERE id = ?";
    $stmt_assign_role = $mysqli->prepare($query_assign_role);
    $stmt_assign_role->bind_param("ii", $respondent_id, $user_id);
    $stmt_assign_role->execute();
    $stmt_assign_role->close();

    $_SESSION['respondent_id'] = $respondent_id;
    header("Location: tests/tests.php");
    exit;
} else {
    echo "Ошибка при регистрации респондента: " . $mysqli->error;
}
        $stmt_insert->close();
    }
    $stmt_check->close();
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
