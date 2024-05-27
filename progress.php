<?php
session_start();

require_once "db-connect.php";

function get_pvk_name($mysqli, $pvk_id) {
    if (empty($pvk_id)) {
        return "Неизвестное ПВК";
    }
    $sql = "SELECT name FROM pvk WHERE id = $pvk_id";
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['name'];
    } else {
        return "Неизвестное ПВК";
    }
}

function calculate_z_score($x, $mean, $stddev, $n) {
    if ($stddev != 0) {
        return ($x - $mean) / ($stddev / sqrt($n));
    } else {
        return 0;
    }
}

function calculate_suitability($mysqli, $user_id, $profession_id) {
    $test_sql = "SELECT t.id as test_id, ec.pvk_id FROM tests t
                 JOIN evaluation_criteria ec ON t.id = ec.test_id
                 WHERE ec.profession_id = $profession_id";
    $test_result = $mysqli->query($test_sql);

    $test_ids = [];
    $pvk_map = [];
    while ($row = $test_result->fetch_assoc()) {
        $test_ids[] = $row['test_id'];
        $pvk_map[$row['test_id']] = $row['pvk_id'];
    }

    if (empty($test_ids)) {
        return ["pvk_data" => [], "overall_score" => 0];
    }

    $test_ids_str = implode(',', $test_ids);

    $sql = "SELECT test_id, result FROM test_results WHERE user_id = $user_id AND test_id IN ($test_ids_str)";
    $result = $mysqli->query($sql);

    $pvk_ratings = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $test_id = $row['test_id'];
            $pvk_id = $pvk_map[$test_id];
            $rating = $row['result'];

            $stats_sql = "SELECT AVG(result) as mean_result, STDDEV(result) as stddev_result, COUNT(*) as n FROM test_results WHERE test_id = $test_id";
            $stats_result = $mysqli->query($stats_sql);
            $stats = $stats_result->fetch_assoc();

            $mean_result = $stats['mean_result'];
            $stddev_result = $stats['stddev_result'];
            $n = $stats['n'];

            $z_score = calculate_z_score($rating, $mean_result, $stddev_result, $n);

            if (!isset($pvk_ratings[$pvk_id])) {
                $pvk_ratings[$pvk_id] = 0;
            }
            $pvk_ratings[$pvk_id] += $rating * $z_score;
        }
    }

    $total_score = 0;
    $pvk_data = [];

    foreach ($pvk_ratings as $pvk_id => $weighted_rating) {
        $pvk_name = get_pvk_name($mysqli, $pvk_id);
        $pvk_data[] = ["name" => $pvk_name, "pvk_id" => $pvk_id, "weighted_rating" => round($weighted_rating, 2)];
        $total_score += $weighted_rating;
    }

    usort($pvk_data, function($a, $b) {
        return $b['weighted_rating'] <=> $a['weighted_rating'];
    });

    return ["pvk_data" => $pvk_data, "overall_score" => round($total_score, 2)];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT role, respondent_id FROM users WHERE id = $user_id";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role = $row['role'];
    $respondent_id = $row['respondent_id'];
} else {
    die("Пользователь не найден.");
}

$respondent_name = "";
if ($role == 'respondent') {
    $sql = "SELECT name FROM respondents WHERE id = $respondent_id";
    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        $respondent_name = $result->fetch_assoc()['name'];
    } else {
        die("Респондент не найден.");
    }
} elseif ($role == 'expert') {
    $sql = "SELECT id, name, gender, age FROM respondents";
    $result = $mysqli->query($sql);
    $respondents = [];
    while ($row = $result->fetch_assoc()) {
        $respondents[] = $row;
    }

    $sql = "SELECT name FROM experts WHERE id = (SELECT expert_id FROM users WHERE id = $user_id)";
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
        $expert_name = $result->fetch_assoc()['name'];
    }
}

$professions = [
    5 => 'DevOps-инженер',
    6 => 'AR/VR-разработчик',
    7 => 'UI/UX дизайнер'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пригодность респондента</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        h1, h2, h3, h4 {
            color: #333;
        }
        .container {
            margin: 70px auto;
        }
        .chart-container {
            width: 100%;
            height: 400px;
            margin: 20px 0;
        }
        .filter-section, .results-section, .expert-progress, .respondent-progress {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .filter-section form, .results-section form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filter-section form > div, .results-section form > div {
            flex: 1;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        select, input[type="submit"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            margin-top: 20px;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .checkbox-container input {
            margin-right: 10px;
        }
        .comparison-table th, .comparison-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .comparison-table th {
            background-color: #f8f8f8;
        }
        .low { background-color: #f8d7da; }
        .medium { background-color: #fff3cd; }
        .high { background-color: #d4edda; }
    </style>
</head>
<body>
<header>
<p><a href="tests/tests.php">Назад</a></p>
    <p><a href="../index.php">Домой</a></p>
    <?php if (isset($_SESSION['username'])): ?>
        <p><a href="../account.php">Личный кабинет</a></p>
    <?php endif; ?>
</header>
    <div class="container">
        <h1>Прогресс по развитию навыков</h1>
        <?php if ($role == 'respondent'): ?>
            <h2>Респондент: <?php echo $respondent_name; ?></h2>

            <div class="respondent-progress">
                <h2>Прогресс респондента по профессиям</h2>
                <?php foreach ($professions as $profession_id => $profession_name): ?>
                    <?php
                    $suitability_data = calculate_suitability($mysqli, $user_id, $profession_id);
                    $pvk_data = $suitability_data['pvk_data'];
                    ?>
                    <h3><?php echo $profession_name; ?></h3>
                    <?php if (!empty($pvk_data)): ?>
                    <div class="chart-container">
                        <canvas id="chart-respondent-<?php echo $profession_id; ?>"></canvas>
                    </div>
                    <script>
                        var ctx = document.getElementById('chart-respondent-<?php echo $profession_id; ?>').getContext('2d');
                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_column($pvk_data, 'name')); ?>,
                                datasets: [{
                                    label: 'Прогресс',
                                    data: <?php echo json_encode(array_column($pvk_data, 'weighted_rating')); ?>,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Прогресс',
                                            font: {
                                                style: 'italic'
                                            }
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            font: {
                                                size: 14
                                            },
                                            autoSkip: false
                                        },
                                        afterFit: function(scale) {
                                            scale.width = 900; // Устанавливаем ширину для длинных меток
                                        }
                                    }
                                },
                                responsive: true,
                                layout: {
                                    padding: {
                                        left: 20,
                                        right: 20,
                                        top: 20,
                                        bottom: 20
                                    }
                                }
                            }
                        });
                    </script>
                    <h4>Общий показатель развития: <?php echo $suitability_data['overall_score']; ?></h4>
                    <?php else: ?>
                        <p>Нет данных</p>
                    <?php endif; ?>
                    <hr>
                <?php endforeach; ?>
            </div>
        <?php elseif ($role == 'expert'): ?>
            <h2>Эксперт: <?php echo $expert_name; ?></h2>
            <div class="expert-progress">
                <h2>Прогресс эксперта по профессиям</h2>
                <?php foreach ($professions as $profession_id => $profession_name): ?>
                    <?php
                    $suitability_data = calculate_suitability($mysqli, $user_id, $profession_id);
                    $pvk_data = $suitability_data['pvk_data'];
                    ?>
                    <h3><?php echo $profession_name; ?></h3>
                    <?php if (!empty($pvk_data)): ?>
                    <div class="chart-container">
                        <canvas id="chart-expert-<?php echo $profession_id; ?>"></canvas>
                    </div>
                    <script>
                        var ctx = document.getElementById('chart-expert-<?php echo $profession_id; ?>').getContext('2d');
                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_column($pvk_data, 'name')); ?>,
                                datasets: [{
                                    label: 'Прогресс',
                                    data: <?php echo json_encode(array_column($pvk_data, 'weighted_rating')); ?>,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Прогресс',
                                            font: {
                                                style: 'italic'
                                            }
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            font: {
                                                size: 14
                                            },
                                            autoSkip: false
                                        },
                                        afterFit: function(scale) {
                                            scale.width = 900; // Устанавливаем ширину для длинных меток
                                        }
                                    }
                                },
                                responsive: true,
                                layout: {
                                    padding: {
                                        left: 20,
                                        right: 20,
                                        top: 20,
                                        bottom: 20
                                    }
                                }
                            }
                        });
                    </script>
                    <h4>Общий показатель развития: <?php echo $suitability_data['overall_score']; ?></h4>
                    <?php else: ?>
                        <p>Нет данных</p>
                    <?php endif; ?>
                    <hr>
                <?php endforeach; ?>
            </div>

            <div class="filter-section">
                <h2>Фильтровать респондентов</h2>
                <form id="filter-form">
                    <div>
                        <label for="gender">Пол:</label>
                        <select id="gender" name="gender" onchange="filterRespondents()">
                            <option value="">Все</option>
                            <option value="Male">Мужской</option>
                            <option value="Female">Женский</option>
                        </select>
                    </div>
                    <div>
                        <label for="age">Возраст:</label>
                        <select id="age" name="age" onchange="filterRespondents()">
                            <option value="">Все</option>
                            <?php for ($i = 18; $i <= 65; $i += 5): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> - <?php echo $i + 4; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="results-section">
                <h2>Выберите респондентов для просмотра их прогресса:</h2>
                <form method="get" action="">
                    <div id="respondent_select"></div>
                    <input type="submit" value="Показать">
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['respondent_ids']) && count($_GET['respondent_ids']) > 0): ?>
            <?php
            $selected_respondent_ids = $_GET['respondent_ids'];
            $respondent_data = [];
            foreach ($selected_respondent_ids as $selected_respondent_id) {
                $sql = "SELECT name FROM respondents WHERE id = $selected_respondent_id";
                $result = $mysqli->query($sql);
                if ($result->num_rows > 0) {
                    $selected_respondent_name = $result->fetch_assoc()['name'];
                    $respondent_data[$selected_respondent_id] = ["name" => $selected_respondent_name, "professions" => []];
                    foreach ($professions as $profession_id => $profession_name) {
                        $suitability_data = calculate_suitability($mysqli, $selected_respondent_id, $profession_id);
                        $respondent_data[$selected_respondent_id]["professions"][$profession_id] = $suitability_data;
                    }
                }
            }

            // Display results for each respondent if only one respondent is selected
            if (count($selected_respondent_ids) === 1) {
                $selected_respondent_id = $selected_respondent_ids[0];
                echo "<h2>Результаты респондента: {$respondent_data[$selected_respondent_id]['name']}</h2>";
                foreach ($respondent_data[$selected_respondent_id]["professions"] as $profession_id => $data) {
                    echo "<h3>{$professions[$profession_id]}</h3>";
                    if (!empty($data['pvk_data'])) {
                        echo "<div class='chart-container'>
                                <canvas id='chart-respondent-{$profession_id}-{$selected_respondent_id}'></canvas>
                              </div>
                              <script>
                                var ctx = document.getElementById('chart-respondent-{$profession_id}-{$selected_respondent_id}').getContext('2d');
                                var chart = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: " . json_encode(array_column($data['pvk_data'], 'name')) . ",
                                        datasets: [{
                                            label: 'Прогресс',
                                            data: " . json_encode(array_column($data['pvk_data'], 'weighted_rating')) . ",
                                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                            borderColor: 'rgba(75, 192, 192, 1)',
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Прогресс',
                                                    font: {
                                                        style: 'italic'
                                                    }
                                                }
                                            },
                                            y: {
                                                ticks: {
                                                    font: {
                                                        size: 14
                                                    },
                                                    autoSkip: false
                                                },
                                                afterFit: function(scale) {
                                                    scale.width = 900; // Устанавливаем ширину для длинных меток
                                                }
                                            }
                                        },
                                        responsive: true,
                                        layout: {
                                            padding: {
                                                left: 20,
                                                right: 20,
                                                top: 20,
                                                bottom: 20
                                            }
                                        }
                                    }
                                });
                              </script>";
                        echo "<h4>Общий показатель развития: {$data['overall_score']}</h4>";
                    } else {
                        echo "<p>Нет данных</p>";
                    }
                    echo "<hr>";
                }
            }

            // Display comparison for multiple respondents
            if (count($selected_respondent_ids) > 1) {
                // Display top progress for respondents by professions
                echo '<h3>Кто больше развивается:</h3>';
                echo '<div class="results-section">';
                foreach ($professions as $profession_id => $profession_name) {
                    $profession_suitability = [];
                    foreach ($selected_respondent_ids as $selected_respondent_id) {
                        $profession_suitability[] = [
                            'respondent_name' => $respondent_data[$selected_respondent_id]['name'],
                            'overall_score' => $respondent_data[$selected_respondent_id]['professions'][$profession_id]['overall_score']
                        ];
                    }

                    usort($profession_suitability, function ($a, $b) {
                        return $b['overall_score'] <=> $a['overall_score'];
                    });
                    echo "<h4>$profession_name</h4>";
                    if (empty($profession_suitability)) {
                        echo "<p>Нет данных</p>";
                    } else {
                        echo "<table class='comparison-table'>
                                <thead>
                                    <tr>
                                        <th>Респондент</th>
                                        <th>Общий показатель развития</th>
                                    </tr>
                                </thead>
                                <tbody>";
                        foreach ($profession_suitability as $data) {
                            $score = $data['overall_score'];
                            if ($score < 0.3) {
                                $score_color_class = "low";
                            } elseif ($score < 0.7) {
                                $score_color_class = "medium";
                            } else {
                                $score_color_class = "high";
                            }
                            echo "<tr>
                                    <td>{$data['respondent_name']}</td>
                                    <td class='{$score_color_class}'>{$score}</td>
                                  </tr>";
                        }
                        echo "</tbody></table>";
                    }
                }
                echo '</div>';

                // Display comparisons by all professions
                echo '<h3>Сравнение респондентов:</h3>';
                foreach ($professions as $profession_id => $profession_name) {
                    $comparison_data = [];
                    foreach ($selected_respondent_ids as $selected_respondent_id) {
                        $suitability_data = calculate_suitability($mysqli, $selected_respondent_id, $profession_id);
                        $comparison_data[] = [
                            "respondent_id" => $selected_respondent_id,
                            "respondent_name" => $respondent_data[$selected_respondent_id]['name'],
                            "overall_score" => $suitability_data['overall_score'],
                            "pvk_data" => $suitability_data['pvk_data']
                        ];
                    }

                    usort($comparison_data, function ($a, $b) {
                        return $b['overall_score'] <=> $a['overall_score'];
                    });

                    // Collect PVK IDs that are common to two or more respondents
                    $pvk_counts = [];
                    foreach ($comparison_data as $data) {
                        foreach ($data['pvk_data'] as $pvk) {
                            if (!isset($pvk_counts[$pvk['pvk_id']])) {
                                $pvk_counts[$pvk['pvk_id']] = 0;
                            }
                            $pvk_counts[$pvk['pvk_id']]++;
                        }
                    }

                    $common_pvk_ids = array_keys(array_filter($pvk_counts, function ($count) {
                        return $count >= 2;
                    }));

                    // Prepare data for the chart
                    $pvk_names = array_map(function ($pvk_id) use ($mysqli) {
                        return get_pvk_name($mysqli, $pvk_id);
                    }, $common_pvk_ids);

                    $datasets = [];
                    foreach ($comparison_data as $index => $data) {
                        $ratings = [];
                        foreach ($common_pvk_ids as $pvk_id) {
                            $rating = 0;
                            foreach ($data['pvk_data'] as $pvk) {
                                if ($pvk['pvk_id'] == $pvk_id) {
                                    $rating = $pvk['weighted_rating'];
                                    break;
                                }
                            }
                            $ratings[] = $rating;
                        }

                        $datasets[] = [
                            "label" => $data['respondent_name'],
                            "data" => $ratings,
                            "backgroundColor" => "rgba(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ",0.8)",
                            "borderColor" => "rgba(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ",1)",
                            "borderWidth" => 1
                        ];
                    }
                    echo "<h3>$profession_name</h3>";
                    if (empty($common_pvk_ids)) {
                        echo "<p>Нет данных</p>";
                    } else {
                        echo "<div class='chart-container'>
                                <canvas id='chart-comparison-$profession_id' style='width: 1200px; height: 400px;'></canvas>
                              </div>
                              <script>
                                const ctx$profession_id = document.getElementById('chart-comparison-$profession_id').getContext('2d');
                                new Chart(ctx$profession_id, {
                                    type: 'bar',
                                    data: {
                                        labels: " . json_encode($pvk_names) . ",
                                        datasets: " . json_encode($datasets) . "
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Прогресс',
                                                    font: {
                                                        style: 'italic'
                                                    }
                                                }
                                            },
                                            y: {
                                                ticks: {
                                                    font: {
                                                        size: 14
                                                    },
                                                    autoSkip: false
                                                },
                                                afterFit: function(scale) {
                                                    scale.width = 900; // Устанавливаем ширину для длинных меток
                                                }
                                            }
                                        },
                                        plugins: {
                                            legend: {
                                                position: 'top'
                                            }
                                        },
                                        responsive: true,
                                        layout: {
                                            padding: {
                                                left: 20,
                                                right: 20,
                                                top: 20,
                                                bottom: 20
                                            }
                                        }
                                    }
                                });
                              </script>";
                    }
                    echo "<hr>";
                }
            }
            ?>
        <?php elseif (isset($_GET['respondent_ids'])): ?>
            <h3>Выберите одного или более респондентов для просмотра прогресса.</h3>
        <?php endif; ?>

        <script>
            const respondents = <?php echo json_encode($respondents); ?>;

            function filterRespondents() {
                const gender = document.getElementById('gender').value;
                const age = document.getElementById('age').value;
                const filteredRespondents = respondents.filter(respondent => {
                    let genderMatch = !gender || respondent.gender === gender;
                    let ageMatch = !age || (respondent.age >= age && respondent.age < parseInt(age) + 5);
                    return genderMatch && ageMatch;
                });
                const respondentSelect = document.getElementById('respondent_select');
                respondentSelect.innerHTML = '';

                if (filteredRespondents.length > 0) {
                    let selectAllContainer = document.createElement('div');
                    selectAllContainer.classList.add('checkbox-container');
                    let selectAllCheckbox = document.createElement('input');
                    selectAllCheckbox.type = 'checkbox';
                    selectAllCheckbox.id = 'select_all';
                    selectAllCheckbox.onchange = function() {
                        const checkboxes = document.querySelectorAll('input[name="respondent_ids[]"]');
                        checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
                    };
                    let selectAllLabel = document.createElement('label');
                    selectAllLabel.htmlFor = 'select_all';
                    selectAllLabel.textContent = 'Выбрать всех';
                    selectAllContainer.appendChild(selectAllCheckbox);
                    selectAllContainer.appendChild(selectAllLabel);
                    respondentSelect.appendChild(selectAllContainer);
                }

                filteredRespondents.forEach(respondent => {
                    let checkboxContainer = document.createElement('div');
                    checkboxContainer.classList.add('checkbox-container');
                    let checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'respondent_ids[]';
                    checkbox.value = respondent.id;
                    checkbox.id = 'respondent_' + respondent.id;
                    let label = document.createElement('label');
                    label.htmlFor = 'respondent_' + respondent.id;
                    label.textContent = respondent.name;
                    checkboxContainer.appendChild(checkbox);
                    checkboxContainer.appendChild(label);
                    respondentSelect.appendChild(checkboxContainer);
                });
            }

            document.addEventListener('DOMContentLoaded', filterRespondents);
        </script>
    </div>
</body>
</html>

<?php
$mysqli->close();
?>
