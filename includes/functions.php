<?php
/**
 * Utility functions for the search engine
 */

// Language Management Functions
function detectLanguage() {
    // Check if language is set via URL parameter (manual selection)
    if (isset($_GET['lang'])) {
        $lang = sanitize_input($_GET['lang']);
        if (isLanguageSupported($lang)) {
            setLanguage($lang);
            return $lang;
        }
    }
    
    // Check if language is set in session
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // Check if language is set in cookie (user previously chose manually)
    if (isset($_COOKIE['language'])) {
        $_SESSION['language'] = $_COOKIE['language'];
        return $_COOKIE['language'];
    }
    
    // Only auto-detect from browser if no previous choice was made
    $browserLang = getBrowserLanguage();
    if (isLanguageSupported($browserLang)) {
        setLanguage($browserLang);
        return $browserLang;
    }
    
    // Default to English
    return 'en';
}

function getBrowserLanguage() {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en';
    }
    
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $languages = explode(',', $acceptLanguage);
    
    foreach ($languages as $language) {
        $lang = trim(explode(';', $language)[0]);
        $lang = strtolower(substr($lang, 0, 2));
        
        if (isLanguageSupported($lang)) {
            return $lang;
        }
    }
    
    return 'en';
}

function isLanguageSupported($lang) {
    $supportedLanguages = getSupportedLanguages();
    return in_array($lang, $supportedLanguages);
}

function getSupportedLanguages() {
    $languages = [];
    $langDir = __DIR__ . '/../lang/';
    
    if (is_dir($langDir)) {
        $files = scandir($langDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $languages[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
    }
    
    return $languages;
}

function getLanguageNames() {
    return [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'ar' => 'العربية'
    ];
}

function setLanguage($lang) {
    if (isLanguageSupported($lang)) {
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (86400 * 30), '/'); // 30 days
        return true;
    }
    return false;
}

function loadLanguage($langCode = null) {
    global $lang;
    
    if ($langCode === null) {
        $langCode = detectLanguage();
    }
    
    $langFile = __DIR__ . "/../lang/{$langCode}.php";
    
    if (file_exists($langFile)) {
        include $langFile;
        $GLOBALS['lang'] = $lang;
        return $langCode;
    }
    
    // Fallback to English
    include __DIR__ . '/../lang/en.php';
    $GLOBALS['lang'] = $lang;
    return $langCode;
}

function t($key, $params = []) {
    global $lang;
    
    if (!isset($lang) || !is_array($lang)) {
        loadLanguage();
    }
    
    $text = isset($lang[$key]) ? $lang[$key] : $key;
    
    if (!empty($params) && is_array($params)) {
        $text = vsprintf($text, $params);
    }
    
    return $text;
}

// Theme Management Functions
function detectTheme() {
    // Check if theme is set via URL parameter (manual selection)
    if (isset($_GET['theme'])) {
        $theme = sanitize_input($_GET['theme']);
        if (in_array($theme, ['light', 'dark'])) {
            setTheme($theme);
            return $theme;
        }
    }
    
    // Check if theme is set in session
    if (isset($_SESSION['theme'])) {
        return $_SESSION['theme'];
    }
    
    // Check if theme is set in cookie (user previously chose manually)
    if (isset($_COOKIE['theme'])) {
        $_SESSION['theme'] = $_COOKIE['theme'];
        return $_COOKIE['theme'];
    }
    
    // Default to light theme
    return 'light';
}

function setTheme($theme) {
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['theme'] = $theme;
        setcookie('theme', $theme, time() + (86400 * 30), '/'); // 30 days
        return true;
    }
    return false;
}

function getCurrentTheme() {
    return detectTheme();
}

function getThemeClass() {
    return getCurrentTheme() === 'dark' ? 'dark' : '';
}

function getLogo($theme = null) {
    if ($theme === null) {
        $theme = getCurrentTheme();
    }
    
    $logoKey = $theme === 'dark' ? 'site_logo_dark' : 'site_logo_light';
    $defaultLogo = 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg?auto=compress&cs=tinysrgb&w=200';
    
    return getSetting($logoKey, $defaultLogo);
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getTrendingTerms($limit = 5) {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("SELECT query, count FROM trending ORDER BY count DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $terms = [];
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $terms[$row['query']] = $row['count'];
        }
        return $terms;
    } catch(Exception $e) {
        error_log("Error getting trending terms: " . $e->getMessage());
        return [];
    }
}

function updateTrendingTerms($query) {
    global $dbInstance;
    try {
        $query = sanitize_input($query);
        
        // Check for existing term
        $stmt = $dbInstance->conn->prepare("SELECT count FROM trending WHERE query = :query");
        $stmt->bindValue(':query', $query, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Update count
            $newCount = $row['count'] + 1;
            $update = $dbInstance->conn->prepare("UPDATE trending SET count = :count WHERE query = :query");
            $update->bindValue(':count', $newCount, SQLITE3_INTEGER);
            $update->bindValue(':query', $query, SQLITE3_TEXT);
            $update->execute();
        } else {
            // Insert new trending entry
            $insert = $dbInstance->conn->prepare("INSERT INTO trending (query, count) VALUES (:query, 1)");
            $insert->bindValue(':query', $query, SQLITE3_TEXT);
            $insert->execute();
        }
    } catch(Exception $e) {
        error_log("Error updating trending terms: " . $e->getMessage());
    }
}

function getCachedResult($query) {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("SELECT results, created_at FROM searches WHERE query = :query ORDER BY created_at DESC LIMIT 1");
        $stmt->bindValue(':query', $query, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cacheTime = strtotime($row['created_at']);
            $cacheDuration = (int)getSetting('cache_duration', 86400);
            
            // Check if cache is still valid
            if(time() - $cacheTime < $cacheDuration) {
                return $row['results'];
            }
        }
        return false;
    } catch(Exception $e) {
        error_log("Error getting cached result: " . $e->getMessage());
        return false;
    }
}

function cacheSearchResult($query, $resultsJson, $aiSummary = null) {
    global $dbInstance;
    try {
        // Store both results and AI summary in JSON
        $data = [
            'results' => json_decode($resultsJson, true),
            'ai_summary' => $aiSummary
        ];
        $jsonData = json_encode($data);
        
        $stmt = $dbInstance->conn->prepare("INSERT INTO searches (query, results) VALUES (:query, :results)");
        $stmt->bindValue(':query', $query, SQLITE3_TEXT);
        $stmt->bindValue(':results', $jsonData, SQLITE3_TEXT);
        $stmt->execute();
    } catch(Exception $e) {
        error_log("Error caching search result: " . $e->getMessage());
    }
}

function getSetting($key, $default = '') {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("SELECT value FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['value'] : $default;
    } catch(Exception $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return $default;
    }
}

function setSetting($key, $value) {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        $stmt->execute();
        return true;
    } catch(Exception $e) {
        error_log("Error setting value: " . $e->getMessage());
        return false;
    }
}

function verifyTurnstile($token) {
    $secretKey = getSetting('turnstile_secret_key');
    if(empty($secretKey) || empty($token)) {
        return false;
    }
    
    $data = [
        'secret' => $secretKey,
        'response' => $token
    ];
    
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($httpCode === 200) {
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
    
    return false;
}

function generateSitemap() {
    global $dbInstance;
    
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Add homepage
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$baseUrl}/</loc>\n";
    $xml .= "    <changefreq>daily</changefreq>\n";
    $xml .= "    <priority>1.0</priority>\n";
    $xml .= "  </url>\n";
    
    // Add search pages for trending terms
    try {
        $stmt = $dbInstance->conn->prepare("SELECT query FROM trending ORDER BY count DESC LIMIT 50");
        $result = $stmt->execute();
        
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $encodedQuery = urlencode($row['query']);
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/search/{$encodedQuery}</loc>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        
        // Add static pages
        $stmt = $dbInstance->conn->prepare("SELECT slug FROM static_pages");
        $result = $stmt->execute();
        
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/{$row['slug']}</loc>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
        }
        
    } catch(Exception $e) {
        error_log("Error generating sitemap: " . $e->getMessage());
    }
    
    $xml .= '</urlset>';
    
    // Save sitemap
    file_put_contents(__DIR__ . '/../sitemap.xml', $xml);
    
    return $xml;
}

function getStaticPage($slug) {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("SELECT * FROM static_pages WHERE slug = :slug");
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    } catch(Exception $e) {
        error_log("Error getting static page: " . $e->getMessage());
        return false;
    }
}

function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if(!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}
?>
