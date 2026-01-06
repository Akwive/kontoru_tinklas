<?php
// admin.php
// Administratoriaus sąsaja - vartotojų valdymas ir specialisto sukūrimas
// galima šalinti vartotojus
// galima sukurti naują specialistą su kontora

session_start();
include_once("include/nustatymai.php");
include_once("include/functions.php");

// Sesijos kontrolė
if (!isset($_SESSION['prev']) || ($_SESSION['ulevel'] != $user_roles[ADMIN_LEVEL])) { 
    header("Location: logout.php");
    exit;
}

$_SESSION['prev'] = "admin";
date_default_timezone_set("Europe/Vilnius");

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die("DB connection error: " . mysqli_connect_error());
}

// Get all kontoras for specialist creation form
$kontoros_sql = "SELECT * FROM Kontora ORDER BY pavadinimas";
$kontoros_result = mysqli_query($db, $kontoros_sql);
$kontoros = array();
if ($kontoros_result) {
    while ($row = mysqli_fetch_assoc($kontoros_result)) {
        $kontoros[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Administratoriaus sąsaja - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .admin-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .admin-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        
        .delete-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .timestamp {
            font-size: 13px;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Tab styles */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .tab.active {
            background: white;
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .form-section h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #667eea;
            color: white;
        }
        
        .role-specialist {
            background: #28a745;
            color: white;
        }
        
        .role-client {
            background: #17a2b8;
            color: white;
        }
        
        .role-user {
            background: #6c757d;
            color: white;
        }
        
        .role-blocked {
            background: #dc3545;
            color: white;
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
                    <h2 style="color: #1e3c72;">Administratoriaus sąsaja</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Valdykite vartotojus ir kurkite naujus specialistus
                    </p>
                    
                    <!-- Pranešimas -->
                    <?php if (!empty($_SESSION['message'])): ?>
                    <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false || strpos($_SESSION['message'], 'sėkminga') !== false) ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tabs -->
                    <div class="tabs">
                        <button class="tab active" onclick="switchTab('users')">Vartotojų valdymas</button>
                        <button class="tab" onclick="switchTab('specialist')">Sukurti specialistą</button>
                    </div>
                    
                    <!-- Tab 1: Vartotojų valdymas -->
                    <div id="tab-users" class="tab-content active">
                        <form name="vartotojai" action="procadmin.php" method="post">
                            
                            <!-- Viršutiniai mygtukai -->
                            <div class="action-buttons">
                                <div>
                                    <a href="index.php" class="btn btn-secondary">Atgal</a>
                                </div>
                                <button type="submit" class="btn">Išsaugoti pakeitimus</button>
                            </div>
                            
                            <!-- Vartotojų lentelė -->
                            <?php
                            // Get all users with kontora info for specialists
                            $sql = "SELECT u.userid, u.username, u.userlevel, u.email, u.timestamp,
                                    sk.kontora_id, k.pavadinimas as kontora_pavadinimas, k.miestas as kontora_miestas
                                    FROM " . TBL_USERS . " u
                                    LEFT JOIN Specialistas_kontora sk ON u.userid = sk.specialistas_id AND u.userlevel = " . $user_roles["Specialistas"] . "
                                    LEFT JOIN Kontora k ON sk.kontora_id = k.id
                                    ORDER BY u.userlevel DESC, u.username";
                            $result = mysqli_query($db, $sql);
                            
                            // Get all kontoras for dropdown
                            $kontoras_sql = "SELECT * FROM Kontora ORDER BY pavadinimas";
                            $kontoras_result = mysqli_query($db, $kontoras_sql);
                            $all_kontoras = array();
                            if ($kontoras_result) {
                                while ($k_row = mysqli_fetch_assoc($kontoras_result)) {
                                    $all_kontoras[] = $k_row;
                                }
                            }
                            
                            if (!$result || (mysqli_num_rows($result) < 1)) {
                                echo '<div class="message error">Klaida skaitant lentelę users</div>';
                            } else {
                            ?>
                            
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Vartotojo vardas</th>
                                        <th>Rolė</th>
                                        <th>Kontora</th>
                                        <th>El. paštas</th>
                                        <th>Paskutinį kartą aktyvus</th>
                                        <th style="text-align: center;">Šalinti?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): 
                                        $userid = $row['userid'];
                                        $username = $row['username'];
                                        $level = $row['userlevel'];
                                        $email = $row['email'];
                                        $time = date("Y-m-d H:i", strtotime($row['timestamp']));
                                        $kontora_id = $row['kontora_id'] ?? null;
                                        $kontora_pavadinimas = $row['kontora_pavadinimas'] ?? null;
                                        $kontora_miestas = $row['kontora_miestas'] ?? null;
                                        
                                        // Determine role badge
                                        $role_badge = "role-user";
                                        $role_name = "Vartotojas";
                                        $is_specialist = false;
                                        if ($level == 9) {
                                            $role_badge = "role-admin";
                                            $role_name = "Administratorius";
                                        } elseif ($level == 5) {
                                            $role_badge = "role-specialist";
                                            $role_name = "Specialistas";
                                            $is_specialist = true;
                                        } elseif ($level == 4) {
                                            $role_badge = "role-client";
                                            $role_name = "Klientas";
                                        } elseif ($level == UZBLOKUOTAS) {
                                            $role_badge = "role-blocked";
                                            $role_name = "Užblokuotas";
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($username); ?></strong></td>
                                        <td>
                                            <span class="role-badge <?php echo $role_badge; ?>">
                                                <?php echo $role_name; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($is_specialist): ?>
                                                <select name="kontora_<?php echo $userid; ?>" style="width: 100%; padding: 5px; border: 2px solid #e0e0e0; border-radius: 4px;">
                                                    <option value="0">-- Nėra kontoros --</option>
                                                    <?php foreach($all_kontoras as $kontora): ?>
                                                        <option value="<?php echo $kontora['id']; ?>" 
                                                                <?php echo ($kontora_id == $kontora['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($kontora['pavadinimas']); ?>
                                                            <?php if (!empty($kontora['miestas'])): ?>
                                                                - <?php echo htmlspecialchars($kontora['miestas']); ?>
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($email); ?></td>
                                        <td class="timestamp"><?php echo $time; ?></td>
                                        <td style="text-align: center;">
                                            <input type="checkbox" 
                                                   name="naikinti_<?php echo $userid; ?>" 
                                                   class="delete-checkbox">
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <!-- Apatiniai mygtukai -->
                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" class="btn">Išsaugoti pakeitimus</button>
                            </div>
                            <?php } ?>
                        </form>
                    </div>
                    
                    <!-- Tab 2: Specialisto sukūrimas -->
                    <div id="tab-specialist" class="tab-content">
                        <form name="specialistas_form" action="procadmin_specialistas.php" method="post">
                            
                            <div class="form-section">
                                <h3>Specialisto duomenys</h3>
                                
                                <div class="form-group">
                                    <label for="username" class="required">Vartotojo vardas</label>
                                    <input type="text" 
                                           id="username" 
                                           name="username" 
                                           required
                                           placeholder="Pvz: petras_petraitis">
                                    <span class="help-text">Mažiausiai 5 simboliai, tik raidės ir skaičiai</span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="required">Slaptažodis</label>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           required
                                           placeholder="••••••••">
                                    <span class="help-text">Mažiausiai 4 simboliai</span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="required">El. pašto adresas</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           required
                                           placeholder="vardas@example.lt">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Kontora</h3>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="radio" 
                                               name="kontora_option" 
                                               value="existing" 
                                               checked 
                                               onchange="toggleKontoraFields()">
                                        Pasirinkti egzistuojančią kontorą
                                    </label>
                                </div>
                                
                                <div class="form-group" id="existing-kontora-group">
                                    <label for="kontora_id" class="required">Kontora</label>
                                    <select id="kontora_id" name="kontora_id">
                                        <option value="">-- Pasirinkite kontorą --</option>
                                        <?php foreach($kontoros as $kontora): ?>
                                            <option value="<?php echo $kontora['id']; ?>">
                                                <?php echo htmlspecialchars($kontora['pavadinimas']); ?>
                                                <?php if (!empty($kontora['miestas'])): ?>
                                                    - <?php echo htmlspecialchars($kontora['miestas']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="radio" 
                                               name="kontora_option" 
                                               value="new" 
                                               onchange="toggleKontoraFields()">
                                        Sukurti naują kontorą
                                    </label>
                                </div>
                                
                                <div id="new-kontora-group" style="display: none;">
                                    <div class="form-group">
                                        <label for="kontora_pavadinimas" class="required">Kontoros pavadinimas</label>
                                        <input type="text" 
                                               id="kontora_pavadinimas" 
                                               name="kontora_pavadinimas" 
                                               placeholder="Pvz: Advokatų kontora 'Teisingumas'">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="kontora_miestas">Miestas</label>
                                        <input type="text" 
                                               id="kontora_miestas" 
                                               name="kontora_miestas" 
                                               placeholder="Pvz: Vilnius">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="kontora_adresas">Adresas</label>
                                        <input type="text" 
                                               id="kontora_adresas" 
                                               name="kontora_adresas" 
                                               placeholder="Pvz: Gedimino pr. 25">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: right; margin-top: 30px;">
                                <a href="index.php" class="btn btn-secondary" style="margin-right: 10px;">Atšaukti</a>
                                <button type="submit" class="btn">Sukurti specialistą</button>
                            </div>
                        </form>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        function toggleKontoraFields() {
            const existingOption = document.querySelector('input[name="kontora_option"][value="existing"]');
            const newOption = document.querySelector('input[name="kontora_option"][value="new"]');
            const existingGroup = document.getElementById('existing-kontora-group');
            const newGroup = document.getElementById('new-kontora-group');
            const kontoraIdSelect = document.getElementById('kontora_id');
            
            if (existingOption.checked) {
                existingGroup.style.display = 'block';
                newGroup.style.display = 'none';
                kontoraIdSelect.required = true;
                document.getElementById('kontora_pavadinimas').required = false;
            } else {
                existingGroup.style.display = 'none';
                newGroup.style.display = 'block';
                kontoraIdSelect.required = false;
                document.getElementById('kontora_pavadinimas').required = true;
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleKontoraFields();
        });
    </script>
</body>
</html>
<?php mysqli_close($db); ?>
