<?php
session_start();

// PROTOTIPAS: automatinis demo prisijungimas (tik dev) -- pašalink po testavimo
// IMPORTANT: This must happen BEFORE any session data is used!
if (empty($_SESSION['user'])) {
    $_SESSION['user'] = 'Demo Vartotojas';
    $_SESSION['userid'] = 1;
    $_SESSION['ulevel'] = 4; // klientas
}

include_once("include/nustatymai.php");

// DEBUG: įrašome SESSION / POST / COOKIE info į sess_debug.txt (laikina diagnostika)
file_put_contents(
    __DIR__ . '/sess_debug.txt',
    "==== " . date('c') . " REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . PHP_EOL
    . "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? '') . PHP_EOL
    . "COOKIES:\n" . print_r($_COOKIE, true)
    . "SESSION:\n" . print_r($_SESSION, true)
    . "POST:\n" . print_r($_POST, true)
    . "----------------\n",
    FILE_APPEND
);

// only clients+
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] < 4) {
    header("Location: index.php");
    exit;
}

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    $_SESSION['message'] = "DB prisijungimo klaida.";
    header("Location: uzsakymas_naujas.php");
    exit;
}

include_once("include/db_init.php");

// read session + POST - Get userid (which is VARCHAR hash)
$user_id = null;

// Debug: log session data
file_put_contents(__DIR__ . '/sess_debug.txt', 
    "=== ORDER SUBMISSION DEBUG ===\n" . 
    "SESSION user: " . ($_SESSION['user'] ?? 'NOT SET') . "\n" .
    "SESSION userid: " . ($_SESSION['userid'] ?? 'NOT SET') . "\n" .
    "SESSION ulevel: " . ($_SESSION['ulevel'] ?? 'NOT SET') . "\n" .
    "===================\n", 
    FILE_APPEND
);

if (isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
    $user_id = $_SESSION['userid'];
    file_put_contents(__DIR__ . '/sess_debug.txt', "Using userid from session: $user_id\n", FILE_APPEND);
} elseif (!empty($_SESSION['user'])) {
// FALLBACK: jei userid ne sesijoje, bandome rasti pagal username
    $uname = mysqli_real_escape_string($db, $_SESSION['user']);
    file_put_contents(__DIR__ . '/sess_debug.txt', "Looking up userid for username: $uname\n", FILE_APPEND);
    $res = mysqli_query($db, "SELECT userid FROM " . TBL_USERS . " WHERE username='$uname' LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);
        $user_id = $row['userid'];
        $_SESSION['userid'] = $user_id; // atstatom sesijoje
        file_put_contents(__DIR__ . '/sess_debug.txt', "Found userid from DB: $user_id\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/sess_debug.txt', "ERROR: User not found in DB for username: $uname\n", FILE_APPEND);
    }
} else {
    file_put_contents(__DIR__ . '/sess_debug.txt', "ERROR: No user in session!\n", FILE_APPEND);
}

$paslauga_id = (int)($_POST['paslauga_id'] ?? 0);
$paslauga_pav = trim($_POST['paslauga_pav'] ?? "");

$kontora_id = (int)($_POST['kontora_id'] ?? 0);
$kontora_pav = trim($_POST['kontora_pav'] ?? "");

$specialistas_id = trim($_POST['specialistas_id'] ?? ""); // Now VARCHAR(32) userid
$specialistas_vardas = trim($_POST['specialistas_vardas'] ?? "");
$specialistas_username = trim($_POST['specialistas_username'] ?? "");

$data = $_POST['data'] ?? "";
$laikas = $_POST['laikas'] ?? "";
$kaina = (float)($_POST['kaina'] ?? 0);

$komentaras = trim($_POST['komentaras'] ?? "");

// minimal validation
$errors = [];
if (empty($user_id)) $errors[] = "Nėra vartotojo ID sesijoje.";
if ($paslauga_id <= 0 || $paslauga_pav === "") $errors[] = "Nepasirinkta paslauga.";
if ($kontora_id <= 0 || $kontora_pav === "") $errors[] = "Nepasirinkta kontora.";
if (empty($specialistas_id) || $specialistas_vardas === "") $errors[] = "Nepasirinktas specialistas.";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $errors[] = "Neteisinga data.";
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $laikas)) $errors[] = "Neteisingas laikas.";
if ($kaina <= 0) $errors[] = "Neteisinga kaina.";

if ($errors) {
    $_SESSION['message'] = "Užsakymas nepateiktas: " . implode(" ", $errors);
    header("Location: uzsakymas_naujas.php");
    exit;
}

// prepared insert
$sql = "INSERT INTO uzsakymai
(user_id, paslauga_id, paslauga_pav, kontora_id, kontora_pav,
 specialistas_id, specialistas_vardas, specialistas_username, data, laikas, kaina, statusas, komentaras)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    $_SESSION['message'] = "DB klaida (prepare): " . mysqli_error($db);
    header("Location: uzsakymas_naujas.php");
    exit;
}

$statusas = "Pateiktas";

// TIPAI: s i s i s s s s s d s s  => "sisissssssdss"
// (atitinka: user_id (VARCHAR), paslauga_id, paslauga_pav, kontora_id, kontora_pav,
//  specialistas_id (VARCHAR), specialistas_vardas, specialistas_username, data, laikas, kaina, statusas, komentaras)
$user_id_escaped = mysqli_real_escape_string($db, $user_id);
$specialistas_id_escaped = mysqli_real_escape_string($db, $specialistas_id);
$specialistas_username_escaped = mysqli_real_escape_string($db, $specialistas_username);
$bind_ok = mysqli_stmt_bind_param(
    $stmt,
    "sisissssssdss",
    $user_id_escaped,
    $paslauga_id,
    $paslauga_pav,
    $kontora_id,
    $kontora_pav,
    $specialistas_id_escaped,
    $specialistas_vardas,
    $specialistas_username_escaped,
    $data,
    $laikas,
    $kaina,
    $statusas,
    $komentaras
);

if ($bind_ok === false) {
    // įrašome klaidą į debug log
    file_put_contents(__DIR__ . '/sess_debug.txt', "BIND ERROR: " . mysqli_stmt_error($stmt) . PHP_EOL, FILE_APPEND);
    $_SESSION['message'] = "Įrašymo klaida (bind): " . mysqli_stmt_error($stmt);
    header("Location: uzsakymas_naujas.php");
    exit;
}

$ok = mysqli_stmt_execute($stmt);
if ($ok === false) {
    // rašome klaidą į logą
    file_put_contents(__DIR__ . '/sess_debug.txt', "EXEC ERROR: " . mysqli_stmt_error($stmt) . PHP_EOL, FILE_APPEND);
}

if ($ok) {
    // Mark the time slot as busy (uzimta = 1)
    $update_time_sql = "UPDATE Darbo_laikas 
                       SET uzimta = 1 
                       WHERE specialistas_id = ? 
                       AND data = ? 
                       AND laikas_nuo = ?";
    
    $time_stmt = mysqli_prepare($db, $update_time_sql);
    
    if ($time_stmt) {
        mysqli_stmt_bind_param($time_stmt, "sss", 
            $specialistas_id_escaped,  // Already defined
            $data,                      // Already defined 
            $laikas                     // Already defined
        );
        
        if (!mysqli_stmt_execute($time_stmt)) {
            // Log error but don't fail the order - it's already created
            file_put_contents(__DIR__ . '/sess_debug.txt', 
                "WARNING: Could not mark time slot as busy: " . mysqli_stmt_error($time_stmt) . PHP_EOL, 
                FILE_APPEND);
        }
        
        mysqli_stmt_close($time_stmt);
    }
}

$_SESSION['message'] = $ok ? "Užsakymas pateiktas sėkmingai!" : ("Įrašymo klaida: " . mysqli_error($db));
header("Location: uzsakymai_sarasas.php");
exit;