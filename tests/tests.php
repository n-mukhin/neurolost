<?php
session_start();

// Подключение к базе данных
require_once "../db-connect.php";

// Проверка, авторизован ли пользователь
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// Проверяем, является ли пользователь администратором, экспертом, респондентом или обычным пользователем
$is_admin = false;
$is_expert = false;
$is_respondent = false;
$is_user = false;

// Получаем информацию о пользователе
$query_user = "SELECT u.username, u.role, u.respondent_id FROM users u WHERE u.id = ?";
$statement = $mysqli->prepare($query_user);
$statement->bind_param("i", $user_id);
$statement->execute();
$result_user = $statement->get_result();

if ($result_user->num_rows == 1) {
    $row_user = $result_user->fetch_assoc();
    $role = $row_user['role'];

    // Устанавливаем флаги для разных ролей
    $is_admin = $role === 'admin';
    $is_expert = $role === 'expert';
    $is_respondent = $role === 'respondent';
    $is_user = $role === 'user';

    // Если пользователь - респондент, получаем его respondent_id
    if ($is_respondent) {
        $respondent_id = $row_user['respondent_id'];
    }
}

// Получаем все тесты
$query_all_tests = "SELECT id, test_name, test_type, file_path FROM tests";
$result_all_tests = $mysqli->query($query_all_tests);

$tests = []; // Инициализируем массив

// Заполняем массив тестов, если запрос успешен
if ($result_all_tests) {
    while ($row_test = $result_all_tests->fetch_assoc()) {
        $tests[$row_test['test_type']][] = $row_test;
    }
}

// Получаем тесты для каждой профессии
$query_profession_tests = "SELECT ec.profession_id, t.id as test_id, t.test_name, t.test_type, t.file_path 
                           FROM tests t
                           INNER JOIN evaluation_criteria ec ON t.id = ec.test_id";
$result_profession_tests = $mysqli->query($query_profession_tests);

$profession_tests = [];
if ($result_profession_tests) {
    while ($row_profession_test = $result_profession_tests->fetch_assoc()) {
        $profession_tests[$row_profession_test['profession_id']][$row_profession_test['test_type']][] = $row_profession_test;
    }
}

// Определение профессий
$professions = [
    5 => 'DevOps-инженер',
    6 => 'AR/VR-разработчик',
    7 => 'UI/UX дизайнер'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/tests.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/background.css">
    <title>Список тестов</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function(){
            $(".test-type").click(function(){
                $(this).next(".test-names").toggle();
            });
            $(".profession").click(function(){
                $(this).next(".profession-tests").toggle();
            });
        });
    </script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        header {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .container {
            margin-top: 70px; /* Отступ сверху, чтобы не накладываться на header */
            margin-bottom: 20px; /* Отступ снизу */
            max-height: calc(100vh - 90px); /* Ограничение высоты контейнера */
            overflow-y: hidden;
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: #888 #f1f1f1; /* Firefox */
   
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
        }

        /* Стили для полосы прокрутки в Chrome, Edge и Safari */
        .container::-webkit-scrollbar {
            width: 12px;
        }

        .container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .container::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
            border: 3px solid #f1f1f1;
        }

        .container::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }

        .container:hover {
            overflow-y: auto;
        }

        .test-type {
            font-weight: bold;
            margin-top: 10px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
        }

        .test-names {
            margin-left: 20px;
            margin-bottom: 10px;
            list-style-type: none;
            padding-left: 0;
        }

        .test-names li {
            margin-top: 5px;
            padding: 5px;

        }

        .profession {
            font-weight: bold;
            margin-top: 20px;
            cursor: pointer;
            padding: 5px;
    
            border-radius: 5px;
        }

        .profession-tests {
            margin-left: 20px;
            margin-bottom: 20px;
            list-style-type: none;
            padding-left: 0;
        }

        .profession-tests li {
            margin-top: 5px;
            padding: 5px;

        }
    
    </style>
</head>
<body>
<div class="background"></div>
<header>
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
<div class="container">
    <h1>Тесты</h1>
    <ul>
        <?php if ($is_expert || $is_admin): ?>
            <li><a href="view_test_results.php"  target="_blank">Результаты тестов</a></li>
            <li><a href="process_tests.php"  target="_blank">Назначить тесты респондентам</a></li>
            <li><a href="../changes.php"  target="_blank">Корректировка Весов и Порогов</a></li>
            <li><a href="../suitability.php"  target="_blank">Предрасположенность к профессиям</a></li>
            <li><a href="../progress.php"  target="_blank">Прогресс по развитию навыков</a></li>
            <li><a href="stress.php"  target="_blank">Уровень стресса</a></li>
            <h3>Список доступных тестов:</h3>
            <!-- Показываем все тесты для администраторов и экспертов -->
            <?php foreach ($tests as $test_type => $test_list): ?>
                <li>
                    <span class="test-type"><?php echo $test_type; ?></span>
                    <ul class="test-names" style="display:none;">
                        <?php foreach ($test_list as $test): ?>
                            <li><a href='<?php echo $test['file_path']; ?>'  target="_blank"><?php echo $test['test_name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
            <h3>Тесты по профессиям:</h3>
            <?php foreach ($professions as $profession_id => $profession_name): ?>
                <?php if (isset($profession_tests[$profession_id])): ?>
                    <li>
                        <span class="profession"><?php echo $profession_name; ?></span>
                        <ul class="profession-tests" style="display:none;">
                            <?php foreach ($profession_tests[$profession_id] as $test_type => $test_list): ?>
                                <li>
                                    <span class="test-type"><?php echo $test_type; ?></span>
                                    <ul class="test-names" style="display:none;">
                                        <?php foreach ($test_list as $test): ?>
                                            <li><a href='<?php echo $test['file_path']; ?>'  target="_blank"><?php echo $test['test_name']; ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php elseif ($is_respondent): ?>
            <br>
            <li><a href="view_test_results.php"  target="_blank">Результаты тестов</a></li>
            <li><a href="../suitability.php"  target="_blank">Предрасположенность к профессиям</a></li>
            <li><a href="../progress.php"  target="_blank">Прогресс по развитию навыков</a></li>
            <li><a href="stress.php"  target="_blank">Уровень стресса</a></li>
            <h3>Назначенные тесты:</h3>
            <!-- Показываем только тесты, назначенные респонденту -->
            <?php
            $query_respondent_tests = "SELECT t.id, t.test_name, t.test_type, t.file_path, ec.profession_id
                                       FROM tests t
                                       INNER JOIN respondent_tests rt ON t.id = rt.test_id
                                       INNER JOIN evaluation_criteria ec ON t.id = ec.test_id
                                       WHERE rt.respondent_id = ?
                                       ORDER BY rt.test_order";
            $statement_respondent_tests = $mysqli->prepare($query_respondent_tests);
            $statement_respondent_tests->bind_param("i", $respondent_id);
            $statement_respondent_tests->execute();
            $result_respondent_tests = $statement_respondent_tests->get_result();
            
            $respondent_tests = [];
            while ($row_respondent_test = $result_respondent_tests->fetch_assoc()) {
                $respondent_tests[$row_respondent_test['profession_id']][$row_respondent_test['test_type']][] = $row_respondent_test;
            }
            ?>

            <?php if (empty($respondent_tests)): ?>
                <p>Вам еще не назначили тесты</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($respondent_tests as $profession_id => $test_types): ?>
                        <li>
                            <span class="profession"><?php echo $professions[$profession_id] ?? 'Назначенные тесты'; ?></span>
                            <ul class="profession-tests" style="display:none;">
                                <?php foreach ($test_types as $test_type => $test_list): ?>
                                    <li>
                                        <span class="test-type"><?php echo $test_type; ?></span>
                                        <ul class="test-names" style="display:none;">
                                            <?php foreach ($test_list as $test): ?>
                                                <li><a href='<?php echo $test['file_path']; ?>'  target="_blank"><?php echo $test['test_name']; ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <!-- Показываем все тесты для неавторизованных пользователей -->
            <?php foreach ($tests as $test_type => $test_list): ?>
                <li>
                    <span class="test-type"><?php echo $test_type; ?></span>
                    <ul class="test-names" style="display:none;">
                        <?php foreach ($test_list as $test): ?>
                            <li><a href='<?php echo $test['file_path']; ?>'  target="_blank"><?php echo $test['test_name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Показываем сообщение о необходимости стать респондентом -->
            <li>Чтобы получить доступ к тестам, необходимо <a href="../register-respondent.php">стать респондентом</a></li>
        <?php endif; ?>
    </ul>
</div>
<script src="../Puppeteer/widget.js"></script>
</body>
</html>

<?php
$mysqli->close();
?>
