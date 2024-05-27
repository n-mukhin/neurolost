<?php
session_start();

// Подключение к базе данных
require_once "db-connect.php";

// Проверяем, является ли пользователь администратором
$is_admin = false;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Получаем информацию о пользователе
    $query = "SELECT username, role FROM users WHERE id = ?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result = $statement->get_result();

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $role = $row['role'];

        // Проверяем, является ли пользователь администратором
        $is_admin = $role === 'admin';

        // Проверяем, является ли пользователь экспертом
        $is_expert = $role === 'expert';
    }
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
    <link rel="stylesheet" href="css/professions.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="css/background.css">
    <title>Профессии</title>
</head>
<body>
<div class="background"></div>
<header>
        <p><a href="index.php">Домой</a></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="account.php">Личный кабинет</a></p>
        <?php endif; ?>
    </header>
    <div class="container">
    <h2>Список профессий</h2>
    <table>
        <tr>
            <th>Название</th>
            <th>Описание</th>
            <?php if ($is_admin): ?>
            <th>Действия</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['description']; ?></td>
            <?php if ($is_admin): ?>
            <td>
                <form action="edit_professions.php" method="post">
                    <input type="hidden" name="profession_id" value="<?php echo $row['id']; ?>">
                    <input type="submit" name="delete" value="Удалить">
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>
    </table>

    <?php if ($is_admin): ?>
    <h2>Редактировать список профессий</h2>
    <form action="edit_professions.php" method="post">
        <label for="name">Название:</label>
        <input type="text" name="name" id="name" required><br>
        <label for="sgroup">Описание:</label>
        <input type="text" name="description" id="description" required><br>
        <input type="submit" value="Добавить профессию">
        
    </form>
    <?php endif; ?>
    <p><a href="evaluate_professions.php">Оценить профессии</a></p>
    <p><a href="rated_professions.php">Результаты оценки профессий</a></p>
    </div>
</body>
</html>
