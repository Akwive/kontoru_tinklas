<?php 
// useredit.php 
// vartotojas gali pasikeisti slaptažodį ar email
// formos reikšmes tikrins procuseredit.php. Esant klaidų pakartotinai rodant formą rodomos ir klaidos

session_start();
include_once("include/nustatymai.php");

// sesijos kontrole
if (!isset($_SESSION['prev']) || (($_SESSION['prev'] != "index") && ($_SESSION['prev'] != "procuseredit") && ($_SESSION['prev'] != "useredit"))) {
    header("Location: logout.php");
    exit;
}

if ($_SESSION['prev'] == "index") {
    $_SESSION['mail_login'] = $_SESSION['umail'];
    $_SESSION['passn_error'] = "";      // papildomi kintamieji naujam password įsiminti
    $_SESSION['passn_login'] = "";      // visos kitos turetų būti tuščios
}

$_SESSION['prev'] = "useredit"; 
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Paskyros redagavimas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .form-section h3 {
            color: #1e3c72;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="text"] {
            width: 100%;
        }
        
        .help-text {
            font-size: 13px;
            color: #999;
            margin-top: 5px;
            display: block;
        }
        
        .password-requirements {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .password-requirements h4 {
            color: #1e3c72;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            color: #666;
            font-size: 13px;
            margin: 5px 0;
        }
        
        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            margin-top: 30px;
        }
        
        .btn-cancel {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #e0e0e0;
        }
        
        .btn-cancel:hover {
            background: #e0e0e0;
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
                    <h2 style="color: #1e3c72;">Paskyros redagavimas</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Vartotojas: <strong><?php echo $_SESSION['user']; ?></strong>
                    </p>
                    
                    <!-- Pranešimas apie klaidas/sėkmę -->
                    <?php if (!empty($_SESSION['message'])): ?>
                    <div class="message <?php echo (strpos($_SESSION['message'], 'sėkminga') !== false || strpos($_SESSION['message'], 'pakeista') !== false) ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['message']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informacija -->
                    <div class="info-box">
                        <p><strong>Svarbu:</strong></p>
                        <p>Norėdami pakeisti paskyros duomenis, turite įvesti dabartinį slaptažodį.</p>
                        <p>Jei nekeičiate slaptažodžio, naujame slaptažodžio lauke įveskite esamą slaptažodį.</p>
                    </div>
                    
                    <!-- FORMA -->
                    <form action="procuseredit.php" method="POST">
                        
                        <!-- SLAPTAŽODIS -->
                        <div class="form-section">
                            <h3>Slaptažodžio keitimas</h3>
                            
                            <div class="form-group">
                                <label for="pass">Dabartinis slaptažodis *</label>
                                <input type="password" 
                                       id="pass" 
                                       name="pass" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['pass_login']; ?>" 
                                       required>
                                <?php if (!empty($_SESSION['pass_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['pass_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Įveskite savo dabartinį slaptažodį patvirtinimui</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="passn">Naujas slaptažodis *</label>
                                <input type="password" 
                                       id="passn" 
                                       name="passn" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['passn_login']; ?>" 
                                       required>
                                <?php if (!empty($_SESSION['passn_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['passn_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Jei nekeičiate slaptažodžio, įveskite dabartinį</span>
                            </div>
                            
                            <!-- Slaptažodžio reikalavimai -->
                            <div class="password-requirements">
                                <h4>Slaptažodžio reikalavimai:</h4>
                                <ul>
                                    <li>Mažiausiai 4 simboliai</li>
                                    <li>Gali būti sudarytas tik iš raidžių ir skaičių</li>
                                    <li>Negalima naudoti specialiųjų simbolių</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- EL. PAŠTAS -->
                        <div class="form-section">
                            <h3>Kontaktinė informacija</h3>
                            
                            <div class="form-group">
                                <label for="email">El. pašto adresas *</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['mail_login']; ?>" 
                                       required>
                                <?php if (!empty($_SESSION['mail_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['mail_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Į šį adresą bus siunčiami pranešimai apie užsakymus</span>
                            </div>
                        </div>
                        
                        <!-- MYGTUKAI -->
                        <div class="button-group">
                            <button type="submit" class="btn">
                                Išsaugoti pakeitimus
                            </button>
                            <a href="index.php" class="btn btn-cancel">
                                Atšaukti
                            </a>
                        </div>
                        
                    </form>
                </div>
                
            </td>
        </tr>
    </table>
</body>
</html>
