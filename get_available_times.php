<?php
// get_available_times.php - Get available time slots for a specialist on a specific date
session_start();
include_once("include/nustatymai.php");

header('Content-Type: application/json');

// Only allow logged in users
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] < 4) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    echo json_encode(['error' => 'Database connection error: ' . mysqli_connect_error()]);
    exit;
}

$specialist_userid = isset($_POST['specialist_userid']) ? trim($_POST['specialist_userid']) : '';
$data = isset($_POST['data']) ? trim($_POST['data']) : '';
$paslaugos_trukme = isset($_POST['paslaugos_trukme']) ? (int)$_POST['paslaugos_trukme'] : 30; // Trukmė minutėmis

// Debug logging
error_log("get_available_times.php called with specialist_userid: " . $specialist_userid . ", data: " . $data . ", trukme: " . $paslaugos_trukme);

if (empty($specialist_userid) || empty($data)) {
    echo json_encode(['error' => 'Missing parameters: specialist_userid=' . ($specialist_userid ? 'set' : 'empty') . ', data=' . ($data ? 'set' : 'empty')]);
    exit;
}

if ($paslaugos_trukme <= 0) {
    $paslaugos_trukme = 30; // Default 30 minutes
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['error' => 'Invalid date format: ' . $data]);
    exit;
}

// Validate specialist_userid format (should be 32 char hash)
if (strlen($specialist_userid) != 32 || !preg_match('/^[a-f0-9]{32}$/i', $specialist_userid)) {
    echo json_encode(['error' => 'Invalid specialist_userid format: ' . substr($specialist_userid, 0, 20) . '...']);
    exit;
}

// Escape for SQL
$specialist_userid_escaped = mysqli_real_escape_string($db, $specialist_userid);
$data_escaped = mysqli_real_escape_string($db, $data);

// Get available time slots for this specialist on this date
// Only get slots that are not booked (uzimta = 0)
$sql = "SELECT laikas_nuo, laikas_iki 
        FROM Darbo_laikas 
        WHERE specialistas_id = '$specialist_userid_escaped' 
        AND data = '$data_escaped' 
        AND uzimta = 0 
        ORDER BY laikas_nuo";

error_log("SQL: " . $sql);

$result = mysqli_query($db, $sql);
$all_slots = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_slots[] = [
            'laikas_nuo' => $row['laikas_nuo'],
            'laikas_iki' => $row['laikas_iki']
        ];
    }
} else {
    $error_msg = mysqli_error($db);
    error_log("SQL error: " . $error_msg);
    echo json_encode(['error' => 'SQL error: ' . $error_msg]);
    mysqli_close($db);
    exit;
}

// Merge continuous time slots into intervals
$intervals = array();
if (count($all_slots) > 0) {
    // Sort slots by start time
    usort($all_slots, function($a, $b) {
        return strcmp($a['laikas_nuo'], $b['laikas_nuo']);
    });
    
    $current_interval_start = strtotime($all_slots[0]['laikas_nuo']);
    $current_interval_end = strtotime($all_slots[0]['laikas_iki']);
    
    for ($i = 1; $i < count($all_slots); $i++) {
        $slot_start = strtotime($all_slots[$i]['laikas_nuo']);
        $slot_end = strtotime($all_slots[$i]['laikas_iki']);
        
        // If this slot continues the current interval
        if ($slot_start == $current_interval_end) {
            $current_interval_end = $slot_end;
        } else {
            // Save current interval and start new one
            $intervals[] = [
                'start' => $current_interval_start,
                'end' => $current_interval_end
            ];
            $current_interval_start = $slot_start;
            $current_interval_end = $slot_end;
        }
    }
    
    // Don't forget the last interval
    $intervals[] = [
        'start' => $current_interval_start,
        'end' => $current_interval_end
    ];
}

// Generate all possible start times (every 30 minutes) within each interval
// where there's enough time for the service
$valid_start_times = array();
$service_duration_seconds = $paslaugos_trukme * 60; // Convert to seconds

foreach ($intervals as $interval) {
    $current_start = $interval['start'];
    $interval_end = $interval['end'];
    
    // Generate start times every 30 minutes
    while ($current_start < $interval_end) {
        // Check if we have enough time from this start time
        if (($interval_end - $current_start) >= $service_duration_seconds) {
            // Valid start time - format as HH:MM
            $valid_start_times[] = date('H:i:s', $current_start);
        }
        
        // Move to next 30-minute slot
        $current_start += 1800; // 30 minutes = 1800 seconds
    }
}

// Remove duplicates and sort
$valid_start_times = array_unique($valid_start_times);
sort($valid_start_times);

// Format as simple time strings for display
$times = array();
foreach ($valid_start_times as $start_time_str) {
    // $start_time_str is already in "HH:MM:SS" format from database
    $times[] = [
        'laikas' => substr($start_time_str, 0, 5) // Format as HH:MM
    ];
}

error_log("Found " . count($times) . " valid start times for service duration " . $paslaugos_trukme . " minutes");

echo json_encode(['times' => $times]);
mysqli_close($db);
?>
