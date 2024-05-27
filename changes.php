<?php
session_start();
require_once "db-connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT role FROM users WHERE id = $user_id";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role = $row['role'];
} else {
    die("Пользователь не найден.");
}

if ($role !== 'expert') {
    die("Доступ запрещен.");
}

function get_professions($mysqli) {
    $sql = "SELECT id, name FROM professions";
    $result = $mysqli->query($sql);
    $professions = [];
    while ($row = $result->fetch_assoc()) {
        $professions[$row['id']] = $row['name'];
    }
    return $professions;
}

function get_pvk_list($mysqli) {
    $sql = "SELECT id, name FROM pvk";
    $result = $mysqli->query($sql);
    $pvk_list = [];
    while ($row = $result->fetch_assoc()) {
        $pvk_list[$row['id']] = $row['name'];
    }
    return $pvk_list;
}

function get_evaluation_criteria($mysqli) {
    $sql = "SELECT * FROM evaluation_criteria";
    $result = $mysqli->query($sql);
    $criteria = [];
    while ($row = $result->fetch_assoc()) {
        $criteria[$row['profession_id']][] = $row;
    }
    return $criteria;
}

function get_weights($mysqli) {
    $sql = "SELECT * FROM weights";
    $result = $mysqli->query($sql);
    $weights = [];
    while ($row = $result->fetch_assoc()) {
        $weights[$row['pvk_id']] = $row['weight'];
    }
    return $weights;
}

function get_thresholds($mysqli) {
    $sql = "SELECT * FROM thresholds";
    $result = $mysqli->query($sql);
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[$row['pvk_id']] = [
            'low' => $row['low_threshold'],
            'medium' => $row['medium_threshold'],
            'high' => $row['high_threshold']
        ];
    }
    return $thresholds;
}

$weights = get_weights($mysqli);
$thresholds = get_thresholds($mysqli);
$pvk_list = get_pvk_list($mysqli);
$evaluation_criteria = get_evaluation_criteria($mysqli);
$professions = get_professions($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['weights'])) {
        foreach ($_POST['weights'] as $pvk_id => $weight) {
            if ($weight !== '') {
                $pvk_id = (int)$pvk_id;
                $weight = (float)$weight;
                $sql = "INSERT INTO weights (pvk_id, weight) VALUES ($pvk_id, $weight) 
                        ON DUPLICATE KEY UPDATE weight = $weight";
                $mysqli->query($sql);
            }
        }
    }

    if (isset($_POST['thresholds'])) {
        foreach ($_POST['thresholds'] as $pvk_id => $threshold) {
            $pvk_id = (int)$pvk_id;
            $low_threshold = isset($threshold['low']) ? (float)$threshold['low'] : null;
            $medium_threshold = isset($threshold['medium']) ? (float)$threshold['medium'] : null;
            $high_threshold = isset($threshold['high']) ? (float)$threshold['high'] : null;

            $sql = "INSERT INTO thresholds (pvk_id, low_threshold, medium_threshold, high_threshold) 
                    VALUES ($pvk_id, $low_threshold, $medium_threshold, $high_threshold) 
                    ON DUPLICATE KEY UPDATE low_threshold = VALUES(low_threshold), medium_threshold = VALUES(medium_threshold), high_threshold = VALUES(high_threshold)";
            $mysqli->query($sql);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Критериями Оценки</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="../css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            width: 800px;
            margin: 70px auto;
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        select, input[type="text"], input[type="number"], input[type="submit"], button {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }
        input[type="submit"], button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
        }
    </style>
</head>
<body>
<header>
<p><a href="tests/tests.php">Назад</a></p>
    <p><a href="index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
    <div class="container">
        <h1>Корректировка Весов и Порогов ПВК</h1>
        <?php foreach ($professions as $profession_id => $profession_name): ?>
            <h2><?php echo $profession_name; ?></h2>
            <?php if (isset($evaluation_criteria[$profession_id])): ?>
                <form method="post" action="">
                    <h3>Текущие веса и пороги</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ПВК</th>
                                <th>Вес</th>
                                <th>Низкий порог</th>
                                <th>Средний порог</th>
                                <th>Высокий порог</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $weights = get_weights($mysqli);
                            $thresholds = get_thresholds($mysqli);
                            foreach ($evaluation_criteria[$profession_id] as $criterion): ?>
                                <tr>
                                    <td><?php echo $pvk_list[$criterion['pvk_id']]; ?></td>
                                    <td><?php echo $weights[$criterion['pvk_id']] ?? '0'; ?></td>
                                    <td><?php echo $thresholds[$criterion['pvk_id']]['low'] ?? '0'; ?></td>
                                    <td><?php echo $thresholds[$criterion['pvk_id']]['medium'] ?? '0'; ?></td>
                                    <td><?php echo $thresholds[$criterion['pvk_id']]['high'] ?? '0'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <h3>Изменить значения</h3>
                    <label for="preset_factor_<?php echo $profession_id; ?>">Выберите внешний человеческий фактор:</label>
                    <select name="preset_factor_<?php echo $profession_id; ?>" id="preset_factor_<?php echo $profession_id; ?>" onchange="applyPreset(<?php echo $profession_id; ?>)">
                        <option value="">Не выбрано</option>
                        <option value="stress">Стресс</option>
                        <option value="insomnia">Бессоница</option>
                        <option value="weekend">Выходные</option>
                    </select>
                    <table>
                        <thead>
                            <tr>
                                <th>ПВК</th>
                                <th>Вес</th>
                                <th>Низкий порог</th>
                                <th>Средний порог</th>
                                <th>Высокий порог</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($evaluation_criteria[$profession_id] as $criterion): ?>
                                <tr>
                                    <td><?php echo $pvk_list[$criterion['pvk_id']]; ?></td>
                                    <td><input type="number" step="0.01" name="weights[<?php echo $criterion['pvk_id']; ?>]" value="<?php echo $weights[$criterion['pvk_id']] ?? '0'; ?>" onchange="autoFillThresholds(this, <?php echo $criterion['pvk_id']; ?>, <?php echo $profession_id; ?>)"></td>
                                    <td><input type="number" step="0.01" name="thresholds[<?php echo $criterion['pvk_id']; ?>][low]" value="<?php echo $thresholds[$criterion['pvk_id']]['low'] ?? '0'; ?>"></td>
                                    <td><input type="number" step="0.01" name="thresholds[<?php echo $criterion['pvk_id']; ?>][medium]" value="<?php echo $thresholds[$criterion['pvk_id']]['medium'] ?? '0'; ?>"></td>
                                    <td><input type="number" step="0.01" name="thresholds[<?php echo $criterion['pvk_id']; ?>][high]" value="<?php echo $thresholds[$criterion['pvk_id']]['high'] ?? '0'; ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="submit" value="Сохранить изменения">
                    <button type="button" onclick="clearValues(<?php echo $profession_id; ?>)">Сбросить значения</button>
                </form>
            <?php else: ?>
                <p>Нет значений для профессии <?php echo $profession_name; ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script>
        function applyPreset(professionId) {
            const presetFactor = document.getElementById('preset_factor_' + professionId).value;
            let adjustment;

            switch (presetFactor) {
                case 'stress':
                    adjustment = 0.2;
                    break;
                case 'insomnia':
                    adjustment = 0.1;
                    break;
                case 'weekend':
                    adjustment = -0.3;
                    break;
                default:
                    adjustment = 0;
            }

            const weightInputs = document.querySelectorAll(`form select[name='preset_factor_${professionId}'] ~ table input[name^="weights"]`);
            weightInputs.forEach(input => {
                let weight = parseFloat(input.value);
                if (!isNaN(weight)) {
                    weight = Math.max(0, weight + adjustment);
                    input.value = weight.toFixed(2);

                    const pvkId = input.name.match(/\d+/)[0];
                    const lowThresholdInput = document.querySelector(`input[name='thresholds[${pvkId}][low]']`);
                    const mediumThresholdInput = document.querySelector(`input[name='thresholds[${pvkId}][medium]']`);
                    const highThresholdInput = document.querySelector(`input[name='thresholds[${pvkId}][high]']`);

                    lowThresholdInput.value = Math.max(0, (weight * 0.5)).toFixed(2);
                    mediumThresholdInput.value = Math.max(0, (weight * 0.75)).toFixed(2);
                    highThresholdInput.value = Math.max(0, (weight * 1.0)).toFixed(2);
                }
            });
        }

        function autoFillThresholds(weightInput, pvkId, professionId) {
            const weight = parseFloat(weightInput.value);
            const lowThresholdInput = document.querySelector(`form select[name='preset_factor_${professionId}'] ~ table input[name='thresholds[${pvkId}][low]']`);
            const mediumThresholdInput = document.querySelector(`form select[name='preset_factor_${professionId}'] ~ table input[name='thresholds[${pvkId}][medium]']`);
            const highThresholdInput = document.querySelector(`form select[name='preset_factor_${professionId}'] ~ table input[name='thresholds[${pvkId}][high]']`);

            if (!isNaN(weight)) {
                lowThresholdInput.value = Math.max(0, (weight * 0.5)).toFixed(2);
                mediumThresholdInput.value = Math.max(0, (weight * 0.75)).toFixed(2);
                highThresholdInput.value = Math.max(0, (weight * 1.0)).toFixed(2);
            }
        }

        function clearValues(professionId) {
            const form = document.querySelector(`form select[name='preset_factor_${professionId}']`).closest('form');
            const inputs = form.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.value = '';
            });
        }
    </script>
</body>
</html>

<?php
$mysqli->close();
?>
