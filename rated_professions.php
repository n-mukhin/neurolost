<?php
// Подключение к базе данных
require_once "db_connect.php";

session_start();

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
    <link rel="stylesheet" href="../css/header.css">
    <title>Результаты оценок профессий</title>
</head>
<body>
<header>
        <p><a href="index.php">Домой</a></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p><a href="account.php">Личный кабинет</a></p>
        <?php endif; ?>
    </header>
    <h2>Результаты оценок профессий</h2>
    <p><?php echo $username; ?>, Ознакомьтесь с результатами оценок профессий:</p>
    <?php while ($row_profession = $result_professions->fetch_assoc()): ?>
        <div class="profession">
            <h3><?php echo $row_profession['name']; ?></h3>
            <h3><?php echo $row_profession['name']; ?></h3>
            <p><strong>Описание:</strong> <?php echo $row_profession['description']; ?></p>
            <h4>Средняя оценка ПВК:</h4>
            <?php
            // Получаем среднюю оценку для каждой ПВК от экспертов
            $profession_id = $row_profession['id'];
            $query_expert_avg_ratings = "SELECT pvk.name, AVG(ratings.rating) as avg_rating
                                  FROM pvk
                                  LEFT JOIN ratings ON pvk.id = ratings.pvk_id
                                  WHERE ratings.profession_id = $profession_id
                                  AND ratings.user_id IN (SELECT user_id FROM users WHERE role = 'expert')
                                  GROUP BY pvk.id
                                  HAVING avg_rating IS NOT NULL";
            $result_expert_avg_ratings = $mysqli->query($query_expert_avg_ratings);

            // Выводим среднюю оценку для каждой ПВК от экспертов в виде полосы прогресса
            while ($row_expert_avg_rating = $result_expert_avg_ratings->fetch_assoc()): ?>
                <div class="pvk">
                    <span><?php echo $row_expert_avg_rating['name']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-inner" style="width: <?php echo ($row_expert_avg_rating['avg_rating'] * 10); ?>%;"></div>
                </div>
                <span class="progress-label"><?php echo number_format($row_expert_avg_rating['avg_rating'], 1); ?></span>
            <?php endwhile; ?>
       

            <?php
            // Если пользователь гость, показываем только его оценки
            if (!isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['guest_ratings'][$profession_id])): ?>
                    <h4>Ваша оценка:</h4>
                    <?php foreach ($_SESSION['guest_ratings'][$profession_id] as $pvk_id => $rating): ?>
                        <?php if ($rating > 0): ?>
                            <?php
                            // Получаем имя ПВК по его ID
                            $query_pvk_name = "SELECT name FROM pvk WHERE id = $pvk_id";
                            $result_pvk_name = $mysqli->query($query_pvk_name);
                            $pvk_name = $result_pvk_name->fetch_assoc()['name'];
                            ?>
                            <div class="pvk">
                                <span><?php echo $pvk_name; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-bar-inner" style="width: <?php echo ($rating * 10); ?>%;"></div>
                            </div>
                            <span class="progress-label"><?php echo $rating; ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // Если пользователь зарегистрирован и имеет оценки, показываем их
            if (isset($_SESSION['user_id'])): ?>
                <?php
                $user_id = $_SESSION['user_id'];
                $query_user_rating = "SELECT pvk.name, ratings.rating
                                      FROM ratings
                                      LEFT JOIN pvk ON ratings.pvk_id = pvk.id
                                      WHERE ratings.profession_id = $profession_id
                                      AND ratings.user_id = $user_id";
                $result_user_rating = $mysqli->query($query_user_rating);

                if ($result_user_rating->num_rows > 0): ?>
                    <h4>Ваша оценка:</h4>
                    <?php while ($row_user_rating = $result_user_rating->fetch_assoc()): ?>
                        <div class="pvk">
                            <span><?php echo $row_user_rating['name']; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-inner" style="width: <?php echo ($row_user_rating['rating'] * 10); ?>%;"></div>
                        </div>
                        <span class="progress-label"><?php echo $row_user_rating['rating']; ?></span>
                    <?php endwhile; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</body>
</html>

<?php
// Закрытие соединения с базой данных
$mysqli->close();
?>
