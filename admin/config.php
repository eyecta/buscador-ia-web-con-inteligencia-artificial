<?php
// Admin configuration and security
session_start();

// Include main database and functions
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Admin security functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/');
        exit;
    }
}

function validateAdminCredentials($username, $password) {
    $adminUsername = getSetting('admin_username', 'admin');
    $adminPasswordHash = getSetting('admin_password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
    
    return $username === $adminUsername && password_verify($password, $adminPasswordHash);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function adminLogout() {
    session_destroy();
    header('Location: /admin/');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
}

// Admin menu items
function getAdminMenuItems() {
    return [
        'dashboard' => [
            'title' => 'Dashboard',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z',
            'url' => '/admin/dashboard.php'
        ],
        'settings' => [
            'title' => 'Configuración',
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'url' => '/admin/dashboard.php?section=settings'
        ],
        'searches' => [
            'title' => 'Búsquedas',
            'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
            'url' => '/admin/dashboard.php?section=searches'
        ],
        'pages' => [
            'title' => 'Páginas',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'url' => '/admin/dashboard.php?section=pages'
        ],
        'trending' => [
            'title' => 'Tendencias',
            'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'url' => '/admin/dashboard.php?section=trending'
        ],
    ];
}

// Utility functions for admin
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace ' . $time . ' segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    return 'hace ' . floor($time/31536000) . ' años';
}

// Admin header function
function renderAdminHeader($title = 'Admin Panel') {
    $siteTitle = getSetting('site_title', 'Buscador IA');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($siteTitle) ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
            .admin-sidebar { min-height: calc(100vh - 4rem); }
        </style>
    </head>
    <body class="bg-gray-50">
    <?php
}

// Admin navigation function
function renderAdminNavigation($currentSection = 'dashboard') {
    $menuItems = getAdminMenuItems();
    ?>
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/admin/dashboard.php" class="flex items-center">
                        <span class="text-xl font-bold text-gray-900">Admin Panel</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" target="_blank" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    <a href="?logout=1" class="text-red-600 hover:text-red-800 font-medium">
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="flex">
        <!-- Sidebar -->
        <div class="admin-sidebar w-64 bg-white shadow-sm">
            <div class="p-4">
                <nav class="space-y-2">
                    <?php foreach($menuItems as $key => $item): ?>
                    <a href="<?= $item['url'] ?>" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $currentSection === $key ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"></path>
                        </svg>
                        <?= $item['title'] ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
    <?php
}

// Admin footer function
function renderAdminFooter() {
    ?>
        </div>
    </div>
    
    <script>
        // Auto-save functionality for forms
        function autoSave(formId, interval = 30000) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            setInterval(() => {
                const formData = new FormData(form);
                formData.append('auto_save', '1');
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        console.log('Auto-saved successfully');
                    }
                }).catch(error => {
                    console.error('Auto-save failed:', error);
                });
            }, interval);
        }
        
        // Confirm delete actions
        function confirmDelete(message = '¿Estás seguro de que quieres eliminar este elemento?') {
            return confirm(message);
        }
        
        // Copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copiado al portapapeles');
            }).catch(err => {
                console.error('Error copying to clipboard:', err);
            });
        }
    </script>
    </body>
    </html>
    <?php
}
?>
