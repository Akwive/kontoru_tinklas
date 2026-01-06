<?php
// duk_valdyti.php - Klausimų valdymas (specialistams)
session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė - tik specialistams ir admin
if (!isset($_SESSION['user']) || ($_SESSION['ulevel'] != $user_roles["Specialistas"] && $_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) {
    header("Location: index.php");
    exit;
}

$_SESSION['prev'] = "duk_valdyti";

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['answer_question'])) {
        $klausimas_id = (int)$_POST['klausimas_id'];
        $atsakymas = mysqli_real_escape_string($db, $_POST['atsakymas']);
        
        $update_sql = "UPDATE Klausimas SET atsakymas = '$atsakymas', atsakymo_data = NOW() WHERE id = $klausimas_id";
        if (mysqli_query($db, $update_sql)) {
            $_SESSION['message'] = "Atsakymas sėkmingai pridėtas!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    } elseif (isset($_POST['add_faq'])) {
        $klausimas = mysqli_real_escape_string($db, $_POST['klausimas']);
        $atsakymas = mysqli_real_escape_string($db, $_POST['atsakymas']);
        
        $insert_sql = "INSERT INTO DUK (klausimas, atsakymas) VALUES ('$klausimas', '$atsakymas')";
        if (mysqli_query($db, $insert_sql)) {
            $_SESSION['message'] = "DUK įrašas sėkmingai pridėtas!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    } elseif (isset($_POST['delete_faq'])) {
        $faq_id = (int)$_POST['faq_id'];
        $delete_sql = "DELETE FROM DUK WHERE id = $faq_id";
        if (mysqli_query($db, $delete_sql)) {
            $_SESSION['message'] = "DUK įrašas pašalintas!";
        } else {
            $_SESSION['message'] = "Klaida: " . mysqli_error($db);
        }
    }
    header("Location: duk_valdyti.php");
    exit;
}

// Get unanswered questions
$unanswered_questions = array();
$questions_sql = "SELECT k.*, u.username as klientas_username 
                  FROM Klausimas k 
                  LEFT JOIN " . TBL_USERS . " u ON k.klientas_id = u.userid 
                  WHERE k.atsakymas IS NULL OR k.atsakymas = '' 
                  ORDER BY k.data DESC";
$questions_result = mysqli_query($db, $questions_sql);
if ($questions_result) {
    while ($row = mysqli_fetch_assoc($questions_result)) {
        $unanswered_questions[] = $row;
    }
}

// Get answered questions
$answered_questions = array();
$answered_sql = "SELECT k.*, u.username as klientas_username 
                 FROM Klausimas k 
                 LEFT JOIN " . TBL_USERS . " u ON k.klientas_id = u.userid 
                 WHERE k.atsakymas IS NOT NULL AND k.atsakymas != '' 
                 ORDER BY k.atsakymo_data DESC";
$answered_result = mysqli_query($db, $answered_sql);
if ($answered_result) {
    while ($row = mysqli_fetch_assoc($answered_result)) {
        $answered_questions[] = $row;
    }
}

// Get FAQ entries
$faq_entries = array();
$faq_sql = "SELECT * FROM DUK ORDER BY id DESC";
$faq_result = mysqli_query($db, $faq_sql);
if ($faq_result) {
    while ($row = mysqli_fetch_assoc($faq_result)) {
        $faq_entries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klausimų valdymas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .question-item { background: white; border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin: 15px 0; }
        .question-item.answered { border-color: #28a745; background: #f0fff4; }
        .question-item.unanswered { border-color: #ffc107; background: #fffbf0; }
        .question-text { font-weight: 600; color: #1e3c72; margin-bottom: 10px; }
        .answer-form { margin-top: 15px; }
        .faq-item { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <table class="center">
        <tr><td><div class="header-section"><h1><?php echo SYSTEM_NAME; ?></h1><p><?php echo SYSTEM_SUBTITLE; ?></p></div></td></tr>
        <tr><td><?php include("include/meniu.php"); ?></td></tr>
        <tr><td>
            <div class="card">
                <h2 style="color: #1e3c72;">Klausimų valdymas</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Atsakykite į klientų klausimus ir valdykite DUK
                </p>
                
                <?php if (!empty($_SESSION['message'])): ?>
                <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                </div>
                <?php endif; ?>
                
                <!-- Unanswered Questions -->
                <h3 style="color: #1e3c72; margin-top: 30px;">Laukia atsakymo (<?php echo count($unanswered_questions); ?>)</h3>
                <?php if (empty($unanswered_questions)): ?>
                    <div style="text-align: center; padding: 20px; color: #999;">
                        <div style="font-size: 36px; margin-bottom: 10px;">✅</div>
                        <div>Nėra klausimų, laukiančių atsakymo</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($unanswered_questions as $q): ?>
                    <div class="question-item unanswered">
                        <div class="question-text">❓ <?php echo htmlspecialchars($q['klausimas']); ?></div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            Klientas: <strong><?php echo htmlspecialchars($q['klientas_username'] ?? 'Nežinomas'); ?></strong> | 
                            Data: <?php echo date('Y-m-d H:i', strtotime($q['data'])); ?>
                        </div>
                        <form method="post" class="answer-form">
                            <input type="hidden" name="klausimas_id" value="<?php echo $q['id']; ?>">
                            <div class="form-group">
                                <label for="atsakymas_<?php echo $q['id']; ?>">Atsakymas</label>
                                <textarea id="atsakymas_<?php echo $q['id']; ?>" name="atsakymas" rows="4" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;"></textarea>
                            </div>
                            <button type="submit" name="answer_question" class="btn">Atsakyti</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Answered Questions -->
                <h3 style="color: #1e3c72; margin-top: 30px;">Atsakyta (<?php echo count($answered_questions); ?>)</h3>
                <?php if (!empty($answered_questions)): ?>
                    <?php foreach ($answered_questions as $q): ?>
                    <div class="question-item answered">
                        <div class="question-text">❓ <?php echo htmlspecialchars($q['klausimas']); ?></div>
                        <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                            <strong>Atsakymas:</strong><br>
                            <?php echo nl2br(htmlspecialchars($q['atsakymas'])); ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            Klientas: <strong><?php echo htmlspecialchars($q['klientas_username'] ?? 'Nežinomas'); ?></strong> | 
                            Atsakyta: <?php echo date('Y-m-d H:i', strtotime($q['atsakymo_data'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- FAQ Management -->
                <h3 style="color: #1e3c72; margin-top: 30px;">DUK valdymas</h3>
                
                <!-- Add new FAQ -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4 style="color: #1e3c72; margin-bottom: 15px;">Pridėti naują DUK įrašą</h4>
                    <form method="post">
                        <div class="form-group">
                            <label for="klausimas" class="required">Klausimas</label>
                            <input type="text" id="klausimas" name="klausimas" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label for="atsakymas" class="required">Atsakymas</label>
                            <textarea id="atsakymas" name="atsakymas" rows="4" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 4px;"></textarea>
                        </div>
                        <button type="submit" name="add_faq" class="btn">Pridėti DUK</button>
                    </form>
                </div>
                
                <!-- Existing FAQ -->
                <h4 style="color: #1e3c72; margin-top: 30px;">Esami DUK įrašai (<?php echo count($faq_entries); ?>)</h4>
                <?php if (empty($faq_entries)): ?>
                    <div style="text-align: center; padding: 20px; color: #999;">
                        <div>Nėra DUK įrašų</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($faq_entries as $faq): ?>
                    <div class="faq-item">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #1e3c72; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($faq['klausimas']); ?>
                                </div>
                                <div style="color: #666;">
                                    <?php echo nl2br(htmlspecialchars($faq['atsakymas'])); ?>
                                </div>
                            </div>
                            <form method="post" style="margin-left: 15px;" onsubmit="return confirm('Ar tikrai norite pašalinti?');">
                                <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                <button type="submit" name="delete_faq" class="btn" style="padding: 5px 10px; font-size: 12px; background: #dc3545;">Pašalinti</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </td></tr>
    </table>
</body>
</html>
