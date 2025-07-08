<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if(!empty($query) && strlen($query) >= 2) {
    $query = sanitize_input($query);
    
    try {
        // Get suggestions from previous searches
        $stmt = $dbInstance->conn->prepare("
            SELECT DISTINCT query, COUNT(*) as frequency 
            FROM searches 
            WHERE query LIKE :query 
            AND query != :exact_query
            GROUP BY query 
            ORDER BY frequency DESC, query ASC 
            LIMIT 5
        ");
        $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
        $stmt->bindValue(':exact_query', $query, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $suggestions[] = $row['query'];
        }
        
        // If we don't have enough suggestions, add from trending
        if(count($suggestions) < 5) {
            $stmt = $dbInstance->conn->prepare("
                SELECT query 
                FROM trending 
                WHERE query LIKE :query 
                AND query != :exact_query
                AND query NOT IN (" . str_repeat('?,', count($suggestions) - 1) . (count($suggestions) > 0 ? '?' : '') . ")
                ORDER BY count DESC 
                LIMIT :limit
            ");
            
            $stmt->bindValue(':query', "%$query%", SQLITE3_TEXT);
            $stmt->bindValue(':exact_query', $query, SQLITE3_TEXT);
            $stmt->bindValue(':limit', 5 - count($suggestions), SQLITE3_INTEGER);
            
            // Bind existing suggestions to exclude them
            $paramIndex = 3;
            foreach($suggestions as $existing) {
                $stmt->bindValue($paramIndex++, $existing, SQLITE3_TEXT);
            }
            
            $result = $stmt->execute();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $suggestions[] = $row['query'];
            }
        }
        
        // Add some common search completions if still not enough
        if(count($suggestions) < 3) {
            $commonCompletions = [
                $query . ' que es',
                $query . ' como funciona',
                $query . ' definicion',
                $query . ' ejemplos',
                $query . ' historia'
            ];
            
            foreach($commonCompletions as $completion) {
                if(count($suggestions) >= 5) break;
                if(!in_array($completion, $suggestions)) {
                    $suggestions[] = $completion;
                }
            }
        }
        
    } catch(Exception $e) {
        error_log("Error getting suggestions: " . $e->getMessage());
        // Return empty array on error
    }
}

// Ensure we don't return more than 5 suggestions
$suggestions = array_slice($suggestions, 0, 5);

// Return JSON response
echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
?>
