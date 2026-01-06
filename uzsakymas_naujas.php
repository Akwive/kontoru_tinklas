<?php
// uzsakymas_naujas.php - Naujo užsakymo pateikimas su kontorų pasirinkimu
session_start();
// PROTOTIPAS: automatinis demo prisijungimas (tik dev)
if (empty($_SESSION['user'])) {
    $_SESSION['user'] = 'Demo Vartotojas';
    $_SESSION['userid'] = 1;
    $_SESSION['ulevel'] = 4; // klientas
}
include_once("include/nustatymai.php");

// Sesijos kontrolė - tik klientams ir aukštesniems
if (!isset($_SESSION['user']) || $_SESSION['ulevel'] < 4) {
    header("Location: index.php");
    exit;
}

// DEMONSTRACINIAI DUOMENYS (prototipui)

// Get services from database - show ALL active services
// (not filtered by specialists, so clients can see all available services)
$db_services = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

$services_sql = "SELECT * FROM Paslauga WHERE aktyvus = 1 ORDER BY pavadinimas";
$services_result = mysqli_query($db_services, $services_sql);
$paslaugos = array();
if ($services_result) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $paslaugos[] = [
            'id' => $row['id'],
            'pavadinimas' => $row['pavadinimas'],
            'kaina' => (float)$row['kaina'],
            'kategorija' => $row['kategorija'] ?? '',
            'trukme' => (int)($row['trukme'] ?? 30) // Trukmė minutėmis, default 30
        ];
    }
}

// Get offices from database with their available services
$offices_sql = "SELECT * FROM Kontora ORDER BY pavadinimas";
$offices_result = mysqli_query($db_services, $offices_sql);
$kontoros = array();
if ($offices_result) {
    while ($row = mysqli_fetch_assoc($offices_result)) {
        $kontora_id = $row['id'];
        
        // Get services available in this office by checking which specialists work here
        // and what service categories they provide
        // Find all service IDs that can be provided by specialists in this office
        // Step 1: Find specialists in this office (Specialistas_kontora)
        // Step 2: Find their service categories (Specialistas_paslauga)
        // Step 3: Find services matching those categories (Paslauga)
        $services_sql = "SELECT DISTINCT p.id 
                        FROM Paslauga p
                        INNER JOIN Specialistas_paslauga sp ON p.kategorija = sp.paslauga_kategorija
                        INNER JOIN Specialistas_kontora sk ON sp.specialistas_id = sk.specialistas_id
                        WHERE sk.kontora_id = $kontora_id 
                        AND p.aktyvus = 1
                        ORDER BY p.id";
        $services_result = mysqli_query($db_services, $services_sql);
        $available_service_ids = array();
        if ($services_result) {
            while ($service_row = mysqli_fetch_assoc($services_result)) {
                $available_service_ids[] = (int)$service_row['id'];
            }
        }
        
        $kontoros[] = [
            'id' => $kontora_id,
            'pavadinimas' => $row['pavadinimas'],
            'adresas' => $row['adresas'] ?? '',
            'miestas' => $row['miestas'] ?? '',
            'telefonas' => '', // Can be added to Kontora table later
            'paslaugos' => $available_service_ids, // Services available in this office
            'įvertinimas' => 4.5
        ];
    }
}

// Get specialists from database, grouped by office
// Get all specialists with their office assignments
$db_specialists = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
$specialistai = array();

// Get all specialists with their office assignments from Specialistas_kontora
$specialists_sql = "SELECT u.userid, u.username, u.email, sk.kontora_id, k.pavadinimas as kontora_pav
                    FROM " . TBL_USERS . " u
                    INNER JOIN Specialistas_kontora sk ON u.userid = sk.specialistas_id
                    INNER JOIN Kontora k ON sk.kontora_id = k.id
                    WHERE u.userlevel = " . $user_roles["Specialistas"] . "
                    ORDER BY k.pavadinimas, u.username";
$specialists_result = mysqli_query($db_specialists, $specialists_sql);

if ($specialists_result) {
    $counter = 1;
    while ($row = mysqli_fetch_assoc($specialists_result)) {
        // Get specialist's service categories (all of them, not just one)
        $categories_sql = "SELECT paslauga_kategorija FROM Specialistas_paslauga 
                          WHERE specialistas_id = '" . mysqli_real_escape_string($db_specialists, $row['userid']) . "'";
        $categories_result = mysqli_query($db_specialists, $categories_sql);
        $specializacija = 'Specialistas';
        $specialist_categories = array();
        if ($categories_result && mysqli_num_rows($categories_result) > 0) {
            $first_cat = true;
            while ($cat_row = mysqli_fetch_assoc($categories_result)) {
                $specialist_categories[] = $cat_row['paslauga_kategorija'];
                if ($first_cat) {
                    $specializacija = $cat_row['paslauga_kategorija'];
                    $first_cat = false;
                }
            }
        }
        
        // Get service IDs that this specialist can provide (based on their categories)
        $specialist_service_ids = array();
        if (!empty($specialist_categories)) {
            $escaped_categories = array_map(function($cat) use ($db_specialists) {
                return "'" . mysqli_real_escape_string($db_specialists, $cat) . "'";
            }, $specialist_categories);
            $specialist_services_sql = "SELECT id FROM Paslauga 
                                       WHERE kategorija IN (" . implode(',', $escaped_categories) . ") 
                                       AND aktyvus = 1";
            $specialist_services_result = mysqli_query($db_specialists, $specialist_services_sql);
            if ($specialist_services_result) {
                while ($service_row = mysqli_fetch_assoc($specialist_services_result)) {
                    $specialist_service_ids[] = (int)$service_row['id'];
                }
            }
        }
        
        // Use username as display name, or email if username is email-like
        $vardas = $row['username'];
        if (filter_var($row['username'], FILTER_VALIDATE_EMAIL)) {
            // Extract name from email if possible
            $vardas = ucfirst(explode('@', $row['username'])[0]);
        }
        
        $specialistai[] = [
            'id' => $counter++,
            'kontora_id' => (int)$row['kontora_id'],
            'vardas' => $vardas,
            'specializacija' => $specializacija,
            'username' => $row['username'],
            'userid' => $row['userid'],
            'paslaugos' => $specialist_service_ids // Services this specialist can provide
        ];
    }
}

// Paverčiame į JSON JavaScript'ui
$paslaugos_json = json_encode($paslaugos, JSON_UNESCAPED_UNICODE);
$kontoros_json = json_encode($kontoros, JSON_UNESCAPED_UNICODE);
$specialistai_json = json_encode($specialistai, JSON_UNESCAPED_UNICODE);

// Debug: uncomment to see kontoros data
// error_log("Kontoros: " . print_r($kontoros, true));

?>


<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naujas užsakymas - <?php echo SYSTEM_NAME; ?></title>
    <link href="include/styles.css" rel="stylesheet" type="text/css">
    <style>
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 13px;
            color: #999;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Form sections */
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            display: none;
        }
        
        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-section h3 {
            color: #1e3c72;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        /* Office cards */
        .office-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .office-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .office-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }
        
        .office-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        
        .office-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 10px;
        }
        
        .office-info {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        
        .office-rating {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        /* Specialist cards */
        .specialist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .specialist-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .specialist-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .specialist-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        
        .specialist-name {
            font-size: 16px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 5px;
        }
        
        .specialist-spec {
            font-size: 13px;
            color: #667eea;
        }
        
        /* Navigation buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #e0e0e0;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        /* Price display */
        .price-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .price-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .price-amount {
            font-size: 36px;
            font-weight: 700;
        }
        
        /* Selection indicator */
        .selection-summary {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .selection-item {
            margin: 8px 0;
            color: #666;
        }
        
        .selection-item strong {
            color: #1e3c72;
        }
        
        @media (max-width: 768px) {
            .office-grid, .specialist-grid {
                grid-template-columns: 1fr;
            }
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
                <?php include("include/meniu.php"); ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="card">
                    <h2 style="color: #1e3c72;">Naujas užsakymas</h2>
                    <?php if (!empty($_SESSION['message'])): ?>
                      <div class="message <?php echo (strpos($_SESSION['message'], 'sėkmingai') !== false) ? 'success' : 'error'; ?>">
                        <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                      </div>
                    <?php endif; ?>

                    <p style="color: #666; margin-bottom: 20px;">
                        Sekite žingsnius, kad pateiktumėte užsakymą
                    </p>
                    
                    <!-- Progress Steps -->
                    <div class="progress-steps">
                        <div class="step active" id="step-indicator-1">
                            <div class="step-number">1</div>
                            <div class="step-label">Kontora</div>
                        </div>
                        <div class="step" id="step-indicator-2">
                            <div class="step-number">2</div>
                            <div class="step-label">Paslauga</div>
                        </div>
                        <div class="step" id="step-indicator-3">
                            <div class="step-number">3</div>
                            <div class="step-label">Specialistas</div>
                        </div>
                        <div class="step" id="step-indicator-4">
                            <div class="step-number">4</div>
                            <div class="step-label">Detalės</div>
                        </div>
                    </div>
                    
                    <form id="orderForm" action="procuzsakymas.php" method="post" onsubmit="return beforeSubmit();">
                    <input type="hidden" name="paslauga_id" id="h_paslauga_id">
                    <input type="hidden" name="paslauga_pav" id="h_paslauga_pav">
                    <input type="hidden" name="kontora_id" id="h_kontora_id">
                    <input type="hidden" name="kontora_pav" id="h_kontora_pav">
                    <input type="hidden" name="specialistas_id" id="h_specialistas_id">
                    <input type="hidden" name="specialistas_vardas" id="h_specialistas_vardas">
                    <input type="hidden" name="specialistas_username" id="h_specialistas_username">
                    <input type="hidden" name="kaina" id="h_kaina">


                        
                        <!-- STEP 1: KONTORA -->
                        <div class="form-section active" id="step-1">
                            <h3>Pasirinkite advokatų kontorą</h3>
                            <p style="color: #666; margin-bottom: 20px;">
                                Pasirinkite kontorą, kurioje norite gauti paslaugą
                            </p>
                            
                            <div class="office-grid" id="offices-list">
                                <!-- Bus užpildyta JavaScript -->
                            </div>
                            
                            <div class="nav-buttons">
                                <a href="paslaugos.php" class="btn btn-secondary">← Atgal į paslaugas</a>
                                <button type="button" class="btn" onclick="nextStep(2)" id="btn-step-1" disabled>
                                    Toliau →
                                </button>
                            </div>
                        </div>
                        
                        <!-- STEP 2: PASLAUGA -->
                        <div class="form-section" id="step-2">
                            <h3>Pasirinkite paslaugą</h3>
                            <p style="color: #666; margin-bottom: 20px;">
                                Rodomos tik paslaugos, kurias teikia pasirinkta kontora
                            </p>
                            
                            <div id="services-list">
                                <!-- Bus užpildyta JavaScript -->
                            </div>
                            
                            <div id="service-details" style="display: none; margin-top: 20px;">
                                <div class="price-display">
                                    <div class="price-label">Paslaugos kaina</div>
                                    <div class="price-amount"><span id="price-display">0.00</span> €</div>
                                </div>
                            </div>
                            
                            <div class="nav-buttons">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                                    ← Atgal
                                </button>
                                <button type="button" class="btn" onclick="nextStep(3)" id="btn-step-2" disabled>
                                    Toliau →
                                </button>
                            </div>
                        </div>
                        
                        <!-- STEP 3: SPECIALISTAS -->
                        <div class="form-section" id="step-3">
                            <h3>Pasirinkite specialistą</h3>
                            
                            <div class="selection-summary" id="selection-summary">
                                <!-- Bus užpildyta JavaScript -->
                            </div>
                            
                            <p style="color: #666; margin-bottom: 20px;">
                                Pasirinkite specialistą iš pasirinktos kontoros
                            </p>
                            
                            <div class="specialist-grid" id="specialists-list">
                                <!-- Bus užpildyta JavaScript -->
                            </div>
                            
                            <div class="nav-buttons">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                    ← Atgal
                                </button>
                                <button type="button" class="btn" onclick="nextStep(4)" id="btn-step-3" disabled>
                                    Toliau →
                                </button>
                            </div>
                        </div>
                        
                        <!-- STEP 4: DETALĖS -->
                        <div class="form-section" id="step-4">
                            <h3>Pasirinkite datą, laiką ir pridėkite pastabas</h3>
                            
                            <div class="selection-summary" id="final-summary">
                                <!-- Bus užpildyta JavaScript -->
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                                <div class="form-group">
                                    <label for="data" class="required">Data</label>
                                    <input type="date" id="data" name="data" required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           onchange="loadAvailableTimes()">
                                </div>
                                
                                <div class="form-group">
                                    <label for="laikas" class="required">Pradžios laikas</label>
                                    <select id="laikas" name="laikas" required>
                                        <option value="">-- Pasirinkite datą pirmiausia --</option>
                                    </select>
                                    <small id="time-info" style="color: #999;">Pasirinkite datą, kad matytumėte galimus laikus</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="komentaras">Pastabos / Komentaras</label>
                                <textarea id="komentaras" name="komentaras" rows="5" 
                                          placeholder="Aprašykite savo situaciją trumpai..."></textarea>
                            </div>
                            
                            <div class="nav-buttons">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                                    ← Atgal
                                </button>
                                <button type="submit" class="btn">
                                    Pateikti užsakymą
                                </button>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </td>
        </tr>
    </table>
    
    <script>
        // Duomenys iš PHP
        const paslaugos = <?php echo $paslaugos_json; ?>;
        const kontoros = <?php echo $kontoros_json; ?>;
        const specialistai = <?php echo $specialistai_json; ?>;
        
        // Debug: log data on page load
        console.log('=== DEBUG: Page Load ===');
        console.log('Paslaugos count:', paslaugos.length, paslaugos);
        console.log('Kontoros count:', kontoros.length, kontoros);
        console.log('Specialistai count:', specialistai.length, specialistai);
        
        // Pasirinkimai
        let selectedService = null;
        let selectedOffice = null;
        let selectedSpecialist = null;
        
        // Initialize: show all offices on page load
        window.addEventListener('DOMContentLoaded', function() {
            showOffices();
        });
        
        // STEP 1: Rodyti visas kontoras
        function showOffices() {
            const officesList = document.getElementById('offices-list');
            officesList.innerHTML = '';
            
            console.log('=== DEBUG: showOffices ===');
            console.log('All offices:', kontoros);
            
            if (kontoros.length === 0) {
                officesList.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">Kontorų nerasta.</p>';
                return;
            }
            
            kontoros.forEach(office => {
                const card = document.createElement('div');
                card.className = 'office-card';
                card.onclick = () => selectOffice(office.id, card);
                card.innerHTML = `
                    <div class="office-name">${office.pavadinimas}</div>
                    ${office.miestas ? `<div class="office-info">${office.miestas}</div>` : ''}
                    ${office.adresas ? `<div class="office-info">${office.adresas}</div>` : ''}
                `;
                officesList.appendChild(card);
            });
        }
        
        function selectOffice(officeId, el) {
            selectedOffice = kontoros.find(k => parseInt(k.id) === parseInt(officeId));
            console.log('Selected office:', selectedOffice);
            document.querySelectorAll('.office-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('btn-step-1').disabled = false;
        }
        
        // STEP 2: Rodyti paslaugas pagal kontorą
        function showServices() {
            const servicesList = document.getElementById('services-list');
            servicesList.innerHTML = '';
            
            console.log('=== DEBUG: showServices ===');
            console.log('Selected office:', selectedOffice);
            console.log('All services:', paslaugos);
            
            if (!selectedOffice || !selectedOffice.paslaugos || !Array.isArray(selectedOffice.paslaugos) || selectedOffice.paslaugos.length === 0) {
                servicesList.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">Pasirinktoje kontoroje paslaugų nerasta.</p>';
                return;
            }
            
            // Filtruoti paslaugas pagal kontorą
            const availableServices = paslaugos.filter(p => {
                const serviceId = parseInt(p.id);
                const hasService = selectedOffice.paslaugos.some(sid => parseInt(sid) === serviceId);
                return hasService;
            });
            
            console.log('Available services:', availableServices);
            
            if (availableServices.length === 0) {
                servicesList.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">Pasirinktoje kontoroje paslaugų nerasta.</p>';
                return;
            }
            
            availableServices.forEach(service => {
                const card = document.createElement('div');
                card.className = 'office-card';
                card.onclick = () => selectService(service.id, card);
                card.innerHTML = `
                    <div class="office-name">${service.pavadinimas}</div>
                    <div class="office-info">${service.kaina.toFixed(2)} €</div>
                    ${service.kategorija ? `<div class="office-info">${service.kategorija}</div>` : ''}
                `;
                servicesList.appendChild(card);
            });
        }
        
        function selectService(serviceId, el) {
            if (!serviceId) {
                document.getElementById('service-details').style.display = 'none';
                document.getElementById('btn-step-2').disabled = true;
                return;
            }
            
            selectedService = paslaugos.find(p => p.id == serviceId);
            console.log('Selected service:', selectedService);
            document.querySelectorAll('#services-list .office-card').forEach(c => c.classList.remove('selected'));
            if (el) el.classList.add('selected');
            document.getElementById('price-display').textContent = selectedService.kaina.toFixed(2);
            document.getElementById('service-details').style.display = 'block';
            document.getElementById('btn-step-2').disabled = false;
        }

        
        // STEP 3: Rodyti specialistus
        function showSpecialists() {
            const specialistsList = document.getElementById('specialists-list');
            specialistsList.innerHTML = '';
            
            console.log('=== DEBUG: showSpecialists ===');
            console.log('Selected office:', selectedOffice);
            console.log('Selected service:', selectedService);
            console.log('All specialists:', specialistai);
            
            // Filtruoti specialistus pagal kontorą IR paslaugą
            // Specialistas turi būti iš pasirinktos kontoros IR turėti paslaugą savo paslaugų sąraše
            const officeSpecialists = specialistai.filter(s => {
                const officeMatch = parseInt(s.kontora_id) === parseInt(selectedOffice.id);
                const serviceId = parseInt(selectedService.id);
                const hasService = s.paslaugos && Array.isArray(s.paslaugos) && 
                                  s.paslaugos.some(sid => parseInt(sid) === serviceId);
                
                console.log('Specialist', s.vardas, 'kontora_id:', s.kontora_id, 'office match:', officeMatch, 
                           'services:', s.paslaugos, 'has service', serviceId, ':', hasService);
                
                return officeMatch && hasService;
            });
            
            console.log('Filtered specialists:', officeSpecialists);
            
            if (officeSpecialists.length === 0) {
                specialistsList.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">Šios paslaugos specialistų šioje kontoroje nerasta</p>';
                document.getElementById('btn-step-3').disabled = true;
                return;
            }
            
            officeSpecialists.forEach(spec => {
                const card = document.createElement('div');
                card.className = 'specialist-card';
                card.onclick = () => selectSpecialist(spec.id, card);
                card.innerHTML = `
                    <div class="specialist-name">${spec.vardas}</div>
                    <div class="specialist-spec">${spec.specializacija}</div>
                `;
                specialistsList.appendChild(card);
            });
            
            // Atnaujinti santrauką
            updateSummary();
        }
        
        function selectSpecialist(specId, el) {
          selectedSpecialist = specialistai.find(s => s.id == specId);
          console.log('Selected specialist:', selectedSpecialist);
          document.querySelectorAll('.specialist-card').forEach(c => c.classList.remove('selected'));
          el.classList.add('selected');
          document.getElementById('btn-step-3').disabled = false;
          
          // Reset time selection when specialist changes
          document.getElementById('laikas').innerHTML = '<option value="">-- Pasirinkite datą pirmiausia --</option>';
          document.getElementById('time-info').textContent = 'Pasirinkite datą, kad matytumėte galimus laikus';
        }
        
        // Load available times for selected specialist and date
        function loadAvailableTimes() {
            const dateInput = document.getElementById('data');
            const timeSelect = document.getElementById('laikas');
            const timeInfo = document.getElementById('time-info');
            
            if (!selectedSpecialist || !dateInput.value) {
                timeSelect.innerHTML = '<option value="">-- Pasirinkite specialistą ir datą --</option>';
                return;
            }
            
            timeSelect.innerHTML = '<option value="">Kraunama...</option>';
            timeSelect.disabled = true;
            timeInfo.textContent = 'Kraunami galimi laikai...';
            
            // Get specialist userid and service duration
            const specialistUserid = selectedSpecialist.userid;
            const selectedDate = dateInput.value;
            const serviceDuration = selectedService ? (selectedService.trukme || 30) : 30; // Get service duration in minutes
            
            // AJAX request to get available times
            console.log('Loading times for specialist:', specialistUserid, 'date:', selectedDate, 'duration:', serviceDuration, 'minutes');
            
            fetch('get_available_times.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'specialist_userid=' + encodeURIComponent(specialistUserid) + 
                      '&data=' + encodeURIComponent(selectedDate) +
                      '&paslaugos_trukme=' + encodeURIComponent(serviceDuration)
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('JSON parse error:', e, 'Text:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                console.log('Parsed data:', data);
                timeSelect.innerHTML = '';
                
                if (data.error) {
                    console.error('API error:', data.error);
                    timeSelect.innerHTML = '<option value="">Klaida: ' + data.error + '</option>';
                    timeInfo.textContent = 'Klaida gaunant laikus: ' + data.error;
                    timeSelect.disabled = true;
                    return;
                }
                
                if (!data.times || data.times.length === 0) {
                    timeSelect.innerHTML = '<option value="">Nėra laisvų laikų šią dieną</option>';
                    timeInfo.textContent = 'Specialistas neturi pakankamai laisvo laiko šią dieną (reikia ' + serviceDuration + ' min.)';
                    timeSelect.disabled = true;
                    return;
                }
                
                // Add available start times
                data.times.forEach(timeSlot => {
                    const option = document.createElement('option');
                    const timeValue = timeSlot.laikas || timeSlot.laikas_nuo; // Support both formats
                    option.value = timeValue;
                    // Calculate end time
                    const startTime = new Date('2000-01-01T' + timeValue + ':00');
                    const endTime = new Date(startTime.getTime() + serviceDuration * 60000);
                    const endTimeStr = endTime.toTimeString().substring(0, 5);
                    option.textContent = timeValue.substring(0, 5) + ' - ' + endTimeStr + ' (' + serviceDuration + ' min.)';
                    timeSelect.appendChild(option);
                });
                
                timeSelect.disabled = false;
                timeInfo.textContent = 'Pasirinkite pradžios laiką (paslauga trunka ' + serviceDuration + ' min.) - ' + data.times.length + ' galimas variantas';
            })
            .catch(error => {
                console.error('Error loading times:', error);
                timeSelect.innerHTML = '<option value="">Klaida kraunant laikus</option>';
                timeInfo.textContent = 'Įvyko klaida: ' + error.message;
                timeSelect.disabled = true;
            });
        }

        
        // Atnaujinti pasirinkimų santrauką
        function updateSummary() {
            const summary = `
                <div class="selection-item"><strong>Paslauga:</strong> ${selectedService.pavadinimas}</div>
                <div class="selection-item"><strong>Kontora:</strong> ${selectedOffice.pavadinimas}</div>
                <div class="selection-item"><strong>Adresas:</strong> ${selectedOffice.adresas}</div>
            `;
            document.getElementById('selection-summary').innerHTML = summary;
        }
        
        function updateFinalSummary() {
            const summary = `
                <div class="selection-item"><strong>Paslauga:</strong> ${selectedService.pavadinimas} (${selectedService.kaina.toFixed(2)} €)</div>
                <div class="selection-item"><strong>Kontora:</strong> ${selectedOffice.pavadinimas}</div>
                <div class="selection-item"><strong>Adresas:</strong> ${selectedOffice.adresas}</div>
                <div class="selection-item"><strong>Specialistas:</strong> ${selectedSpecialist.vardas}</div>
            `;
            document.getElementById('final-summary').innerHTML = summary;
        }
        
        // Žingsnių navigacija
        function nextStep(stepNum) {
            // Paslėpti dabartinį
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            
            // Rodyti naują
            document.getElementById('step-' + stepNum).classList.add('active');
            document.getElementById('step-indicator-' + stepNum).classList.add('active');
            
            // Pažymėti užbaigtus
            for (let i = 1; i < stepNum; i++) {
                document.getElementById('step-indicator-' + i).classList.add('completed');
            }
            
            // Specifiniai veiksmai
            if (stepNum === 2) showServices();
            if (stepNum === 3) showSpecialists();
            if (stepNum === 4) updateFinalSummary();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function prevStep(stepNum) {
            nextStep(stepNum);
        }
        
        // Formos pateikimas
        function beforeSubmit() {
          if (!selectedService || !selectedOffice || !selectedSpecialist) {
            alert("Prašome pasirinkti paslaugą, kontorą ir specialistą.");
            return false;
          }

          // fill hidden inputs
          document.getElementById('h_paslauga_id').value = selectedService.id;
          document.getElementById('h_paslauga_pav').value = selectedService.pavadinimas;

          document.getElementById('h_kontora_id').value = selectedOffice.id;
          document.getElementById('h_kontora_pav').value = selectedOffice.pavadinimas;

          document.getElementById('h_specialistas_id').value = selectedSpecialist.userid || selectedSpecialist.id;
          document.getElementById('h_specialistas_vardas').value = selectedSpecialist.vardas;
          document.getElementById('h_specialistas_username').value = selectedSpecialist.username || '';

          document.getElementById('h_kaina').value = selectedService.kaina.toFixed(2);

          return true; // allow normal POST submit
        }

    </script>
</body>
</html>
