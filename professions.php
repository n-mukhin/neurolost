<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db_connect.php";

// Проверяем, является ли пользователь экспертом
$is_expert = false;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'expert') {
    $is_expert = true;
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
    <link rel="stylesheet" href="../css/professions-experts.css">
    <title>Профессии</title>
</head>
<body>
    <h2>Список профессии</h2>
    <table>
        <tr>
            <th>Название</th>
            <th>Описание</th>
            <?php if ($is_expert): ?>
            <th>Действия</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['description']; ?></td>
            <?php if ($is_expert): ?>
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

    <?php if ($is_expert): ?>
    <h2>Редактировать список профессии</h2>
    <form action="edit_professions.php" method="post">
        <label for="name">Название:</label>
        <input type="text" name="name" id="name" required><br>
        <label for="sgroup">Описание:</label>
        <input type="text" name="description" id="description" required><br>
        <input type="submit" value="Добавить профессию">
        
    </form>
    <?php endif; ?>

    <p><a href="home.php">Домой</a></p>
    <?php if ($is_expert): ?>
    <p><a href="evaluate_professions.php">Оценить профессии</a></p>
    <?php endif; ?>
    <p><a href="rated_professions.php">Результаты оценки профессий</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>
