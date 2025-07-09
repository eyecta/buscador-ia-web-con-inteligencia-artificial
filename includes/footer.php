<!-- Footer -->
<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- About Section -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                    <?= t('about') ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <?= htmlspecialchars(getSetting('site_description', t('site_description'))) ?>
                </p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                    <?= t('links') ?>
                </h3>
                <ul class="space-y-2">
                    <li>
                        <a href="/" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 text-sm transition-colors">
                            <?= t('home') ?>
                        </a>
                    </li>
                    <?php
                    // Get static pages for footer links
                    try {
                        $stmt = $dbInstance->conn->prepare("SELECT slug, title FROM static_pages ORDER BY title");
                        $result = $stmt->execute();
                        while($page = $result->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <li>
                        <a href="/<?= htmlspecialchars($page['slug']) ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 text-sm transition-colors">
                            <?= htmlspecialchars($page['title']) ?>
                        </a>
                    </li>
                    <?php 
                        endwhile;
                    } catch(Exception $e) {
                        // Silently handle error
                    }
                    ?>
                    <li>
                        <a href="/sitemap.xml" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 text-sm transition-colors">
                            <?= t('sitemap') ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Contact/Info -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                    <?= t('information') ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                    <?= t('ai_powered_searches') ?>
                </p>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <?= t('fast_accurate_results') ?>
                </p>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    © <?= date('Y') ?> <?= htmlspecialchars(getSetting('site_title', t('site_title'))) ?>. <?= t('all_rights_reserved') ?>
                </p>
                <div class="mt-4 md:mt-0">
                    <p class="text-gray-500 dark:text-gray-400 text-xs">
                        <?= t('developed_with_love') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Language and Theme Selector - Fixed Bottom Right -->
<div class="fixed bottom-4 right-4 z-50 flex space-x-2">
    <!-- Language Selector -->
    <div class="dropdown">
        <button class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-200 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>
        <div class="dropdown-content absolute bottom-full right-0 mb-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg min-w-[160px]">
            <?php
            $supportedLanguages = getSupportedLanguages();
            $languageNames = getLanguageNames();
            foreach ($supportedLanguages as $langCode):
                $langName = $languageNames[$langCode] ?? ucfirst($langCode);
                $isActive = $currentLang === $langCode;
            ?>
            <a href="?lang=<?= $langCode ?><?= isset($_GET['theme']) ? '&theme=' . $_GET['theme'] : '' ?>" 
               class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= $isActive ? 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium' : '' ?>">
                <span class="mr-2"><?= $isActive ? '✓' : '  ' ?></span>
                <?= htmlspecialchars($langName) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Theme Selector -->
    <div class="dropdown">
        <button class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-200 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            <?php if($currentTheme === 'dark'): ?>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <?php else: ?>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <?php endif; ?>
        </button>
        <div class="dropdown-content absolute bottom-full right-0 mb-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg min-w-[140px]">
            <a href="?theme=light<?= isset($_GET['lang']) ? '&lang=' . $_GET['lang'] : '' ?>" 
               class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= $currentTheme === 'light' ? 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium' : '' ?>">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span><?= $currentTheme === 'light' ? '✓ ' : '' ?>Light</span>
            </a>
            <a href="?theme=dark<?= isset($_GET['lang']) ? '&lang=' . $_GET['lang'] : '' ?>" 
               class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors <?= $currentTheme === 'dark' ? 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-medium' : '' ?>">
                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
                <span><?= $currentTheme === 'dark' ? '✓ ' : '' ?>Dark</span>
            </a>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="query"]');
        const searchForm = document.querySelector('form[action*="search"]');
        let suggestionBox = null;
        let debounceTimer = null;
        
        if (searchInput) {
            // Create suggestion box
            suggestionBox = document.createElement('div');
            suggestionBox.className = 'absolute bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shadow-lg mt-1 rounded-lg w-full z-50 hidden';
            suggestionBox.id = 'suggestions';
            
            // Position suggestion box
            const inputContainer = searchInput.parentNode;
            if (inputContainer.style.position !== 'relative') {
                inputContainer.style.position = 'relative';
            }
            inputContainer.appendChild(suggestionBox);
            
            // Handle input for suggestions
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timer
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
                
                if (query.length < 2) {
                    hideSuggestions();
                    return;
                }
                
                // Debounce the API call
                debounceTimer = setTimeout(() => {
                    fetchSuggestions(query);
                }, 300);
            });
            
            // Handle keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                const suggestions = suggestionBox.querySelectorAll('.suggestion-item');
                const activeSuggestion = suggestionBox.querySelector('.suggestion-item.active');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (activeSuggestion) {
                        activeSuggestion.classList.remove('active');
                        const next = activeSuggestion.nextElementSibling;
                        if (next) {
                            next.classList.add('active');
                        } else {
                            suggestions[0]?.classList.add('active');
                        }
                    } else {
                        suggestions[0]?.classList.add('active');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (activeSuggestion) {
                        activeSuggestion.classList.remove('active');
                        const prev = activeSuggestion.previousElementSibling;
                        if (prev) {
                            prev.classList.add('active');
                        } else {
                            suggestions[suggestions.length - 1]?.classList.add('active');
                        }
                    } else {
                        suggestions[suggestions.length - 1]?.classList.add('active');
                    }
                } else if (e.key === 'Enter') {
                    if (activeSuggestion) {
                        e.preventDefault();
                        searchInput.value = activeSuggestion.textContent;
                        hideSuggestions();
                        searchForm?.submit();
                    }
                } else if (e.key === 'Escape') {
                    hideSuggestions();
                }
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!inputContainer.contains(e.target)) {
                    hideSuggestions();
                }
            });
        }
        
        function fetchSuggestions(query) {
            fetch(`suggestions.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    showSuggestions(data);
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    hideSuggestions();
                });
        }
        
        function showSuggestions(suggestions) {
            if (!suggestions || suggestions.length === 0) {
                hideSuggestions();
                return;
            }
            
            suggestionBox.innerHTML = '';
            
            suggestions.forEach((suggestion, index) => {
                const item = document.createElement('div');
                item.className = 'suggestion-item px-4 py-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
                item.textContent = suggestion;
                
                item.addEventListener('click', function() {
                    searchInput.value = suggestion;
                    hideSuggestions();
                    searchForm?.submit();
                });
                
                suggestionBox.appendChild(item);
            });
            
            suggestionBox.classList.remove('hidden');
        }
        
        function hideSuggestions() {
            if (suggestionBox) {
                suggestionBox.classList.add('hidden');
                suggestionBox.querySelectorAll('.suggestion-item').forEach(item => {
                    item.classList.remove('active');
                });
            }
        }
        
        // Form submission with loading state
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<div class="loading-spinner inline-block mr-2"></div>' + '<?= t("loading") ?>';
                    submitBtn.disabled = true;
                    
                    // Re-enable after a delay in case of errors
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 10000);
                }
            });
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add fade-in animation to results
        const resultCards = document.querySelectorAll('.result-card');
        resultCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });
    });
    
    // Utility function for copying text
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            console.log('Copied to clipboard');
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    
    // Performance monitoring
    window.addEventListener('load', function() {
        if ('performance' in window) {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log('Page load time:', loadTime + 'ms');
        }
    });
</script>
</body>
</html>
