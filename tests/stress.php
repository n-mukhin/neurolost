<?php
session_start();
require_once "../db-connect.php";

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

function get_pulse_data($mysqli, $user_id = null) {
    if ($user_id) {
        $sql = "SELECT * FROM pulse_data WHERE user_id = $user_id ORDER BY recorded_at DESC";
    } else {
        $sql = "SELECT pd.*, r.name FROM pulse_data pd JOIN respondents r ON pd.user_id = r.user_id ORDER BY recorded_at DESC";
    }
    $result = $mysqli->query($sql);
    $pulse_data = [];
    while ($row = $result->fetch_assoc()) {
        $pulse_data[] = $row;
    }
    return $pulse_data;
}

function get_respondents($mysqli) {
    $sql = "SELECT u.id, r.name, r.gender, r.age FROM users u JOIN respondents r ON u.id = r.user_id";
    $result = $mysqli->query($sql);
    $respondents = [];
    while ($row = $result->fetch_assoc()) {
        $respondents[] = $row;
    }
    return $respondents;
}

function calculate_stress_coefficient($avg_pulse) {
    $normal_pulse = 70;
    return abs($avg_pulse - $normal_pulse);
}

function get_pulse_color($pulse) {
    if ($pulse < 60 || $pulse > 100) {
        return 'red';
    } elseif (($pulse >= 60 && $pulse <= 70) || ($pulse >= 90 && $pulse <= 100)) {
        return 'yellow';
    } else {
        return 'green';
    }
}

$pulse_data = get_pulse_data($mysqli, $user_id);
$respondents = get_respondents($mysqli);

$expert_stress = 0;
$user_stress = 0;

if ($role === 'expert') {
    $total_stress = 0;
    $count = 0;
    foreach ($pulse_data as $data) {
        $total_stress += calculate_stress_coefficient($data['avg_pulse']);
        $count++;
    }
    if ($count > 0) {
        $expert_stress = $total_stress / $count;
    }
} else {
    $total_stress = 0;
    $count = 0;
    foreach ($pulse_data as $data) {
        $total_stress += calculate_stress_coefficient($data['avg_pulse']);
        $count++;
    }
    if ($count > 0) {
        $user_stress = $total_stress / $count;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Данные Пульса</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            width: 1000px;
            margin: 70px auto;
        }
        h1, h2, h3 {
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
        select, input[type="submit"], input[type="checkbox"] {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
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
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            margin-bottom: 20px;
        }
        .checkbox-container {
            display: inline-block;
            margin-right: 10px;
        }
        .red { background-color: #f8d7da; }
        .yellow { background-color: #fff3cd; }
        .green { background-color: #d4edda; }
    </style>
</head>
<body>
<header>
<p><a href="tests.php">Назад</a></p>
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
    <div class="container">
        <h1>Данные Пульса</h1>
        <?php if ($role === 'expert'): ?>
            <h2>Результаты Эксперта</h2>
            <?php if (empty($pulse_data)): ?>
                <p>Нет данных для отображения.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Максимальный Пульс</th>
                            <th>Минимальный Пульс</th>
                            <th>Коэффициент стресса</th>
                            <th>Время записи (сек)</th>
                            <th>Дата Записи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pulse_data as $data): ?>
                            <tr>
                                <td class="<?php echo get_pulse_color($data['max_pulse']); ?>"><?php echo htmlspecialchars($data['max_pulse']); ?></td>
                                <td class="<?php echo get_pulse_color($data['min_pulse']); ?>"><?php echo htmlspecialchars($data['min_pulse']); ?></td>
                                <td class="<?php echo get_pulse_color($data['avg_pulse']); ?>"><?php echo htmlspecialchars(calculate_stress_coefficient($data['avg_pulse'])); ?></td>
                                <td><?php echo htmlspecialchars($data['time_recorded']); ?></td>
                                <td><?php echo htmlspecialchars($data['recorded_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>Коэффициент стресса: <?php echo htmlspecialchars(round($expert_stress, 2)); ?></p>
            <?php endif; ?>

            <h2>Фильтрация респондентов</h2>
            <form id="filter-form">
                <label for="gender">Пол:</label>
                <select id="gender" name="gender">
                    <option value="">Все</option>
                    <option value="Male">Мужской</option>
                    <option value="Female">Женский</option>
                </select>

                <label for="age">Возраст:</label>
                <select id="age" name="age">
                    <option value="">Все</option>
                    <?php for ($i = 18; $i <= 65; $i += 5): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> - <?php echo $i + 4; ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <h2>Выберите респондентов для просмотра результатов:</h2>
            <form id="respondent-form" method="post" action="">
                <div id="respondent-select"></div>
                <input type="submit" value="Посмотреть результаты">
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respondent_ids'])) {
                $selected_respondent_ids = $_POST['respondent_ids'];
                if (count($selected_respondent_ids) > 0) {
                    $selected_data = [];
                    $stress_coefficients = [];
                    foreach ($selected_respondent_ids as $selected_respondent_id) {
                        $respondent_data = get_pulse_data($mysqli, $selected_respondent_id);
                        $selected_data[$selected_respondent_id] = $respondent_data;
                        $total_stress = 0;
                        $count = 0;
                        foreach ($respondent_data as $data) {
                            $total_stress += calculate_stress_coefficient($data['avg_pulse']);
                            $count++;
                        }
                        if ($count > 0) {
                            $stress_coefficient = $total_stress / $count;
                        } else {
                            $stress_coefficient = 0;
                        }
                        $stress_coefficients[] = [
                            'id' => $selected_respondent_id,
                            'name' => $respondents[array_search($selected_respondent_id, array_column($respondents, 'id'))]['name'],
                            'stress_coefficient' => $stress_coefficient,
                        ];
                    }

                    // Sort selected respondents by stress coefficient
                    usort($stress_coefficients, function($a, $b) {
                        return $a['stress_coefficient'] <=> $b['stress_coefficient'];
                    });

                    if (count($selected_respondent_ids) > 1) {
                    ?>
                    <h2>Топ респондентов по психической устойчивости</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Коэффициент стресса</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stress_coefficients as $respondent): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($respondent['name']); ?></td>
                                    <td><?php echo htmlspecialchars(round($respondent['stress_coefficient'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                    }
                    ?>

                    <h2>Результаты респондентов</h2>
                    <?php foreach ($selected_data as $respondent_id => $data): ?>
                        <h3><?php echo htmlspecialchars($respondents[array_search($respondent_id, array_column($respondents, 'id'))]['name']); ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Максимальный Пульс</th>
                                    <th>Минимальный Пульс</th>
                                    <th>Коэффициент стресса</th>
                                    <th>Время записи (сек)</th>
                                    <th>Дата Записи</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $pulse): ?>
                                    <tr>
                                        <td class="<?php echo get_pulse_color($pulse['max_pulse']); ?>"><?php echo htmlspecialchars($pulse['max_pulse']); ?></td>
                                        <td class="<?php echo get_pulse_color($pulse['min_pulse']); ?>"><?php echo htmlspecialchars($pulse['min_pulse']); ?></td>
                                        <td class="<?php echo get_pulse_color($pulse['avg_pulse']); ?>"><?php echo htmlspecialchars(calculate_stress_coefficient($pulse['avg_pulse'])); ?></td>
                                        <td><?php echo htmlspecialchars($pulse['time_recorded']); ?></td>
                                        <td><?php echo htmlspecialchars($pulse['recorded_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>Коэффициент стресса: <?php echo htmlspecialchars(round(array_column($stress_coefficients, 'stress_coefficient', 'id')[$respondent_id], 2)); ?></p>
                    <?php endforeach; ?>

                    <?php if (count($selected_respondent_ids) > 1): ?>
                        <h2>Сравнение респондентов</h2>
                        <div class="chart-container">
                            <canvas id="comparisonChart"></canvas>
                        </div>
                        <script>
                            const ctx = document.getElementById('comparisonChart').getContext('2d');
                            const comparisonChart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode(array_column($stress_coefficients, 'name')); ?>,
                                    datasets: [{
                                        label: 'Коэффициент стресса',
                                        data: <?php echo json_encode(array_column($stress_coefficients, 'stress_coefficient')); ?>,
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        </script>
                    <?php endif; ?>
                <?php } else { ?>
                    <p>Пожалуйста, выберите хотя бы одного респондента.</p>
                <?php } ?>
            <?php } ?>
        <?php else: ?>
            <h2>Ваши данные пульса</h2>
            <?php if (empty($pulse_data)): ?>
                <p>Нет данных для отображения.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Максимальный Пульс</th>
                            <th>Минимальный Пульс</th>
                            <th>Коэффициент стресса</th>
                            <th>Время записи (сек)</th>
                            <th>Дата Записи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pulse_data as $data): ?>
                            <tr>
                                <td class="<?php echo get_pulse_color($data['max_pulse']); ?>"><?php echo htmlspecialchars($data['max_pulse']); ?></td>
                                <td class="<?php echo get_pulse_color($data['min_pulse']); ?>"><?php echo htmlspecialchars($data['min_pulse']); ?></td>
                                <td class="<?php echo get_pulse_color($data['avg_pulse']); ?>"><?php echo htmlspecialchars(calculate_stress_coefficient($data['avg_pulse'])); ?></td>
                                <td><?php echo htmlspecialchars($data['time_recorded']); ?></td>
                                <td><?php echo htmlspecialchars($data['recorded_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>Коэффициент стресса: <?php echo htmlspecialchars(round($user_stress, 2)); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        const respondents = <?php echo json_encode($respondents); ?>;
        const respondentSelect = document.getElementById('respondent-select');

        function filterRespondents() {
            const gender = document.getElementById('gender').value;
            const age = document.getElementById('age').value;
            const filteredRespondents = respondents.filter(respondent => {
                let genderMatch = !gender || respondent.gender === gender;
                let ageMatch = !age || (respondent.age >= age && respondent.age < parseInt(age) + 5);
                return genderMatch && ageMatch;
            });
            respondentSelect.innerHTML = '';
            if (filteredRespondents.length > 0) {
                const selectAllDiv = document.createElement('div');
                selectAllDiv.classList.add('checkbox-container');
                const selectAllCheckbox = document.createElement('input');
                selectAllCheckbox.type = 'checkbox';
                selectAllCheckbox.id = 'select_all';
                selectAllCheckbox.onclick = selectAllRespondents;
                const selectAllLabel = document.createElement('label');
                selectAllLabel.htmlFor = 'select_all';
                selectAllLabel.textContent = 'Выбрать всех';
                selectAllDiv.appendChild(selectAllCheckbox);
                selectAllDiv.appendChild(selectAllLabel);
                respondentSelect.appendChild(selectAllDiv);
            }
            filteredRespondents.forEach(respondent => {
                const checkboxContainer = document.createElement('div');
                checkboxContainer.classList.add('checkbox-container');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'respondent_ids[]';
                checkbox.value = respondent.id;
                checkbox.id = 'respondent_' + respondent.id;
                const label = document.createElement('label');
                label.htmlFor = 'respondent_' + respondent.id;
                label.textContent = respondent.name;
                checkboxContainer.appendChild(checkbox);
                checkboxContainer.appendChild(label);
                respondentSelect.appendChild(checkboxContainer);
            });
        }

        function selectAllRespondents() {
            const selectAllCheckbox = document.getElementById('select_all');
            const checkboxes = document.querySelectorAll('#respondent-select input[type="checkbox"]:not(#select_all)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        document.getElementById('filter-form').addEventListener('change', function(e) {
            e.preventDefault();
            filterRespondents();
        });

        document.addEventListener('DOMContentLoaded', filterRespondents);
    </script>
</body>
</html>

<?php
$mysqli->close();
?>
