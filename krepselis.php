<?php
// krepseli.php - Paslaugų krepšelis (prototipas)
session_start();
include_once("include/nustatymai.php");

// Sesijos kontrolė - tik klientams ir aukštesniems
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] < 4) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krepšelis - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cart-empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .cart-total {
            font-size: 24px;
            font-weight: 700;
            color: #1e3c72;
        }
    </style>
</head>
<body>
    <table class="center">
        <tr>
            <td>
                <div class="header-section">
                    <h1>⚖️ <?php echo SYSTEM_NAME; ?></h1>
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
                    <h2 style="color: #1e3c72;">🛒 Paslaugų krepšelis</h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Čia galite peržiūrėti pasirinktas paslaugas ir pateikti užsakymą
                    </p>
                    
                    <!-- Tuščias krepšelis -->
                    <div class="cart-empty">
                        <div class="cart-empty-icon">🛒</div>
                        <h3 style="color: #666; margin-bottom: 10px;">Jūsų krepšelis tuščias</h3>
                        <p style="color: #999; margin-bottom: 30px;">
                            Pridėkite paslaugų iš paslaugų sąrašo, kad galėtumėte pateikti užsakymą
                        </p>
                        
                        <a href="paslaugos.php" class="btn">
                            Peržiūrėti paslaugas
                        </a>
                    </div>
                    
                    <!-- Krepšelio lentelė (paslėpta, nes tuščia) -->
                    <table style="display: none;">
                        <thead>
                            <tr>
                                <th>Paslauga</th>
                                <th>Kategorija</th>
                                <th>Trukmė</th>
                                <th>Kaina (€)</th>
                                <th>Pageidaujama data</th>
                                <th>Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Čia būtų paslaugos -->
                        </tbody>
                    </table>
                    
                    <!-- Krepšelio veiksmai (paslėpta, nes tuščia) -->
                    <div class="cart-actions" style="display: none;">
                        <div class="cart-total">
                            Viso: <span style="color: #667eea;">0.00 €</span>
                        </div>
                        <div>
                            <a href="paslaugos.php" class="btn" style="background: #f8f9fa; color: #667eea; margin-right: 10px;">
                                ← Tęsti apsipirkimą
                            </a>
                            <a href="uzsakymas_naujas.php" class="btn">
                                Pateikti užsakymą →
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Informacija apie krepšelį -->
                <div class="card mt-20">
                    <h3 style="color: #1e3c72;">Kaip naudotis krepšeliu?</h3>
                    <div style="padding: 10px 0;">
                        <p style="margin: 10px 0; color: #666;">
                            <strong>1.</strong> Peržiūrėkite <a href="paslaugos.php" style="font-weight: 600;">paslaugų sąrašą</a>
                        </p>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>2.</strong> Paspauskite "Įdėti į krepšelį" prie norimų paslaugų
                        </p>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>3.</strong> Grįžkite į krepšelį ir peržiūrėkite pasirinkimus
                        </p>
                        <p style="margin: 10px 0; color: #666;">
                            <strong>4.</strong> Paspauskite "Pateikti užsakymą" ir užpildykite formą
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
