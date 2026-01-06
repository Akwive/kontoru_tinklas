<?php
// paslaugos_valdyti.php - Paslaugų valdymas (tik admin)
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik admin
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL]) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "paslaugos_valdyti";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        $pavadinimas = mysqli_real_escape_string($db, $_POST['pavadinimas']);
        $kategorija = mysqli_real_escape_string($db, $_POST['kategorija']);
        $kaina = (float)$_POST['kaina'];
        $aprasymas = mysqli_real_escape_string($db, $_POST['aprasymas'] ?? '');
        $aktyvus = isset($_POST['aktyvus']) ? 1 : 0;
        
        $insert_sql = "INSERT INTO Paslauga (pavadinimas, kategorija, kaina, aprasymas, aktyvus) 
                      VALUES ('$pavadinimas', '$kategorija', $kaina, '$aprasymas', $aktyvus)";
        if (mysqli_query($db, $insert_sql)) {
            $_SESSION['message'] = "Paslauga sėkmingai pridėta!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    } elseif (isset($_POST['update_service'])) {
        $id = (int)$_POST['service_id'];
        $pavadinimas = mysqli_real_escape_string($db, $_POST['pavadinimas']);
        $kategorija = mysqli_real_escape_string($db, $_POST['kategorija']);
        $kaina = (float)$_POST['kaina'];
        $aprasymas = mysqli_real_escape_string($db, $_POST['aprasymas'] ?? '');
        $aktyvus = isset($_POST['aktyvus']) ? 1 : 0;
        
        $update_sql = "UPDATE Paslauga SET pavadinimas='$pavadinimas', kategorija='$kategorija', 
                      kaina=$kaina, aprasymas='$aprasymas', aktyvus=$aktyvus WHERE id=$id";
        if (mysqli_query($db, $update_sql)) {
            $_SESSION['message'] = "Paslauga sėkmingai atnaujinta!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    } elseif (isset($_POST['delete_service'])) {
        $id = (int)$_POST['service_id'];
        $delete_sql = "DELETE FROM Paslauga WHERE id=$id";
        if (mysqli_query($db, $delete_sql)) {
            $_SESSION['message'] = "Paslauga sėkmingai pašalinta!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    }
    header("Location: paslaugos_valdyti.php");
    exit;
}

// Get all services
$services_sql = "SELECT * FROM Paslauga ORDER BY kategorija, pavadinimas";
$services_result = mysqli_query($db, $services_sql);
$services = array();
if ($services_result) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $services[] = $row;
    }
}

// Get all categories
$categories_sql = "SELECT DISTINCT kategorija FROM Paslauga WHERE kategorija IS NOT NULL AND kategorija != '' ORDER BY kategorija";
$categories_result = mysqli_query($db, $categories_sql);
$categories = array();
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row['kategorija'];
    }
}
// Add common categories if not in DB
$common_categories = ['Konsultacija', 'Dokumentai', 'Teismas', 'Verslo teisė', 'Šeimos teisė'];
foreach ($common_categories as $cat) {
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}
sort($categories);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paslaugų valdymas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .service-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .service-item { background: white; border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .service-item.inactive { opacity: 0.6; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge.active { background: #28a745; color: white; }
        .badge.inactive { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Paslaugų valdymas</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Valdykite sistemos paslaugas: pridėkite, redaguokite arba pašalinkite paslaugas
                </p>
                
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                
                <!-- Add new service form -->
                <div class="service-form">
                    <h3 style="color: #1e3c72; margin-bottom: 15px;">Pridėti naują paslaugą</h3>
                    <form method="post">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label for="pavadinimas" class="required">Pavadinimas *</label>
                                <input type="text" id="pavadinimas" name="pavadinimas" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                            </div>
                            <div class="form-group">
                                <label for="kategorija" class="required">Kategorija *</label>
                                <input type="text" id="kategorija" name="kategorija" list="categories" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                                <datalist id="categories">
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label for="kaina" class="required">Kaina (€) *</label>
                                <input type="number" id="kaina" name="kaina" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="aprasymas">Aprašymas</label>
                            <textarea id="aprasymas" name="aprasymas" rows="3" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>
                                <input type="checkbox" name="aktyvus" checked> Aktyvi paslauga
                            </label>
                        </div>
                        <button type="submit" name="add_service" class="btn">Pridėti paslaugą</button>
                    </form>
                </div>
                
                <!-- Existing services -->
                <h3 style="color: #1e3c72; margin-top: 30px;">Esamos paslaugos (<?php echo count($services); ?>)</h3>
                <?php if (empty($services)): ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                        <div style="font-size: 18px; font-weight: 500; color: #666;">Nėra paslaugų</div>
                    </div>
                <?php else: ?>
                    <?php 
                    $current_category = null;
                    foreach ($services as $service): 
                        if ($current_category != $service['kategorija']):
                            if ($current_category !== null) echo '</div>';
                            $current_category = $service['kategorija'];
                    ?>
                    <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($current_category); ?></h4>
                    <div>
                    <?php endif; ?>
                        <div class="service-item <?php echo !$service['aktyvus'] ? 'inactive' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 10px 0; color: #1e3c72;">
                                        <?php echo htmlspecialchars($service['pavadinimas']); ?>
                                        <span class="badge <?php echo $service['aktyvus'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $service['aktyvus'] ? 'Aktyvi' : 'Neaktyvi'; ?>
                                        </span>
                                    </h4>
                                    <?php if ($service['aprasymas']): ?>
                                    <p style="color: #666; margin: 5px 0;"><?php echo htmlspecialchars($service['aprasymas']); ?></p>
                                    <?php endif; ?>
                                    <p style="color: #667eea; font-weight: 600; margin: 5px 0;">
                                        Kaina: <?php echo number_format($service['kaina'], 2); ?> €
                                    </p>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <input type="hidden" name="pavadinimas" value="<?php echo htmlspecialchars($service['pavadinimas']); ?>">
                                        <input type="hidden" name="kategorija" value="<?php echo htmlspecialchars($service['kategorija']); ?>">
                                        <input type="hidden" name="kaina" value="<?php echo $service['kaina']; ?>">
                                        <input type="hidden" name="aprasymas" value="<?php echo htmlspecialchars($service['aprasymas']); ?>">
                                        <input type="hidden" name="aktyvus" value="<?php echo $service['aktyvus']; ?>">
                                        <button type="submit" name="update_service" class="btn" style="padding: 5px 15px; font-size: 12px;">
                                            Redaguoti
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Ar tikrai norite pašalinti šią paslaugą?');">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" name="delete_service" class="btn" style="padding: 5px 15px; font-size: 12px; background: #dc3545;">
                                            Pašalinti
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </td></tr>
    </table>
</body>
</html>
