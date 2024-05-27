<?php
session_start();
require_once "../db-connect.php";

// Получаем список респондентов
$sql_respondents = "SELECT id, name FROM respondents";
$result_respondents = $mysqli->query($sql_respondents);
$respondents = [];
while ($row = $result_respondents->fetch_assoc()) {
    $respondents[] = $row;
}

// Получаем список профессий
$sql_professions = "SELECT id, name FROM professions";
$result_professions = $mysqli->query($sql_professions);
$professions = [];
while ($row = $result_professions->fetch_assoc()) {
    $professions[] = $row;
}

// Получаем все тесты, связанные с профессиями
$sql_profession_tests = "SELECT profession_id, t.id as test_id, t.test_type, t.test_name 
                         FROM tests t
                         INNER JOIN evaluation_criteria ec ON t.id = ec.test_id";
$result_profession_tests = $mysqli->query($sql_profession_tests);
$profession_tests = [];
while ($row = $result_profession_tests->fetch_assoc()) {
    $profession_tests[$row['profession_id']][] = $row;
}

// Проверяем, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respondent_id']) && isset($_POST['professions'])) {
    // Получение ID респондента и выбранных профессий
    $respondent_id = $_POST['respondent_id'];
    $selected_professions = $_POST['professions'];

    // Очищаем предыдущие выбранные тесты для данного респондента
    $sql_delete = "DELETE FROM respondent_tests WHERE respondent_id = ?";
    $statement = $mysqli->prepare($sql_delete);
    $statement->bind_param("i", $respondent_id);
    if ($statement->execute() === FALSE) {
        echo "Error deleting previous tests: " . $mysqli->error;
        exit;
    }

    // Вставляем выбранные тесты для данного респондента в базу данных
    $sql_insert = "INSERT INTO respondent_tests (respondent_id, test_id, test_order) VALUES (?, ?, ?)";
    $statement = $mysqli->prepare($sql_insert);
    $statement->bind_param("iii", $respondent_id, $test_id, $test_order);

    $order = 1;
    foreach ($selected_professions as $profession_id) {
        if (isset($profession_tests[$profession_id])) {
            foreach ($profession_tests[$profession_id] as $test) {
                $test_id = $test['test_id'];
                $test_order = $order++;
                if ($statement->execute() === FALSE) {
                    echo "Error inserting test: " . $mysqli->error;
                    exit;
                }
            }
        }
    }

    echo "<script>alert('Тесты для выбранных профессий успешно сохранены для респондента.')</script>";
    echo "<script>window.location.href = 'tests.php';</script>";

    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выбор тестов для респондента</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h3, label {
            color: #333;
        }
        select, input[type="number"], button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .test-type-group {
            margin-bottom: 20px;
        }
        .test-type-group h4 {
            margin-bottom: 10px;
            cursor: pointer;
        }
        .test-type-group ul {
            list-style-type: none;
            padding: 0;
        }
        .test-type-group ul li {
            padding: 5px 0;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $(".test-type-group h4").click(function(){
                $(this).next("ul").toggle();
            });

            $('input[name="professions[]"]').change(function() {
                var selectedProfessions = Array.from($('input[name="professions[]"]:checked')).map(cb => cb.value);
                var testItems = $('.test-item');

                testItems.find('input').prop('disabled', true).val(0);

                var professionTests = <?php echo json_encode($profession_tests); ?>;
                var order = 1;

                selectedProfessions.forEach(function(professionId) {
                    if (professionTests[professionId]) {
                        professionTests[professionId].forEach(function(test) {
                            var testItem = $('.test-item[data-test-id="' + test.test_id + '"]');
                            testItem.find('input').prop('disabled', false).val(order++);
                        });
                    }
                });
            });
        });
    </script>
</head>
<body>
<header>
    <p><a href="tests.php">Назад</a></p>
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
    <div class="container">
        <h1>Выбор тестов для респондента</h1>
        <form method="POST" action="">
            <label for="respondent_id">Респондент:</label>
            <select name="respondent_id" id="respondent_id" required>
                <option value="">Выберите респондента</option>
                <?php foreach ($respondents as $respondent): ?>
                    <option value="<?php echo $respondent['id']; ?>"><?php echo $respondent['name']; ?></option>
                <?php endforeach; ?>
            </select>

            <h3>Профессии:</h3>
            <?php foreach ($professions as $profession): ?>
                <label>
                    <input type="checkbox" name="professions[]" value="<?php echo $profession['id']; ?>">
                    <?php echo $profession['name']; ?>
                </label><br>
            <?php endforeach; ?>

            <h3>Выбранные тесты:</h3>
            <div id="test-list">
                <?php
                $test_types = [];
                foreach ($profession_tests as $profession_id => $tests) {
                    foreach ($tests as $test) {
                        $test_types[$test['test_type']][] = $test;
                    }
                }
                foreach ($test_types as $test_type => $tests):
                ?>
                    <div class="test-type-group">
                        <h4><?php echo $test_type; ?></h4>
                        <ul style="display:none;">
                            <?php foreach ($tests as $test): ?>
                                <li class="test-item" data-test-id="<?php echo $test['test_id']; ?>">
                                    <label for="test_order_<?php echo $test['test_id']; ?>"><?php echo $test['test_name']; ?></label>
                                    <input type="number" name="test_order[<?php echo $test['test_id']; ?>]" id="test_order_<?php echo $test['test_id']; ?>" min="0" placeholder="0" disabled>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
<?php
$mysqli->close();
?>
