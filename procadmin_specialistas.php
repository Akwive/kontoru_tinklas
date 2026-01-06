<?php
// procadmin_specialistas.php - Sukuria naują specialistą su kontora

session_start();

include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik administratorius gali kurti specialistus
if (!isset($_SESSION['prev']) || ($_SESSION['prev'] != "admin") || 
    !isset($_SESSION['ulevel']) || ($_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: logout.php");
    exit;
}

$_SESSION['prev'] = "procadmin_specialistas";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    $_SESSION['message'] = "DB prisijungimo klaida: " . mysqli_connect_error();
    header("Location: admin.php");
    exit;
}

// Validate input
$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
$email = trim($_POST['email'] ?? '');
$kontora_option = $_POST['kontora_option'] ?? 'existing';
$kontora_id = (int)($_POST['kontora_id'] ?? 0);
$kontora_pavadinimas = trim($_POST['kontora_pavadinimas'] ?? '');
$kontora_miestas = trim($_POST['kontora_miestas'] ?? '');
$kontora_adresas = trim($_POST['kontora_adresas'] ?? '');

$errors = array();

// Validate username
if (empty($username)) {
    $errors[] = "Vartotojo vardas yra privalomas";
} elseif (!checkname($username)) {
    $errors[] = "Vartotojo vardas turi būti mažiausiai 5 simboliai, tik raidės ir skaičiai";
} else {
    // Check if username already exists
    list($dbuname) = checkdb($username);
    if ($dbuname) {
        $errors[] = "Vartotojas su tokiu vardu jau egzistuoja";
    }
}

// Validate password
if (empty($password)) {
    $errors[] = "Slaptažodis yra privalomas";
} elseif (!checkpass($password, substr(hash('sha256', $password), 5, 32))) {
    $errors[] = "Slaptažodis turi būti mažiausiai 4 simboliai, tik raidės ir skaičiai";
}

// Validate email
if (empty($email)) {
    $errors[] = "El. paštas yra privalomas";
} elseif (!checkmail($email)) {
    $errors[] = "Neteisingas el. pašto adresas";
}

// Validate kontora
$final_kontora_id = null;
if ($kontora_option == 'existing') {
    if ($kontora_id <= 0) {
        $errors[] = "Pasirinkite kontorą";
    } else {
        // Verify kontora exists
        $check_sql = "SELECT id FROM Kontora WHERE id = ?";
        $check_stmt = mysqli_prepare($db, $check_sql);
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "i", $kontora_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            if (mysqli_num_rows($check_result) == 0) {
                $errors[] = "Pasirinkta kontora neegzistuoja";
            } else {
                $final_kontora_id = $kontora_id;
            }
            mysqli_stmt_close($check_stmt);
        }
    }
} elseif ($kontora_option == 'new') {
    if (empty($kontora_pavadinimas)) {
        $errors[] = "Kontoros pavadinimas yra privalomas";
    } else {
        // Create new kontora
        $insert_kontora_sql = "INSERT INTO Kontora (pavadinimas, miestas, adresas) VALUES (?, ?, ?)";
        $kontora_stmt = mysqli_prepare($db, $insert_kontora_sql);
        if ($kontora_stmt) {
            mysqli_stmt_bind_param($kontora_stmt, "sss", $kontora_pavadinimas, $kontora_miestas, $kontora_adresas);
            if (mysqli_stmt_execute($kontora_stmt)) {
                $final_kontora_id = mysqli_insert_id($db);
            } else {
                $errors[] = "Klaida kuriant kontorą: " . mysqli_stmt_error($kontora_stmt);
            }
            mysqli_stmt_close($kontora_stmt);
        } else {
            $errors[] = "Klaida kuriant kontorą: " . mysqli_error($db);
        }
    }
}

// If there are errors, return to admin page
if (!empty($errors)) {
    $_SESSION['message'] = "Klaidos: " . implode("; ", $errors);
    header("Location: admin.php");
    exit;
}

// All validation passed, create specialist user
$userid = md5(uniqid($username));
$passhash = substr(hash('sha256', $password), 5, 32);
$userlevel = $user_roles["Specialistas"]; // Level 5

$insert_user_sql = "INSERT INTO " . TBL_USERS . " (username, password, userid, userlevel, email) VALUES (?, ?, ?, ?, ?)";
$user_stmt = mysqli_prepare($db, $insert_user_sql);

if (!$user_stmt) {
    $_SESSION['message'] = "Klaida kuriant vartotoją: " . mysqli_error($db);
    header("Location: admin.php");
    exit;
}

mysqli_stmt_bind_param($user_stmt, "sssis", $username, $passhash, $userid, $userlevel, $email);

if (!mysqli_stmt_execute($user_stmt)) {
    $_SESSION['message'] = "Klaida kuriant vartotoją: " . mysqli_stmt_error($user_stmt);
    mysqli_stmt_close($user_stmt);
    header("Location: admin.php");
    exit;
}

mysqli_stmt_close($user_stmt);

// Assign kontora to specialist
if ($final_kontora_id) {
    // Create Specialistas_kontora table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS Specialistas_kontora (
        id INT AUTO_INCREMENT PRIMARY KEY,
        specialistas_id VARCHAR(32) NOT NULL,
        kontora_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_specialist_office (specialistas_id, kontora_id),
        INDEX(specialistas_id),
        INDEX(kontora_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($db, $create_table);
    
    // Assign kontora
    $assign_sql = "INSERT INTO Specialistas_kontora (specialistas_id, kontora_id) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE kontora_id = VALUES(kontora_id)";
    $assign_stmt = mysqli_prepare($db, $assign_sql);
    
    if ($assign_stmt) {
        mysqli_stmt_bind_param($assign_stmt, "si", $userid, $final_kontora_id);
        if (!mysqli_stmt_execute($assign_stmt)) {
            // Log error but don't fail - kontora assignment is not critical
            error_log("Warning: Could not assign kontora to specialist: " . mysqli_stmt_error($assign_stmt));
        }
        mysqli_stmt_close($assign_stmt);
    }
}

mysqli_close($db);

$_SESSION['message'] = "Specialistas '$username' sėkmingai sukurtas" . 
                       ($final_kontora_id ? " ir priskirtas kontorai" : "");
header("Location: admin.php");
exit;
