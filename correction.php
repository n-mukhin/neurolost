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

function get_evaluation_criteria($mysqli) {
    $sql = "SELECT * FROM evaluation_criteria";
    $result = $mysqli->query($sql);
    $criteria = [];
    while ($row = $result->fetch_assoc()) {
        $criteria[] = $row;
    }
    return $criteria;
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

function get_pvk($mysqli) {
    $sql = "SELECT id, name FROM pvk";
    $result = $mysqli->query($sql);
    $pvk = [];
    while ($row = $result->fetch_assoc()) {
        $pvk[$row['id']] = $row['name'];
    }
    return $pvk;
}

function get_tests($mysqli) {
    $sql = "SELECT id, test_name FROM tests";
    $result = $mysqli->query($sql);
    $tests = [];
    while ($row = $result->fetch_assoc()) {
        $tests[$row['id']] = $row['test_name'];
    }
    return $tests;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_criteria'])) {
        $profession_id = (int)$_POST['profession_id'];
        $pvk_id = (int)$_POST['pvk_id'];
        $test_id = (int)$_POST['test_id'];
        $sql = "INSERT INTO evaluation_criteria (profession_id, pvk_id, test_id) VALUES ($profession_id, $pvk_id, $test_id)";
        $mysqli->query($sql);
    }

    if (isset($_POST['update_criteria'])) {
        $id = (int)$_POST['id'];
        $profession_id = (int)$_POST['profession_id'];
        $pvk_id = (int)$_POST['pvk_id'];
        $test_id = (int)$_POST['test_id'];
        $sql = "UPDATE evaluation_criteria SET profession_id = $profession_id, pvk_id = $pvk_id, test_id = $test_id WHERE id = $id";
        $mysqli->query($sql);
    }

    if (isset($_POST['delete_criteria'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM evaluation_criteria WHERE id = $id";
        $mysqli->query($sql);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$criteria = get_evaluation_criteria($mysqli);
$professions = get_professions($mysqli);
$pvk = get_pvk($mysqli);
$tests = get_tests($mysqli);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление Критериями Оценки</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fafafa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        select, input[type="text"], input[type="number"], input[type="submit"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
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
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .action-buttons form {
            display: inline-block;
        }
        .action-buttons select, .action-buttons input {
            width: 100%;
            padding: 5px;
            margin: 0;
            overflow: hidden;
        }
        .action-buttons input[type="submit"] {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Управление Критериями Оценки</h1>
        <form method="post" action="">
            <h2>Добавить Критерий Оценки</h2>
            <label for="add-profession_id">Профессия</label>
            <select name="profession_id" id="add-profession_id" required>
                <?php foreach ($professions as $id => $name): ?>
                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="add-pvk_id">ПВК</label>
            <select name="pvk_id" id="add-pvk_id" required>
                <?php foreach ($pvk as $id => $name): ?>
                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="add-test_id">Тест</label>
            <select name="test_id" id="add-test_id" required>
                <?php foreach ($tests as $id => $name): ?>
                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_criteria" value="Добавить Критерий">
        </form>

        <h2>Существующие Критерии Оценки</h2>
        <?php foreach ($professions as $profession_id => $profession_name): ?>
            <h3><?php echo $profession_name; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ПВК</th>
                        <th>Тест</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($criteria as $criterion): ?>
                        <?php if ($criterion['profession_id'] == $profession_id): ?>
                            <tr>
                                <td><?php echo $criterion['id']; ?></td>
                                <td><?php echo $pvk[$criterion['pvk_id']]; ?></td>
                                <td><?php echo $tests[$criterion['test_id']]; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="post" action="">
                                            <input type="hidden" name="id" value="<?php echo $criterion['id']; ?>">
                                            <select name="pvk_id" required>
                                                <?php foreach ($pvk as $id => $name): ?>
                                                    <option value="<?php echo $id; ?>" <?php if ($criterion['pvk_id'] == $id) echo 'selected'; ?>><?php echo $name; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="test_id" required>
                                                <?php foreach ($tests as $id => $name): ?>
                                                    <option value="<?php echo $id; ?>" <?php if ($criterion['test_id'] == $id) echo 'selected'; ?>><?php echo $name; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="submit" name="update_criteria" value="Обновить">
                                        </form>
                                        <form method="post" action="">
                                            <input type="hidden" name="id" value="<?php echo $criterion['id']; ?>">
                                            <input type="submit" name="delete_criteria" value="Удалить">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
</body>
</html>

<?php
$mysqli->close();
?>
