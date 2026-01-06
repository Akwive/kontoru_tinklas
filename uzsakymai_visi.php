<?php
// uzsakymai_visi.php - Visi užsakymai (specialistams ir admin)
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"] && $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: index.php");
    exit;
}
$_SESSION['prev'] = "uzsakymai_visi";
$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) die("DB connection error: " . mysqli_connect_error());
include_once("include/db_init.php");
$specialist_username = $_SESSION['user'];
$is_admin = ($_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL]);
if ($is_admin) {
    $sql = "SELECT * FROM uzsakymai ORDER BY created_at DESC";
} else {
    // For specialist, filter by specialistas_username matching their logged-in username
    $escaped_username = mysqli_real_escape_string($db, $specialist_username);
    // Try to match by username first, fallback to display name if username not set
    $sql = "SELECT * FROM uzsakymai WHERE (specialistas_username = '$escaped_username' OR (specialistas_username IS NULL AND specialistas_vardas LIKE '%$escaped_username%')) ORDER BY created_at DESC";
}
$result = mysqli_query($db, $sql);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_status'])) {
    $uzsakymas_id = (int)$_POST['uzsakymas_id'];
    $naujas_statusas = mysqli_real_escape_string($db, $_POST['naujas_statusas']);
    $komentaras = mysqli_real_escape_string($db, $_POST['komentaras'] ?? '');
    $update_sql = "UPDATE uzsakymai SET statusas = '$naujas_statusas', komentaras = '$komentaras' WHERE id = $uzsakymas_id";
    if (mysqli_query($db, $update_sql)) {
        $_SESSION['message'] = "Užsakymo būsena sėkmingai pakeista!";
        header("Location: uzsakymai_visi.php");
        exit;
    } else {
        $_SESSION['message'] = "Klaida keičiant būseną: " . mysqli_error($db);
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Užsakymų valdymas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-Pateiktas { background: #ffc107; color: #333; }
        .status-Patvirtintas { background: #17a2b8; color: white; }
        .status-Užbaigtas { background: #28a745; color: white; }
        .status-Atšauktas { background: #dc3545; color: white; }
        .order-row:hover { background: #f8f9fa; }
        .status-form { display: inline-flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .status-select { padding: 5px 10px; border: 2px solid #e0e0e0; border-radius: 4px; }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Užsakymų valdymas</h2>
                <p style="color: #666; margin-bottom: 20px;"><?php if ($is_admin): ?>Visi sistemos užsakymai<?php else: ?>Jūsų priskirti užsakymai<?php endif; ?></p>
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Klientas</th><th>Paslauga</th><th>Specialistas</th><th>Data</th><th>Laikas</th><th>Kaina (€)</th><th>Būsena</th><th>Komentaras</th><th>Veiksmai</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                      <?php while($o = mysqli_fetch_assoc($result)): ?>
                        <tr class="order-row">
                          <td><?php echo $o['id']; ?></td>
                          <td><?php 
                              $client_id = $o['user_id'];
                              $client_query = mysqli_query($db, "SELECT username FROM " . TBL_USERS . " WHERE userid = '$client_id' LIMIT 1");
                              if ($client_query && mysqli_num_rows($client_query) > 0) {
                                  $client = mysqli_fetch_assoc($client_query);
                                  echo htmlspecialchars($client['username']);
                              } else {
                                  echo "ID: " . htmlspecialchars($o['user_id']);
                              }
                          ?></td>
                          <td><?php echo htmlspecialchars($o['paslauga_pav']); ?></td>
                          <td><?php echo htmlspecialchars($o['specialistas_vardas']); ?></td>
                          <td><?php echo htmlspecialchars($o['data']); ?></td>
                          <td><?php echo htmlspecialchars(substr($o['laikas'], 0, 5)); ?></td>
                          <td><?php echo number_format((float)$o['kaina'], 2); ?></td>
                          <td><span class="status-badge status-<?php echo htmlspecialchars($o['statusas']); ?>"><?php echo htmlspecialchars($o['statusas']); ?></span></td>
                          <td><?php echo htmlspecialchars($o['komentaras'] ?? ''); ?></td>
                          <td>
                              <form method="post" class="status-form" onsubmit="return confirm('Ar tikrai norite pakeisti užsakymo būseną?');">
                                  <input type="hidden" name="uzsakymas_id" value="<?php echo $o['id']; ?>">
                                  <select name="naujas_statusas" class="status-select" required>
                                      <option value="Pateiktas" <?php echo $o['statusas'] == 'Pateiktas' ? 'selected' : ''; ?>>Pateiktas</option>
                                      <option value="Patvirtintas" <?php echo $o['statusas'] == 'Patvirtintas' ? 'selected' : ''; ?>>Patvirtintas</option>
                                      <option value="Užbaigtas" <?php echo $o['statusas'] == 'Užbaigtas' ? 'selected' : ''; ?>>Užbaigtas</option>
                                      <option value="Atšauktas" <?php echo $o['statusas'] == 'Atšauktas' ? 'selected' : ''; ?>>Atšauktas</option>
                                  </select>
                                  <textarea name="komentaras" placeholder="Komentaras..." rows="2" style="width: 150px; padding: 5px;"><?php echo htmlspecialchars($o['komentaras'] ?? ''); ?></textarea>
                                  <button type="submit" name="change_status" class="btn" style="padding: 5px 10px; font-size: 12px;">Keisti</button>
                              </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                        <div style="font-size: 18px; font-weight: 500; color: #666;">Nėra užsakymų</div>
                      </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="stats-grid mt-20">
                    <div class="stat-card"><h3>Viso užsakymų</h3><div class="number"><?php echo $result ? mysqli_num_rows($result) : 0; ?></div></div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>Laukia patvirtinimo</h3>
                        <div class="number"><?php 
                            if ($result) { mysqli_data_seek($result, 0); $pending = 0;
                                while($o = mysqli_fetch_assoc($result)) if ($o['statusas'] == 'Pateiktas') $pending++;
                                echo $pending;
                            } else echo 0;
                        ?></div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>Užbaigti</h3>
                        <div class="number"><?php 
                            if ($result) { mysqli_data_seek($result, 0); $completed = 0;
                                while($o = mysqli_fetch_assoc($result)) if ($o['statusas'] == 'Užbaigtas') $completed++;
                                echo $completed;
                            } else echo 0;
                        ?></div>
                    </div>
                </div>
            </div>
        </td></tr>
    </table>
</body>
</html>
