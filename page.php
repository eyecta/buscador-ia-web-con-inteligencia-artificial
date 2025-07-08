<?php
require_once 'includes/header.php';

// Get the page slug from URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    require_once '404.php';
    exit;
}

// Get the static page from database
$page = getStaticPage($slug);
if (!$page) {
    header('HTTP/1.0 404 Not Found');
    require_once '404.php';
    exit;
}

// Set page-specific meta tags
$pageTitle = htmlspecialchars($page['title']) . ' - ' . getSetting('site_title', 'Buscador IA');
$pageDescription = strip_tags(substr($page['content'], 0, 160)) . '...';
?>

<main id="main-content" class="flex-1">
    <!-- Header with Navigation -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <img src="<?= htmlspecialchars(getSetting('site_logo')) ?>" 
                             alt="<?= htmlspecialchars(getSetting('site_title')) ?>" 
                             class="w-8 h-8 rounded-full object-cover">
                        <span class="ml-2 text-xl font-semibold text-gray-900">
                            <?= htmlspecialchars(getSetting('site_title')) ?>
                        </span>
                    </a>
                </div>
                
                <!-- Search Form -->
                <form action="search.php" method="GET" class="flex-1 max-w-md mx-8">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="query" 
                            placeholder="Buscar..." 
                            class="w-full py-2 px-4 pr-10 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            autocomplete="off">
                        <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
                
                <div>
                    <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">
                        Inicio
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li>
                    <a href="/" class="hover:text-gray-700">Inicio</a>
                </li>
                <li>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </li>
                <li class="text-gray-900 font-medium">
                    <?= htmlspecialchars($page['title']) ?>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <?= htmlspecialchars($page['title']) ?>
            </h1>
            <div class="text-sm text-gray-500">
                Última actualización: <?= date('d/m/Y', strtotime($page['created_at'])) ?>
            </div>
        </div>

        <!-- Page Content -->
        <div class="prose prose-lg max-w-none">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <?= $page['content'] ?>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-12 text-center">
            <a href="/" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver al inicio
            </a>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
