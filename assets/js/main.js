/**
 * Main JavaScript file for the search engine
 * Handles search functionality, autocomplete, and UI interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    initializeUI();
    initializeAccessibility();
});

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInputs = document.querySelectorAll('input[name="query"]');
    const searchForms = document.querySelectorAll('form[action*="search"]');
    
    searchInputs.forEach(input => {
        setupAutocomplete(input);
        setupSearchValidation(input);
    });
    
    searchForms.forEach(form => {
        setupFormSubmission(form);
    });
}

/**
 * Setup autocomplete functionality
 */
function setupAutocomplete(searchInput) {
    let suggestionBox = null;
    let debounceTimer = null;
    let currentFocus = -1;
    
    // Create suggestion box
    suggestionBox = document.createElement('div');
    suggestionBox.className = 'absolute bg-white border border-gray-200 shadow-lg mt-1 rounded-lg w-full z-50 hidden max-h-60 overflow-y-auto';
    suggestionBox.id = 'suggestions-' + Math.random().toString(36).substr(2, 9);
    
    // Position suggestion box
    const inputContainer = searchInput.parentNode;
    if (getComputedStyle(inputContainer).position === 'static') {
        inputContainer.style.position = 'relative';
    }
    inputContainer.appendChild(suggestionBox);
    
    // Handle input for suggestions
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        currentFocus = -1;
        
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
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentFocus++;
            if (currentFocus >= suggestions.length) currentFocus = 0;
            setActiveSuggestion(suggestions, currentFocus);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus--;
            if (currentFocus < 0) currentFocus = suggestions.length - 1;
            setActiveSuggestion(suggestions, currentFocus);
        } else if (e.key === 'Enter') {
            if (currentFocus > -1 && suggestions[currentFocus]) {
                e.preventDefault();
                searchInput.value = suggestions[currentFocus].textContent;
                hideSuggestions();
                searchInput.closest('form')?.submit();
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
            searchInput.blur();
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!inputContainer.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    function fetchSuggestions(query) {
        fetch(`suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
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
        currentFocus = -1;
        
        suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'suggestion-item px-4 py-3 cursor-pointer text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-50 last:border-b-0';
            item.textContent = suggestion;
            item.setAttribute('role', 'option');
            item.setAttribute('tabindex', '-1');
            
            item.addEventListener('click', function() {
                searchInput.value = suggestion;
                hideSuggestions();
                searchInput.closest('form')?.submit();
            });
            
            item.addEventListener('mouseenter', function() {
                setActiveSuggestion(suggestionBox.querySelectorAll('.suggestion-item'), index);
                currentFocus = index;
            });
            
            suggestionBox.appendChild(item);
        });
        
        suggestionBox.classList.remove('hidden');
        suggestionBox.setAttribute('role', 'listbox');
    }
    
    function hideSuggestions() {
        if (suggestionBox) {
            suggestionBox.classList.add('hidden');
            suggestionBox.removeAttribute('role');
            currentFocus = -1;
        }
    }
    
    function setActiveSuggestion(suggestions, index) {
        suggestions.forEach((item, i) => {
            if (i === index) {
                item.classList.add('bg-blue-100', 'text-blue-900');
                item.setAttribute('aria-selected', 'true');
            } else {
                item.classList.remove('bg-blue-100', 'text-blue-900');
                item.setAttribute('aria-selected', 'false');
            }
        });
    }
}

/**
 * Setup search validation
 */
function setupSearchValidation(searchInput) {
    searchInput.addEventListener('blur', function() {
        const query = this.value.trim();
        if (query.length > 0 && query.length < 2) {
            showValidationMessage(this, 'La búsqueda debe tener al menos 2 caracteres');
        } else {
            hideValidationMessage(this);
        }
    });
}

/**
 * Setup form submission with loading state
 */
function setupFormSubmission(form) {
    form.addEventListener('submit', function(e) {
        const searchInput = this.querySelector('input[name="query"]');
        const submitBtn = this.querySelector('button[type="submit"]');
        const query = searchInput?.value.trim();
        
        // Validate query
        if (!query || query.length < 2) {
            e.preventDefault();
            showValidationMessage(searchInput, 'Por favor, ingresa al menos 2 caracteres');
            searchInput?.focus();
            return false;
        }
        
        // Show loading state
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading-spinner inline-block mr-2"></div>Buscando...';
            submitBtn.disabled = true;
            
            // Re-enable after a delay in case of errors
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        }
        
        // Track search event (if analytics is available)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'search', {
                search_term: query
            });
        }
    });
}

/**
 * Initialize UI enhancements
 */
function initializeUI() {
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
    
    // Initialize copy to clipboard functionality
    initializeCopyButtons();
    
    // Initialize image lazy loading
    initializeLazyLoading();
    
    // Initialize tooltips
    initializeTooltips();
}

/**
 * Initialize accessibility features
 */
function initializeAccessibility() {
    // Add skip links
    addSkipLinks();
    
    // Enhance keyboard navigation
    enhanceKeyboardNavigation();
    
    // Add ARIA labels where needed
    addAriaLabels();
    
    // Handle focus management
    manageFocus();
}

/**
 * Copy to clipboard functionality
 */
function initializeCopyButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-copy]') || e.target.closest('[data-copy]')) {
            const button = e.target.matches('[data-copy]') ? e.target : e.target.closest('[data-copy]');
            const text = button.getAttribute('data-copy');
            copyToClipboard(text);
        }
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copiado al portapapeles', 'success');
        }).catch(err => {
            console.error('Could not copy text: ', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('Copiado al portapapeles', 'success');
    } catch (err) {
        console.error('Fallback: Could not copy text: ', err);
        showToast('Error al copiar', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Initialize lazy loading for images
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', showTooltip);
        element.addEventListener('blur', hideTooltip);
    });
}

/**
 * Show validation message
 */
function showValidationMessage(input, message) {
    hideValidationMessage(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-message text-red-600 text-sm mt-1';
    errorDiv.textContent = message;
    errorDiv.setAttribute('role', 'alert');
    
    input.parentNode.appendChild(errorDiv);
    input.classList.add('border-red-500');
}

/**
 * Hide validation message
 */
function hideValidationMessage(input) {
    const existingMessage = input.parentNode.querySelector('.validation-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    input.classList.remove('border-red-500');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white transform transition-transform duration-300 translate-x-full ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-blue-600'
    }`;
    toast.textContent = message;
    toast.setAttribute('role', 'alert');
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after delay
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

/**
 * Show tooltip
 */
function showTooltip(e) {
    const element = e.target;
    const text = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg pointer-events-none';
    tooltip.textContent = text;
    tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element.setAttribute('aria-describedby', tooltip.id);
}

/**
 * Hide tooltip
 */
function hideTooltip(e) {
    const element = e.target;
    const tooltipId = element.getAttribute('aria-describedby');
    if (tooltipId) {
        const tooltip = document.getElementById(tooltipId);
        if (tooltip) {
            tooltip.remove();
        }
        element.removeAttribute('aria-describedby');
    }
}

/**
 * Add skip links for accessibility
 */
function addSkipLinks() {
    const skipLink = document.querySelector('a[href="#main-content"]');
    if (skipLink) {
        skipLink.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById('main-content');
            if (target) {
                target.focus();
                target.scrollIntoView();
            }
        });
    }
}

/**
 * Enhance keyboard navigation
 */
function enhanceKeyboardNavigation() {
    // Add keyboard support for custom elements
    document.addEventListener('keydown', function(e) {
        // Handle escape key globally
        if (e.key === 'Escape') {
            // Close any open modals, dropdowns, etc.
            document.querySelectorAll('.modal, .dropdown').forEach(el => {
                el.classList.add('hidden');
            });
        }
    });
}

/**
 * Add ARIA labels where needed
 */
function addAriaLabels() {
    // Add labels to form elements without labels
    document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])').forEach(input => {
        const placeholder = input.getAttribute('placeholder');
        if (placeholder && !input.labels.length) {
            input.setAttribute('aria-label', placeholder);
        }
    });
}

/**
 * Manage focus for better accessibility
 */
function manageFocus() {
    // Ensure focus is visible
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
}

/**
 * Performance monitoring
 */
window.addEventListener('load', function() {
    if ('performance' in window) {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log('Page load time:', loadTime + 'ms');
        
        // Report to analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'timing_complete', {
                name: 'load',
                value: loadTime
            });
        }
    }
});

/**
 * Error handling
 */
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    
    // Report to analytics if available
    if (typeof gtag !== 'undefined') {
        gtag('event', 'exception', {
            description: e.error.toString(),
            fatal: false
        });
    }
});

/**
 * Service Worker registration (for future PWA features)
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        // Uncomment when service worker is implemented
        // navigator.serviceWorker.register('/sw.js');
    });
}

// Export functions for global use
window.searchEngine = {
    copyToClipboard,
    showToast,
    showValidationMessage,
    hideValidationMessage
};
