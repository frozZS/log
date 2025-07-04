<?php
require_once __DIR__ . '/config.php';

// Mostrar errores (temporal)
ini_set('display_errors',1); error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error'=>'Método no permitido']);
  exit;
}

$page      = max(1, intval($_GET['page']  ?? 1));
$limit     = max(1, intval($_GET['limit'] ?? 12));
$offset    = ($page - 1) * $limit;
$allowedOrderFields = ['fecha_creacion', 'titulo', 'anio'];
$orderBy = in_array($_GET['order'] ?? '', $allowedOrderFields, true)
           ? $_GET['order']
           : 'fecha_creacion';

// Dirección ascendente o descendente (por defecto DESC)
$direction = strtoupper($_GET['direction'] ?? 'DESC') === 'ASC'
             ? 'ASC'
             : 'DESC';

$pdo = getConnection();

// Conteo total
$total = $pdo->query("SELECT COUNT(*) FROM peliculas")->fetchColumn();

// Consulta con interpolación segura de enteros
$sql = "
  SELECT id, titulo, descripcion, genero, anio, poster_path, video_path
  FROM peliculas
  ORDER BY {$orderBy} {$direction}
  LIMIT {$limit} OFFSET {$offset}
";
$stmt   = $pdo->query($sql);
$movies = $stmt->fetchAll();

// Ajusta rutas absolutas si quieres...
foreach ($movies as &$m) {
  if ($m['poster_path'] && !filter_var($m['poster_path'], FILTER_VALIDATE_URL)) {
    $m['poster_path'] = 'http://localhost/under/' . $m['poster_path'];
  }
  // idem para video_path
}

echo json_encode([
  'success'    => true,
  'data'       => $movies,
  'pagination' => [
    'current_page' => $page,
    'total_pages'  => ceil($total/$limit),
    'total_movies' => intval($total),
    'per_page'     => $limit,
    'has_next'     => $page* $limit < $total,
    'has_prev'     => $page > 1
  ]
]);
