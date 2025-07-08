<?php
session_start();

if(!isset($dbInstance)) {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/functions.php';
}

// Load language
$currentLang = loadLanguage();

$siteTitle = getSetting('site_title', t('site_title'));
$siteDescription = getSetting('site_description', t('site_description'));
$metaKeywords = getSetting('meta_keywords', 'búsqueda, IA, motor de búsqueda, inteligencia artificial');
$customJs = getSetting('custom_js', '');

// Dynamic meta tags for search pages
$pageTitle = $siteTitle;
$pageDescription = $siteDescription;

if(isset($_GET['query']) && !empty($_GET['query'])) {
    $query = sanitize_input($_GET['query']);
    $pageTitle = "Resultados para: " . $query . " - " . $siteTitle;
    $pageDescription = "Encuentra información sobre " . $query . " con nuestro motor de búsqueda impulsado por IA.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?= htmlspecialchars($siteTitle) ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteTitle) ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔍</text></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .suggestion-item:hover {
            background-color: #f3f4f6;
        }
        
        .result-card {
            transition: all 0.2s ease-in-out;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <?php if(!empty($customJs)): ?>
    <!-- Custom JavaScript from Admin -->
    <?= $customJs ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded">
        Saltar al contenido principal
    </a>
