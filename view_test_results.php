<?php
session_start();

require_once "db_connect.php";

$respondent_id = $_SESSION['respondent_id'];

$query = "SELECT * FROM test_results WHERE respondent_id = $respondent_id";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Вывод результатов тестирования
    }
} else {
    echo "Результаты тестирования не найдены.";
}
?>
