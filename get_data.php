<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "db_user";
$password = "password";
$dbname = "axpro_data";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$is_latest = isset($_GET['latest']) && $_GET['latest'] === 'true';

$sql = "";
if ($is_latest) {
    // โหมดดึงข้อมูลล่าสุด 1 รายการ
    $sql = "SELECT recorded_at, temperature, humidity FROM sensor_log ORDER BY recorded_at DESC LIMIT 1";
} else {
    // โหมดดึงข้อมูลตามช่วงเวลา
    $date_param = isset($_GET['date']) ? $_GET['date'] : null;
    $time_param = isset($_GET['time']) ? $_GET['time'] : null;
    $duration_param = isset($_GET['duration']) ? intval($_GET['duration']) : 30;

    $where_clause = "";
    if ($date_param && $time_param) {
        try {
            $end_datetime = new DateTime("{$date_param} {$time_param}");
            $start_datetime = clone $end_datetime;
            $start_datetime->modify("-{$duration_param} minutes");
            
            $start_str = $start_datetime->format('Y-m-d H:i:s');
            $end_str = $end_datetime->format('Y-m-d H:i:s');
            
            $where_clause = " WHERE recorded_at BETWEEN '{$start_str}' AND '{$end_str}'";
        } catch (Exception $e) {
            die(json_encode(["error" => "Invalid date or time format"]));
        }
    }
    $sql = "SELECT recorded_at, temperature, humidity FROM sensor_log {$where_clause} ORDER BY recorded_at ASC";
}

$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();
echo json_encode($data);
