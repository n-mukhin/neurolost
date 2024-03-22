<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

// Инициализация переменных
$greeting = "";
$links = array();

// Проверяем, если пользователь уже вошел в систему
// Проверяем, если пользователь уже вошел в систему
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Получаем информацию о пользователе
    $query = "SELECT username, role, respondent_id, expert_id FROM users WHERE id = ?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result = $statement->get_result();

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $role = $row['role'];
        $respondent_id = $row['respondent_id'];
        $expert_id = $row['expert_id'];

        // Определяем приветствие и доступные ссылки в зависимости от роли пользователя
        switch ($role) {
            case 'user':
                $greeting = "Здравствуйте, $username!";
                $links = array(
                    "Профессии" => "professions.php",
                    "Результаты оценки профессий" => "rated_professions.php",
                    "Тесты" => "tests/tests.php",
                    "Личный кабинет" => "account.php",
                    "Выйти" => "logout.php"
                );
                break;
            case 'respondent':
                if ($respondent_id) {
                    $query_respondent = "SELECT name FROM respondents WHERE id = ?";
                    $statement_respondent = $mysqli->prepare($query_respondent);
                    $statement_respondent->bind_param("i", $respondent_id);
                    $statement_respondent->execute();
                    $result_respondent = $statement_respondent->get_result();

                    if($result_respondent->num_rows == 1){
                        $row_respondent = $result_respondent->fetch_assoc();
                        $name = $row_respondent['name'];
                        $greeting = "Здравствуйте, $name!";
                        $links = array(
                            "Профессии" => "professions.php",
                            "Результаты оценки профессий" => "rated_professions.php",
                            "Тесты" => "tests/tests.php",
                            "Личный кабинет" => "account.php",
                            "Выйти" => "logout.php"
                        );
                    }
                } else {
                    // Обработка случая, когда пользователь является респондентом, но не имеет идентификатора респондента
                    $greeting = "Здравствуйте, $username!";
                    $links = array(
                        "Профессии" => "professions.php",
                        "Результаты оценки профессий" => "rated_professions.php",
                        "Тесты" => "tests/tests.php",
                        "Личный кабинет" => "account.php",
                        "Выйти" => "logout.php"
                    );
                }
                break;
            case 'expert':
                if ($expert_id) {
                    $query_expert = "SELECT name FROM experts WHERE id = ?";
                    $statement_expert = $mysqli->prepare($query_expert);
                    $statement_expert->bind_param("i", $expert_id);
                    $statement_expert->execute();
                    $result_expert = $statement_expert->get_result();

                    if($result_expert->num_rows == 1){
                        $row_expert = $result_expert->fetch_assoc();
                        $name = $row_expert['name'];
                        $greeting = "Здравствуйте, $name!";
                        $links = array(
                            "Профессии" => "professions.php",
                            "Результаты оценки профессий" => "rated_professions.php",
                            "Эксперты" => "experts.php",
                            "Тесты" => "tests/tests.php",
                            "Личный кабинет" => "account.php",
                            "Выйти" => "logout.php"
                        );
                    }
                } else {
                    // Обработка случая, когда пользователь является экспертом, но не имеет идентификатора эксперта
                    $greeting = "Здравствуйте, $username!";
                    $links = array(
                        "Профессии" => "professions.php",
                        "Результаты оценки профессий" => "rated_professions.php",
                        "Эксперты" => "experts.php",
                        "Тесты" => "tests/tests.php",
                        "Личный кабинет" => "account.php",
                        "Выйти" => "logout.php"
                    );
                }
                break;
                case 'admin':
                    $greeting = "Вы вошли как администратор!";
                    $links = array(
                        "Профессии" => "professions.php",
                        "Результаты оценки профессий" => "rated_professions.php",
                        "Эксперты" => "experts.php",
                        "Тесты" => "tests/tests.php",
                        "Личный кабинет" => "account.php",
                        "Выйти" => "logout.php"
                    );
                    break;
        }
    }
}
else {
    // Если пользователь не вошел в систему
    $greeting = "Здравствуйте, Гость!";
    $links = array(
        "Войти" => "login.php",
        "Профессии" => "professions.php",
        "Результаты оценки профессий" => "rated_professions.php",
        "Тесты" => "tests/tests.php"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroLost</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/background.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body>
<div class="background"></div>
<header>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="../account.php">Личный кабинет</a></p>
        <?php endif; ?>
    </header>
    <div class="container">
        <h2><?php echo $greeting; ?></h2>
        <ul>
        <?php
        // Выводим ссылки в зависимости от роли пользователя
        foreach ($links as $text => $link) {
            echo "<li><a href=\"$link\">$text</a></li>";
        }
        ?>
        </ul>
    </div>
</body>
</html>
