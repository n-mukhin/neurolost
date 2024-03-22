<?php
$host = "VH306.spaceweb.ru";
$username = "mukhinnnik";
$password = "XQJ712BCKUQX@gM3";
$database = "mukhinnnik";
$port = 3308;

// Подключение к базе данных
$mysqli = new mysqli($host, $username, $password, $database, $port);

// Проверяем соединение
if ($mysqli->connect_error) {
    die("Ошибка подключения к базе данных: " . $mysqli->connect_error);
}
?>
