<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$host     = "132.148.183.22";
$dbname   = "i10949992_ziel1";
$username = "salva";
$password = "Salvagusto301190!";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents("php://input"), true);


// Busca el id en la tabla catalogo a partir del texto que nos mandaron
// Por ejemplo: "Masculino" → 1, "CUI" → 3, etc.
function obtenerIdCatalogo($pdo, $tipo, $descripcion) {
    $stmt = $pdo->prepare("
        SELECT c.id_catalogo
        FROM catalogo c
        INNER JOIN catalogo_tipo ct ON ct.id_catalogo_tipo = c.id_catalogo_tipo
        WHERE ct.nombre = :tipo
          AND c.descripcion = :descripcion
          AND c.activo = 1
    ");
    $stmt->execute([':tipo' => $tipo, ':descripcion' => $descripcion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id_catalogo'] : null;
}

function obtenerIdCatalogoTipo($pdo, $nombre) {
    $stmt = $pdo->prepare("SELECT id_catalogo_tipo FROM catalogo_tipo WHERE nombre = :nombre");
    $stmt->execute([':nombre' => $nombre]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id_catalogo_tipo'] : null;
}

// Convierte los campos de texto del body a sus ids
// correspondientes en la tabla catalogo
function resolverCatalogos($pdo, &$body, &$errores) {
    $mapeo = [
        'tipo_documento' => 'TIPO_DOCUMENTO',
        'genero'         => 'GENERO',
        'estado_civil'   => 'ESTADO_CIVIL',
        'nacionalidad'   => 'NACIONALIDAD',
        'profesion'      => 'PROFESION',
        'municipio'      => 'MUNICIPIO',
    ];

    foreach ($mapeo as $campo => $tipo) {
        if (isset($body[$campo])) {
            $id = obtenerIdCatalogoTipo($pdo, $tipo);
            if (!$id) {
                $errores[] = "$campo (valor no encontrado: {$body[$campo]})";
            } else {
                $body["id_$campo"] = $id;
            }
            unset($body[$campo]);
        }
    }
}
// GET Obtener catalogo(s)
// Si viene id_catalogo en el query string trae solo ese catalogo
// Si no viene ningún parámetro trae todos los catalogos activos
if ($method === 'GET') {
    try {
        $tipo = $_GET['tipo'];
        $id = $_GET['id'];
        
        if(!empty($tipo)){
            $sql_query = $pdo->prepare("
                SELECT cat.id_catalogo,
                    cat.id_catalogo_tipo,
                    cat.codigo,
                    cat.descripcion
                FROM catalogo AS cat 
                INNER JOIN catalogo_tipo AS cat_ti ON cat.id_catalogo_tipo = cat_ti.id_catalogo_tipo
                WHERE 1=1
                AND cat_ti.nombre = :catalogo_tipo_nombre
                AND cat.activo = 1");
        $sql_query->execute([
            ':catalogo_tipo_nombre' => $tipo
            ]);
        }else if (!empty($id)){
            $sql_query = $pdo->prepare("
                SELECT cat.id_catalogo,
                    cat_ti.nombre AS catalogo_tipo,
                    cat.codigo,
                    cat.descripcion
                FROM catalogo AS cat 
                INNER JOIN catalogo_tipo AS cat_ti ON cat.id_catalogo_tipo = cat_ti.id_catalogo_tipo
                WHERE 1=1
                AND cat.id_catalogo = :id_catalogo
                AND cat.activo = 1");
                $sql_query->execute([
                    ':id_catalogo' => $id
                ]);
        }else{
            $sql_query = $pdo->prepare("
                SELECT cat.id_catalogo,
                    cat_ti.nombre AS catalogo_tipo,
                    cat.codigo,
                    cat.descripcion
                FROM catalogo AS cat 
                INNER JOIN catalogo_tipo AS cat_ti ON cat.id_catalogo_tipo = cat_ti.id_catalogo_tipo
                WHERE 1=1
                AND cat.activo = 1");
            $sql_query->execute();
        }
        $list = $sql_query->fetchAll(PDO::FETCH_ASSOC);
        if (!$list) {
            http_response_code(404);
            echo json_encode(["error" => "Catalogo no encontrada con el tipo: $tipo"]);
            exit();
        }
        http_response_code(200);
        echo json_encode($list);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener catalogo: " . $e->getMessage()]);
    }
    exit();
}

// POST Crear nuevo catalogo

if ($method === "POST") {
    $catalogo_tipo = $_POST["catalogo_tipo"];
    $errores = [];

    if (!empty($catalogo_tipo)) {
        $requeridos = ['catalogo_tipo', 'codigo', 'descripcion'];
    } else {
        $requeridos = ['codigo', 'descripcion'];
    }
    foreach ($requeridos as $campo) {
        if (empty($body[$campo])) $errores[] = $campo;
    }

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: " . implode(", ", $errores)]);
        exit();
    }

    if (!empty($catalogo_tipo)) {
        $idCatalogoTipo = obtenerIdCatalogoTipo($pdo, $catalogo_tipo);
    } else {
        $idCatalogoTipo = obtenerIdCatalogoTipo($pdo, $body['catalogo_tipo']);
    }    
    
    if (!$idCatalogoTipo) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: catalogo_tipo (valor no encontrado: {$body['catalogo_tipo']})"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO catalogo (
                id_catalogo_tipo, codigo, descripcion, id_catalogo_padre, activo
            ) VALUES (
                :id_catalogo_tipo, :codigo, :descripcion, null, 1
            )
        ");
        $stmt->execute([
            ':id_catalogo_tipo' => $idCatalogoTipo,
            ':codigo' => trim($body['codigo']),
            ':descripcion' => trim($body['descripcion'])
        ]);

        $id = (int) $pdo->lastInsertId();

        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_despues, ip_origen)
            VALUES (null, 'CREATE', 'catalogo', :id_registro, :datos_despues, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro'   => $id,
            ':datos_despues' => json_encode($body),
            ':ip_origen'     => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i:%s') AS fecha_creacion FROM catalogo WHERE id_catalogo = :id");
        $fetch->execute([':id' => $id]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "id"             => $id,
            "fecha_creacion" => $row['fecha_creacion']
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(["error" => "El código o descripción ya está registrado."]);
            exit();
        }
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar el catalogo."]);
    }
    exit();
}


// PUT Actualizar persona
if ($method === 'PUT') {
    $id_catalogo = $_GET['id'];

    if ($id_catalogo === null) {
        if(empty($body['id_catalogo']) || !is_numeric($body['id_catalogo'])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: id_catalogo (debe ser un número entero)"]);
        exit();
        }else{
            $id_catalogo = intval($body['id_catalogo']);
        }
    }

    $stmt = $pdo->prepare("SELECT id_catalogo FROM catalogo WHERE id_catalogo = :id AND activo = 1");
    $stmt->execute([':id' => $id_catalogo]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Catalogo no encontrado con id: $id_catalogo"]);
        exit();
    }

    $errores = [];

    if (isset($body['codigo']) && empty(trim($body['codigo'])))     $errores[] = "codigo";
    if (isset($body['descripcion']) && empty(trim($body['descripcion']))) $errores[] = "descripcion";
    if (isset($body["catalogo_tipo"]) && empty(trim($body['catalogo_tipo']))) $errores[] = "catalogo_tipo";

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: " . implode(", ", $errores)]);
        exit();
    }

    $campos_permitidos = [
        'codigo', 'descripcion', 'catalogo_tipo'
    ];

    $sets   = [];
    $params = [':id' => $id_catalogo];

    foreach ($campos_permitidos as $campo) {
        if (array_key_exists($campo, $body)) {
            if($campo === 'catalogo_tipo'){
                $id_catalogo_tipo = obtenerIdCatalogoTipo($pdo, $body['catalogo_tipo']);
                if (!$id_catalogo_tipo) {
                    http_response_code(400);
                    echo json_encode(["error" => "Datos incorrectos. Por favor validar: catalogo_tipo (valor no encontrado: {$body['catalogo_tipo']})"]);
                    exit();
                }
                $sets[]            = "id_catalogo_tipo = :id_catalogo_tipo";
                $params[":id_catalogo_tipo"] = $id_catalogo_tipo;
                continue;
            } else {
                $sets[] = "$campo = :$campo";
            }
            
            $params[":$campo"] = $body[$campo];
        }
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(["error" => "No se enviaron campos para actualizar"]);
        exit();
    }

    try {
        $sql  = "UPDATE catalogo SET " . implode(", ", $sets) . " WHERE id_catalogo = :id AND activo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_despues, ip_origen)
            VALUES (null, 'UPDATE', 'catalogo', :id_registro, :datos_despues, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro'   => $id_catalogo,
            ':datos_despues' => json_encode($body),
            ':ip_origen'     => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i:%s') AS fecha_actualizacion FROM catalogo WHERE id_catalogo = :id");
        $fetch->execute([':id' => $id_catalogo]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "id_catalogo"         => $id_catalogo,
            "fecha_actualizacion" => $row['fecha_actualizacion']
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(["error" => "El correo o número de documento ya está registrado para otra persona."]);
            exit();
        }
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar: " . $e->getMessage()]);
    }
    exit();
}

// DELETE Eliminar persona (eliminación lógica)
if ($method === 'DELETE') {

    $id_catalogo = $_GET['id'];

    if ($id_catalogo === null) {
        if(empty($body['id_catalogo']) || !is_numeric($body['id_catalogo'])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: id_catalogo (debe ser un número entero)"]);
        exit();
        }else{
            $id_catalogo = intval($body['id_catalogo']);
        }
    }

    // Verificamos que exista y que no haya sido eliminada antes
    // no tiene sentido eliminar algo que ya está inactivo
    $stmt = $pdo->prepare("SELECT id_catalogo FROM catalogo WHERE id_catalogo = :id AND activo = 1");
    $stmt->execute([':id' => $id_catalogo]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Catalogo no encontrado o ya fue eliminado con id: $id_catalogo"]);
        exit();
    }

    try {
        // Eliminación lógica: no borramos el registro,
        // solo cambiamos el flag activo de 1 a 0
        $stmt = $pdo->prepare("UPDATE catalogo SET activo = 0 WHERE id_catalogo = :id");
        $stmt->execute([':id' => $id_catalogo]);

        // Guardamos en bitácora el estado anterior
        // por si algún día hay que recuperarlo
        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_antes, ip_origen)
            VALUES (null, 'DELETE', 'catalogo', :id_registro, :datos_antes, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro' => $id_catalogo,
            ':datos_antes' => json_encode(["id_catalogo" => $id_catalogo, "activo" => 1]),
            ':ip_origen'   => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i:%s') AS fecha_actualizacion FROM catalogo WHERE id_catalogo = :id");
        $fetch->execute([':id' => $id_catalogo]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "id_catalogo"         => $id_catalogo,
            "eliminado"              => "TRUE",
            "fecha_eliminacion" => $row['fecha_actualizacion']
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar: " . $e->getMessage()]);
    }
    exit();
}
 
// Si llega un método que no manejamos
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
?>