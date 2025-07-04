<?php
require_once 'config.php';



try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    // Obtener datos del formulario
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $anio = intval($_POST['anio'] ?? 0);

    // Validar datos requeridos
    if (empty($titulo)) {
        http_response_code(400);
        echo json_encode(['error' => 'El título es obligatorio']);
        exit;
    }

    if ($anio < 1900 || $anio > 2030) {
        http_response_code(400);
        echo json_encode(['error' => 'Año inválido']);
        exit;
    }

    $posterPath = null;
    $videoPath = null;

    // Procesar archivo de poster
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $poster = $_FILES['poster'];
        
        // Validar tipo de archivo para poster
        $allowedPosterTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validateFileType($poster, $allowedPosterTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de archivo de poster no válido. Use JPG, PNG, GIF o WebP']);
            exit;
        }

        // Validar tamaño del poster
        if ($poster['size'] > MAX_POSTER_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo del poster es demasiado grande. Máximo 2MB']);
            exit;
        }

        // Generar nombre único y mover archivo
        $posterName = generateUniqueFileName($poster['name']);
        $posterDestination = POSTER_DIR . $posterName;
        
        if (!move_uploaded_file($poster['tmp_name'], $posterDestination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al subir el poster']);
            exit;
        }
        
        $posterPath = 'uploads/posters/' . $posterName;
    }

    // Procesar archivo de video
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $video = $_FILES['video'];
        
        // Validar tipo de archivo para video
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        if (!validateFileType($video, $allowedVideoTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de archivo de video no válido. Use MP4, WebM u OGG']);
            exit;
        }

        // Validar tamaño del video
        if ($video['size'] > MAX_VIDEO_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo de video es demasiado grande. Máximo 100MB']);
            exit;
        }

        // Generar nombre único y mover archivo
        $videoName = generateUniqueFileName($video['name']);
        $videoDestination = VIDEO_DIR . $videoName;
        
        if (!move_uploaded_file($video['tmp_name'], $videoDestination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al subir el video']);
            exit;
        }
        
        $videoPath = 'uploads/videos/' . $videoName;
    }

    // Insertar en la base de datos
    $pdo = getConnection();
    $sql = "INSERT INTO peliculas (titulo, descripcion, genero, anio, poster_path, video_path) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titulo, $descripcion, $genero, $anio, $posterPath, $videoPath]);

    $movieId = $pdo->lastInsertId();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Película subida con éxito',
        'movie_id' => $movieId,
        'data' => [
            'id' => $movieId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'genero' => $genero,
            'anio' => $anio,
            'poster_path' => $posterPath,
            'video_path' => $videoPath
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