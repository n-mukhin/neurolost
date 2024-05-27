-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Май 27 2024 г., 12:21
-- Версия сервера: 8.0.29-21
-- Версия PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `mukhinnnik`
--

-- --------------------------------------------------------

--
-- Структура таблицы `evaluation_criteria`
--

CREATE TABLE `evaluation_criteria` (
  `id` int NOT NULL,
  `profession_id` int NOT NULL,
  `pvk_id` int NOT NULL,
  `test_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `evaluation_criteria`
--

INSERT INTO `evaluation_criteria` (`id`, `profession_id`, `pvk_id`, `test_id`) VALUES
(1, 5, 27, 16),
(2, 5, 26, 10),
(3, 5, 63, 12),
(4, 5, 67, 4),
(5, 5, 101, 6),
(6, 6, 58, 14),
(7, 6, 76, 3),
(8, 6, 57, 5),
(9, 6, 47, 8),
(10, 6, 67, 7),
(11, 7, 67, 11),
(12, 7, 61, 9),
(13, 7, 62, 2),
(14, 7, 48, 15),
(15, 7, 49, 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profession_id` (`profession_id`),
  ADD KEY `pvk_id` (`pvk_id`),
  ADD KEY `test_id` (`test_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  ADD CONSTRAINT `evaluation_criteria_ibfk_1` FOREIGN KEY (`profession_id`) REFERENCES `professions` (`id`),
  ADD CONSTRAINT `evaluation_criteria_ibfk_2` FOREIGN KEY (`pvk_id`) REFERENCES `pvk` (`id`),
  ADD CONSTRAINT `evaluation_criteria_ibfk_3` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
