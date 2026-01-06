<?php
// paslaugos_specialistui.php - Paslaugų valdymas specialistui
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik specialistams ir admin
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"] && $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "paslaugos_specialistui";

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

// Create table for specialist services if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS Specialistas_paslauga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    specialistas_id VARCHAR(32) NOT NULL,
    paslauga_kategorija VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_specialist_category (specialistas_id, paslauga_kategorija),
    INDEX(specialistas_id),
    INDEX(paslauga_kategorija)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($db, $create_table);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_services'])) {
    // Delete existing selections
    if ($specialist_userid) {
        $delete_sql = "DELETE FROM Specialistas_paslauga WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "'";
        mysqli_query($db, $delete_sql);
        
        // Insert new selections
        if (isset($_POST['kategorijos']) && is_array($_POST['kategorijos'])) {
            foreach ($_POST['kategorijos'] as $kategorija) {
                $kategorija = mysqli_real_escape_string($db, $kategorija);
                if (!empty($kategorija)) {
                    $insert_sql = "INSERT INTO Specialistas_paslauga (specialistas_id, paslauga_kategorija) 
                                  VALUES ('" . mysqli_real_escape_string($db, $specialist_userid) . "', '$kategorija')";
                    mysqli_query($db, $insert_sql);
                }
            }
            $_SESSION['message'] = "Paslaugų kategorijos sėkmingai išsaugotos!";
        } else {
            $_SESSION['message'] = "Paslaugų kategorijos pašalintos (jūs neteikiate jokių paslaugų).";
        }
    }
    header("Location: paslaugos_specialistui.php");
    exit;
}

// Get all available service categories
$categories_sql = "SELECT DISTINCT kategorija FROM Paslauga WHERE kategorija IS NOT NULL AND kategorija != '' ORDER BY kategorija";
$categories_result = mysqli_query($db, $categories_sql);
$available_categories = array();
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $available_categories[] = $row['kategorija'];
    }
}

// Get services in each category
$services_by_category = array();
foreach ($available_categories as $cat) {
    $services_sql = "SELECT * FROM Paslauga WHERE kategorija = '" . mysqli_real_escape_string($db, $cat) . "' AND aktyvus = 1 ORDER BY pavadinimas";
    $services_result = mysqli_query($db, $services_sql);
    $services_by_category[$cat] = array();
    if ($services_result) {
        while ($row = mysqli_fetch_assoc($services_result)) {
            $services_by_category[$cat][] = $row;
        }
    }
}

// Get specialist's selected categories
$selected_categories = array();
if ($specialist_userid) {
    $selected_sql = "SELECT paslauga_kategorija FROM Specialistas_paslauga WHERE specialistas_id = '" . mysqli_real_escape_string($db, $specialist_userid) . "'";
    $selected_result = mysqli_query($db, $selected_sql);
    if ($selected_result) {
        while ($row = mysqli_fetch_assoc($selected_result)) {
            $selected_categories[] = $row['paslauga_kategorija'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mano paslaugos - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .category-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        .category-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .category-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .category-checkbox {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }
        .services-list {
            margin-left: 30px;
            margin-top: 10px;
            padding-left: 15px;
            border-left: 2px solid #e0e0e0;
        }
        .service-item {
            padding: 5px 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Mano paslaugų kategorijos</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Pasirinkite paslaugų kategorijas, kurias teikiate. Klientai matys jus kaip galimą specialistą šioms paslaugoms.
                </p>
                
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <?php if (empty($available_categories)): ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                            <div style="font-size: 18px; font-weight: 500; color: #666;">Nėra prieinamų paslaugų kategorijų</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_categories as $category): ?>
                        <div class="category-card <?php echo in_array($category, $selected_categories) ? 'selected' : ''; ?>">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" 
                                       name="kategorijos[]" 
                                       value="<?php echo htmlspecialchars($category); ?>"
                                       class="category-checkbox"
                                       <?php echo in_array($category, $selected_categories) ? 'checked' : ''; ?>
                                       onchange="this.closest('.category-card').classList.toggle('selected', this.checked)">
                                <div style="flex: 1;">
                                    <h3 style="color: #1e3c72; margin: 0;"><?php echo htmlspecialchars($category); ?></h3>
                                    <?php if (isset($services_by_category[$category]) && !empty($services_by_category[$category])): ?>
                                    <div class="services-list">
                                        <?php foreach ($services_by_category[$category] as $service): ?>
                                        <div class="service-item">
                                            • <?php echo htmlspecialchars($service['pavadinimas']); ?> 
                                            (<?php echo number_format($service['kaina'], 2); ?> €, 
                                            <?php echo $service['trukme']; ?> min.)
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                        <button type="submit" name="save_services" class="btn">Išsaugoti paslaugų kategorijas</button>
                    </div>
                </form>
                
                <!-- Statistics -->
                <div class="stats-grid mt-20">
                    <div class="stat-card">
                        <h3>Pasirinkta kategorijų</h3>
                        <div class="number"><?php echo count($selected_categories); ?></div>
                        <p style="font-size: 14px; opacity: 0.9;">Iš <?php echo count($available_categories); ?> galimų</p>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>Paslaugų skaičius</h3>
                        <div class="number">
                            <?php 
                            $total_services = 0;
                            foreach ($selected_categories as $cat) {
                                if (isset($services_by_category[$cat])) {
                                    $total_services += count($services_by_category[$cat]);
                                }
                            }
                            echo $total_services;
                            ?>
                        </div>
                        <p style="font-size: 14px; opacity: 0.9;">Paslaugų pasirinktose kategorijose</p>
                    </div>
                </div>
            </div>
        </td></tr>
    </table>
</body>
</html>
