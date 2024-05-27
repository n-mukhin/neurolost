<?php
session_start();
require_once "db-connect.php";

function get_pvk_name($mysqli, $pvk_id) {
    $sql = "SELECT name FROM pvk WHERE id = $pvk_id";
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['name'];
    } else {
        return "Неизвестное ПВК";
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

            if (!isset($pvk_ratings[$pvk_id])) {
                $pvk_ratings[$pvk_id] = [];
            }
            $pvk_ratings[$pvk_id][] = $rating;
        }
    }

    $total_score = 0;
    $total_weight = 0;
    $pvk_data = [];

    foreach ($pvk_ratings as $pvk_id => $ratings) {
        $pvk_name = get_pvk_name($mysqli, $pvk_id);

        // Fetch weight and thresholds for the PVK
        $weight_sql = "SELECT weight FROM weights WHERE pvk_id = $pvk_id";
        $threshold_sql = "SELECT low_threshold, medium_threshold, high_threshold FROM thresholds WHERE pvk_id = $pvk_id";
        $weight_result = $mysqli->query($weight_sql);
        $threshold_result = $mysqli->query($threshold_sql);

        if ($weight_result->num_rows > 0 && $threshold_result->num_rows > 0) {
            $weight = $weight_result->fetch_assoc()['weight'];
            $thresholds = $threshold_result->fetch_assoc();

            $average_rating = array_sum($ratings) / count($ratings);
            $weighted_rating = $average_rating * $weight;

            if ($weighted_rating < $thresholds['low_threshold']) {
                $rating_display = "-";
                $rating_color_class = "low";
            } elseif ($weighted_rating < $thresholds['medium_threshold']) {
                $rating_display = round($weighted_rating, 2);
                $rating_color_class = "medium";
            } else {
                $rating_display = round($weighted_rating, 2);
                $rating_color_class = "high";
            }

            $pvk_data[] = [
                "name" => $pvk_name,
                "pvk_id" => $pvk_id,
                "average_rating" => $rating_display,
                "rating_color_class" => $rating_color_class
            ];

            if ($rating_display !== "-") {
                $total_score += $weighted_rating;
                $total_weight += $weight;
            }
        }
    }

    usort($pvk_data, function ($a, $b) {
        return $b['average_rating'] <=> $a['average_rating'];
    });

    if ($total_weight > 0) {
        $overall_score = round($total_score / $total_weight, 2);
    } else {
        $overall_score = 0;
    }

    return ["pvk_data" => $pvk_data, "overall_score" => $overall_score];
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
        $respondents[$row['id']] = $row;
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

function get_all_professions_suitability($mysqli, $user_id) {
    global $professions;
    $profession_data = [];

    foreach ($professions as $profession_id => $profession_name) {
        $suitability_data = calculate_suitability($mysqli, $user_id, $profession_id);
        $profession_data[] = [
            "name" => $profession_name,
            "overall_score" => $suitability_data['overall_score'],
            "pvk_data" => $suitability_data['pvk_data']
        ];
    }

    usort($profession_data, function ($a, $b) {
        return $b['overall_score'] <=> $a['overall_score'];
    });

    return $profession_data;
}

function get_age_categories($ages) {
    $categories = [];
    foreach ($ages as $age) {
        $min_age = floor($age / 5) * 5;
        $max_age = $min_age + 4;
        $category = "$min_age-$max_age";
        if (!in_array($category, $categories)) {
            $categories[] = $category;
        }
    }
    return $categories;
}

$respondents = $respondents ?? [];

$ages = array_unique(array_column($respondents, 'age'));
sort($ages);
$age_categories = get_age_categories($ages);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пригодность респондента</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 70px auto;
            background-color: #f4f4f4;
        }
        h1, h2, h3, h4 {
            color: #333;
        }
        table {
            width: 70%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
        }
        .low { background-color: #f8d7da; }
        .medium { background-color: #fff3cd; }
        .high { background-color: #d4edda; }
        .select2-container {
            width: 100% !important;
        }
        .comparison-container {
            margin-top: 20px;
        }
        .chart-container {
            position: relative;
            margin-bottom: 20px;
            width: 100%; /* Increased width for better visibility */
            height: 400px; /* Increased height for better visibility */
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .dropdown-container {
            margin-left: 20px;
        }
        .dropdown-btn {
            cursor: pointer;
            display: inline-block;
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            width: 100%;
            text-align: left;
        }
        .filters {
            display: flex;
            gap: 20px;
        }
        .filters div {
            flex: 1;
        }
        .filters label {
            display: block;
            margin-bottom: 10px;
        }
        #view-results-btn {
            display: block;
            margin-top: 20px;
        }
        /* Стиль для контейнера селектора */
        .select2-container--default .select2-selection--multiple {
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            cursor: text;
            padding: 5px;
            position: relative;
            height: auto;
        }

        /* Стиль для элементов списка */
        .select2-container--default .select2-results__option {
            padding: 8px 12px;
            cursor: pointer;
        }

        /* Стиль для активных элементов списка */
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f1f1f1;
        }

        /* Стиль для отмеченных элементов */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e2e2e2;
            border: 1px solid #aaa;
            border-radius: 4px;
            margin: 4px 2px;
            padding: 2px 5px;
            font-size: 14px;
        }

        /* Стиль для удаления отмеченных элементов */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #888;
            cursor: pointer;
            display: inline-block;
            font-weight: bold;
            margin-right: 5px;
        }

        /* Стиль для контейнера, когда активен */
        .select2-container--default .select2-selection--multiple:focus {
            border-color: #5b9dd9;
            outline: 0;
        }

        /* Стиль для подсказок и загрузки */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .select2-container--default .select2-search--inline .select2-search__field {
            width: auto;
            padding: 5px;
            margin: 2px;
            box-sizing: border-box;
        }

        /* Стиль для пустых опций */
        .select2-container--default .select2-results__option[aria-selected] {
            background-color: #f5f5f5;
        }

        /* Стиль для выделенного элемента */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #5897fb;
            color: white;
        }

        /* Стиль для состояния загрузки */
        .select2-container--default .select2-results__option.loading-results {
            padding: 10px;
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
    <h1>Пригодность для различных профессий</h1>
    <?php if ($role == 'respondent'): ?>
        <h2>Респондент: <?php echo $respondent_name; ?></h2>
        <?php
        $profession_data = get_all_professions_suitability($mysqli, $user_id);
        foreach ($profession_data as $profession):
        ?>
            <h3><?php echo $profession['name']; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Критерии личных качеств (ПВК)</th>
                        <th>Рейтинг</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($profession['pvk_data'])) {
                        echo "<tr><td colspan='2'>Ни один тест не пройден.</td></tr>";
                    } else {
                        foreach ($profession['pvk_data'] as $data) {
                            $rating = $data['average_rating'];
                            $rating_color_class = $data['rating_color_class'];

                            echo "<tr>
                                    <td>{$data['name']}</td>
                                    <td class='$rating_color_class'>{$rating}</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <h4>Общий показатель пригодности: <?php echo $profession['overall_score']; ?></h4>
            <hr>
        <?php endforeach; ?>
    <?php elseif ($role == 'expert'): ?>
        <h2>Эксперт: <?php echo $expert_name; ?></h2>
        <?php
        $profession_data = get_all_professions_suitability($mysqli, $user_id);
        foreach ($profession_data as $profession):
        ?>
            <h3><?php echo $profession['name']; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Критерии личных качеств (ПВК)</th>
                        <th>Рейтинг</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($profession['pvk_data'])) {
                        echo "<tr><td colspan='2'>Ни один тест не пройден.</td></tr>";
                    } else {
                        foreach ($profession['pvk_data'] as $data) {
                            $rating = $data['average_rating'];
                            $rating_color_class = $data['rating_color_class'];

                            echo "<tr>
                                    <td>{$data['name']}</td>
                                    <td class='$rating_color_class'>{$rating}</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <h4>Общий показатель пригодности: <?php echo $profession['overall_score']; ?></h4>
            <hr>
        <?php endforeach; ?>

        <h3>Результаты респондентов:</h3>
        <form method="get" action="">
            <div class="filters">
                <div>
                    <h4>Пол</h4>
                    <label>
                        <input type="checkbox" name="respondent_gender[]" value="Male"> Мужской
                    </label>
                    <label>
                        <input type="checkbox" name="respondent_gender[]" value="Female"> Женский
                    </label>
                </div>
                <div>
                    <h4>Возраст</h4>
                    <?php foreach ($age_categories as $category): ?>
                        <label>
                            <input type="checkbox" name="respondent_age[]" value="<?php echo $category; ?>"> <?php echo $category; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="respondent-select-container" style="display: none;">
                <h4>Респонденты:</h4>
                <select name="respondent_ids[]" id="respondent_select" class="select2" multiple>
                    <?php foreach ($respondents as $id => $details): ?>
                        <option value="<?php echo $id; ?>" data-gender="<?php echo $details['gender']; ?>" data-age="<?php echo $details['age']; ?>"><?php echo $details['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" id="view-results-btn" style="display: none;">Выбрать</button>
        </form>

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
            ?>

            <?php if (count($selected_respondent_ids) > 1): ?>
                <h3>Кому больше подходит профессия:</h3>
                <?php foreach ($professions as $profession_id => $profession_name): ?>
                    <?php
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
                    ?>
                    <h4><?php echo $profession_name; ?></h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Респондент</th>
                                <th>Общий показатель пригодности</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profession_suitability as $data): 
                                $score = $data['overall_score'];
                                if ($score < 0.3) {
                                    $score_color_class = "low";
                                } elseif ($score < 0.7) {
                                    $score_color_class = "medium";
                                } else {
                                    $score_color_class = "high";
                                }
                            ?>
                                <tr>
                                    <td><?php echo $data['respondent_name']; ?></td>
                                    <td class="<?php echo $score_color_class; ?>"><?php echo $score; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php foreach ($selected_respondent_ids as $selected_respondent_id): ?>
                <h2>Респондент: <?php echo $respondent_data[$selected_respondent_id]['name']; ?></h2>
                <?php foreach ($respondent_data[$selected_respondent_id]["professions"] as $profession_id => $data): ?>
                    <h3><?php echo $professions[$profession_id]; ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Критерии личных качеств (ПВК)</th>
                                <th>Рейтинг</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($data['pvk_data'])) {
                                echo "<tr><td colspan='2'>Ни один тест не пройден.</td></tr>";
                            } else {
                                foreach ($data['pvk_data'] as $pvk_data) {
                                    $rating = $pvk_data['average_rating'];
                                    $rating_color_class = $pvk_data['rating_color_class'];

                                    echo "<tr>
                                            <td>{$pvk_data['name']}</td>
                                            <td class='$rating_color_class'>{$rating}</td>
                                          </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    <h4>Общий показатель пригодности: <?php echo $data['overall_score']; ?></h4>
                    <hr>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if (count($selected_respondent_ids) > 1): ?>
                <h3>Сравнение респондентов:</h3>
                <?php
                foreach ($professions as $profession_id => $profession_name):
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
                                    $rating = $pvk['average_rating'];
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
                ?>
                    <h3><?php echo $profession_name; ?></h3>
                    <div class="chart-container">
                    <canvas id="chart-comparison-<?php echo $profession_id; ?>" style="width: 1200px; height: 400px;"></canvas>
                    </div>
                    <script>
const ctx<?php echo $profession_id; ?> = document.getElementById('chart-comparison-<?php echo $profession_id; ?>').getContext('2d');
new Chart(ctx<?php echo $profession_id; ?>, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($pvk_names); ?>,
        datasets: <?php echo json_encode($datasets); ?>
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
        maintainAspectRatio: false,
        layout: {
            padding: {
                left: 20,
                right: 20,
                top: 20,
                bottom: 20
            }
        },
    }
});
</script>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Выберите",
                allowClear: true
            });

            $('input[name="respondent_gender[]"], input[name="respondent_age[]"]').on('change', function() {
                var genders = $('input[name="respondent_gender[]"]:checked').map(function() {
                    return this.value;
                }).get();

                var ages = $('input[name="respondent_age[]"]:checked').map(function() {
                    return this.value;
                }).get();

                if (genders.length > 0 || ages.length > 0) {
                    $('#respondent-select-container').show();
                } else {
                    $('#respondent-select-container').hide();
                }

                $('#respondent_select option').each(function() {
                    var show = true;

                    if (genders.length > 0 && !genders.includes($(this).data('gender'))) {
                        show = false;
                    }

                    if (ages.length > 0) {
                        var ageRange = ages.map(function(age) {
                            return age.split('-');
                        });

                        var respondentAge = $(this).data('age');
                        var inRange = ageRange.some(function(range) {
                            return respondentAge >= range[0] && respondentAge <= range[1];
                        });

                        if (!inRange) {
                            show = false;
                        }
                    }

                    $(this).toggle(show);
                });

                $('#respondent_select').trigger('change');
            });

            $('#respondent_select').on('change', function() {
                if ($(this).val().length > 0) {
                    $('#view-results-btn').show();
                } else {
                    $('#view-results-btn').hide();
                }
            });
        });
    </script>
</body>
</html>

<?php
$mysqli->close();
?>
