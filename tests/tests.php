<?php
session_start();

// Подключение к базе данных
require_once "../db_connect.php";

// Получаем список всех тестов из базы данных
$query = "SELECT id, test_name, file_path FROM tests";
$result = $mysqli->query($query);

// Проверяем, является ли пользователь администратором
$is_expert = false;
$is_admin = false;
$is_respondent = false;
$is_user = false;

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Получаем информацию о пользователе
    $query_user = "SELECT username, role FROM users WHERE id = ?";
    $statement = $mysqli->prepare($query_user);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result_user = $statement->get_result();

    if($result_user->num_rows == 1){
        $row_user = $result_user->fetch_assoc();
        $username = $row_user['username'];
        $role = $row_user['role'];

        // Проверяем, является ли пользователь администратором
        $is_admin = $role === 'admin';

        // Проверяем, является ли пользователь экспертом
        $is_expert = $role === 'expert';
        $is_user = $role === 'expert';
        $is_respondent = $role === 'respondent';
    }
}

// Создаем массив для хранения тестов
$tests = [];

// Получаем список тестов и сохраняем их в массиве
while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

// Получаем список профессий
$query = "SELECT id, name, description FROM professions";
$result = $mysqli->query($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/tests.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/background.css">
    <title>Список тестов</title>
</head>
<body>
<div class="background"></div>
<header>
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
<div class="container">
    <h2>Список доступных тестов:</h2>
    <ul>
        <?php if ($is_respondent || $is_expert  || $is_admin): ?>
            <?php foreach ($tests as $test): ?>
                <li><a href='<?php echo $test['file_path']; ?>'><?php echo $test['test_name']; ?></a></li>
            <?php endforeach; ?>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <?php foreach ($tests as $test): ?>
                <li><a href='<?php echo $test['file_path']; ?>'><?php echo $test['test_name']; ?></a></li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>Чтобы получить доступ к тестам, необходимо <a href="../register_respondent.php">стать респондентом</a></li>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
