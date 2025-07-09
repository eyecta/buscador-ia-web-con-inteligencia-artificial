<?php
require_once 'includes/header.php';
require_once 'includes/ai_search.php';

// Get and validate search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
if(empty($query)) {
    header('Location: /');
    exit;
}

$query = sanitize_input($query);
$showImages = getSetting('show_images', '1') === '1';
$showAISummary = getSetting('show_ai_summary', '1') === '1';
$turnstileSecretKey = getSetting('turnstile_secret_key');

// Verify Turnstile if configured and token provided
if(!empty($turnstileSecretKey) && isset($_POST['cf-turnstile-response'])) {
    if(!verifyTurnstile($_POST['cf-turnstile-response'])) {
        $error = t('security_verification_failed');
    }
}

$results = null;
$aiSummary = null;
$searchTime = 0;
$error = null;

if(!isset($error)) {
    $startTime = microtime(true);
    
    // Check cache first
    $cached = getCachedResult($query);
    if($cached) {
        $cachedData = json_decode($cached, true);
        if (isset($cachedData['results']) && is_array($cachedData['results'])) {
            $results = $cachedData['results'];
        } else {
            $results = $cachedData;
        }
        $searchTime = microtime(true) - $startTime;
    } else {
        // Perform AI search
        $results = performAISearch($query);
        $searchTime = microtime(true) - $startTime;
        
        if($results === false) {
            $error = t('error_search_desc');
        } else {
            // Cache the results
            cacheSearchResult($query, json_encode($results));
        }
    }
    
    // Generate AI summary if enabled and results found
    if($showAISummary && $results !== false && !empty($results['items'])) {
        $aiSummary = generateAISummary($query);
    }
    
    // Update trending terms
    if($results !== false) {
        updateTrendingTerms($query);
    }
}

$siteLogo = getSetting('site_logo', 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg?auto=compress&cs=tinysrgb&w=200');
$siteTitle = getSetting('site_title', t('site_title'));

// Get advertisements
$topAds = getAdvertisements('top');
$middleAds = getAdvertisements('middle');
?>

<main id="main-content" class="flex-1">
    <!-- Header with Search -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo and Search -->
                <div class="flex items-center flex-1">
                    <a href="/" class="flex items-center mr-8">
                    <img src="<?= htmlspecialchars($siteLogo) ?>" 
                         alt="<?= htmlspecialchars($siteTitle) ?>" 
                         class="w-8 h-8 object-cover">
                        <span class="ml-2 text-xl font-semibold text-gray-900 hidden sm:block">
                            <?= htmlspecialchars($siteTitle) ?>
                        </span>
                    </a>
                    
                    <!-- Search Form -->
                    <form action="search.php" method="GET" class="flex-1 max-w-2xl">
                        <div class="relative">
                            <input 
                                type="text" 
                                name="query" 
                                value="<?= htmlspecialchars($query) ?>"
                                placeholder="<?= t('search_placeholder') ?>" 
                                class="search-input w-full py-2 px-4 pr-10 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                autocomplete="off">
                            <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Admin Link -->
                <div class="ml-4">
                    <a href="/admin/" class="text-gray-500 hover:text-gray-700 text-sm">
                        <?= t('admin') ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Search Results -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Search Info -->
        <div class="mb-6">
            <h1 class="text-2xl font-normal text-gray-900 mb-2">
                <?= t('results_for') ?> <span class="font-semibold"><?= htmlspecialchars($query) ?></span>
            </h1>
            <?php if($results && isset($results['total_results'])): ?>
            <p class="text-sm text-gray-600">
                <?= sprintf(t('about_results'), number_format($results['total_results']), number_format($searchTime, 2)) ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Top Advertisements -->
        <?php if(!empty($topAds)): ?>
        <div class="mb-6">
            <?php foreach(array_slice($topAds, 0, 1) as $ad): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-yellow-600 font-medium uppercase"><?= t('advertisement') ?></span>
                    <span class="text-xs text-yellow-500"><?= t('sponsored') ?></span>
                </div>
                <div class="text-gray-800">
                    <?php if(!empty($ad['click_url'])): ?>
                    <a href="<?= htmlspecialchars($ad['click_url']) ?>" target="_blank" rel="noopener noreferrer" class="block hover:text-blue-600">
                        <h3 class="font-semibold mb-1"><?= htmlspecialchars($ad['title']) ?></h3>
                        <div class="text-sm"><?= $ad['content'] ?></div>
                    </a>
                    <?php else: ?>
                    <h3 class="font-semibold mb-1"><?= htmlspecialchars($ad['title']) ?></h3>
                    <div class="text-sm"><?= $ad['content'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if($error): ?>
        <!-- Error Message -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-red-800"><?= t('error_search') ?></h3>
                    <p class="text-red-700 mt-1"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        </div>
        
        <?php elseif($results && isset($results['items']) && !empty($results['items'])): ?>
        
        <!-- AI Summary -->
        <?php if($aiSummary): ?>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-blue-900"><?= t('ai_summary') ?></h2>
            </div>
            <div class="text-blue-800 leading-relaxed">
                <?= nl2br(htmlspecialchars($aiSummary)) ?>
            </div>
            <div class="mt-3 text-xs text-blue-600">
                <?= t('ai_summary_desc') ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Search Results -->
        <div class="space-y-6">
            <?php foreach($results['items'] as $index => $result): ?>
            
            <!-- Middle Advertisement (after first result) -->
            <?php if($index === 1 && !empty($middleAds)): ?>
            <div class="my-6">
                <?php foreach(array_slice($middleAds, 0, 1) as $ad): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-green-600 font-medium uppercase"><?= t('advertisement') ?></span>
                        <span class="text-xs text-green-500"><?= t('sponsored') ?></span>
                    </div>
                    <div class="text-gray-800">
                        <?php if(!empty($ad['click_url'])): ?>
                        <a href="<?= htmlspecialchars($ad['click_url']) ?>" target="_blank" rel="noopener noreferrer" class="block hover:text-blue-600">
                            <h3 class="font-semibold mb-1"><?= htmlspecialchars($ad['title']) ?></h3>
                            <div class="text-sm"><?= $ad['content'] ?></div>
                        </a>
                        <?php else: ?>
                        <h3 class="font-semibold mb-1"><?= htmlspecialchars($ad['title']) ?></h3>
                        <div class="text-sm"><?= $ad['content'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <article class="result-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <!-- Result Title and URL -->
                <div class="mb-3">
                    <h2 class="text-xl font-medium mb-1">
                        <a href="<?= htmlspecialchars($result['url']) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="text-blue-600 hover:text-blue-800 hover:underline">
                            <?= htmlspecialchars($result['title']) ?>
                        </a>
                    </h2>
                    <div class="text-sm text-green-700 break-all">
                        <?= htmlspecialchars($result['url']) ?>
                    </div>
                </div>
                
                <!-- Result Description -->
                <p class="text-gray-700 mb-4 leading-relaxed">
                    <?= htmlspecialchars($result['description']) ?>
                </p>
                
                <!-- References Section -->
                <?php if(!empty($result['references'])): ?>
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        <?= t('references') ?>
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach(array_slice($result['references'], 0, 3) as $ref): ?>
                        <a href="<?= htmlspecialchars($ref) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="inline-flex items-center text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded-full transition-colors">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <?= htmlspecialchars(parse_url($ref, PHP_URL_HOST) ?: $ref) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Images Section -->
                <?php if($showImages && !empty($result['images'])): ?>
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <?= t('related_images') ?>
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        <?php foreach(array_slice($result['images'], 0, 4) as $img): ?>
                        <a href="<?= htmlspecialchars($img) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="group block">
                            <img src="<?= htmlspecialchars($img) ?>" 
                                 alt="<?= htmlspecialchars($result['title']) ?>" 
                                 class="w-full h-24 object-cover rounded-lg border border-gray-200 group-hover:shadow-md transition-shadow"
                                 loading="lazy"
                                 onerror="this.parentElement.style.display='none'">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex space-x-4">
                        <button onclick="copyToClipboard('<?= htmlspecialchars($result['url']) ?>')" 
                                class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <?= t('copy_link') ?>
                        </button>
                        <button onclick="window.open('<?= htmlspecialchars($result['url']) ?>', '_blank')" 
                                class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <?= t('open') ?>
                        </button>
                    </div>
                    <div class="text-xs text-gray-400">
                        <?= sprintf(t('result_number'), $index + 1) ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination placeholder -->
        <div class="mt-12 text-center">
            <p class="text-gray-500 text-sm">
                <?= t('showing_best_results') ?>
            </p>
        </div>
        
        <?php else: ?>
        <!-- No Results -->
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                <?= t('no_results') ?>
            </h2>
            <p class="text-gray-600 mb-6">
                <?= t('no_results_desc') ?>
            </p>
            <div class="space-y-2 text-sm text-gray-500">
                <?php foreach(t('search_suggestions') as $suggestion): ?>
                <p>• <?= htmlspecialchars($suggestion) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Related Searches -->
        <?php
        $relatedSearches = getTrendingTerms(5);
        if(!empty($relatedSearches)):
        ?>
        <div class="mt-12 bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <?= t('related_searches') ?>
            </h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach($relatedSearches as $term => $count): ?>
                    <?php if($term !== $query): ?>
                    <a href="search.php?query=<?= urlencode($term) ?>" 
                       class="inline-block bg-white hover:bg-gray-100 text-gray-700 px-3 py-2 rounded-full border border-gray-200 text-sm transition-colors">
                        <?= htmlspecialchars($term) ?>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
