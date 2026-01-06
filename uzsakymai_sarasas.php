<?php
// uzsakymai_sarasas.php - Mano užsakymai
session_start();

// PROTOTIPAS: automatinis demo prisijungimas (tik dev)
if (empty($_SESSION['user'])) {
    $_SESSION['user'] = 'Demo Vartotojas';
    $_SESSION['userid'] = 1;
    $_SESSION['ulevel'] = 4; // klientas
}

include_once("include/nustatymai.php");

// Sesijos kontrolė - tik klientams ir aukštesniems
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] < 4) {
    header("Location: index.php");
    exit;
}
$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
include_once("include/db_init.php");

// Get userid (VARCHAR hash) from session
$user_id = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
if (empty($user_id) && !empty($_SESSION['user'])) {
    // Fallback: get userid from username
    $uname = mysqli_real_escape_string($db, $_SESSION['user']);
    $res = mysqli_query($db, "SELECT userid FROM " . TBL_USERS . " WHERE username='$uname' LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);
        $user_id = $row['userid'];
        $_SESSION['userid'] = $user_id;
    }
}
$user_id_escaped = mysqli_real_escape_string($db, $user_id);
$sql = "SELECT * FROM uzsakymai WHERE user_id='$user_id_escaped' ORDER BY created_at DESC";
$result = mysqli_query($db, $sql);

?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mano užsakymai - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body>
    <table class="center">
        <tr>
            <td>
                <div class="header-section">
                    <h1><?php echo SYSTEM_NAME; ?></h1>
                    <p><?php echo SYSTEM_SUBTITLE; ?></p>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <?php include("include/meniu.php"); ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="card">
                    <h2 style="color: #1e3c72;">Mano užsakymai</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Vartotojas: <strong><?php echo $_SESSION['user']; ?></strong>
                    </p>
                    
                    <!-- Lentelė su užsakymais -->
                    <table>
                        <thead>
                            <tr>
                                <th>Nr.</th>
                                <th>Paslauga</th>
                                <th>Specialistas</th>
                                <th>Data</th>
                                <th>Laikas</th>
                                <th>Kaina (€)</th>
                                <th>Būsena</th>
                                <th>Komentaras</th>
                                <th>Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                          <?php $nr=1; while($o = mysqli_fetch_assoc($result)): ?>
                            <tr>
                              <td><?php echo $nr++; ?></td>
                              <td><?php echo htmlspecialchars($o['paslauga_pav']); ?></td>
                              <td><?php echo htmlspecialchars($o['specialistas_vardas']); ?></td>
                              <td><?php echo htmlspecialchars($o['data']); ?></td>
                              <td><?php echo htmlspecialchars(substr($o['laikas'],0,5)); ?></td>
                              <td><?php echo number_format((float)$o['kaina'], 2); ?></td>
                              <td><?php echo htmlspecialchars($o['statusas']); ?></td>
                              <td><?php echo htmlspecialchars($o['komentaras'] ?? ""); ?></td>
                              <td>-</td>
                            </tr>
                          <?php endwhile; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                              <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                              <div style="font-size: 18px; font-weight: 500; color: #666;">
                                Jūs dar neturite užsakymų
                              </div>
                              <div style="margin-top: 10px; color: #999;">
                                Pradėkite naršyti paslaugas ir sukurkite pirmąjį užsakymą!
                              </div>
                            </td>
                          </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Statistikos kortelės -->
                    <div class="stats-grid mt-20">
                        <div class="stat-card">
                            <h3>Viso užsakymų</h3>
                            <div class="number"><?php echo $result ? mysqli_num_rows($result) : 0; ?></div>
                            <p style="font-size: 14px; opacity: 0.9;">Visa istorija</p>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>Aktyvūs</h3>
                            <div class="number">
                            <?php 
                            if ($result) {
                                mysqli_data_seek($result, 0);
                                $active = 0;
                                while($o = mysqli_fetch_assoc($result)) {
                                    if ($o['statusas'] === 'Pateiktas' || $o['statusas'] === 'Patvirtintas') $active++;
                                }
                                echo $active;
                            } else {
                                echo 0;
                            }
                            ?>
                            </div>
                            <p style="font-size: 14px; opacity: 0.9;">Vykdomi dabar</p>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3>Užbaigti</h3>
                            <div class="number">
                            <?php 
                            if ($result) {
                                mysqli_data_seek($result, 0);
                                $completed = 0;
                                while($o = mysqli_fetch_assoc($result)) {
                                    if ($o['statusas'] === 'Užbaigtas') $completed++;
                                }
                                echo $completed;
                            } else {
                                echo 0;
                            }
                            ?>
                            </div>
                            <p style="font-size: 14px; opacity: 0.9;">Sėkmingai atlikti</p>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="uzsakymas_naujas.php" class="btn">+ Sukurti naują užsakymą</a>
                        <a href="paslaugos.php" class="btn" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); margin-left: 10px;">
                            Peržiūrėti paslaugas
                        </a>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
