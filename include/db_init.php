<?php
// include/db_init.php
// Creates required prototype tables if they don't exist yet.

if (!isset($db) || !$db) {
    // if caller didn't pass $db, create it
    $db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
    if (!$db) {
        die("DB connection error: " . mysqli_connect_error());
    }
}

mysqli_set_charset($db, "utf8mb4");

$sql = "CREATE TABLE IF NOT EXISTS uzsakymai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,

  paslauga_id INT NOT NULL,
  paslauga_pav VARCHAR(255) NOT NULL,

  kontora_id INT NOT NULL,
  kontora_pav VARCHAR(255) NOT NULL,

  specialistas_id INT NOT NULL,
  specialistas_vardas VARCHAR(255) NOT NULL,

  data DATE NOT NULL,
  laikas TIME NOT NULL,
  kaina DECIMAL(10,2) NOT NULL,

  statusas VARCHAR(30) NOT NULL DEFAULT 'Pateiktas',
  komentaras TEXT NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX(user_id),
  INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($db, $sql);

