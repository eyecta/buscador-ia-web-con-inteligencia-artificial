<?php
/**
 * Spanish Language File
 * Archivo de idioma español para el motor de búsqueda
 */

$lang = [
    // General
    'site_title' => 'Buscador IA',
    'site_description' => 'Motor de búsqueda web impulsado por IA',
    'search_placeholder' => 'Buscar en la web...',
    'search_button' => 'Buscar',
    'clear_button' => 'Limpiar',
    'loading' => 'Buscando...',
    'home' => 'Inicio',
    'admin' => 'Admin',
    'back_to_home' => 'Volver al inicio',
    'language' => 'Idioma',
    
    // Search Results
    'results_for' => 'Resultados para:',
    'about_results' => 'Aproximadamente %s resultados (%s segundos)',
    'no_results' => 'No se encontraron resultados',
    'no_results_desc' => 'Intenta con diferentes palabras clave o términos más generales.',
    'search_suggestions' => [
        'Verifica la ortografía de las palabras',
        'Usa términos más generales',
        'Prueba con sinónimos'
    ],
    'related_searches' => 'Búsquedas relacionadas',
    'references' => 'Referencias',
    'related_images' => 'Imágenes relacionadas',
    'copy_link' => 'Copiar enlace',
    'open' => 'Abrir',
    'result_number' => 'Resultado #%d',
    'showing_best_results' => 'Mostrando los mejores resultados para tu búsqueda',
    
    // AI Summary
    'ai_summary' => 'Resumen IA',
    'ai_summary_desc' => 'Resumen contextual generado por IA sobre tu búsqueda',
    'generating_summary' => 'Generando resumen IA...',
    
    // Trending
    'trending_searches' => 'Búsquedas Populares',
    'trending_title' => '🔥 Tendencias',
    'searches_performed' => 'búsquedas realizadas',
    'average_time' => 'tiempo promedio',
    'unique_terms' => 'términos únicos',
    
    // Features
    'why_choose_title' => '¿Por qué elegir nuestro buscador?',
    'why_choose_desc' => 'Experimenta la próxima generación de búsqueda web con tecnología de inteligencia artificial avanzada.',
    'feature_fast_title' => 'Búsqueda Rápida',
    'feature_fast_desc' => 'Resultados instantáneos con tecnología de caché inteligente y optimización avanzada.',
    'feature_ai_title' => 'IA Avanzada',
    'feature_ai_desc' => 'Powered by OpenRouter.ai para resultados más precisos y contextualmente relevantes.',
    'feature_secure_title' => 'Seguro y Privado',
    'feature_secure_desc' => 'Protección con Cloudflare Turnstile y respeto total por tu privacidad.',
    
    // Footer
    'about' => 'Acerca de',
    'links' => 'Enlaces',
    'information' => 'Información',
    'ai_powered_searches' => 'Búsquedas impulsadas por IA',
    'fast_accurate_results' => 'Resultados rápidos y precisos',
    'all_rights_reserved' => 'Todos los derechos reservados.',
    'developed_with_love' => 'Desarrollado con ❤️ usando PHP y IA',
    'sitemap' => 'Sitemap',
    
    // Error Messages
    'error_search' => 'Error en la búsqueda',
    'error_search_desc' => 'Error al realizar la búsqueda. Por favor, intenta de nuevo más tarde.',
    'error_invalid_query' => 'Por favor ingrese una consulta válida.',
    'error_min_chars' => 'La búsqueda debe tener al menos 2 caracteres.',
    'security_verification_failed' => 'Verificación de seguridad fallida. Por favor, intenta de nuevo.',
    
    // 404 Page
    'page_not_found' => 'Página no encontrada',
    'page_not_found_desc' => 'Lo sentimos, la página que buscas no existe o ha sido movida.',
    'what_are_you_looking_for' => '¿Qué estás buscando?',
    'go_home' => 'Ir al inicio',
    'go_back' => 'Volver atrás',
    'popular_searches' => 'Búsquedas populares',
    'error_help' => 'Si crees que esto es un error, puedes:',
    'error_help_items' => [
        'Verificar la URL en la barra de direcciones',
        'Usar el buscador para encontrar lo que necesitas',
        'Contactar al administrador del sitio'
    ],
    
    // Admin Panel (English only - as requested)
    'admin_panel' => 'Panel de Administración',
    'admin_login' => 'Iniciar Sesión',
    'admin_access_desc' => 'Accede para gestionar tu motor de búsqueda',
    'username' => 'Usuario',
    'password' => 'Contraseña',
    'login' => 'Iniciar Sesión',
    'logout' => 'Cerrar Sesión',
    'dashboard' => 'Dashboard',
    'settings' => 'Configuración',
    'searches' => 'Búsquedas',
    'pages' => 'Páginas',
    'trending' => 'Tendencias',
    'models' => 'Modelos IA',
    'advertisements' => 'Anuncios',
    
    // Statistics
    'total_searches' => 'Total Búsquedas',
    'today' => 'Hoy',
    'recent_searches' => 'Búsquedas Recientes',
    'top_trending' => 'Tendencias Populares',
    
    // Validation
    'field_required' => 'Este campo es obligatorio',
    'invalid_credentials' => 'Credenciales incorrectas. Por favor, verifica tu usuario y contraseña.',
    'csrf_token_invalid' => 'Token de seguridad inválido.',
    'settings_updated' => 'Configuración actualizada correctamente.',
    'page_saved' => 'Página guardada correctamente.',
    'page_deleted' => 'Página eliminada correctamente.',
    'search_deleted' => 'Búsqueda eliminada correctamente.',
    'all_searches_deleted' => 'Todas las búsquedas han sido eliminadas.',
    'trending_updated' => 'Tendencia actualizada correctamente.',
    'trending_deleted' => 'Tendencia eliminada correctamente.',
    'model_added' => 'Modelo agregado correctamente.',
    'model_deleted' => 'Modelo eliminado correctamente.',
    'ads_updated' => 'Configuración de anuncios actualizada correctamente.',
    
    // Time
    'seconds_ago' => 'hace %d segundos',
    'minutes_ago' => 'hace %d minutos',
    'hours_ago' => 'hace %d horas',
    'days_ago' => 'hace %d días',
    'months_ago' => 'hace %d meses',
    'years_ago' => 'hace %d años',
    'just_now' => 'ahora mismo',
    
    // Accessibility
    'skip_to_content' => 'Saltar al contenido principal',
    'search_field' => 'Campo de búsqueda',
    'search_suggestions' => 'Sugerencias de búsqueda',
    'main_navigation' => 'Navegación principal',
    
    // Ads
    'advertisement' => 'Anuncio',
    'sponsored' => 'Patrocinado',
];
?>
