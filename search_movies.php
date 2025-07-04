<?php
require_once 'config.php';

try {
    // Verificar que sea una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    // Obtener término de búsqueda
    $searchTerm = trim($_GET['q'] ?? '');
    
    if (empty($searchTerm)) {
        http_response_code(400);
        echo json_encode(['error' => 'Término de búsqueda requerido']);
        exit;
    }

    // Parámetros de paginación
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 12);
    $offset = ($page - 1) * $limit;

    $pdo = getConnection();

    // Preparar término de búsqueda para LIKE
    $searchPattern = "%$searchTerm%";

    // Contar resultados de búsqueda
    $countSql = "SELECT COUNT(*) as total FROM peliculas 
                 WHERE titulo LIKE ? 
                 OR descripcion LIKE ? 
                 OR genero LIKE ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$searchPattern, $searchPattern, $searchPattern]);
    $totalResults = $countStmt->fetch()['total'];

    // Buscar películas
    $sql = "SELECT id, titulo, descripcion, genero, anio, poster_path, video_path, fecha_creacion,
            (CASE 
                WHEN titulo LIKE ? THEN 3
                WHEN genero LIKE ? THEN 2
                WHEN descripcion LIKE ? THEN 1
                ELSE 0
            END) as relevance
            FROM peliculas 
            WHERE titulo LIKE ? 
            OR descripcion LIKE ? 
            OR genero LIKE ?
            ORDER BY relevance DESC, fecha_creacion DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $searchPattern, $searchPattern, $searchPattern,  // Para relevance
        $searchPattern, $searchPattern, $searchPattern,  // Para WHERE
        $limit, $offset
    ]);
    $movies = $stmt->fetchAll();

    // Procesar rutas de archivos para URLs completas
    foreach ($movies as &$movie) {
        if ($movie['poster_path'] && !filter_var($movie['poster_path'], FILTER_VALIDATE_URL)) {
            $movie['poster_path'] = 'http://localhost/' . basename(dirname(__DIR__)) . '/' . $movie['poster_path'];
        }
        if ($movie['video_path'] && !filter_var($movie['video_path'], FILTER_VALIDATE_URL)) {
            $movie['video_path'] = 'http://localhost/' . basename(dirname(__DIR__)) . '/' . $movie['video_path'];
        }
        // Remover campo de relevance de la respuesta
        unset($movie['relevance']);
    }

    // Calcular información de paginación
    $totalPages = ceil($totalResults / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $movies,
        'search_term' => $searchTerm,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_results' => $totalResults,
            'per_page' => $limit,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>