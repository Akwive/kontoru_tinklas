<?php
// duk.php - Dažnai užduodami klausimai (FAQ)
// Prieinama visiems vartotojams
session_start();
include("include/nustatymai.php");

// Demonstraciniai klausimai ir atsakymai
$klausimai = [
    [
        'klausimas' => 'Kaip užsiregistruoti sistemoje?',
        'atsakymas' => 'Pagrindiniame puslapyje paspauskite nuorodą "Registracija" ir užpildykite registracijos formą. Po to galėsite prisijungti su savo vartotojo vardu ir slaptažodžiu.',
        'kategorija' => 'Registracija'
    ],
    [
        'klausimas' => 'Kokias paslaugas teikiate?',
        'atsakymas' => 'Teikiame įvairias teisines paslaugas: konsultacijas, sutarčių sudarymą, atstovavimą teisme, dokumentų rengimą ir kt. Pilną paslaugų sąrašą rasite skyriuje "Paslaugų sąrašas".',
        'kategorija' => 'Paslaugos'
    ],
    [
        'klausimas' => 'Kaip pateikti užsakymą?',
        'atsakymas' => 'Prisijungę prie sistemos, pasirinkite "Pateikti užsakymą" meniu, užpildykite formą nurodydami norimą paslaugą, datą ir laiką. Specialistas peržiūrės jūsų užsakymą ir patvirtins.',
        'kategorija' => 'Užsakymai'
    ],
    [
        'klausimas' => 'Kiek laiko trunka paslaugų suteikimas?',
        'atsakymas' => 'Kiekvienos paslaugos trukmė nurodyta paslaugų sąraše. Paprastai konsultacija trunka 60 min., sutarčių sudarymas - 120 min., o atstovavimas teisme - nuo 180 min.',
        'kategorija' => 'Paslaugos'
    ],
    [
        'klausimas' => 'Kaip galiu matyti savo užsakymų būseną?',
        'atsakymas' => 'Prisijungę prie sistemos, eikite į "Mano užsakymai". Ten matysite visų savo užsakymų sąrašą su aktualiomis būsenomis: "Laukiama patvirtinimo", "Patvirtinta", "Užbaigta" ar "Atšaukta".',
        'kategorija' => 'Užsakymai'
    ],
    [
        'klausimas' => 'Ar galiu atšaukti užsakymą?',
        'atsakymas' => 'Taip, užsakymus su būsena "Laukiama patvirtinimo" galite atšaukti bet kada. Patvirtintus užsakymus atšaukti galite ne vėliau kaip 24 valandas iki paslaugos suteikimo.',
        'kategorija' => 'Užsakymai'
    ],
    [
        'klausimas' => 'Kokios yra paslaugų kainos?',
        'atsakymas' => 'Kiekvienos paslaugos kaina nurodyta paslaugų sąraše. Kainos svyruoja nuo 50€ už konsultaciją iki 300€ už atstovavimą teisme. Tikslią kainą pamatysite prieš pateikdami užsakymą.',
        'kategorija' => 'Kainos'
    ],
    [
        'klausimas' => 'Kaip susisiekti su specialistu?',
        'atsakymas' => 'Po užsakymo patvirtinimo jūsų užsakymui bus paskirtas specialistas. Jo kontaktinę informaciją rasite užsakymo detalėse skyriuje "Mano užsakymai".',
        'kategorija' => 'Specialistai'
    ]
];

// Kategorijų skaičiavimas
$kategorijos = array_unique(array_column($klausimai, 'kategorija'));
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUK - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        .faq-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .faq-question {
            font-size: 18px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .faq-question::before {
            content: "❓";
            font-size: 24px;
            margin-right: 10px;
        }
        
        .faq-answer {
            color: #555;
            line-height: 1.6;
            margin-left: 34px;
        }
        
        .category-filter {
            margin: 20px 0;
            text-align: center;
        }
        
        .category-btn {
            display: inline-block;
            padding: 8px 20px;
            margin: 5px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .category-btn:hover, .category-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
    </style>
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
                    <h2 style="color: #1e3c72; text-align: center;">Dažnai užduodami klausimai</h2>
                    <p style="text-align: center; color: #666; margin-bottom: 30px;">
                        Čia rasite atsakymus į dažniausiai užduodamus klausimus
                    </p>
                    
                    <!-- Kategorijų filtras (prototipui - nefunkcionalus) -->
                    <div class="category-filter">
                        <a href="#" class="category-btn active">Visi</a>
                        <?php foreach ($kategorijos as $kategorija): ?>
                        <a href="#" class="category-btn"><?php echo $kategorija; ?></a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Klausimų ir atsakymų sąrašas -->
                    <?php foreach ($klausimai as $index => $item): ?>
                    <div class="faq-item" data-category="<?php echo $item['kategorija']; ?>">
                        <div class="faq-question">
                            <?php echo $item['klausimas']; ?>
                            <span class="badge info" style="margin-left: auto; font-size: 11px;">
                                <?php echo $item['kategorija']; ?>
                            </span>
                        </div>
                        <div class="faq-answer">
                            <?php echo $item['atsakymas']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Papildoma informacija -->
                    <div class="message info mt-20">
                        <strong>Neradote atsakymo?</strong> 
                        <?php if (!empty($_SESSION['user'])): ?>
                            Susisiekite su mumis per sistemą arba el. paštu: <?php echo EMAIL_FROM_ADDR; ?>
                        <?php else: ?>
                            <a href="register.php" style="font-weight: bold;">Užsiregistruokite</a> 
                            ir galėsite pateikti užklausą mūsų specialistams.
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistika (prototipas) -->
                <?php if (!empty($_SESSION['user']) && $_SESSION['ulevel'] >= 5): ?>
                <div class="card mt-20">
                    <h3 style="color: #1e3c72;">📊 DUK Statistika</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Klausimų</h3>
                            <div class="number"><?php echo count($klausimai); ?></div>
                            <p style="font-size: 14px; opacity: 0.9;">Iš viso</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>Kategorijų</h3>
                            <div class="number"><?php echo count($kategorijos); ?></div>
                            <p style="font-size: 14px; opacity: 0.9;">Aktyvios</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3>Peržiūros</h3>
                            <div class="number">0</div>
                            <p style="font-size: 14px; opacity: 0.9;">Šį mėnesį</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <script>
        // Paprastas filtravimas (prototipui)
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Pašalinti active iš visų
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                
                // Pridėti active paspaudimui
                this.classList.add('active');
                
                // Prototipui - filtravimas neveikia, tik vizualinis efektas
                alert('Prototipas - filtravimas neveikia. Vėliau galima pridėti JavaScript filtravimą.');
            });
        });
    </script>
</body>
</html>
