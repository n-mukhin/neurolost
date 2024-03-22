<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

// Если пользователь отправил форму оценки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Проверяем, зарегистрирован ли пользователь
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // Если пользователь не зарегистрирован, установим $user_id в NULL
        $user_id = NULL;
    }

    $profession_id = $_POST['profession'];
    $ratings = $_POST['ratings'];
    $has_non_empty_ratings = false;

    // Проверяем, есть ли хотя бы одна непустая оценка
    foreach ($ratings as $pvk_id => $rating) {
        if (!empty($rating) && $rating > 0) {
            $has_non_empty_ratings = true;
            break;
        }
    }

    // Если есть хотя бы одна непустая оценка, сохраняем оценки в базу данных
    if ($has_non_empty_ratings) {
        save_ratings_to_database($mysqli, $profession_id, $user_id, $ratings);

        // Перенаправляем пользователя на страницу с результатами оценки
        header("Location: rated_professions.php");
        exit;
    } else {
        // Если все оценки пусты, выводим сообщение об ошибке
        $error = "Необходимо заполнить хотя бы одну оценку.";
    }
} else {
    // Если гость отправил форму оценки
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
        $profession_id = $_POST['profession'];

        // Получаем оценки из формы
        $ratings = $_POST['ratings'];

        // Проверяем, есть ли хотя бы одна оценка, которая не пуста
        $has_non_empty_ratings = false;
        foreach ($ratings as $pvk_id => $rating) {
            if (!empty($rating) && $rating > 0) {
                $has_non_empty_ratings = true;
                break; // Если хотя бы одна оценка не пуста, прекращаем цикл
            }
        }

        // Если хотя бы одна оценка не пуста, сохраняем оценки в сессию
        if ($has_non_empty_ratings) {
            // Сохраняем оценки в сессию
            $_SESSION['guest_ratings'][$profession_id] = $ratings;

            // Перенаправляем пользователя на страницу с результатами оценки
            header("Location: rated_professions.php");
            exit;
        } else {
            // Если все оценки пусты, выводим сообщение об ошибке
            $error = "Необходимо заполнить хотя бы одну оценку.";
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

// Функция для сохранения оценок в базу данных
function save_ratings_to_database($mysqli, $profession_id, $user_id, $ratings) {
    foreach ($ratings as $pvk_id => $rating) {
        // Проверяем, есть ли оценки для данной ПВК и они не пусты
        if (!empty($rating) && $rating > 0) {
            // Подготавливаем запрос для вставки оценки
            $insert_query = "INSERT INTO ratings (profession_id, pvk_id, user_id, rating) VALUES (?, ?, ?, ?)";
            $statement_insert = $mysqli->prepare($insert_query);
            $statement_insert->bind_param("iiii", $profession_id, $pvk_id, $user_id, $rating);
            $statement_insert->execute();
        }
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/evaluate.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="css/background.css">
    <title>Оценка профессий</title>
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
    <h2>Оцените профессии:</h2>
    <?php if(isset($error)) echo $error; ?>
    <!-- Выводим сообщение об ошибке, если есть -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="profession">Выберите профессию:</label>
        <select name="profession" id="profession">
            <?php while ($row_profession = $result_professions->fetch_assoc()): ?>
                <option value="<?php echo $row_profession['id']; ?>"><?php echo $row_profession['name']; ?></option>
            <?php endwhile; ?>
        </select><br>
        <h3>Выберите профессионально важные качества:</h3>
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
                echo $pvk['name'] . " <input type='number' name='ratings[{$pvk['id']}]' min='0' max='10'> <br>";
            }
        }
        ?>
        <input type="submit" name="submit" value="Сохранить оценки">
    </form>
</div>
<script>
      window.addEventListener('scroll', function() {
            var header = document.querySelector('header');
            var container = document.getElementById('container');
            var headerHeight = header.offsetHeight;
            container.style.marginTop = headerHeight + 'px';
        });
    </script>
</body>
</html>