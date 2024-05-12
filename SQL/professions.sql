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
-- Структура таблицы `professions`
--

CREATE TABLE `professions` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `professions`
--

INSERT INTO `professions` (`id`, `name`, `description`) VALUES
(5, 'DevOps-инженер', 'Инженер DevOps — это ИТ-специалист общего профиля, которому нужны обширные знания в области разработки и эксплуатации, включая написание кода, управление инфраструктурой, системное администрирование и работу с пакетами инструментов DevOps.'),
(6, 'AR/VR-разработчик', 'VR / AR-разработчик — это специалист, занимающийся проектированием, разработкой и внедрением виртуальной и дополненной реальности в различные приложения и системы. Приложения создаются для планшетов, PC, смартфонов, очков и VR-шлемов.'),
(7, 'UI/UX дизайнер', 'UX/UI дизайнер — специалист, который проектирует и рисует интерфейсы цифровых продуктов: мобильных и веб-приложений, сайтов. Такой дизайнер может участвовать как в создании новых продуктов, так и помогать дорабатывать те, что уже запущены.');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `professions`
--
ALTER TABLE `professions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `professions`
--
ALTER TABLE `professions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
