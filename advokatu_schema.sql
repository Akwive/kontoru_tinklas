-- Advokatų kontorų tinklo DB schema (PROTOTYPE - Simplified)
-- Use this for your prototype demonstration

-- 1. VARTOTOJAS (Users) - Main user table
CREATE TABLE Vartotojas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vardas VARCHAR(100) NOT NULL,
  el_pastas VARCHAR(100) NOT NULL UNIQUE,
  slaptazodis VARCHAR(255) NOT NULL,
  telefonas VARCHAR(20),
  role ENUM('klientas', 'specialistas', 'administratorius') DEFAULT 'klientas',
  sukurimo_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. KONTORA (Law Firm)
CREATE TABLE Kontora (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pavadinimas VARCHAR(200) NOT NULL,
  miestas VARCHAR(100),
  adresas VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. SPECIALIZACIJA
CREATE TABLE Specializacija (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pavadinimas VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. SPECIALISTAS_SPECIALIZACIJA (Many-to-many)
CREATE TABLE Specialistas_specializacija (
  specialistas_id INT,
  specializacija_id INT,
  PRIMARY KEY (specialistas_id, specializacija_id),
  FOREIGN KEY (specialistas_id) REFERENCES Vartotojas(id) ON DELETE CASCADE,
  FOREIGN KEY (specializacija_id) REFERENCES Specializacija(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. PASLAUGA (Services)
CREATE TABLE Paslauga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pavadinimas VARCHAR(200) NOT NULL,
  aprasymas TEXT,
  kaina DECIMAL(10,2) NOT NULL,
  trukme INT DEFAULT 30 COMMENT 'Minutes',
  kategorija VARCHAR(100),
  aktyvus BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. UZSAKYMAS (Orders)
CREATE TABLE Uzsakymas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klientas_id INT NOT NULL,
  data_laikas DATETIME NOT NULL,
  busena ENUM('laukiama', 'patvirtinta', 'ivykdyta', 'atsaukta') DEFAULT 'laukiama',
  pastabos TEXT,
  bendra_kaina DECIMAL(10,2),
  sukurimo_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (klientas_id) REFERENCES Vartotojas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. UZSAKYMO_PASLAUGA (Order items - many-to-many)
CREATE TABLE Uzsakymo_paslauga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uzsakymas_id INT NOT NULL,
  paslauga_id INT NOT NULL,
  kiekis INT DEFAULT 1,
  suma DECIMAL(10,2),
  FOREIGN KEY (uzsakymas_id) REFERENCES Uzsakymas(id) ON DELETE CASCADE,
  FOREIGN KEY (paslauga_id) REFERENCES Paslauga(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. KLAUSIMAS (Questions)
CREATE TABLE Klausimas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klientas_id INT NOT NULL,
  klausimas TEXT NOT NULL,
  atsakymas TEXT,
  data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atsakymo_data TIMESTAMP NULL,
  FOREIGN KEY (klientas_id) REFERENCES Vartotojas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. DUK (FAQ)
CREATE TABLE DUK (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klausimas VARCHAR(500) NOT NULL,
  atsakymas TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. DARBO_LAIKAS (Work Schedule) - Simplified for prototype
CREATE TABLE Darbo_laikas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  specialistas_id INT NOT NULL,
  data DATE NOT NULL,
  laikas_nuo TIME NOT NULL,
  laikas_iki TIME NOT NULL,
  uzimta BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (specialistas_id) REFERENCES Vartotojas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT SAMPLE DATA FOR TESTING
-- ============================================

-- Insert test users
INSERT INTO Vartotojas (vardas, el_pastas, slaptazodis, role) VALUES
('Administratorius', 'admin@advokatu.lt', SHA2('admin123', 256), 'administratorius'),
('Petras Petraitis', 'petras@advokatu.lt', SHA2('petras123', 256), 'specialistas'),
('Jonas Jonaitis', 'jonas@gmail.com', SHA2('jonas123', 256), 'klientas'),
('Marija Marytė', 'marija@advokatu.lt', SHA2('marija123', 256), 'specialistas');

-- Insert law firms
INSERT INTO Kontora (pavadinimas, miestas, adresas) VALUES
('Advokatų kontora "Teisingumas"', 'Kaunas', 'Laisvės al. 60'),
('AB "Teisės ekspertai"', 'Vilnius', 'Gedimino pr. 25'),
('UAB "Juridinė pagalba"', 'Klaipėda', 'Taikos pr. 15');

-- Insert specializations
INSERT INTO Specializacija (pavadinimas) VALUES
('Civilinė teisė'),
('Baudžiamoji teisė'),
('Šeimos teisė'),
('Darbo teisė'),
('Verslo teisė');

-- Link specialists to specializations
INSERT INTO Specialistas_specializacija (specialistas_id, specializacija_id) VALUES
(2, 1), -- Petras - Civilinė teisė
(2, 3), -- Petras - Šeimos teisė
(4, 2), -- Marija - Baudžiamoji teisė
(4, 4); -- Marija - Darbo teisė

-- Insert services
INSERT INTO Paslauga (pavadinimas, aprasymas, kaina, trukme, kategorija) VALUES
('Pirminė konsultacija', 'Pirmoji konsultacija su advokatu, bylos įvertinimas', 50.00, 30, 'Konsultacija'),
('Išplėstinė konsultacija', 'Detalus bylos aptarimas ir strategijos planavimas', 100.00, 60, 'Konsultacija'),
('Dokumentų parengimas', 'Teisinių dokumentų rengimas ir peržiūra', 150.00, 90, 'Dokumentai'),
('Atstovavimas teisme', 'Kliento atstovavimas teismo procese', 300.00, 120, 'Teismas'),
('Sutarties sudarymas', 'Verslo ar kitos sutarties parengimas', 200.00, 60, 'Dokumentai');

-- Insert FAQ
INSERT INTO DUK (klausimas, atsakymas) VALUES
('Kaip užsiregistruoti sistemoje?', 'Spauskite mygtuką "Registracija" ir užpildykite formą.'),
('Kaip pasirinkti advokatą?', 'Paslaugų kataloge pasirinkite paslaugą ir sistema pasiūlys laisvus advokatus.'),
('Ar galima atšaukti užsakymą?', 'Taip, užsakymą galite atšaukti ne vėliau kaip 24 val. iki konsultacijos.');

-- Insert sample work schedule (for demo)
INSERT INTO Darbo_laikas (specialistas_id, data, laikas_nuo, laikas_iki, uzimta) VALUES
(2, '2025-01-20', '09:00', '09:30', FALSE),
(2, '2025-01-20', '09:30', '10:00', FALSE),
(2, '2025-01-20', '10:00', '10:30', TRUE),
(2, '2025-01-20', '14:00', '14:30', FALSE),
(4, '2025-01-20', '10:00', '10:30', FALSE),
(4, '2025-01-20', '10:30', '11:00', FALSE);

-- Insert sample order
INSERT INTO Uzsakymas (klientas_id, data_laikas, busena, pastabos, bendra_kaina) VALUES
(3, '2025-01-20 09:00:00', 'patvirtinta', 'Skubus klausimas dėl sutarties', 50.00);

INSERT INTO Uzsakymo_paslauga (uzsakymas_id, paslauga_id, kiekis, suma) VALUES
(1, 1, 1, 50.00);

-- Insert sample question
INSERT INTO Klausimas (klientas_id, klausimas) VALUES
(3, 'Ar galiu gauti nemokamą konsultaciją dėl darbo sutarties?');
