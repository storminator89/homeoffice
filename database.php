<?php
class Database {
    private $db;

    public function __construct() {
        try {
            $this->db = new SQLite3('homeoffice.db');
            $this->createTables();
        } catch (Exception $e) {
            die('Verbindungsfehler: ' . $e->getMessage());
        }
    }

    private function createTables() {
        // Bookings table (with allowed types)
        $query = "CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date DATE NOT NULL,
            location TEXT CHECK(location IN ('homeoffice', 'office', 'vacation', 'sick', 'training')) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);

        // Lightweight migration: expand CHECK constraint if needed
        $schema = $this->db->querySingle("SELECT sql FROM sqlite_master WHERE type='table' AND name='bookings'");
        if ($schema && strpos($schema, "CHECK(location IN ('homeoffice', 'office'))") !== false) {
            $this->db->exec('BEGIN TRANSACTION');
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS bookings_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    date DATE NOT NULL,
                    location TEXT CHECK(location IN ('homeoffice', 'office', 'vacation', 'sick', 'training')) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $this->db->exec("INSERT INTO bookings_new (id, date, location, created_at)
                                 SELECT id, date, location, created_at FROM bookings");
                $this->db->exec("DROP TABLE bookings");
                $this->db->exec("ALTER TABLE bookings_new RENAME TO bookings");
                $this->db->exec('COMMIT');
            } catch (Exception $e) {
                $this->db->exec('ROLLBACK');
                throw $e;
            }
        }

        // Settings table
        $query = "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);

        // Users table
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);

        // Insert default settings if they don't exist
        $this->setSetting('homeoffice_quota', '50', false);
    }

    public function validateBooking($date, $location) {
        $errors = [];
        
        // Prüfe ob das Datum in der Zukunft liegt
        $bookingDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Setze Zeit auf Mitternacht für korrekten Vergleich
        
        if ($bookingDate > $today) {
            $errors[] = "Buchungen können nicht für zukünftige Daten vorgenommen werden.";
        }
        
        // Prüfe ob es ein Arbeitstag (Montag-Freitag) ist
        $dayOfWeek = $bookingDate->format('N'); // 1 (Montag) bis 7 (Sonntag)
        if ($dayOfWeek > 5) {
            $errors[] = "Buchungen sind nur für Arbeitstage (Montag-Freitag) möglich.";
        }

        // Prüfe ob der Location-Wert gültig ist
        $allowed = ['homeoffice', 'office', 'vacation', 'sick', 'training'];
        if (!empty($location) && !in_array($location, $allowed)) {
            $errors[] = "Ungültiger Arbeitsort. Erlaubte Werte: homeoffice, office, vacation, sick, training.";
        }
        
        return $errors;
    }

    public function addBooking($date, $location) {
        // Wenn location leer ist, nur löschen ohne Validierung
        if (empty($location)) {
            $deleteStmt = $this->db->prepare('DELETE FROM bookings WHERE date = :date');
            $deleteStmt->bindValue(':date', $date, SQLITE3_TEXT);
            return $deleteStmt->execute();
        }

        // Validiere die Eingaben nur bei aktiver Buchung
        $errors = $this->validateBooking($date, $location);
        if (!empty($errors)) {
            throw new Exception(implode(" ", $errors));
        }

        // Überprüfe auf existierende Buchung
        $checkStmt = $this->db->prepare('SELECT location FROM bookings WHERE date = :date');
        $checkStmt->bindValue(':date', $date, SQLITE3_TEXT);
        $result = $checkStmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);

        // Wenn eine Buchung existiert, lösche sie
        if ($existing) {
            $deleteStmt = $this->db->prepare('DELETE FROM bookings WHERE date = :date');
            $deleteStmt->bindValue(':date', $date, SQLITE3_TEXT);
            $deleteStmt->execute();
        }

        // Füge neue Buchung hinzu
        $insertStmt = $this->db->prepare('INSERT INTO bookings (date, location) VALUES (:date, :location)');
        $insertStmt->bindValue(':date', $date, SQLITE3_TEXT);
        $insertStmt->bindValue(':location', $location, SQLITE3_TEXT);
        return $insertStmt->execute();
    }

    public function getQuarterBookings($year, $quarter) {
        $startDate = date('Y-m-d', strtotime($year . '-' . (($quarter - 1) * 3 + 1) . '-1'));
        $endDate = date('Y-m-d', strtotime($year . '-' . ($quarter * 3) . '-' . date('t', strtotime($year . '-' . ($quarter * 3) . '-1'))));
        
        $stmt = $this->db->prepare('SELECT date, location FROM bookings WHERE date BETWEEN :start AND :end ORDER BY date');
        $stmt->bindValue(':start', $startDate, SQLITE3_TEXT);
        $stmt->bindValue(':end', $endDate, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function getBookingsForDates($dates) {
        $placeholders = str_repeat('?,', count($dates) - 1) . '?';
        $stmt = $this->db->prepare("SELECT date, location FROM bookings WHERE date IN ($placeholders)");
        
        foreach ($dates as $index => $date) {
            $stmt->bindValue($index + 1, $date, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        $bookings = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $bookings[$row['date']] = $row['location'];
        }
        
        return $bookings;
    }

    public function getSetting($key) {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['value'] : null;
    }

    public function setSetting($key, $value, $overwrite = true) {
        if (!$overwrite) {
            // Check if setting already exists
            $existing = $this->getSetting($key);
            if ($existing !== null) {
                return;
            }
        }

        $stmt = $this->db->prepare('INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (:key, :value, CURRENT_TIMESTAMP)');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function getDb() {
        return $this->db;
    }

    public function calculateHomeofficeQuota($bookings) {
        $totalDays = 0;
        $homeofficeDays = 0;
        
        while ($row = $bookings->fetchArray(SQLITE3_ASSOC)) {
            $totalDays++;
            if ($row['location'] === 'homeoffice') {
                $homeofficeDays++;
            }
        }
        
        if ($totalDays === 0) {
            return 0;
        }
        
        return round(($homeofficeDays / $totalDays) * 100);
    }

    public function getTargetHomeofficeQuota() {
        return (int)$this->getSetting('homeoffice_quota');
    }
    
    public function getQuarterQuotaStatus($year, $quarter) {
        $bookings = $this->getQuarterBookings($year, $quarter);
        $actualQuota = $this->calculateHomeofficeQuota($bookings);
        $targetQuota = $this->getTargetHomeofficeQuota();
        
        return [
            'actual' => $actualQuota,
            'target' => $targetQuota,
            'difference' => $actualQuota - $targetQuota,
            'status' => $actualQuota <= $targetQuota ? 'ok' : 'exceeded'
        ];
    }

    public function createUser($username, $password) {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            throw new Exception('Benutzername existiert bereits');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function validateLogin($username, $password) {
        $stmt = $this->db->prepare('SELECT id, password FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user['id'];
        }
        return false;
    }

    public function hasUsers() {
        $result = $this->db->query('SELECT COUNT(*) as count FROM users');
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'] > 0;
    }
}
