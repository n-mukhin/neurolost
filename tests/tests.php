<?php
session_start();

// Подключение к базе данных
require_once "../db_connect.php";

// Проверяем, является ли пользователь администратором, экспертом, респондентом или обычным пользователем
$is_admin = false;
$is_expert = false;
$is_respondent = false;
$is_user = false;

// Получаем тесты
$query_all_tests = "SELECT id, test_name, file_path FROM tests";
$result_all_tests = $mysqli->query($query_all_tests);

$tests = []; // Инициализируем массив

// Заполняем массив тестов, если запрос успешен
if ($result_all_tests) {
    while ($row_test = $result_all_tests->fetch_assoc()) {
        $tests[] = $row_test;
    }
}


if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Получаем информацию о пользователе
    $query_user = "SELECT u.username, u.role, u.respondent_id FROM users u WHERE u.id = ?";
    $statement = $mysqli->prepare($query_user);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result_user = $statement->get_result();

    if($result_user->num_rows == 1){
        $row_user = $result_user->fetch_assoc();
        $role = $row_user['role'];

        // Устанавливаем флаги для разных ролей
        $is_admin = $role === 'admin';
        $is_expert = $role === 'expert';
        $is_respondent = $role === 'respondent';
        $is_user = $role === 'user';
        // Если пользователь - респондент, выбираем только те тесты, которые доступны ему
        if ($is_respondent) {
            $respondent_id = $row_user['respondent_id'];
            $query_respondent_tests = "SELECT t.id, t.test_name, t.file_path 
                                       FROM tests t
                                       INNER JOIN respondent_tests rt ON t.id = rt.test_id
                                       WHERE rt.respondent_id = ?
                                       ORDER BY rt.test_order"; // Добавляем сортировку по test_order
            $statement_respondent_tests = $mysqli->prepare($query_respondent_tests);
            $statement_respondent_tests->bind_param("i", $respondent_id);
            $statement_respondent_tests->execute();
            $result_respondent_tests = $statement_respondent_tests->get_result();
        
            // Создаем массив для хранения тестов только для респондента
            $tests = [];
            while ($row_respondent_test = $result_respondent_tests->fetch_assoc()) {
                $tests[] = $row_respondent_test;
            }
        } else {
            // Если пользователь не респондент, то выводим все тесты без сортировки
            $query_all_tests = "SELECT id, test_name, file_path FROM tests";
            $result_all_tests = $mysqli->query($query_all_tests);
        
            $tests = [];
            while ($row_test = $result_all_tests->fetch_assoc()) {
                $tests[] = $row_test;
            }
        }
    }
}
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
    <h1>Тесты</h1>
    <ul>
        <?php if ($is_expert || $is_admin): ?>
            <li><a href="../view_test_results.php">Результаты тестов</a></li>
                <li><a href="../select_tests.php">Назначить тесты респондентам</a></li>
                <h3>Список доступных тестов:</h3>
            <!-- Показываем все тесты для администраторов и экспертов -->
            <?php foreach ($tests as $test): ?>
                <li><a href='<?php echo $test['file_path']; ?>'><?php echo $test['test_name']; ?></a></li>
            <?php endforeach; ?>
            <?php elseif ($is_respondent): ?>
                <br>
                <li><a href="../view_test_results.php">Результаты тестов</a></li>
                <h3>Список доступных тестов:</h3>
    <!-- Показываем только те тесты, которые доступны респонденту -->
        <?php if (empty($tests)): ?>
            <p>Вам еще не назначили тесты</p>
        <?php else: ?>
            <?php $counter = 1; ?>
            <ul>
            <?php foreach ($tests as $test): ?>
                <li><?php echo $counter++; ?>. <a href='<?php echo $test['file_path']; ?>'><?php echo $test['test_name']; ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <!-- Показываем все тесты для неавторизованных пользователей -->
            <?php foreach ($tests as $test): ?>
                <li><a href='<?php echo $test['file_path']; ?>'><?php echo $test['test_name']; ?></a></li>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Показываем сообщение о необходимости стать респондентом -->
            <li>Чтобы получить доступ к тестам, необходимо <a href="../register_respondent.php">стать респондентом</a></li>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
