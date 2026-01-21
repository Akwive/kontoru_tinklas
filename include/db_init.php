<?php
// include/db_init.php
// Creates required tables if they don't exist yet.
// Local setup goal:
// - import only `vartvald.sql` (users table)
// - first page load will auto-create the remaining app tables

if (!isset($db) || !$db) {
    // if caller didn't pass $db, create it
    $db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if (!$db) {
        die("DB connection error: " . mysqli_connect_error());
    }
}

mysqli_set_charset($db, "utf8mb4");

// --- Core domain tables ---
mysqli_query($db, "CREATE TABLE IF NOT EXISTS Kontora (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pavadinimas VARCHAR(200) NOT NULL,
  miestas VARCHAR(100) NULL,
  adresas VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($db, "CREATE TABLE IF NOT EXISTS Paslauga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pavadinimas VARCHAR(200) NOT NULL,
  aprasymas TEXT NULL,
  kaina DECIMAL(10,2) NOT NULL DEFAULT 0,
  trukme INT NULL DEFAULT 30,
  kategorija VARCHAR(100) NULL,
  aktyvus TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Specialist <-> office assignment (specialistas_id == users.userid (VARCHAR(32)))
mysqli_query($db, "CREATE TABLE IF NOT EXISTS Specialistas_kontora (
  specialistas_id VARCHAR(32) NOT NULL,
  kontora_id INT NOT NULL,
  PRIMARY KEY (specialistas_id),
  INDEX (kontora_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Specialist <-> service category assignment (category string stored directly)
mysqli_query($db, "CREATE TABLE IF NOT EXISTS Specialistas_paslauga (
  specialistas_id VARCHAR(32) NOT NULL,
  paslauga_kategorija VARCHAR(100) NOT NULL,
  PRIMARY KEY (specialistas_id, paslauga_kategorija)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($db, "CREATE TABLE IF NOT EXISTS Darbo_laikas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  specialistas_id VARCHAR(32) NOT NULL,
  data DATE NOT NULL,
  laikas_nuo TIME NOT NULL,
  laikas_iki TIME NOT NULL,
  uzimta TINYINT(1) NOT NULL DEFAULT 0,
  INDEX (specialistas_id),
  INDEX (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($db, "CREATE TABLE IF NOT EXISTS Klausimas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klientas_id VARCHAR(32) NOT NULL,
  klausimas TEXT NOT NULL,
  atsakymas TEXT NULL,
  data TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atsakymo_data TIMESTAMP NULL DEFAULT NULL,
  INDEX (klientas_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($db, "CREATE TABLE IF NOT EXISTS DUK (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klausimas VARCHAR(500) NOT NULL,
  atsakymas TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- Orders table (used across multiple pages) ---
mysqli_query($db, "CREATE TABLE IF NOT EXISTS uzsakymai (
  id INT AUTO_INCREMENT PRIMARY KEY,

  user_id VARCHAR(32) NOT NULL,

  paslauga_id INT NOT NULL,
  paslauga_pav VARCHAR(255) NOT NULL,

  kontora_id INT NOT NULL,
  kontora_pav VARCHAR(255) NOT NULL,

  specialistas_id VARCHAR(32) NOT NULL,
  specialistas_vardas VARCHAR(255) NOT NULL,
  specialistas_username VARCHAR(255) NULL,

  data DATE NOT NULL,
  laikas TIME NOT NULL,
  kaina DECIMAL(10,2) NOT NULL,

  statusas VARCHAR(30) NOT NULL DEFAULT 'Pateiktas',
  komentaras TEXT NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX(user_id),
  INDEX(specialistas_id),
  INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

