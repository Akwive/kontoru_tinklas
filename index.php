<?php
// index.php - Advokatų kontorų užsakymų vykdymo sistema
// Jei vartotojas prisijungęs - rodomas meniu pagal jo rolę
// Jei neprisijungęs - prisijungimo forma

session_start();
include("include/nustatymai.php");
include("include/functions.php");
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo SYSTEM_NAME; ?> - Pradžia</title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body>
    <table class="center">
        <tr>
            <td>
                <!-- HEADER SEKCIJA -->
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
        // VARTOTOJAS PRISIJUNGĘS - rodome meniu ir turinį
        inisession("part");
        $_SESSION['prev'] = "index"; 
        
        // Įterpiamas meniu pagal vartotojo rolę
        include("include/meniu.php");
?>
                <div class="card text-center">
                    <h2>Sveiki prisijungę!</h2>
                    <p style="color: #666; margin-top: 10px;">
                        Pasirinkite norimą operaciją iš meniu.
                    </p>
                    
                    <!-- Statistikos kortelės -->
                    <div class="stats-grid mt-20">
                        <div class="stat-card">
                            <h3>Viso užsakymų</h3>
                            <div class="number">0</div>
                            <p style="font-size: 14px; opacity: 0.9;">Šiame mėnesyje</p>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>Aktyvūs užsakymai</h3>
                            <div class="number">0</div>
                            <p style="font-size: 14px; opacity: 0.9;">Vykdomi dabar</p>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3>Paslaugos</h3>
                            <div class="number">0</div>
                            <p style="font-size: 14px; opacity: 0.9;">Prieinamos paslaugos</p>
                        </div>
                    </div>
                </div>
<?php
    } else {
        // VARTOTOJAS NEPRISIJUNGĘS - rodome prisijungimo formą
        
        if (!isset($_SESSION['prev'])) {
            inisession("full");
        } else {
            if ($_SESSION['prev'] != "proclogin") {
                inisession("part");
            }
        }
        
        // Pranešimas apie klaidas arba sėkmę (jei yra)
        if (!empty($_SESSION['message'])) {
            $message_class = (strpos($_SESSION['message'], 'sėkminga') !== false) ? 'success' : 'error';
            echo "<div class='message {$message_class} text-center'>";
            echo $_SESSION['message'];
            echo "</div>";
            $_SESSION['message'] = ''; // Clear message after displaying
        }
        
        // Prisijungimo forma
        include("include/login.php");
        
        // Informacija apie sistemą
?>
                <div class="card mt-20">
                    <h3 style="text-align: center; color: #1e3c72;">Apie sistemą</h3>
                    <p style="text-align: center; color: #666; line-height: 1.6;">
                        <?php echo SYSTEM_NAME; ?> - tai profesionali užsakymų valdymo sistema,<br>
                        skirta advokatų kontoroms ir jų klientams.<br><br>
                        <strong>Registruokitės ir pradėkite naudotis paslaugomis jau šiandien!</strong>
                    </p>
                </div>
                
                <!-- Papildoma informacija svečiams -->
                <div style="text-align: center; margin: 20px; color: #888; font-size: 13px;">
                    <p>Autorius: <?php echo SYSTEM_AUTHOR; ?> | <?php echo date('Y'); ?></p>
                </div>
<?php
    }
?>
            </td>
        </tr>
    </table>
</body>
</html>
