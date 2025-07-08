<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if this is a request to generate/view the sitemap
$generate = isset($_GET['generate']) && $_GET['generate'] === '1';
$view = isset($_GET['view']) && $_GET['view'] === '1';

if($generate || $view) {
    // Set appropriate headers
    if($view) {
        header('Content-Type: application/xml; charset=utf-8');
    } else {
        header('Content-Type: text/html; charset=utf-8');
    }
    
    // Generate sitemap content
    $sitemapContent = generateSitemap();
    
    if($view) {
        // Output XML directly
        echo $sitemapContent;
        exit;
    } else {
        // Show generation result
        $message = "Sitemap generado exitosamente.";
        $success = true;
    }
} else {
    // Show sitemap management interface
    require_once 'includes/header.php';
}

// Get sitemap info
$sitemapFile = __DIR__ . '/sitemap.xml';
$sitemapExists = file_exists($sitemapFile);
$sitemapSize = $sitemapExists ? filesize($sitemapFile) : 0;
$sitemapDate = $sitemapExists ? date('Y-m-d H:i:s', filemtime($sitemapFile)) : 'Nunca';

// Count URLs that would be in sitemap
try {
    $totalSearches = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM trending");
    $totalPages = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM static_pages");
    $estimatedUrls = 1 + $totalSearches + $totalPages; // 1 for homepage
} catch(Exception $e) {
    $estimatedUrls = 1;
    $totalSearches = 0;
    $totalPages = 0;
}
?>

<?php if(!$view): ?>
<main id="main-content" class="flex-1">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Gestión de Sitemap
            </h1>
            <p class="text-gray-600">
                Administra y genera el sitemap XML para mejorar el SEO del sitio.
            </p>
        </div>

        <!-- Success/Error Messages -->
        <?php if(isset($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?= $success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <div class="flex items-center">
                <svg class="w-5 h-5 <?= $success ? 'text-green-600' : 'text-red-600' ?> mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php if($success): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <?php endif; ?>
                </svg>
                <span class="<?= $success ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($message) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sitemap Status -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Estado del Sitemap</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold <?= $sitemapExists ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $sitemapExists ? '✓' : '✗' ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        <?= $sitemapExists ? 'Existe' : 'No existe' ?>
                    </div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">
                        <?= number_format($estimatedUrls) ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">URLs estimadas</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">
                        <?= $sitemapExists ? number_format($sitemapSize / 1024, 1) . ' KB' : '0 KB' ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Tamaño</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-lg font-bold text-gray-600">
                        <?= $sitemapDate ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Última actualización</div>
                </div>
            </div>

            <!-- URL Breakdown -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Desglose de URLs</h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Página principal</span>
                        <span class="font-medium">1</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Páginas de búsqueda (trending)</span>
                        <span class="font-medium"><?= number_format($totalSearches) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Páginas estáticas</span>
                        <span class="font-medium"><?= number_format($totalPages) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 font-semibold">
                        <span class="text-gray-900">Total</span>
                        <span class="text-blue-600"><?= number_format($estimatedUrls) ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="?generate=1" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Generar Sitemap
                </a>
                
                <?php if($sitemapExists): ?>
                <a href="?view=1" 
                   target="_blank"
                   class="inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Ver Sitemap
                </a>
                
                <a href="/sitemap.xml" 
                   target="_blank"
                   class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEO Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Información SEO</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">¿Qué es un sitemap?</h3>
                    <p class="text-gray-600 text-sm">
                        Un sitemap es un archivo XML que enumera las URLs de tu sitio web junto con metadatos adicionales 
                        sobre cada URL. Esto ayuda a los motores de búsqueda a descubrir e indexar tu contenido de manera más eficiente.
                    </p>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">Beneficios del sitemap</h3>
                    <ul class="text-gray-600 text-sm space-y-1 list-disc list-inside">
                        <li>Mejora la indexación de páginas por parte de los motores de búsqueda</li>
                        <li>Ayuda a descubrir contenido nuevo más rápidamente</li>
                        <li>Proporciona información sobre la frecuencia de actualización</li>
                        <li>Indica la prioridad relativa de las páginas</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">Envío a motores de búsqueda</h3>
                    <p class="text-gray-600 text-sm mb-2">
                        Una vez generado, puedes enviar tu sitemap a:
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="https://search.google.com/search-console" 
                           target="_blank" 
                           class="inline-flex items-center text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                            Google Search Console
                        </a>
                        <a href="https://www.bing.com/webmasters" 
                           target="_blank" 
                           class="inline-flex items-center text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                            Bing Webmaster Tools
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-8 text-center">
            <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">
                ← Volver al inicio
            </a>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
<?php endif; ?>
