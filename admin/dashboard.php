<?php
require_once 'config.php';
requireAdminLogin();

$section = $_GET['section'] ?? 'dashboard';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Token de seguridad inválido.';
    } else {
        switch ($action) {
            case 'update_settings':
                handleUpdateSettings();
                break;
            case 'delete_search':
                handleDeleteSearch();
                break;
            case 'clear_all_searches':
                handleClearAllSearches();
                break;
            case 'save_page':
                handleSavePage();
                break;
            case 'delete_page':
                handleDeletePage();
                break;
            case 'update_trending':
                handleUpdateTrending();
                break;
            case 'delete_trending':
                handleDeleteTrending();
                break;
            case 'save_advertisement':
                handleSaveAdvertisement();
                break;
            case 'delete_advertisement':
                handleDeleteAdvertisement();
                break;
        }
    }
}


function handleUpdateSettings() {
    global $message, $error;
    
    $settings = [
        'site_title' => $_POST['site_title'] ?? '',
        'site_description' => $_POST['site_description'] ?? '',
        'openrouter_api_key' => $_POST['openrouter_api_key'] ?? '',
        'openrouter_model' => $_POST['openrouter_model'] ?? '',
        'turnstile_site_key' => $_POST['turnstile_site_key'] ?? '',
        'turnstile_secret_key' => $_POST['turnstile_secret_key'] ?? '',
        'show_images' => isset($_POST['show_images']) ? '1' : '0',
        'trending_count' => (int)($_POST['trending_count'] ?? 5),
        'cache_duration' => (int)($_POST['cache_duration'] ?? 86400),
        'site_logo' => $_POST['site_logo'] ?? '',
        'custom_js' => $_POST['custom_js'] ?? '',
        'meta_keywords' => $_POST['meta_keywords'] ?? ''
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!setSetting($key, $value)) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        $message = 'Configuración actualizada correctamente.';
    } else {
        $error = 'Error al actualizar la configuración.';
    }
}

function handleDeleteSearch() {
    global $message, $error, $dbInstance;
    
    $searchId = (int)($_POST['search_id'] ?? 0);
    
    try {
        $stmt = $dbInstance->conn->prepare("DELETE FROM searches WHERE id = ?");
        $stmt->bindValue(1, $searchId, SQLITE3_INTEGER);
        $stmt->execute();
        $message = 'Búsqueda eliminada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al eliminar la búsqueda.';
    }
}

function handleClearAllSearches() {
    global $message, $error, $dbInstance;
    
    try {
        $dbInstance->conn->exec("DELETE FROM searches");
        $dbInstance->conn->exec("DELETE FROM trending");
        $message = 'Todas las búsquedas han sido eliminadas.';
    } catch (Exception $e) {
        $error = 'Error al eliminar las búsquedas.';
    }
}

function handleSavePage() {
    global $message, $error, $dbInstance;
    
    $pageId = (int)($_POST['page_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if (empty($slug) || empty($title)) {
        $error = 'El slug y título son obligatorios.';
        return;
    }
    
    try {
        if ($pageId > 0) {
            // Update existing page
            $stmt = $dbInstance->conn->prepare("UPDATE static_pages SET slug = ?, title = ?, content = ? WHERE id = ?");
            $stmt->bindValue(1, $slug, SQLITE3_TEXT);
            $stmt->bindValue(2, $title, SQLITE3_TEXT);
            $stmt->bindValue(3, $content, SQLITE3_TEXT);
            $stmt->bindValue(4, $pageId, SQLITE3_INTEGER);
        } else {
            // Create new page
            $stmt = $dbInstance->conn->prepare("INSERT INTO static_pages (slug, title, content) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $slug, SQLITE3_TEXT);
            $stmt->bindValue(2, $title, SQLITE3_TEXT);
            $stmt->bindValue(3, $content, SQLITE3_TEXT);
        }
        
        $stmt->execute();
        $message = 'Página guardada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al guardar la página: ' . $e->getMessage();
    }
}

function handleDeletePage() {
    global $message, $error, $dbInstance;
    
    $pageId = (int)($_POST['page_id'] ?? 0);
    
    try {
        $stmt = $dbInstance->conn->prepare("DELETE FROM static_pages WHERE id = ?");
        $stmt->bindValue(1, $pageId, SQLITE3_INTEGER);
        $stmt->execute();
        $message = 'Página eliminada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al eliminar la página.';
    }
}

function handleUpdateTrending() {
    global $message, $error, $dbInstance;
    
    $query = trim($_POST['trending_query'] ?? '');
    $count = (int)($_POST['trending_count_value'] ?? 1);
    
    if (empty($query)) {
        $error = 'La consulta es obligatoria.';
        return;
    }
    
    try {
        $stmt = $dbInstance->conn->prepare("INSERT OR REPLACE INTO trending (query, count) VALUES (?, ?)");
        $stmt->bindValue(1, $query, SQLITE3_TEXT);
        $stmt->bindValue(2, $count, SQLITE3_INTEGER);
        $stmt->execute();
        $message = 'Tendencia actualizada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al actualizar la tendencia.';
    }
}

function handleDeleteTrending() {
    global $message, $error, $dbInstance;
    
    $query = $_POST['trending_query'] ?? '';
    
    try {
        $stmt = $dbInstance->conn->prepare("DELETE FROM trending WHERE query = ?");
        $stmt->bindValue(1, $query, SQLITE3_TEXT);
        $stmt->execute();
        $message = 'Tendencia eliminada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al eliminar la tendencia.';
    }
}

// Get statistics
try {
    $totalSearches = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM searches");
    $totalTrending = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM trending");
    $totalPages = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM static_pages");
    $recentSearches = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM searches WHERE created_at >= datetime('now', '-24 hours')");
} catch (Exception $e) {
    $totalSearches = $totalTrending = $totalPages = $recentSearches = 0;
}

$csrfToken = generateCSRFToken();

renderAdminHeader('Dashboard');
renderAdminNavigation($section);
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto">
    <!-- Messages -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-green-800"><?= htmlspecialchars($message) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($section === 'dashboard'): ?>
    <!-- Dashboard Overview -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard</h1>
        <p class="text-gray-600">Resumen general del motor de búsqueda</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Búsquedas</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($totalSearches) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Tendencias</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($totalTrending) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Páginas</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($totalPages) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Hoy</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($recentSearches) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Searches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Búsquedas Recientes</h3>
            <?php
            try {
                $stmt = $dbInstance->conn->prepare("SELECT query, created_at FROM searches ORDER BY created_at DESC LIMIT 5");
                $result = $stmt->execute();
                $hasResults = false;
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $hasResults = true;
                    echo '<div class="flex justify-between items-center py-2 border-b border-gray-100">';
                    echo '<span class="text-gray-900">' . htmlspecialchars($row['query']) . '</span>';
                    echo '<span class="text-sm text-gray-500">' . timeAgo($row['created_at']) . '</span>';
                    echo '</div>';
                }
                if (!$hasResults) {
                    echo '<p class="text-gray-500 text-center py-4">No hay búsquedas recientes</p>';
                }
            } catch (Exception $e) {
                echo '<p class="text-red-500 text-center py-4">Error al cargar búsquedas</p>';
            }
            ?>
        </div>

        <!-- Top Trending -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tendencias Populares</h3>
            <?php
            $trending = getTrendingTerms(5);
            if (!empty($trending)) {
                foreach ($trending as $term => $count) {
                    echo '<div class="flex justify-between items-center py-2 border-b border-gray-100">';
                    echo '<span class="text-gray-900">' . htmlspecialchars($term) . '</span>';
                    echo '<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">' . number_format($count) . '</span>';
                    echo '</div>';
                }
            } else {
                echo '<p class="text-gray-500 text-center py-4">No hay tendencias disponibles</p>';
            }
            ?>
        </div>
    </div>

    <?php elseif ($section === 'settings'): ?>
    <!-- Settings Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Configuración</h1>
        <p class="text-gray-600">Administra la configuración del motor de búsqueda</p>
    </div>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="update_settings">

        <!-- General Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título del Sitio</label>
                    <input type="text" name="site_title" value="<?= htmlspecialchars(getSetting('site_title')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo del Sitio (URL)</label>
                    <input type="url" name="site_logo" value="<?= htmlspecialchars(getSetting('site_logo')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del Sitio</label>
                    <textarea name="site_description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars(getSetting('site_description')) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                    <input type="text" name="meta_keywords" value="<?= htmlspecialchars(getSetting('meta_keywords')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="palabra1, palabra2, palabra3">
                </div>
            </div>
        </div>

        <!-- API Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración de API</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">OpenRouter API Key</label>
                    <input type="password" name="openrouter_api_key" value="<?= htmlspecialchars(getSetting('openrouter_api_key')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="sk-or-...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modelo de IA</label>
                    <select name="openrouter_model" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="openai/gpt-3.5-turbo" <?= getSetting('openrouter_model') === 'openai/gpt-3.5-turbo' ? 'selected' : '' ?>>GPT-3.5 Turbo</option>
                        <option value="openai/gpt-4" <?= getSetting('openrouter_model') === 'openai/gpt-4' ? 'selected' : '' ?>>GPT-4</option>
                        <option value="anthropic/claude-3-haiku" <?= getSetting('openrouter_model') === 'anthropic/claude-3-haiku' ? 'selected' : '' ?>>Claude 3 Haiku</option>
                        <option value="meta-llama/llama-3.1-8b-instruct" <?= getSetting('openrouter_model') === 'meta-llama/llama-3.1-8b-instruct' ? 'selected' : '' ?>>Llama 3.1 8B</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración de Caché (segundos)</label>
                    <input type="number" name="cache_duration" value="<?= htmlspecialchars(getSetting('cache_duration')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           min="300" max="604800">
                </div>
            </div>
        </div>

        <!-- Cloudflare Turnstile -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Cloudflare Turnstile</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
                    <input type="text" name="turnstile_site_key" value="<?= htmlspecialchars(getSetting('turnstile_site_key')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                    <input type="password" name="turnstile_secret_key" value="<?= htmlspecialchars(getSetting('turnstile_secret_key')) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Visualización</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="show_images" id="show_images" 
                           <?= getSetting('show_images') === '1' ? 'checked' : '' ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="show_images" class="ml-2 block text-sm text-gray-900">
                        Mostrar imágenes en los resultados
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de tendencias a mostrar</label>
                    <input type="number" name="trending_count" value="<?= htmlspecialchars(getSetting('trending_count')) ?>" 
                           class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           min="1" max="20">
                </div>
            </div>
        </div>

        <!-- Custom JavaScript -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">JavaScript Personalizado</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código JavaScript (Google Analytics, etc.)</label>
                <textarea name="custom_js" rows="6" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                          placeholder="<!-- Google Analytics o cualquier otro código JavaScript -->"><?= htmlspecialchars(getSetting('custom_js')) ?></textarea>
            </div>
        </div>

        <!-- Advertisement Management -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestión de Anuncios</h3>
            <!-- Add/Edit Advertisement Form -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-md font-semibold text-gray-900 mb-3">Agregar/Editar Anuncio</h4>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_advertisement">
                    <input type="hidden" name="ad_id" value="<?= isset($_GET['edit_ad']) ? (int)$_GET['edit_ad'] : 0 ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                        <input type="text" name="ad_title" value="<?= htmlspecialchars($_POST['ad_title'] ?? '') ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido (HTML permitido)</label>
                        <textarea name="ad_content" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['ad_content'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Posición</label>
                            <select name="ad_position" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="top" <?= (($_POST['ad_position'] ?? '') === 'top') ? 'selected' : '' ?>>Arriba</option>
                                <option value="middle" <?= (($_POST['ad_position'] ?? '') === 'middle') ? 'selected' : '' ?>>Medio</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL de clic (opcional)</label>
                            <input type="url" name="ad_click_url" value="<?= htmlspecialchars($_POST['ad_click_url'] ?? '') ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="ad_active" value="1" <?= isset($_POST['ad_active']) ? 'checked' : '' ?> class="form-checkbox">
                                <span class="ml-2 text-sm text-gray-700">Activo</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        Guardar Anuncio
                    </button>
                </form>
            </div>

            <!-- Advertisement List -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posición</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $dbInstance->conn->prepare("SELECT * FROM advertisements ORDER BY created_at DESC");
                            $result = $stmt->execute();
                            $hasResults = false;
                            
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $hasResults = true;
                                echo '<tr>';
                                echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['title']) . '</td>';
                                echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['position']) . '</td>';
                                echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">' . ($row['is_active'] ? 'Sí' : 'No') . '</td>';
                                echo '<td class="px-4 py-4 whitespace-nowrap text-sm font-medium space-x-2">';
                                echo '<form method="POST" class="inline" onsubmit="return confirmDelete()">';
                                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
                                echo '<input type="hidden" name="action" value="delete_advertisement">';
                                echo '<input type="hidden" name="ad_id" value="' . $row['id'] . '">';
                                echo '<button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            if (!$hasResults) {
                                echo '<tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No hay anuncios disponibles</td></tr>';
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="4" class="px-4 py-4 text-center text-red-500">Error al cargar anuncios</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                Guardar Configuración
            </button>
        </div>
    </form>

    <?php elseif ($section === 'searches'): ?>
    <!-- Searches Management -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Búsquedas</h1>
        <p class="text-gray-600">Administra las búsquedas realizadas y su caché</p>
    </div>

    <!-- Search Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <form method="POST" class="inline" onsubmit="return confirmDelete('¿Estás seguro de que quieres eliminar TODAS las búsquedas?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="clear_all_searches">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Eliminar Todas las Búsquedas
                </button>
            </form>
            <a href="/sitemap.php?generate=1" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors inline-block text-center">
                Regenerar Sitemap
            </a>
        </div>
    </div>

    <!-- Searches List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Búsquedas Recientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consulta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    try {
                        $stmt = $dbInstance->conn->prepare("SELECT id, query, created_at FROM searches ORDER BY created_at DESC LIMIT 50");
                        $result = $stmt->execute();
                        $hasResults = false;
                        
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $hasResults = true;
                            echo '<tr>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['query']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
                            echo '<form method="POST" class="inline" onsubmit="return confirmDelete()">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
                            echo '<input type="hidden" name="action" value="delete_search">';
                            echo '<input type="hidden" name="search_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        if (!$hasResults) {
                            echo '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay búsquedas disponibles</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="3" class="px-6 py-4 text-center text-red-500">Error al cargar búsquedas</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($section === 'pages'): ?>
    <!-- Pages Management -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Páginas</h1>
        <p class="text-gray-600">Administra las páginas estáticas del sitio</p>
    </div>

    <!-- Page Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <?= isset($_GET['edit']) ? 'Editar Página' : 'Nueva Página' ?>
        </h3>
        
        <?php
        $editPage = null;
        if (isset($_GET['edit'])) {
            try {
                $stmt = $dbInstance->conn->prepare("SELECT * FROM static_pages WHERE id = ?");
                $stmt->bindValue(1, (int)$_GET['edit'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                $editPage = $result->fetchArray(SQLITE3_ASSOC);
            } catch (Exception $e) {
                // Handle error
            }
        }
        ?>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="save_page">
            <?php if ($editPage): ?>
            <input type="hidden" name="page_id" value="<?= $editPage['id'] ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug (URL)</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="politica-privacidad" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($editPage['title'] ?? '') ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Política de Privacidad" required>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenido</label>
                <textarea name="content" rows="10" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Contenido de la página en HTML..."><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    <?= $editPage ? 'Actualizar' : 'Crear' ?> Página
                </button>
                <?php if ($editPage): ?>
                <a href="?section=pages" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    Cancelar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Pages List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Páginas Existentes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    try {
                        $stmt = $dbInstance->conn->prepare("SELECT * FROM static_pages ORDER BY created_at DESC");
                        $result = $stmt->execute();
                        $hasResults = false;
                        
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $hasResults = true;
                            echo '<tr>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['title']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">/' . htmlspecialchars($row['slug']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('d/m/Y', strtotime($row['created_at'])) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">';
                            echo '<a href="?section=pages&edit=' . $row['id'] . '" class="text-blue-600 hover:text-blue-900">Editar</a>';
                            echo '<form method="POST" class="inline ml-2" onsubmit="return confirmDelete()">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
                            echo '<input type="hidden" name="action" value="delete_page">';
                            echo '<input type="hidden" name="page_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        if (!$hasResults) {
                            echo '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay páginas creadas</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error al cargar páginas</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($section === 'trending'): ?>
    <!-- Trending Management -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestión de Tendencias</h1>
        <p class="text-gray-600">Administra las búsquedas populares y tendencias</p>
    </div>

    <!-- Add/Edit Trending -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Agregar/Editar Tendencia</h3>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update_trending">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consulta</label>
                    <input type="text" name="trending_query" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Término de búsqueda" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de búsquedas</label>
                    <input type="number" name="trending_count_value" value="1" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                Agregar/Actualizar Tendencia
            </button>
        </form>
    </div>

    <!-- Trending List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Tendencias Actuales</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consulta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Búsquedas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $trending = getTrendingTerms(50);
                    if (!empty($trending)) {
                        foreach ($trending as $term => $count) {
                            echo '<tr>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($term) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . number_format($count) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
                            echo '<form method="POST" class="inline" onsubmit="return confirmDelete()">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
                            echo '<input type="hidden" name="action" value="delete_trending">';
                            echo '<input type="hidden" name="trending_query" value="' . htmlspecialchars($term) . '">';
                            echo '<button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay tendencias disponibles</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>
