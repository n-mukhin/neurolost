<?php
session_start();

// Если пользователь не вошел в систему, перенаправляем его на страницу входа
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db_connect.php";

// Проверяем, был ли отправлен код эксперта
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $expert_code = $_POST['expert_code'];

    // Проверяем, существует ли эксперт с введенным кодом
    $query_check_expert = "SELECT * FROM experts WHERE expert_code = ?";
    $statement_check_expert = $mysqli->prepare($query_check_expert);
    $statement_check_expert->bind_param("s", $expert_code);
    $statement_check_expert->execute();
    $result_check_expert = $statement_check_expert->get_result();

    if ($result_check_expert->num_rows > 0) {
        // Код эксперта найден, получаем его имя
        $row_expert = $result_check_expert->fetch_assoc();
        $expert_name = $row_expert['name'];

        // Сохраняем идентификатор эксперта в сессии
        $_SESSION['user_id'] = $row_expert['id'];
        $_SESSION['username'] = $expert_name;
        $_SESSION['user_type'] = 'expert'; // Добавляем тип пользователя в сессию

        // Перенаправляем на главную страницу
        header("Location: home.php");
        exit;
    } else {
        // Код эксперта не найден, выводим сообщение об ошибке
        $error_message = "Неверный код эксперта. Пожалуйста, введите корректный код.";
    }
}

// Получаем имя пользователя из сессии
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroLost</title>
    <link rel="stylesheet" href="../css/home.css">
</head>
<body>
    <?php if(isset($error_message)) echo "<p>$error_message</p>"; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="expert_code">Введите код эксперта:</label>
        <input type="text" name="expert_code" id="expert_code" required>
        <input type="submit" name="submit" value="Войти">
    </form>
    <?php if(isset($expert_name)): ?>
    <h2>Добрый день, <?php echo $expert_name; ?>!</h2>
    <?php else: ?>
    <h2>Добро пожаловать, <?php echo $username; ?>!</h2>
    <?php endif; ?>
    <p><a href="professions.php">Профессии</a></p>
    <p><a href="rated_professions.php">Результаты оценки профессий</a></p>
    <p><a href="experts.php">Эксперты</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>
