<?php
class DB {
    public $conn;
    
    public function __construct() {
        try {
            $this->conn = new SQLite3(__DIR__ . '/../database.sqlite');
            $this->conn->enableExceptions(true);
            $this->initializeTables();
        } catch(Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection error. Please check the logs.");
        }
    }
    
    private function initializeTables() {
        // Create searches table for caching results
        $this->conn->exec("CREATE TABLE IF NOT EXISTS searches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            query TEXT NOT NULL,
            results TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create trending table for popular searches
        $this->conn->exec("CREATE TABLE IF NOT EXISTS trending (
            query TEXT PRIMARY KEY,
            count INTEGER DEFAULT 0
        )");
        
        // Create static_pages table for admin managed content
        $this->conn->exec("CREATE TABLE IF NOT EXISTS static_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE,
            title TEXT,
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create settings table for configuration options
        $this->conn->exec("CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )");
        
        // Create ai_models table for custom OpenRouter models
        $this->conn->exec("CREATE TABLE IF NOT EXISTS ai_models (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            model_name TEXT UNIQUE NOT NULL,
            display_name TEXT,
            description TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create advertisements table for ad management
        $this->conn->exec("CREATE TABLE IF NOT EXISTS advertisements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            position TEXT DEFAULT 'top',
            is_active INTEGER DEFAULT 1,
            click_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default settings if they don't exist
        $this->insertDefaultSettings();
    }
    
    private function insertDefaultSettings() {
        $defaultSettings = [
            'site_title' => 'Buscador IA',
            'site_description' => 'Motor de búsqueda web impulsado por IA',
            'openrouter_api_key' => '',
            'openrouter_model' => 'openai/gpt-3.5-turbo',
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'show_images' => '1',
            'trending_count' => '5',
            'cache_duration' => '86400',
            'site_logo' => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg?auto=compress&cs=tinysrgb&w=200',
            'admin_username' => 'admin',
            'admin_password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'custom_js' => '',
            'meta_keywords' => 'búsqueda, IA, motor de búsqueda, inteligencia artificial',
            'ads_enabled' => '0',
            'ads_code' => '',
            'show_ai_summary' => '1',
            'default_language' => 'en',
            'site_logo_light' => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg?auto=compress&cs=tinysrgb&w=200',
            'site_logo_dark' => 'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?auto=compress&cs=tinysrgb&w=200'
        ];
        
        foreach ($defaultSettings as $key => $value) {
            $stmt = $this->conn->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
            $stmt->bindValue(1, $key, SQLITE3_TEXT);
            $stmt->bindValue(2, $value, SQLITE3_TEXT);
            $stmt->execute();
        }
    }
}

// Create a global database instance
$dbInstance = new DB();
?>
