<?php
// specialistu_statistika.php - Specialistų statistika (užsakymų skaičius)
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik specialistams
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"])) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "specialistu_statistika";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Get current logged-in specialist username
$current_username = $_SESSION['user'];

// Get statistics: count orders per specialist
$stats_sql = "SELECT 
                u.username,
                u.userid,
                COALESCE(COUNT(u2.id), 0) as uzsakymu_skaicius,
                COALESCE(MAX(u2.created_at), NULL) as paskutinis_uzsakymas
              FROM " . TBL_USERS . " u
              LEFT JOIN uzsakymai u2 ON u.username = u2.specialistas_username
              WHERE u.userlevel = " . $user_roles["Specialistas"] . "
              GROUP BY u.userid, u.username
              ORDER BY uzsakymu_skaicius DESC, u.username";

$stats_result = mysqli_query($db, $stats_sql);

$statistics = array();
$max_orders = 0;
$top_specialist = null;

if ($stats_result) {
    while ($row = mysqli_fetch_assoc($stats_result)) {
        $statistics[] = $row;
        if ($row['uzsakymu_skaicius'] > $max_orders) {
            $max_orders = $row['uzsakymu_skaicius'];
            $top_specialist = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialistų statistika - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .stats-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .leader-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .leader-info h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #1e3c72;
            font-weight: 600;
        }
        
        .leader-info .leader-name {
            font-size: 20px;
            color: #667eea;
            font-weight: 600;
            margin: 5px 0;
        }
        
        .leader-info .leader-count {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-table th {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .stats-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stats-table tr:hover {
            background: #f8f9fa;
        }
        
        .stats-table tr:last-child td {
            border-bottom: none;
        }
        
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .rank-other {
            background: #e0e0e0;
            color: #666;
        }
        
        .number-cell {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
        }
        
        .zero-orders {
            color: #999;
            font-style: italic;
        }
        
        .current-user {
            background: #f0f4ff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <table class="center">
        <tr>
            <td>
                <!-- HEADER -->
                <div class="header-section">
                    <h1><?php echo SYSTEM_NAME; ?></h1>
                    <p><?php echo SYSTEM_SUBTITLE; ?></p>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <!-- MENU -->
                <?php include("include/meniu.php"); ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="card">
                    <div class="stats-header">
                        <h2>Specialistų statistika</h2>
                        <p style="margin: 0; opacity: 0.9;">Užsakymų skaičius pagal specialistą</p>
                    </div>
                    
                    <?php if ($top_specialist && $max_orders > 0): ?>
                    <div class="leader-info">
                        <h3>Lyderis</h3>
                        <div class="leader-name"><?php echo htmlspecialchars($top_specialist['username']); ?></div>
                        <div class="leader-count"><?php echo $max_orders; ?> aptarnautų klientų</div>
                    </div>
                    <?php endif; ?>
                    
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Vieta</th>
                                <th>Specialistas</th>
                                <th style="text-align: center; width: 150px;">Užsakymų skaičius</th>
                                <th style="text-align: center; width: 200px;">Paskutinis užsakymas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($statistics)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px; color: #999;">
                                        Nėra duomenų
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $rank = 1;
                                $previous_count = null;
                                foreach ($statistics as $stat): 
                                    // If same count as previous, keep same rank
                                    if ($previous_count !== null && $stat['uzsakymu_skaicius'] < $previous_count) {
                                        $rank = $rank + 1;
                                    }
                                    $previous_count = $stat['uzsakymu_skaicius'];
                                    
                                    $rank_class = 'rank-other';
                                    if ($rank == 1) $rank_class = 'rank-1';
                                    elseif ($rank == 2) $rank_class = 'rank-2';
                                    elseif ($rank == 3) $rank_class = 'rank-3';
                                    
                                    // Check if this is the current user
                                    $is_current_user = (strtolower($stat['username']) === strtolower($current_username));
                                    $row_class = $is_current_user ? 'current-user' : '';
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td style="text-align: center;">
                                        <span class="rank-badge <?php echo $rank_class; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($stat['username']); ?></strong>
                                        <?php if ($is_current_user): ?>
                                            <span style="color: #667eea; font-weight: normal;">(jūs)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;" class="<?php echo $stat['uzsakymu_skaicius'] == 0 ? 'zero-orders' : 'number-cell'; ?>">
                                        <?php echo $stat['uzsakymu_skaicius']; ?>
                                    </td>
                                    <td style="text-align: center; color: #666;">
                                        <?php if ($stat['paskutinis_uzsakymas']): ?>
                                            <?php echo date('Y-m-d H:i', strtotime($stat['paskutinis_uzsakymas'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999; font-style: italic;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h3 style="color: #1e3c72; margin-bottom: 10px;">Informacija</h3>
                        <p style="color: #666; margin: 5px 0;">
                            • Statistika atnaujinama automatiškai pagal užsakymus
                        </p>
                        <p style="color: #666; margin: 5px 0;">
                            • Skaičiuojami visi užsakymai, kurie buvo pateikti specialistams
                        </p>
                        <p style="color: #666; margin: 5px 0;">
                            • Lyderis yra specialistas su daugiausiai aptarnautų klientų
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
<?php mysqli_close($db); ?>
