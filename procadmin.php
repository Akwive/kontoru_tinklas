<?php
// procadmin.php - Apdoroja admin.php formos duomenis - šalina vartotojus ir atnaujina kontoras

session_start();

include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik administratorius gali valdyti vartotojus
if (!isset($_SESSION['prev']) || ($_SESSION['prev'] != "admin") || 
    !isset($_SESSION['ulevel']) || ($_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: logout.php");
    exit;
}

$_SESSION['prev'] = "procadmin";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    $_SESSION['message'] = "DB prisijungimo klaida: " . mysqli_connect_error();
    header("Location: admin.php");
    exit;
}

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

$success_count = 0;
$error_count = 0;
$errors = array();
$kontora_update_count = 0;

// Surinkime visus userid, kuriuos reikia ištrinti
// SVARBU: userid yra VARCHAR(32) hash, ne INT!
$trinti_userids = array();
foreach ($_POST as $key => $value) {
    if (strpos($key, 'naikinti_') === 0) {
        $userid = substr($key, 9); // Pašaliname "naikinti_" prefixą
        // Validuojame kad userid yra 32 simbolių hash
        if (!empty($userid) && strlen($userid) == 32 && preg_match('/^[a-f0-9]{32}$/i', $userid)) {
            $trinti_userids[] = $userid;
        }
    }
}

// Pirmiausia ištriname pažymėtus vartotojus
foreach ($trinti_userids as $userid) {
    if (!empty($userid) && strlen($userid) == 32) {
        // Ištriname vartotoją - userid yra string, naudojame "s" tipą
        $sql = "DELETE FROM " . TBL_USERS . " WHERE userid = ?";
        $stmt = mysqli_prepare($db, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $userid); // "s" = string
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Klaida šalinant userid=$userid: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_count++;
            $errors[] = "Prepare klaida šalinant userid=$userid: " . mysqli_error($db);
        }
    }
}

// Dabar apdorojame kontora atnaujinimus
foreach ($_POST as $key => $value) {
    if (strpos($key, 'kontora_') === 0) {
        $userid = substr($key, 8); // Pašaliname "kontora_" prefixą
        $kontora_id = (int)$value;
        
        // Skip if user is being deleted
        if (in_array($userid, $trinti_userids)) {
            continue;
        }
        
        // Validuojame kad userid yra 32 simbolių hash
        if (!empty($userid) && strlen($userid) == 32 && preg_match('/^[a-f0-9]{32}$/i', $userid)) {
            // Check if user is a specialist
            $check_user_sql = "SELECT userlevel FROM " . TBL_USERS . " WHERE userid = ?";
            $check_stmt = mysqli_prepare($db, $check_user_sql);
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, "s", $userid);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $user_row = mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_stmt);
                
                if ($user_row && $user_row['userlevel'] == $user_roles["Specialistas"]) {
                    // User is a specialist, update kontora
                    if ($kontora_id > 0) {
                        // Verify kontora exists
                        $verify_kontora_sql = "SELECT id FROM Kontora WHERE id = ?";
                        $verify_stmt = mysqli_prepare($db, $verify_kontora_sql);
                        if ($verify_stmt) {
                            mysqli_stmt_bind_param($verify_stmt, "i", $kontora_id);
                            mysqli_stmt_execute($verify_stmt);
                            $verify_result = mysqli_stmt_get_result($verify_stmt);
                            
                            if (mysqli_num_rows($verify_result) > 0) {
                                // Kontora exists, update assignment
                                $update_sql = "INSERT INTO Specialistas_kontora (specialistas_id, kontora_id) VALUES (?, ?)
                                              ON DUPLICATE KEY UPDATE kontora_id = VALUES(kontora_id)";
                                $update_stmt = mysqli_prepare($db, $update_sql);
                                if ($update_stmt) {
                                    mysqli_stmt_bind_param($update_stmt, "si", $userid, $kontora_id);
                                    if (mysqli_stmt_execute($update_stmt)) {
                                        $kontora_update_count++;
                                    } else {
                                        $error_count++;
                                        $errors[] = "Klaida atnaujinant kontorą userid=$userid: " . mysqli_stmt_error($update_stmt);
                                    }
                                    mysqli_stmt_close($update_stmt);
                                }
                            } else {
                                $error_count++;
                                $errors[] = "Kontora su ID=$kontora_id neegzistuoja";
                            }
                            mysqli_stmt_close($verify_stmt);
                        }
                    } else {
                        // Remove kontora assignment (kontora_id = 0 means no kontora)
                        $delete_sql = "DELETE FROM Specialistas_kontora WHERE specialistas_id = ?";
                        $delete_stmt = mysqli_prepare($db, $delete_sql);
                        if ($delete_stmt) {
                            mysqli_stmt_bind_param($delete_stmt, "s", $userid);
                            if (mysqli_stmt_execute($delete_stmt)) {
                                $kontora_update_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Klaida šalinant kontorą userid=$userid: " . mysqli_stmt_error($delete_stmt);
                            }
                            mysqli_stmt_close($delete_stmt);
                        }
                    }
                }
            }
        }
    }
}

mysqli_close($db);

// Formuojame pranešimą
$messages = array();
if ($success_count > 0) {
    $messages[] = "Pašalinta vartotojų: $success_count";
}
if ($kontora_update_count > 0) {
    $messages[] = "Atnaujinta kontorų: $kontora_update_count";
}
if ($error_count > 0) {
    $messages[] = "Klaidų: $error_count";
}

if (empty($messages) && $error_count == 0) {
    $_SESSION['message'] = "Nėra pakeitimų.";
} elseif ($error_count > 0) {
    $_SESSION['message'] = implode(". ", $messages) . ". " . implode(" ", $errors);
} else {
    $_SESSION['message'] = "Pakeitimai atlikti sėkmingai! " . implode(". ", $messages);
}

header("Location: admin.php");
exit;
