<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор респондента и тестов</title>
</head>
<body>
    <?php
    session_start();
    // Установка соединения с базой данных
    require_once "db_connect.php"; 

    // Проверяем, является ли пользователь экспертом
    $is_expert = false;

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Получаем информацию о пользователе
        $query_user = "SELECT username, role, expert_id, respondent_id FROM users WHERE id = ?";
        $statement = $mysqli->prepare($query_user);
        $statement->bind_param("i", $user_id);
        $statement->execute();
        $result_user = $statement->get_result();

        if ($result_user->num_rows == 1) {
            $row_user = $result_user->fetch_assoc();
            $username = $row_user['username'];
            $role = $row_user['role'];
            $expert_id = $row_user['expert_id'];
            $respondent_id = $row_user['respondent_id'];

            // Проверяем, является ли пользователь экспертом
            if ($role === 'expert') {
                $is_expert = true;

                // Получаем данные о респондентах из таблицы respondents
                $query_respondents = "SELECT r.id, r.name, r.age, u.username FROM respondents r JOIN users u ON r.user_id = u.id";
                $result_respondents = $mysqli->query($query_respondents);

                $respondents = array();
                while ($row_respondent = $result_respondents->fetch_assoc()) {
                    $respondents[$row_respondent['id']] = $row_respondent;
                }

                // Получаем список тестов, доступных эксперту
                $query_tests = "SELECT * FROM tests";
                $result_tests = $mysqli->query($query_tests);

                $tests = array();
                while ($row_test = $result_tests->fetch_assoc()) {
                    $tests[$row_test['id']] = $row_test['test_name'];
                }
            }
        }
    }
    ?>
    <?php if ($is_expert): ?>
        <form action='process_tests.php' method='post' id="testForm">
            <input type="hidden" name="respondent_id" value="<?php echo $respondent_id; ?>">
            <h2>Выберите респондента:</h2>
            <select name='respondent_id'>
                <?php foreach ($respondents as $respondent_id => $respondent_info): ?>
                    <option value='<?php echo $respondent_id; ?>'>
                        Пользователь: <?php echo $respondent_info['username']; ?>, 
                        Имя: <?php echo $respondent_info['name']; ?>, 
                        Возраст: <?php echo $respondent_info['age']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <h2>Выберите порядок прохождения тестов:</h2>
            <?php foreach ($tests as $test_id => $test_name): ?>
                <label for='test_<?php echo $test_id; ?>'>
                    <?php echo $test_name; ?>:
                </label>
                <select name='test_order[<?php echo $test_id; ?>]' class="test-order">
                    <option value='0'>Выберите порядок</option>
                    <?php for ($i = 1; $i <= count($tests); $i++): ?>
                        <option value='<?php echo $i; ?>'><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select><br>
            <?php endforeach; ?>
            <input type='submit' value='Сохранить'>
        </form>
        <script>
            // JavaScript для проверки выбранных значений
            const selects = document.querySelectorAll('.test-order');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    const selectedValues = Array.from(selects)
                        .filter(s => s !== select && s.value !== '0') // Исключаем текущий селект и те, где значение 0
                        .map(s => s.value);
                    const currentValue = this.value;
                    if (selectedValues.includes(currentValue)) {
                        alert('Это значение уже выбрано. Выберите другое значение.');
                        this.value = '0'; // Сбросить текущее значение
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
