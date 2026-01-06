<?php
// kontora_specialistui.php - Kontoros valdymas specialistui
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik specialistams ir admin
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"] && $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "kontora_specialistui";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Get specialist username and userid
$specialist_username = $_SESSION['user'];
$user_query = mysqli_query($db, "SELECT userid FROM " . TBL_USERS . " WHERE username = '" . mysqli_real_escape_string($db, $specialist_username) . "' LIMIT 1");
$specialist_userid = null;
if ($user_query && mysqli_num_rows($user_query) > 0) {
    $user_row = mysqli_fetch_assoc($user_query);
    $specialist_userid = $user_row['userid'];
}

// Create table for specialist-office relationship if it doesn't exist
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

// Handle form submission - ONLY FOR ADMIN, NOT FOR SPECIALISTS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL]) {
    // Only admin can change kontora assignments
    if (isset($_POST['save_office'])) {
        $kontora_id = (int)$_POST['kontora_id'];
        
        if ($specialist_userid && $kontora_id > 0) {
            // Delete existing office assignment
            $delete_sql = "DELETE FROM Specialistas_kontora WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "'";
            mysqli_query($db, $delete_sql);
            
            // Insert new assignment
            $insert_sql = "INSERT INTO Specialistas_kontora (specialistas_id, kontora_id) 
                          VALUES ('" . mysqli_real_escape_string($db, $specialist_userid) . "', $kontora_id)";
            if (mysqli_query($db, $insert_sql)) {
                $_SESSION['message'] = "Kontora sėkmingai išsaugota!";
            } else {
                $_SESSION['message'] = "Klaida: " . mysqli_error($db);
            }
        }
    } elseif (isset($_POST['create_office'])) {
        $pavadinimas = mysqli_real_escape_string($db, $_POST['pavadinimas']);
        $miestas = mysqli_real_escape_string($db, $_POST['miestas'] ?? '');
        $adresas = mysqli_real_escape_string($db, $_POST['adresas'] ?? '');
        
        if (!empty($pavadinimas)) {
            $insert_office = "INSERT INTO Kontora (pavadinimas, miestas, adresas) VALUES ('$pavadinimas', '$miestas', '$adresas')";
            if (mysqli_query($db, $insert_office)) {
                $new_office_id = mysqli_insert_id($db);
                
                // Automatically assign this office to the specialist
                if ($specialist_userid) {
                    $delete_sql = "DELETE FROM Specialistas_kontora WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "'";
                    mysqli_query($db, $delete_sql);
                    
                    $assign_sql = "INSERT INTO Specialistas_kontora (specialistas_id, kontora_id) 
                                  VALUES ('" . mysqli_real_escape_string($db, $specialist_userid) . "', $new_office_id)";
                    mysqli_query($db, $assign_sql);
                }
                
                $_SESSION['message'] = "Nauja kontora sukurta ir jums priskirta!";
            } else {
                $_SESSION['message'] = "Klaida kuriant kontorą: " . mysqli_error($db);
            }
        }
    }
    header("Location: kontora_specialistui.php");
    exit;
}

// Get all available offices
$offices_sql = "SELECT * FROM Kontora ORDER BY pavadinimas";
$offices_result = mysqli_query($db, $offices_sql);
$available_offices = array();
if ($offices_result) {
    while ($row = mysqli_fetch_assoc($offices_result)) {
        $available_offices[] = $row;
    }
}

// Get specialist's current office
$current_office_id = null;
$current_office = null;
if ($specialist_userid) {
    $office_sql = "SELECT k.* FROM Kontora k 
                   INNER JOIN Specialistas_kontora sk ON k.id = sk.kontora_id 
                   WHERE sk.specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "' 
                   LIMIT 1";
    $office_result = mysqli_query($db, $office_sql);
    if ($office_result && mysqli_num_rows($office_result) > 0) {
        $current_office = mysqli_fetch_assoc($office_result);
        $current_office_id = $current_office['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mano kontora - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .office-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        .office-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .office-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .office-radio {
            margin-right: 10px;
            cursor: pointer;
        }
        .create-office-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #e0e0e0;
        }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Mano kontora</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    <?php if ($_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL]): ?>
                        Valdykite kontorą specialistui. Kontorą gali keisti tik administratorius.
                    <?php else: ?>
                        Informacija apie kontorą, kuriai priklausote. Kontorą gali keisti tik administratorius.
                    <?php endif; ?>
                </p>
                
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($current_office): ?>
                <div class="message success" style="margin-bottom: 20px;">
                    <strong>Dabartinė kontora:</strong> <?php echo htmlspecialchars($current_office['pavadinimas']); ?>
                    <?php if ($current_office['miestas']): ?>
                        <br><strong>Miestas:</strong> <?php echo htmlspecialchars($current_office['miestas']); ?>
                    <?php endif; ?>
                    <?php if ($current_office['adresas']): ?>
                        <br><strong>Adresas:</strong> <?php echo htmlspecialchars($current_office['adresas']); ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="message info" style="margin-bottom: 20px;">
                    <strong>Nėra priskirtos kontoros.</strong> Administratorius gali priskirti kontorą per administratoriaus sąsają.
                </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL]): ?>
                    <!-- Admin can edit kontora -->
                    <form method="post">
                        <h3 style="color: #1e3c72; margin-top: 30px;">Pasirinkite egzistuojančią kontorą</h3>
                        
                        <?php if (empty($available_offices)): ?>
                            <div style="text-align: center; padding: 40px; color: #999;">
                                <div style="font-size: 48px; margin-bottom: 10px;">🏢</div>
                                <div style="font-size: 18px; font-weight: 500; color: #666;">Nėra prieinamų kontorų</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($available_offices as $office): ?>
                            <div class="office-card <?php echo ($current_office_id == $office['id']) ? 'selected' : ''; ?>">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="radio" 
                                           name="kontora_id" 
                                           value="<?php echo $office['id']; ?>"
                                           class="office-radio"
                                           <?php echo ($current_office_id == $office['id']) ? 'checked' : ''; ?>
                                           onchange="this.closest('.office-card').classList.toggle('selected', this.checked); document.querySelectorAll('.office-card').forEach(c => { if (c !== this.closest('.office-card')) c.classList.remove('selected'); });">
                                    <div style="flex: 1;">
                                        <h3 style="color: #1e3c72; margin: 0;"><?php echo htmlspecialchars($office['pavadinimas']); ?></h3>
                                        <?php if ($office['miestas']): ?>
                                        <p style="color: #666; margin: 5px 0;"><strong>Miestas:</strong> <?php echo htmlspecialchars($office['miestas']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($office['adresas']): ?>
                                        <p style="color: #666; margin: 5px 0;"><strong>Adresas:</strong> <?php echo htmlspecialchars($office['adresas']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                            <button type="submit" name="save_office" class="btn">Išsaugoti kontorą</button>
                        </div>
                    </form>
                    
                    <!-- Create new office (only for admin) -->
                    <div class="create-office-form">
                        <h3 style="color: #1e3c72; margin-bottom: 15px;">Sukurti naują kontorą</h3>
                        <form method="post">
                            <div class="form-group">
                                <label for="pavadinimas" class="required">Kontoros pavadinimas *</label>
                                <input type="text" id="pavadinimas" name="pavadinimas" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="miestas">Miestas</label>
                                    <input type="text" id="miestas" name="miestas" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                                </div>
                                <div class="form-group">
                                    <label for="adresas">Adresas</label>
                                    <input type="text" id="adresas" name="adresas" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                                </div>
                            </div>
                            <button type="submit" name="create_office" class="btn" style="margin-top: 15px;">Sukurti ir priskirti specialistui</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Specialist can only view, not edit -->
                    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <p style="color: #666; margin: 0;">
                            Kontorą gali keisti tik administratorius per administratoriaus sąsają.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </td></tr>
    </table>
</body>
</html>
