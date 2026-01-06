<?php
// paslaugos.php - Paslaugų sąrašas
// Prieinama klientams ir admin (ne specialistams)
session_start();
include("include/nustatymai.php");

// Get services from database
$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

$services_sql = "SELECT * FROM Paslauga WHERE aktyvus = 1 ORDER BY kategorija, pavadinimas";
$services_result = mysqli_query($db, $services_sql);
$paslaugos = array();
if ($services_result) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $paslaugos[] = [
            'id' => $row['id'],
            'pavadinimas' => $row['pavadinimas'],
            'kategorija' => $row['kategorija'] ?? '',
            'kaina' => (float)$row['kaina'],
            'aprasymas' => $row['aprasymas'] ?? ''
        ];
    }
}

// Check if user is client (can order) - level 4
$can_order = isset($_SESSION['ulevel']) && $_SESSION['ulevel'] == $user_roles["Klientas"];
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paslaugos - <?php echo SYSTEM_NAME; ?></title>
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
                <?php 
                if (!empty($_SESSION['user'])) {
                    include("include/meniu.php");
                } else {
                    echo "<div style='text-align: center; padding: 15px;'>";
                    echo "<a href='index.php'>← Grįžti į pradžią</a>";
                    echo "</div>";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="card">
                    <h2 style="color: #1e3c72;">Teikiamų paslaugų sąrašas</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Siūlome platų teisinių paslaugų spektrą įvairiose teisės srityse
                    </p>
                    
                    <?php if (empty($paslaugos)): ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                        <div style="font-size: 18px; font-weight: 500; color: #666;">Nėra prieinamų paslaugų</div>
                    </div>
                    <?php else: ?>
                    <table style="margin-top: 20px; width: 100%;">
                        <thead>
                            <tr>
                                <th>Paslauga</th>
                                <th>Kategorija</th>
                                <th>Kaina (€)</th>
                                <th>Aprašymas</th>
                                <?php if ($can_order): ?>
                                <th>Veiksmai</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_category = null;
                            foreach ($paslaugos as $paslauga): 
                                if ($current_category != $paslauga['kategorija']):
                                    if ($current_category !== null):
                            ?>
                            </tbody>
                            <?php endif; ?>
                            <tbody>
                                <tr style="background: #f8f9fa;">
                                    <td colspan="<?php echo $can_order ? 5 : 4; ?>" style="padding: 10px; font-weight: 600; color: #667eea;">
                                        <?php echo htmlspecialchars($paslauga['kategorija']); ?>
                                    </td>
                                </tr>
                            <?php 
                                    $current_category = $paslauga['kategorija'];
                                endif;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($paslauga['pavadinimas']); ?></strong></td>
                                <td>
                                    <span class="badge info"><?php echo htmlspecialchars($paslauga['kategorija']); ?></span>
                                </td>
                                <td><strong><?php echo number_format($paslauga['kaina'], 2); ?> €</strong></td>
                                <td><?php echo htmlspecialchars($paslauga['aprasymas']); ?></td>
                                <?php if ($can_order): ?>
                                <td>
                                    <a href="uzsakymas_naujas.php?paslauga_id=<?php echo $paslauga['id']; ?>" 
                                       class="btn" style="font-size: 12px; padding: 6px 12px;">
                                        Užsakyti
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    
                    <?php if (empty($_SESSION['user'])): ?>
                    <div class="message info mt-20">
                        <strong>Dėmesio!</strong> Norėdami užsakyti paslaugas, turite 
                        <a href="index.php" style="font-weight: bold;">prisijungti</a> arba 
                        <a href="register.php" style="font-weight: bold;">registruotis</a>.
                    </div>
                    <?php elseif (!$can_order && isset($_SESSION['ulevel']) && $_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL]): ?>
                    <div class="message info mt-20">
                        <strong>Informacija:</strong> Administratoriai negali kurti užsakymų. Jūs galite valdyti paslaugas per "Paslaugų valdymas" meniu.
                    </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
