<?php
// darbo_laikas.php - Darbo laiko valdymas (specialistams)
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik specialistams ir admin
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"] && $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "darbo_laikas";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Get specialist username
$specialist_username = $_SESSION['user'];

// Get specialist userid from username
$user_query = mysqli_query($db, "SELECT userid FROM " . TBL_USERS . " WHERE username = '" . mysqli_real_escape_string($db, $specialist_username) . "' LIMIT 1");
$specialist_userid = null;
if ($user_query && mysqli_num_rows($user_query) > 0) {
    $user_row = mysqli_fetch_assoc($user_query);
    $specialist_userid = $user_row['userid'];
}

// Handle form submission - add/remove work hours
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_schedule'])) {
        $period_type = $_POST['period_type'] ?? 'single'; // 'single', 'week', 'month'
        $data = mysqli_real_escape_string($db, $_POST['data']);
        $laikas_nuo = mysqli_real_escape_string($db, $_POST['laikas_nuo']);
        $laikas_iki = mysqli_real_escape_string($db, $_POST['laikas_iki']);
        
        if ($specialist_userid) {
            $dates = array();
            
            if ($period_type == 'single') {
                $dates[] = $data;
            } elseif ($period_type == 'week') {
                // Generate dates for the week (Monday to Friday)
                $start_date = new DateTime($data);
                // Find Monday of the week
                $day_of_week = (int)$start_date->format('w');
                $monday_offset = ($day_of_week == 0) ? -6 : 1 - $day_of_week;
                $start_date->modify("$monday_offset days");
                
                // Add Monday to Friday
                for ($i = 0; $i < 5; $i++) {
                    $dates[] = $start_date->format('Y-m-d');
                    $start_date->modify('+1 day');
                }
            } elseif ($period_type == 'month') {
                // Generate dates for the month (all weekdays)
                $start_date = new DateTime($data);
                $start_date->modify('first day of this month');
                $end_date = new DateTime($data);
                $end_date->modify('last day of this month');
                
                $current = clone $start_date;
                while ($current <= $end_date) {
                    $day_of_week = (int)$current->format('w');
                    // Monday (1) to Friday (5)
                    if ($day_of_week >= 1 && $day_of_week <= 5) {
                        $dates[] = $current->format('Y-m-d');
                    }
                    $current->modify('+1 day');
                }
            }
            
            // Insert all dates
            $inserted = 0;
            foreach ($dates as $date) {
                // Check if already exists
                $check_sql = "SELECT id FROM Darbo_laikas WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "' 
                             AND data = '$date' AND laikas_nuo = '$laikas_nuo' AND laikas_iki = '$laikas_iki'";
                $check_result = mysqli_query($db, $check_sql);
                
                if (!$check_result || mysqli_num_rows($check_result) == 0) {
            $insert_sql = "INSERT INTO Darbo_laikas (specialistas_id, data, laikas_nuo, laikas_iki, uzimta) 
                                  VALUES ('" . mysqli_real_escape_string($db, $specialist_userid) . "', '$date', '$laikas_nuo', '$laikas_iki', 0)";
            if (mysqli_query($db, $insert_sql)) {
                        $inserted++;
                    }
                }
            }
            
            if ($inserted > 0) {
                $_SESSION['message'] = "Darbo laikas sėkmingai pridėtas ($inserted dienos)!";
            } else {
                $_SESSION['message'] = "Darbo laikas jau egzistuoja arba įvyko klaida.";
            }
        }
    } elseif (isset($_POST['delete_schedule'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $delete_sql = "DELETE FROM Darbo_laikas WHERE id = $schedule_id AND specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "'";
        if (mysqli_query($db, $delete_sql)) {
            $_SESSION['message'] = "Darbo laikas pašalintas!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    }
    header("Location: darbo_laikas.php");
    exit;
}

// Get existing schedule
$schedules = array();
if ($specialist_userid) {
        $schedule_sql = "SELECT * FROM Darbo_laikas WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "' ORDER BY data, laikas_nuo";
        $schedule_result = mysqli_query($db, $schedule_sql);
        if ($schedule_result) {
            while ($row = mysqli_fetch_assoc($schedule_result)) {
                $schedules[] = $row;
        }
    }
}

// Generate 30-minute time slots
function generateTimeSlots() {
    $slots = array();
    for ($hour = 8; $hour < 18; $hour++) {
        for ($min = 0; $min < 60; $min += 30) {
            $time = sprintf("%02d:%02d", $hour, $min);
            $slots[] = $time;
        }
    }
    return $slots;
}
$time_slots = generateTimeSlots();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darbo laiko valdymas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .schedule-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .schedule-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .schedule-item { background: white; border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; }
        .schedule-item.uzimta { background: #ffe0e0; border-color: #dc3545; }
        .time-slot { display: inline-block; padding: 5px 10px; margin: 3px; background: #667eea; color: white; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Darbo laiko valdymas</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Nustatykite savo darbo valandas (30 min. intervalais)
                </p>
                
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                
                <!-- Add new schedule form -->
                <div class="schedule-form">
                    <h3 style="color: #1e3c72; margin-bottom: 15px;">Pridėti darbo laiką</h3>
                    <form method="post">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="period_type" class="required">Laikotarpis *</label>
                            <select id="period_type" name="period_type" required onchange="updateDateLabel()" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                                <option value="single">Viena diena</option>
                                <option value="week">Savaitei (Pirmadienis-Penktadienis)</option>
                                <option value="month">Mėnesiui (visi darbo dienos)</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group">
                                <label for="data" class="required" id="data_label">Data *</label>
                                <input type="date" id="data" name="data" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="laikas_nuo" class="required">Laikas nuo</label>
                                <select id="laikas_nuo" name="laikas_nuo" required>
                                    <option value="">-- Pasirinkite --</option>
                                    <?php foreach ($time_slots as $slot): ?>
                                    <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="laikas_iki" class="required">Laikas iki</label>
                                <select id="laikas_iki" name="laikas_iki" required>
                                    <option value="">-- Pasirinkite --</option>
                                    <?php foreach ($time_slots as $slot): ?>
                                    <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_schedule" class="btn">Pridėti</button>
                        </div>
                    </form>
                    <script>
                        function updateDateLabel() {
                            const periodType = document.getElementById('period_type').value;
                            const label = document.getElementById('data_label');
                            if (periodType === 'week') {
                                label.textContent = 'Savaitės pradžia (bet kuri diena) *';
                            } else if (periodType === 'month') {
                                label.textContent = 'Mėnuo (bet kuri diena) *';
                            } else {
                                label.textContent = 'Data *';
                            }
                        }
                    </script>
                </div>
                
                <!-- Existing schedules -->
                <h3 style="color: #1e3c72; margin-top: 30px;">Esamas darbo laikas</h3>
                <?php if (empty($schedules)): ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📅</div>
                        <div style="font-size: 18px; font-weight: 500; color: #666;">Darbo laikas nenustatytas</div>
                    </div>
                <?php else: ?>
                    <div class="schedule-grid">
                        <?php 
                        $current_date = null;
                        foreach ($schedules as $schedule): 
                            if ($current_date != $schedule['data']):
                                if ($current_date !== null) echo '</div>';
                                $current_date = $schedule['data'];
                        ?>
                        <div class="schedule-item <?php echo $schedule['uzimta'] ? 'uzimta' : ''; ?>">
                            <strong><?php echo date('Y-m-d', strtotime($schedule['data'])); ?></strong><br>
                            <span class="time-slot"><?php echo substr($schedule['laikas_nuo'], 0, 5); ?> - <?php echo substr($schedule['laikas_iki'], 0, 5); ?></span>
                            <?php if ($schedule['uzimta']): ?><br><small style="color: #dc3545;">Užimta</small><?php endif; ?>
                            <form method="post" style="margin-top: 10px;" onsubmit="return confirm('Ar tikrai norite pašalinti?');">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                <button type="submit" name="delete_schedule" class="btn" style="padding: 5px 10px; font-size: 12px; background: #dc3545;">Pašalinti</button>
                            </form>
                        </div>
                        <?php 
                            else:
                        ?>
                        <div class="schedule-item <?php echo $schedule['uzimta'] ? 'uzimta' : ''; ?>">
                            <span class="time-slot"><?php echo substr($schedule['laikas_nuo'], 0, 5); ?> - <?php echo substr($schedule['laikas_iki'], 0, 5); ?></span>
                            <?php if ($schedule['uzimta']): ?><br><small style="color: #dc3545;">Užimta</small><?php endif; ?>
                            <form method="post" style="margin-top: 10px;" onsubmit="return confirm('Ar tikrai norite pašalinti?');">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                <button type="submit" name="delete_schedule" class="btn" style="padding: 5px 10px; font-size: 12px; background: #dc3545;">Pašalinti</button>
                            </form>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        if ($current_date !== null) echo '</div>';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </td></tr>
    </table>
</body>
</html>
