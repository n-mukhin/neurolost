<?php

require_once "db_connect.php";

// Проверяем, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respondent_id'])) {

    // Получение ID респондента
    $respondent_id = $_POST['respondent_id'];

    // Очищаем предыдущие выбранные тесты для данного респондента
    $sql_delete = "DELETE FROM respondent_tests WHERE respondent_id = ?";
    $statement = $mysqli->prepare($sql_delete);
    $statement->bind_param("i", $respondent_id);
    if ($statement->execute() === FALSE) {
        echo "Error deleting previous tests: " . $mysqli->error;
        exit; // Если произошла ошибка, завершаем выполнение скрипта
    }

    // Проверяем, были ли выбраны какие-либо тесты
    if (isset($_POST['test_order']) && is_array($_POST['test_order'])) {
        // Вставляем выбранные тесты для данного респондента в базу данных
        $sql_insert = "INSERT INTO respondent_tests (respondent_id, test_id, test_order) VALUES (?, ?, ?)";
        $statement = $mysqli->prepare($sql_insert);
        $statement->bind_param("iii", $respondent_id, $test_id, $test_order);
        foreach ($_POST['test_order'] as $test_id => $order) {
            $test_order = $order; // Получаем порядковый номер из выбранного порядка выполнения тестов
            if ($test_order != 0) { // Проверяем, что test_order не равен 0
                if ($statement->execute() === FALSE) {
                    echo "Error inserting test: " . $mysqli->error;
                    exit; // Если произошла ошибка, завершаем выполнение скрипта
                }
            }
        }
        echo "<script>alert('Выбранные тесты успешно сохранены для респондента.')</script>";
        echo "<script>window.location.href = 'tests/tests.php';</script>"; 
    } else {
        echo "<script>alert('Ошибка: не выбран ни один тест.')</script>";
        echo "<script>window.location.href = 'select_tests.php';</script>"; // Перенаправляем пользователя обратно на страницу выбора тестов
    }

    $mysqli->close();
} else {
    echo "<script>window.location.href = 'select_tests.php';</script>"; // Перенаправляем пользователя обратно на страницу выбора тестов
    echo "<script>alert('Ошибка: форма не была отправлена или не указан респондент.')</script>";
    
}

?>
