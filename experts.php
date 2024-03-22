<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


// Подключение к базе данных
require_once "db_connect.php";

// Проверяем, если пользователь уже вошел в систему
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

// Получаем список экспертов
$query = "SELECT id, name, sgroup, code FROM experts WHERE name != 'admin'";
$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/experts.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/background.css">
    <title>Эксперты</title>
</head>
<body>
    <div class = "background"></div>
<header>
        <p><a href="index.php">Домой</a></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="account.php">Личный кабинет</a></p>
        <?php endif; ?>
    </header>
    <div class = "container">
    <h2>Список экспертов</h2>
    <table>
        <tr>
            <th>Имя</th>
            <th>Группа</th>
            <?php if ($is_admin): ?>
            <th>Код</th>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <th>Действия</th>
            <?php endif; ?>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['sgroup']; ?></td>
            <?php if ($is_admin): ?>
            <td><?php echo $row['code']; ?></td>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <td>
                <!-- Добавляем кнопку удаления -->
                <form action="edit_experts.php" method="post">
                    <input type="hidden" name="expert_id" value="<?php echo $row['id']; ?>">
                    <input type="submit" name="delete" value="Удалить">
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>
    </table>

    <?php if ($is_admin): ?>
    <!-- Форма добавления эксперта -->
    <h2>Добавить эксперта</h2>
    <form action="edit_experts.php" method="post">
        <label for="name">Имя:</label>
        <input type="text" name="name" id="name" required><br>
        <label for="sgroup">Группа:</label>
        <input type="text" name="sgroup" id="sgroup" required><br>
        <label for="code">Код:</label>
        <input type="text" name="code" id="code" required><br>
        <input type="submit" name="add_expert" value="Добавить эксперта">
    </form>
    <?php endif; ?>
    </div>
</body>
</html>
