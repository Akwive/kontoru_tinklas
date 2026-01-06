<?php
// register.php - registracijos forma
// jei pats registruojasi rolė = DEFAULT_LEVEL, jei registruoja ADMIN_LEVEL vartotojas, rolę parenka
// Kaip atsiranda vartotojas: nustatymuose $uregister=
//                                         self - pats registruojasi, admin - tik ADMIN_LEVEL, both - abu atvejai galimi
// formos laukus tikrins procregister.php

session_start();

if (empty($_SESSION['prev'])) { 
    header("Location: logout.php");
    exit;
}

include_once("include/nustatymai.php");
include_once("include/functions.php");

if ($_SESSION['prev'] != "procregister") {
    inisession("part");  // pradinis bandymas registruoti
}

$_SESSION['prev'] = "register";

// Patikrinti ar vartotojas yra administratorius
$is_admin = isset($_SESSION['ulevel']) && $_SESSION['ulevel'] == $user_roles[ADMIN_LEVEL];
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Registracija - <?php echo SYSTEM_NAME; ?></title>
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
        
        .warning-box {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .warning-box p {
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
        
        <?php if ($is_admin): ?>
        <tr>
            <td>
                <!-- MENU (tik jei admin) -->
                <?php include("include/meniu.php"); ?>
            </td>
        </tr>
        <?php endif; ?>
        
        <tr>
            <td>
                <div class="card">
                    <h2 style="color: #1e3c72;">Registracija</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        <?php if ($is_admin): ?>
                            Kuriate naują vartotoją kaip administratorius
                        <?php else: ?>
                            Sukurkite naują paskyrą sistemoje
                        <?php endif; ?>
                    </p>
                    
                    <!-- Informacija apie rolę -->
                    <?php if (!$is_admin): ?>
                    <div class="info-box">
                        <p><strong>Svarbu:</strong></p>
                        <p>Registruodamiesi automatiškai gaunate rolę <strong>"<?php echo DEFAULT_LEVEL; ?>"</strong>.</p>
                        <p>Jei reikia kitos rolės, susisiekite su administratoriumi.</p>
                    </div>
                    <?php else: ?>
                    <div class="warning-box">
                        <p><strong>Administratoriaus režimas:</strong></p>
                        <p>Galite pasirinkti vartotojo rolę ir teises.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Klaidos pranešimas -->
                    <?php if (!empty($_SESSION['message'])): ?>
                    <div class="message <?php echo (strpos($_SESSION['message'], 'sėkminga') !== false) ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['message']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- FORMA -->
                    <form action="procregister.php" method="POST">
                        
                        <div class="form-section">
                            <h3>Paskyros duomenys</h3>
                            
                            <div class="form-group">
                                <label for="user" class="required">Vartotojo vardas</label>
                                <input type="text" 
                                       id="user" 
                                       name="user" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['name_login']; ?>" 
                                       required
                                       placeholder="Pvz: jonas123">
                                <?php if (!empty($_SESSION['name_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['name_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Mažiausiai 5 simboliai, tik raidės ir skaičiai</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="pass" class="required">Slaptažodis</label>
                                <input type="password" 
                                       id="pass" 
                                       name="pass" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['pass_login']; ?>" 
                                       required
                                       placeholder="••••••••">
                                <?php if (!empty($_SESSION['pass_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['pass_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Mažiausiai 4 simboliai, tik raidės ir skaičiai</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">El. pašto adresas</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="s1" 
                                       value="<?php echo $_SESSION['mail_login']; ?>" 
                                       required
                                       placeholder="vardas@pastas.lt">
                                <?php if (!empty($_SESSION['mail_error'])): ?>
                                    <span class="error"><?php echo $_SESSION['mail_error']; ?></span>
                                <?php endif; ?>
                                <span class="help-text">Į šį adresą bus siunčiami pranešimai</span>
                            </div>
                        </div>
                        
                        <!-- ROLĖS PASIRINKIMAS (tik administratoriui) -->
                        <?php if ($is_admin): ?>
                        <div class="form-section">
                            <h3>Vartotojo rolė ir teisės</h3>
                            
                            <div class="form-group">
                                <label for="role" class="required">Rolė</label>
                                <select id="role" name="role" class="s1">
                                    <?php foreach($user_roles as $role_name => $role_level): ?>
                                        <option value="<?php echo $role_level; ?>" 
                                                <?php echo ($role_name == DEFAULT_LEVEL) ? 'selected' : ''; ?>>
                                            <?php echo $role_name; ?> (Level <?php echo $role_level; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="help-text">Pasirinkite vartotojo rolę ir teisių lygį</span>
                            </div>
                            
                            <div class="info-box">
                                <p><strong>Rolių aprašymai:</strong></p>
                                <p>• <strong>Administratorius (9):</strong> Pilnos sistemos valdymo teisės</p>
                                <p>• <strong>Specialistas (5):</strong> Gali valdyti užsakymus ir teikti paslaugas</p>
                                <p>• <strong>Klientas (4):</strong> Gali pateikti užsakymus ir naudotis paslaugomis</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- MYGTUKAI -->
                        <div class="button-group">
                            <button type="submit" class="btn">
                                Registruoti
                            </button>
                            <a href="index.php" class="btn btn-cancel">
                                Atšaukti
                            </a>
                        </div>
                        
                    </form>
                </div>
                
                <!-- PAPILDOMA INFORMACIJA -->
                <div class="card mt-20">
                    <h3 style="color: #1e3c72;">Registracijos reikalavimai</h3>
                    <div style="padding: 10px 0;">
                        <p style="margin: 10px 0; color: #666;">
                            <strong>Vartotojo vardas:</strong> Turi būti unikalus, sudarytas tik iš raidžių ir skaičių, 
                            mažiausiai 5 simboliai.
                        </p>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>Slaptažodis:</strong> Mažiausiai 4 simboliai, tik raidės ir skaičiai 
                            (specialieji simboliai neleistini).
                        </p>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>El. paštas:</strong> Turi būti teisingas el. pašto adresas. 
                            Į jį bus siunčiami pranešimai apie užsakymus.
                        </p>
                        <?php if (!$is_admin): ?>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>Rolė:</strong> Automatiškai gaunate "<?php echo DEFAULT_LEVEL; ?>" rolę. 
                            Jei reikia aukštesnių teisių, kreipkitės į administratorių.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
            </td>
        </tr>
    </table>
</body>
</html>
