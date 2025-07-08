<?php
require_once 'includes/header.php';
http_response_code(404);
?>

<main id="main-content" class="flex-1">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <!-- 404 Illustration -->
            <div class="mb-8">
                <div class="text-9xl font-bold text-gray-200 mb-4">404</div>
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>

            <!-- Error Message -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    Página no encontrada
                </h1>
                <p class="text-gray-600 mb-6">
                    Lo sentimos, la página que buscas no existe o ha sido movida.
                </p>
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <!-- Search Form -->
                <form action="search.php" method="GET" class="mb-6">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="query" 
                            placeholder="¿Qué estás buscando?" 
                            class="w-full py-3 px-4 pr-12 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            autocomplete="off">
                        <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Navigation Links -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/" 
                       class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-full transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Ir al inicio
                    </a>
                    
                    <button onclick="history.back()" 
                            class="inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-full transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver atrás
                    </button>
                </div>
            </div>

            <!-- Popular Searches -->
            <?php
            $trending = getTrendingTerms(5);
            if (!empty($trending)):
            ?>
            <div class="mt-12">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Búsquedas populares
                </h3>
                <div class="flex flex-wrap justify-center gap-2">
                    <?php foreach($trending as $term => $count): ?>
                    <a href="search.php?query=<?= urlencode($term) ?>" 
                       class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-full text-sm transition-colors">
                        <?= htmlspecialchars($term) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Text -->
            <div class="mt-12 text-sm text-gray-500">
                <p>Si crees que esto es un error, puedes:</p>
                <ul class="mt-2 space-y-1">
                    <li>• Verificar la URL en la barra de direcciones</li>
                    <li>• Usar el buscador para encontrar lo que necesitas</li>
                    <li>• Contactar al administrador del sitio</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
