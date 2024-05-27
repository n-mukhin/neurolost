<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['pulse'])) {
        file_put_contents('pulse_data.json', json_encode($data));
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Pulse data not found"]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists('pulse_data.json')) {
        echo file_get_contents('pulse_data.json');
    } else {
        echo json_encode(["pulse" => null]);
    }
    exit();
}
?>
