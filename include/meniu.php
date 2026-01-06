<?php
// meniu.php - rodomas meniu pagal vartotojo rolę

if (!isset($_SESSION)) { header("Location: logout.php");exit;}

include_once("include/nustatymai.php");

$user=$_SESSION['user'];
$userlevel=$_SESSION['ulevel'];

if ($_SESSION['ulevel'] == 0) {
    $role="Svečias";
} else {
    $role="";
    foreach($user_roles as $x=>$x_value) {
        if ($x_value == $userlevel) $role=$x;
    }
} 
?>

<style>
/* Menu button styling */
.menu-container {
    background: linear-gradient(to right, #f8f9fa, #e9ecef);
    border: none;
    border-bottom: 3px solid #667eea;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.menu-user-info {
    margin-bottom: 12px;
    color: #333;
    font-size: 14px;
}

.menu-links {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.menu-btn {
    display: inline-block;
    padding: 8px 16px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.menu-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.menu-btn.logout {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-color: #f5576c;
}

.menu-btn.logout:hover {
    background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
}

/* Responsive design */
@media (max-width: 768px) {
    .menu-links {
        flex-direction: column;
        align-items: stretch;
    }
    
    .menu-btn {
        text-align: center;
        width: 100%;
    }
}
</style>

<div class="menu-container">
    <div class="menu-user-info">
        Prisijungęs vartotojas: <strong><?php echo $user; ?></strong> &nbsp;&nbsp;&nbsp; 
        Rolė: <strong><?php echo $role; ?></strong>
    </div>
    
    <div class="menu-links">
        <?php
        // "Redaguoti paskyrą" - visiems išskyrus svečius
        if ($userlevel != 0) {
            echo '<a href="useredit.php" class="menu-btn">Redaguoti paskyrą</a>';
        }
        
        // Meniu punktai pagal rolę
        foreach ($usermenu as $x) {
            foreach ($x[1] as $menulevel) {
                if($menulevel == $userlevel) {
                    // Pridedame emoji pagal operacijos pavadinimą
                    $icon = '';
                    
                    echo '<a href="'.$x[2].'" class="menu-btn">'.$icon.$x[0].'</a>';
                }
            }
        }
        
        // Administratoriaus sąsaja - tik administratoriui
        if ($userlevel == $user_roles[ADMIN_LEVEL]) {
            echo '<a href="admin.php" class="menu-btn">Administratoriaus sąsaja</a>';
        }
        
        // Atsijungti - visiems
        echo '<a href="logout.php" class="menu-btn logout">Atsijungti</a>';
        ?>
    </div>
</div>
