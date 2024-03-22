<?php
session_start();

// Проверяем, вошел ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Подключение к базе данных
require_once "db_connect.php";

// Проверяем, если пользователь уже вошел в систему
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Получаем информацию о пользователе
    $query = "SELECT username, role FROM users WHERE id = ?";
    $statement = $mysqli->prepare($query);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result = $statement->get_result();

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $role = $row['role'];

        // Проверяем, является ли пользователь администратором
        $is_admin = $role === 'admin';

        // Проверяем, является ли пользователь экспертом
        $is_expert = $role === 'expert';
    }
}

// Обработка добавления нового эксперта
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_expert'])) {
    $name = $_POST['name'];
    $sgroup = $_POST['sgroup'];
    $code = $_POST['code'];

    // Подготавливаем запрос для добавления нового эксперта
    $add_query = "INSERT INTO experts (name, sgroup, code) VALUES (?, ?, ?)";
    $add_statement = $mysqli->prepare($add_query);
    $add_statement->bind_param("sss", $name, $sgroup, $code);

    // Выполняем запрос
    if ($add_statement->execute()) {
        // После успешного добавления перезагружаем страницу для обновления списка экспертов
        header("Location: experts.php");
        exit;
    } else {
        echo "Ошибка при добавлении эксперта: " . $mysqli->error;
    }
}

// Обработка удаления эксперта
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $expert_id = $_POST['expert_id'];

    // Удаляем записи из таблицы ratings, связанные с удаляемым экспертом
    $delete_ratings_query = "DELETE FROM ratings WHERE user_id = ?";
    $delete_ratings_statement = $mysqli->prepare($delete_ratings_query);
    $delete_ratings_statement->bind_param("i", $expert_id);

    // Выполняем запрос на удаление записей из таблицы ratings
    if ($delete_ratings_statement->execute()) {
        // После успешного удаления записей из ratings, удаляем самого эксперта
        $delete_expert_query = "DELETE FROM experts WHERE id = ?";
        $delete_expert_statement = $mysqli->prepare($delete_expert_query);
        $delete_expert_statement->bind_param("i", $expert_id);

        // Выполняем запрос на удаление эксперта
        if ($delete_expert_statement->execute()) {
            // После успешного удаления перезагружаем страницу для обновления списка экспертов
            header("Location: experts.php");
            exit;
        } else {
            echo "Ошибка при удалении эксперта: " . $mysqli->error;
        }
    } else {
        echo "Ошибка при удалении записей из таблицы ratings: " . $mysqli->error;
    }
}