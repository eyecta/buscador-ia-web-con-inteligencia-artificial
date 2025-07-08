<?php
require_once 'includes/header.php';

// Get trending searches
$trendingCount = (int)getSetting('trending_count', 5);
$trending = getTrendingTerms($trendingCount);
$siteLogo = getSetting('site_logo', 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg?auto=compress&cs=tinysrgb&w=200');
$siteTitle = getSetting('site_title', t('site_title'));
$turnstileSiteKey = getSetting('turnstile_site_key');
?>

<main id="main-content" class="flex-1">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <img src="<?= htmlspecialchars($siteLogo) ?>" 
                 alt="<?= htmlspecialchars($siteTitle) ?>" 
                 class="w-24 h-24 mx-auto mb-4 rounded-full object-cover shadow-lg">
            <h1 class="text-4xl md:text-5xl font-light text-gray-900 mb-2">
                <?= htmlspecialchars($siteTitle) ?>
            </h1>
            <p class="text-gray-600 text-lg">
                <?= htmlspecialchars(getSetting('site_description', t('site_description'))) ?>
            </p>
        </div>

        <!-- Search Form -->
        <div class="w-full max-w-2xl mb-8">
            <form action="search.php" method="GET" class="relative" role="search">
                <div class="relative">
                    <!-- Search Input -->
                    <div class="relative">
                        <input 
                            type="text" 
                            name="query" 
                            placeholder="<?= t('search_placeholder') ?>" 
                            class="search-input w-full py-4 px-6 pr-12 text-lg border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            required
                            aria-label="<?= t('search_field') ?>">
                        
                        <!-- Search Icon -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Suggestions will be inserted here by JavaScript -->
                </div>

                <!-- Cloudflare Turnstile Captcha -->
                <?php if(!empty($turnstileSiteKey)): ?>
                <div class="flex justify-center mt-6">
                    <div class="cf-turnstile" 
                         data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>"
                         data-theme="light"
                         data-size="normal">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Search Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <?= t('search_button') ?>
                    </button>
                    <button 
                        type="button" 
                        onclick="document.querySelector('input[name=query]').value = ''; document.querySelector('input[name=query]').focus();"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-8 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        <?= t('clear_button') ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Trending Searches -->
        <?php if(!empty($trending)): ?>
        <div class="w-full max-w-4xl">
            <div class="text-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <?= t('trending_title') ?>
                </h2>
                <div class="flex flex-wrap justify-center gap-3">
                    <?php foreach($trending as $term => $count): ?>
                    <a href="search.php?query=<?= urlencode($term) ?>" 
                       class="inline-flex items-center bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-full border border-gray-200 shadow-sm transition-all duration-200 hover:shadow-md group">
                        <span class="text-sm font-medium"><?= htmlspecialchars($term) ?></span>
                        <span class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full group-hover:bg-gray-200 transition-colors">
                            <?= number_format($count) ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="mt-12 text-center">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-md mx-auto">
                <?php
                try {
                    // Get total searches
                    $totalSearches = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM searches");
                    $totalTrending = $dbInstance->conn->querySingle("SELECT COUNT(*) FROM trending");
                    $avgResponseTime = "0.3";
                } catch(Exception $e) {
                    $totalSearches = 0;
                    $totalTrending = 0;
                    $avgResponseTime = "0.3";
                }
                ?>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= number_format($totalSearches) ?></div>
                    <div class="text-sm text-gray-600"><?= t('searches_performed') ?></div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?= $avgResponseTime ?>s</div>
                    <div class="text-sm text-gray-600"><?= t('average_time') ?></div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600"><?= number_format($totalTrending) ?></div>
                    <div class="text-sm text-gray-600"><?= t('unique_terms') ?></div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="mt-16 w-full max-w-6xl">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    <?= t('why_choose_title') ?>
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    <?= t('why_choose_desc') ?>
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= t('feature_fast_title') ?></h3>
                    <p class="text-gray-600 text-sm"><?= t('feature_fast_desc') ?></p>
                </div>
                
                <!-- Feature 2 -->
                <div class="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= t('feature_ai_title') ?></h3>
                    <p class="text-gray-600 text-sm"><?= t('feature_ai_desc') ?></p>
                </div>
                
                <!-- Feature 3 -->
                <div class="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= t('feature_secure_title') ?></h3>
                    <p class="text-gray-600 text-sm"><?= t('feature_secure_desc') ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
