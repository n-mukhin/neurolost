<?php
require_once '../db-connect.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if ($user_id !== null && isset($data['maxPulse'], $data['minPulse'], $data['avgPulse'], $data['timeRecorded'])) {
        $stmt = $mysqli->prepare('INSERT INTO pulse_data (user_id, max_pulse, min_pulse, avg_pulse, time_recorded) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiidi', $user_id, $data['maxPulse'], $data['minPulse'], $data['avgPulse'], $data['timeRecorded']);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data or user not logged in']);
    }
    exit();
}
?>