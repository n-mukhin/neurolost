<?php
session_start();

// Подключение к базе данных
require_once "db_connect.php";

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Гость';

// Получаем список профессий из базы данных
$query_professions = "SELECT * FROM professions";
$result_professions = $mysqli->query($query_professions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/rated.css">
    <title>Результаты оценок профессий</title>
</head>
<body>
    <h2>Результаты оценок профессий</h2>
    <p><?php echo $username; ?>, Ознакомтесь с результатами оценок профессий:</p>
    <?php while ($row_profession = $result_professions->fetch_assoc()): ?>
        <div class="profession">
            <h3><?php echo $row_profession['name']; ?></h3>
            <p><strong>Описание:</strong> <?php echo $row_profession['description']; ?></p>
            <h4>Оценки ПВК:</h4>
            <?php
            // Получаем список ПВК для текущей профессии
            $profession_id = $row_profession['id'];
            $query_pvk = "SELECT pvk.name, AVG(ratings.rating) as avg_rating
                          FROM pvk
                          LEFT JOIN ratings ON pvk.id = ratings.pvk_id
                          WHERE ratings.profession_id = $profession_id
                          GROUP BY pvk.id
                          HAVING avg_rating IS NOT NULL";
            $result_pvk = $mysqli->query($query_pvk);

            // Выводим среднюю оценку для каждой ПВК в виде полосы прогресса
            while ($row_pvk = $result_pvk->fetch_assoc()): ?>
                <div class="pvk">
                    <span><?php echo $row_pvk['name']; ?></span>
                </div>
                <span class="progress-label"><?php echo number_format($row_pvk['avg_rating'], 1); ?></span>
                <div class="progress-bar">
                <div class="progress-bar-inner" style="width: <?php echo ($row_pvk['avg_rating'] * 10); ?>%;"></div>
                </div>
                
        
            <?php endwhile; ?>
            <?php
            // Получаем оценки экспертов для текущей профессии
            $query_ratings = "SELECT experts.name as expert_name, pvk.name as pvk_name, ratings.rating
                              FROM ratings
                              LEFT JOIN experts ON ratings.user_id = experts.id
                              LEFT JOIN pvk ON ratings.pvk_id = pvk.id
                              WHERE ratings.profession_id = $profession_id";
            $result_ratings = $mysqli->query($query_ratings);

            // Выводим индивидуальные оценки экспертов для каждой ПВК в виде полосы прогресса
            $current_expert = null;
            while ($row_rating = $result_ratings->fetch_assoc()): ?>
                <?php if ($current_expert !== $row_rating['expert_name']): ?>
                    <?php if ($current_expert !== null): ?>
                        </div> <!-- Закрываем div.expert-rating -->
                    <?php endif; ?>
                    <div class="expert-rating">
                        <br>
                        <span class="expert-name"><?php echo $row_rating['expert_name']; ?>:</span>
                <?php endif; ?>
                <div class="pvk">
                    <span><?php echo $row_rating['pvk_name']; ?></span>
                </div>
                <span class="progress-label"><?php echo $row_rating['rating']; ?></span>
                <div class="progress-bar">
                    <div class="progress-bar-inner" style="width: <?php echo ($row_rating['rating'] * 10); ?>%;"></div>
                </div>
               
            
                <?php $current_expert = $row_rating['expert_name']; ?>
            <?php endwhile; ?>
            <?php if ($current_expert !== null): ?>
                </div> <!-- Закрываем div.expert-rating -->
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
    <p><a href="home.php">Домой</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>

<?php
// Закрытие соединения с базой данных
$mysqli->close();
?>
