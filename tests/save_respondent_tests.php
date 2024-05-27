<?php
require_once "../db-connect.php";

// Проверяем, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respondent_id']) && isset($_POST['profession_id'])) {

    // Получение ID респондента и профессии
    $respondent_id = $_POST['respondent_id'];
    $profession_id = $_POST['profession_id'];

    // Очищаем предыдущие выбранные тесты для данного респондента
    $sql_delete = "DELETE FROM respondent_tests WHERE respondent_id = ?";
    $statement = $mysqli->prepare($sql_delete);
    $statement->bind_param("i", $respondent_id);
    if ($statement->execute() === FALSE) {
        echo "Error deleting previous tests: " . $mysqli->error;
        exit;
    }

    // Вставляем выбранные тесты для данного респондента в базу данных
    $sql_insert = "INSERT INTO respondent_tests (respondent_id, test_id, test_order, profession_id) VALUES (?, ?, ?, ?)";
    $statement = $mysqli->prepare($sql_insert);
    $statement->bind_param("iiii", $respondent_id, $test_id, $test_order, $profession_id);

    // Получаем тесты, связанные с выбранной профессией, из базы данных
    $sql_profession_tests = "SELECT test_id FROM evaluation_criteria WHERE profession_id = ?";
    $statement_profession_tests = $mysqli->prepare($sql_profession_tests);
    $statement_profession_tests->bind_param("i", $profession_id);
    $statement_profession_tests->execute();
    $result_profession_tests = $statement_profession_tests->get_result();

    $order = 1;
    while ($row = $result_profession_tests->fetch_assoc()) {
        $test_id = $row['test_id'];
        $test_order = $order;
        if ($statement->execute() === FALSE) {
            echo "Error inserting test: " . $mysqli->error;
            exit;
        }
        $order++;
    }

    echo "<script>alert('Тесты для выбранной профессии успешно сохранены для респондента.')</script>";
    echo "<script>window.location.href = 'tests.php';</script>";

    $mysqli->close();
} else {
    echo "<script>window.location.href = 'select_tests.php';</script>";
    echo "<script>alert('Ошибка: форма не была отправлена или не указан респондент.')</script>";
}
?>
