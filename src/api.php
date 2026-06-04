<?php
// Configuración CORS (Obligatorio para que Google Sites o tu dominio frontend pueda acceder)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de peticiones previas CORS (Pre-flight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de la base de datos
$db_host = 'bd';
$db_user = 'root';     
$db_pass = 'Utipec2026*$';
$db_name = 'indicadores';

// Conexión segura con PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    // Configurar PDO para que lance excepciones ante cualquier error de MySQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "error" => "Error de conexión a BD", "details" => $e->getMessage()]);
    exit();
}

// Obtener los parámetros de la URL
$action = $_GET['action'] ?? '';
$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Lista blanca de tablas permitidas (Medida de Seguridad Crítica)
$allowed_tables = ['activities', 'departments', 'collaborators'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(["success" => false, "error" => "Intento de acceso a tabla no permitida"]);
    exit();
}

try {
    // ---------------------------------------------------------
    // 1. LEER DATOS (GET) - Carga inicial del panel
    // ---------------------------------------------------------
    if ($method == 'GET' && $action == 'get') {
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    } 
    // ---------------------------------------------------------
    // 2. CREAR NUEVO REGISTRO (POST) - Nuevos usuarios, áreas, metas
    // ---------------------------------------------------------
    elseif ($method == 'POST' && $action == 'post') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) throw new Exception("No se recibieron datos para insertar.");
        
        $keys = array_keys($input);
        $fields = "`" . implode("`, `", $keys) . "`"; // Envolver en backticks para evitar palabras reservadas
        $placeholders = ":" . implode(", :", $keys);
        
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($input);
        
        echo json_encode(["success" => true]);
    }
    // ---------------------------------------------------------
    // 3. EDITAR REGISTRO EXISTENTE (PUT) - Cambiar claves, roles, o datos de tarea
    // ---------------------------------------------------------
    elseif ($method == 'PUT' && $action == 'put') {
        if (empty($id)) throw new Exception("Falta el ID del registro a actualizar.");
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) throw new Exception("No se recibieron datos para actualizar.");
        
        $setParts = [];
        $params = ['id_busqueda' => $id]; // Parámetro para la condición WHERE
        
        foreach ($input as $key => $value) {
            $setParts[] = "`$key` = :$key";
            $params[$key] = $value;
        }
        
        $setSql = implode(", ", $setParts);
        $sql = "UPDATE `$table` SET $setSql WHERE `id` = :id_busqueda";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(["success" => true]);
    }
    // ---------------------------------------------------------
    // 4. ELIMINAR REGISTRO (DELETE) - Borrar usuarios, áreas o tareas
    // ---------------------------------------------------------
    elseif ($method == 'DELETE' && $action == 'delete') {
        if (empty($id)) throw new Exception("Falta el ID del registro a eliminar.");
        
        $sql = "DELETE FROM `$table` WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        echo json_encode(["success" => true]);
    } 
    // ---------------------------------------------------------
    // RUTA DESCONOCIDA
    // ---------------------------------------------------------
    else {
        echo json_encode(["success" => false, "error" => "Método o acción no reconocida"]);
    }
    
} catch (Exception $e) {
    // Si algo falla, devuelve el error exacto a React para que lo registre en la consola
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>