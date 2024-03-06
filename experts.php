<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Проверяем, является ли пользователь экспертом
$is_expert = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'expert';

// Подключение к базе данных
require_once "db_connect.php";

// Получаем список экспертов
$query = "SELECT id, name, sgroup FROM experts";
$result = $mysqli->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/professions-experts.css">
    <title>Эксперты</title>
</head>
<body>
    <h2>Список экспертов</h2>
    <table>
        <tr>
            <th>Имя</th>
            <th>Группа</th>
            <?php if ($is_expert): ?>
                <th>Действия</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['sgroup']; ?></td>
            <?php if ($is_expert): ?>
                <td>
                    <form action="edit_experts.php" method="post">
                        <input type="hidden" name="expert_id" value="<?php echo $row['id']; ?>">
                        <input type="submit" name="delete" value="Удалить">
                    </form>
                </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>
    </table>

    <?php if ($is_expert): ?>
        <h2>Редактировать список экспертов</h2>
        <form action="edit_experts.php" method="post">
            <label for="name">Имя:</label>
            <input type="text" name="name" id="name" required><br>
            <label for="sgroup">Группа:</label>
            <input type="text" name="sgroup" id="sgroup" required><br>
            <input type="submit" value="Добавить эксперта">
        </form>
    <?php endif; ?>
    
    <p><a href="home.php">Домой</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>
