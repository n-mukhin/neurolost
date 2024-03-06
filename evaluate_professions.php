<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

// Если пользователь не вошел в систему или не является экспертом, выводим сообщение о доступе только для экспертов
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'expert') {
    echo "Доступно только экспертам. <a href='home.php'>На главную</a>";
    exit;
}

$username = $_SESSION['username'];

// Проверяем, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $profession_id = $_POST['profession'];
    $expert_id = $_SESSION['user_id'];

    // Удаляем предыдущие оценки для этого эксперта и этой профессии
    $delete_query = "DELETE FROM ratings WHERE profession_id = ? AND user_id = ?";
    $statement_delete = $mysqli->prepare($delete_query);
    $statement_delete->bind_param("ii", $profession_id, $expert_id);
    $statement_delete->execute();

    // Сохраняем новые оценки в базу данных
    $at_least_one_rating_saved = false; // Флаг, указывающий, была ли хотя бы одна оценка сохранена
    if (isset($_POST['ratings'])) {
        foreach ($_POST['ratings'] as $pvk_id => $ratings) {
            // Проверяем, есть ли оценки для данной ПВК и они не пусты
            if (!empty($ratings) && !empty(array_filter($ratings))) {
                // Определяем переменную $statement_insert перед циклом
                $statement_insert = null;
                foreach ($ratings as $rating) {
                    // Игнорируем оценки, равные нулю
                    if ($rating != 0) {
                        $insert_query = "INSERT INTO ratings (profession_id, pvk_id, user_id, rating) VALUES (?, ?, ?, ?)";
                        $statement_insert = $mysqli->prepare($insert_query);
                        $statement_insert->bind_param("iiii", $profession_id, $pvk_id, $expert_id, $rating);
                        $statement_insert->execute();
                        // Если хотя бы одна оценка сохранена, устанавливаем флаг в true
                        $at_least_one_rating_saved = true;
                    }
                }
            }
        }
        // Выводим сообщение только если была сохранена хотя бы одна оценка
        if ($at_least_one_rating_saved && $statement_insert->affected_rows > 0) {
            echo "Оценки успешно сохранены.";
        } else {
            echo "Ошибка: оценка не была сохранена.";
        }
    }
}

// Получаем список профессий из базы данных
$query_professions = "SELECT id, name FROM professions";
$result_professions = $mysqli->query($query_professions);

// Получаем список ПВК из базы данных
$query_pvk = "SELECT id, name, category FROM pvk";
// Добавляем поиск по ПВК
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $_POST['search'];
    $query_pvk .= " WHERE name LIKE '%$search%'";
}
$result_pvk = $mysqli->query($query_pvk);

// Закрытие соединения с базой данных
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/evaluate.css">
    <title>Оценка профессий</title>
</head>
<body>
<h2><?php echo "Добрый день, $username! Оцените профессии:"; ?></h2>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label for="profession">Выберите профессию:</label>
    <select name="profession" id="profession">
        <?php while ($row_profession = $result_professions->fetch_assoc()): ?>
            <option value="<?php echo $row_profession['id']; ?>"><?php echo $row_profession['name']; ?></option>
        <?php endwhile; ?>
    </select><br>

    <!-- Добавляем поле для поиска по ПВК -->
    <label for="search">Поиск по ПВК:</label>
    <input type="text" name="search" id="search" placeholder="Введите ключевое слово"><br>

    <h3>Выберите профессионально важные качества по категориям:</h3>
    <?php 
    // Группировка ПВК по категориям
    $categories = [];
    while ($row_pvk = $result_pvk->fetch_assoc()) {
        $category = $row_pvk['category'];
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $row_pvk;
    }
    // Вывод качеств по категориям
    foreach ($categories as $category => $pvks) {
        echo "<h4>$category</h4>";
        foreach ($pvks as $pvk) {
            echo $pvk['name'] . " <input type='number' name='ratings[{$pvk['id']}][]' min='0' max='10'> <br>";
        }
    }
    ?>

    <input type="submit" name="submit" value="Сохранить оценки">
</form>
    <p><a href="home.php">Домой</a></p>
    <p><a href="rated_professions.php">Результаты оценки профессий</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>
