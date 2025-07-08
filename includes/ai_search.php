<?php
/**
 * AI Search Integration with Openrouter.ai
 */

function performAISearch($query) {
    $apiKey = getSetting('openrouter_api_key');
    $model = getSetting('openrouter_model', 'openai/gpt-3.5-turbo');
    
    if(empty($apiKey)) {
        logError("OpenRouter API key not configured");
        return false;
    }
    
    // Get current language for localized responses
    $currentLang = $_SESSION['language'] ?? 'en';
    $isSpanish = $currentLang === 'es';
    
    // Create a comprehensive search prompt
    $searchPrompt = $isSpanish ? 
        "Actúa como un motor de búsqueda web avanzado. Para la consulta '{$query}', proporciona resultados de búsqueda detallados en formato JSON con la siguiente estructura:" :
        "Act as an advanced web search engine. For the query '{$query}', provide detailed search results in JSON format with the following structure:";
    
    $searchPrompt .= "

{
  \"items\": [
    {
      \"title\": \"" . ($isSpanish ? "Título del resultado" : "Result title") . "\",
      \"description\": \"" . ($isSpanish ? "Descripción detallada del contenido (150-200 caracteres)" : "Detailed content description (150-200 characters)") . "\",
      \"url\": \"https://example.com/relevant-url\",
      \"references\": [\"https://source1.com\", \"https://source2.com\"],
      \"images\": [\"https://images.pexels.com/photo1.jpg\", \"https://images.pexels.com/photo2.jpg\"]
    }
  ],
  \"total_results\": 10,
  \"search_time\": \"0.25 " . ($isSpanish ? "segundos" : "seconds") . "\"
}

" . ($isSpanish ? 
    "Proporciona 5-8 resultados relevantes y de alta calidad. Las URLs deben ser reales y verificables. Para las imágenes, usa URLs de Pexels o Unsplash relacionadas con el tema. Asegúrate de que toda la información sea precisa y útil." :
    "Provide 5-8 relevant and high-quality results. URLs should be real and verifiable. For images, use Pexels or Unsplash URLs related to the topic. Ensure all information is accurate and useful.");

    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $searchPrompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens' => 2000
    ];
    
    try {
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: ' . $_SERVER['HTTP_HOST'],
                'X-Title: Buscador IA'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if($curlError) {
            logError("cURL error in performAISearch", ['error' => $curlError, 'query' => $query]);
            return generateFallbackResults($query);
        }
        
        if($httpCode !== 200) {
            logError("HTTP error in performAISearch", ['code' => $httpCode, 'response' => $response]);
            return generateFallbackResults($query);
        }
        
        $result = json_decode($response, true);
        
        if(empty($result) || isset($result['error'])) {
            logError("API Error in performAISearch", ['result' => $result, 'query' => $query]);
            return generateFallbackResults($query);
        }
        
        // Extract the JSON from the AI response
        $content = $result['choices'][0]['message']['content'] ?? '';
        
        // Try to extract JSON from the response
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');
        
        if($jsonStart !== false && $jsonEnd !== false) {
            $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $searchResults = json_decode($jsonContent, true);
            
            if($searchResults && isset($searchResults['items'])) {
                // Validate and clean the results
                return validateSearchResults($searchResults);
            }
        }
        
        // If JSON parsing fails, generate fallback results
        return generateFallbackResults($query);
        
    } catch(Exception $e) {
        logError("Exception in performAISearch", ['error' => $e->getMessage(), 'query' => $query]);
        return generateFallbackResults($query);
    }
}

function validateSearchResults($results) {
    $validatedResults = [
        'items' => [],
        'total_results' => $results['total_results'] ?? 0,
        'search_time' => $results['search_time'] ?? '0.1 segundos'
    ];
    
    if(isset($results['items']) && is_array($results['items'])) {
        foreach($results['items'] as $item) {
            if(isset($item['title'], $item['description'], $item['url'])) {
                $validatedItem = [
                    'title' => sanitize_input($item['title']),
                    'description' => sanitize_input($item['description']),
                    'url' => filter_var($item['url'], FILTER_VALIDATE_URL) ? $item['url'] : '#',
                    'references' => [],
                    'images' => []
                ];
                
                // Validate references
                if(isset($item['references']) && is_array($item['references'])) {
                    foreach($item['references'] as $ref) {
                        if(filter_var($ref, FILTER_VALIDATE_URL)) {
                            $validatedItem['references'][] = $ref;
                        }
                    }
                }
                
                // Validate images
                if(isset($item['images']) && is_array($item['images'])) {
                    foreach($item['images'] as $img) {
                        if(filter_var($img, FILTER_VALIDATE_URL)) {
                            $validatedItem['images'][] = $img;
                        }
                    }
                }
                
                $validatedResults['items'][] = $validatedItem;
            }
        }
    }
    
    return $validatedResults;
}

function generateFallbackResults($query) {
    // Generate basic fallback results when AI search fails
    $fallbackResults = [
        'items' => [
            [
                'title' => 'Resultados para: ' . htmlspecialchars($query),
                'description' => 'Lo sentimos, no pudimos procesar tu búsqueda en este momento. Por favor, intenta de nuevo más tarde.',
                'url' => '#',
                'references' => [],
                'images' => []
            ]
        ],
        'total_results' => 1,
        'search_time' => '0.1 segundos'
    ];
    
    // Try to add some generic web results based on the query
    $searchTerms = explode(' ', $query);
    $mainTerm = $searchTerms[0];
    
    if(strlen($mainTerm) > 2) {
        $fallbackResults['items'][] = [
            'title' => 'Información sobre ' . htmlspecialchars($mainTerm),
            'description' => 'Encuentra más información sobre ' . htmlspecialchars($query) . ' en recursos web confiables.',
            'url' => 'https://www.google.com/search?q=' . urlencode($query),
            'references' => [
                'https://es.wikipedia.org/wiki/' . urlencode($mainTerm),
                'https://www.google.com/search?q=' . urlencode($query)
            ],
            'images' => [
                'https://images.pexels.com/photos/267350/pexels-photo-267350.jpeg?auto=compress&cs=tinysrgb&w=300',
                'https://images.pexels.com/photos/159711/books-bookstore-book-reading-159711.jpeg?auto=compress&cs=tinysrgb&w=300'
            ]
        ];
        $fallbackResults['total_results'] = 2;
    }
    
    return $fallbackResults;
}

function getSearchSuggestions($query) {
    global $dbInstance;
    $suggestions = [];
    
    try {
        // Get suggestions from previous searches
        $stmt = $dbInstance->conn->prepare("SELECT DISTINCT query FROM searches WHERE query LIKE :query ORDER BY created_at DESC LIMIT 5");
        $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
        $result = $stmt->execute();
        
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $suggestions[] = $row['query'];
        }
        
        // Add trending suggestions if we don't have enough
        if(count($suggestions) < 5) {
            $stmt = $dbInstance->conn->prepare("SELECT query FROM trending WHERE query LIKE :query ORDER BY count DESC LIMIT 3");
            $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
            $result = $stmt->execute();
            
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if(!in_array($row['query'], $suggestions)) {
                    $suggestions[] = $row['query'];
                }
            }
        }
        
    } catch(Exception $e) {
        logError("Error getting search suggestions", ['error' => $e->getMessage(), 'query' => $query]);
    }
    
    return array_slice($suggestions, 0, 5);
}

function generateAISummary($query) {
    $apiKey = getSetting('openrouter_api_key');
    $model = getSetting('openrouter_model', 'openai/gpt-3.5-turbo');
    
    if(empty($apiKey)) {
        return false;
    }
    
    // Get current language for localized responses
    $currentLang = $_SESSION['language'] ?? 'en';
    $isSpanish = $currentLang === 'es';
    
    $summaryPrompt = $isSpanish ?
        "Proporciona un resumen contextual detallado de 2-3 párrafos sobre '{$query}'. El resumen debe ser informativo, preciso y fácil de entender. Incluye información relevante, datos importantes y contexto útil sobre el tema." :
        "Provide a detailed contextual summary of 2-3 paragraphs about '{$query}'. The summary should be informative, accurate and easy to understand. Include relevant information, important data and useful context about the topic.";
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $summaryPrompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens' => 500
    ];
    
    try {
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: ' . $_SERVER['HTTP_HOST'],
                'X-Title: AI Search Engine'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if($curlError || $httpCode !== 200) {
            return false;
        }
        
        $result = json_decode($response, true);
        
        if(empty($result) || isset($result['error'])) {
            return false;
        }
        
        return $result['choices'][0]['message']['content'] ?? false;
        
    } catch(Exception $e) {
        logError("Exception in generateAISummary", ['error' => $e->getMessage(), 'query' => $query]);
        return false;
    }
}

function getAvailableModels() {
    global $dbInstance;
    try {
        $models = [];
        
        // Get default models
        $defaultModels = [
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'openai/gpt-4' => 'GPT-4',
            'anthropic/claude-3-haiku' => 'Claude 3 Haiku',
            'meta-llama/llama-3.1-8b-instruct' => 'Llama 3.1 8B',
            'perplexity/llama-3.1-sonar-small-128k-online' => 'Perplexity Sonar Small',
            'google/gemini-pro' => 'Gemini Pro'
        ];
        
        foreach ($defaultModels as $modelName => $displayName) {
            $models[] = [
                'model_name' => $modelName,
                'display_name' => $displayName,
                'is_custom' => false
            ];
        }
        
        // Get custom models from database
        $stmt = $dbInstance->conn->prepare("SELECT model_name, display_name FROM ai_models WHERE is_active = 1 ORDER BY display_name");
        $result = $stmt->execute();
        
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $models[] = [
                'model_name' => $row['model_name'],
                'display_name' => $row['display_name'] ?: $row['model_name'],
                'is_custom' => true
            ];
        }
        
        return $models;
        
    } catch(Exception $e) {
        logError("Error getting available models", ['error' => $e->getMessage()]);
        return [];
    }
}

function addCustomModel($modelName, $displayName = '', $description = '') {
    global $dbInstance;
    try {
        // Validate model name format
        if (!preg_match('/^[a-zA-Z0-9\-_\/\.]+$/', $modelName)) {
            return false;
        }
        
        $stmt = $dbInstance->conn->prepare("INSERT INTO ai_models (model_name, display_name, description) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $modelName, SQLITE3_TEXT);
        $stmt->bindValue(2, $displayName ?: $modelName, SQLITE3_TEXT);
        $stmt->bindValue(3, $description, SQLITE3_TEXT);
        $stmt->execute();
        
        return true;
        
    } catch(Exception $e) {
        logError("Error adding custom model", ['error' => $e->getMessage(), 'model' => $modelName]);
        return false;
    }
}

function deleteCustomModel($modelId) {
    global $dbInstance;
    try {
        $stmt = $dbInstance->conn->prepare("DELETE FROM ai_models WHERE id = ?");
        $stmt->bindValue(1, $modelId, SQLITE3_INTEGER);
        $stmt->execute();
        
        return true;
        
    } catch(Exception $e) {
        logError("Error deleting custom model", ['error' => $e->getMessage(), 'model_id' => $modelId]);
        return false;
    }
}

function getAdvertisements($position = null) {
    global $dbInstance;
    try {
        $adsEnabled = getSetting('ads_enabled', '0') === '1';
        if (!$adsEnabled) {
            return [];
        }
        
        $sql = "SELECT * FROM advertisements WHERE is_active = 1";
        if ($position) {
            $sql .= " AND position = ?";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $dbInstance->conn->prepare($sql);
        if ($position) {
            $stmt->bindValue(1, $position, SQLITE3_TEXT);
        }
        $result = $stmt->execute();
        
        $ads = [];
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ads[] = $row;
        }
        
        return $ads;
        
    } catch(Exception $e) {
        logError("Error getting advertisements", ['error' => $e->getMessage()]);
        return [];
    }
}
?>
