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
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int NOT NULL,
  `test_type` varchar(255) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `test_type`, `test_name`, `file_path`) VALUES
(1, 'Оценка простых сенсомоторных реакций человека', 'реакция на свет', 'simple_color_test.php'),
(2, 'Оценка простых сенсомоторных реакций человека', 'реакция на звук', 'sound_reaction_test.php'),
(3, 'Оценка сложных сенсомоторных реакций человека', 'оценка скорости реакции на разные цвета', 'advanced_color_test.php'),
(4, 'Оценка сложных сенсомоторных реакций человека', 'оценка скорости реакции на сложный звуковой сигнал – сложение в уме ', 'audio_count_test.php'),
(5, 'Оценка сложных сенсомоторных реакций человека', 'оценка скорости реакции на сложение в уме (чет/нечет) - визуально', 'visual_count_test.php'),
(6, 'Оценка простой реакции человека на движущийся объект', 'реакция на движение', 'movement_test.php'),
(7, 'Оценка сложной реакции человека на движущиеся объекты', 'реакция на множество движущихся объектов', 'advanced_movement_test.php'),
(8, 'Оценка аналогового слежения', 'реакция на изменение направления движения', 'analog_test.php'),
(9, 'Оценка слежения с преследованием', 'слежение за объектом', 'chaseTest.php');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `test_name` (`test_name`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
