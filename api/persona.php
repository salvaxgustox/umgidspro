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
            $id = obtenerIdCatalogo($pdo, $tipo, $body[$campo]);
            if (!$id) {
                $errores[] = "$campo (valor no encontrado: {$body[$campo]})";
            } else {
                $body["id_$campo"] = $id;
            }
            unset($body[$campo]);
        }
    }
}


// GET Obtener persona(s)
// Si viene id_persona en el query string trae solo esa persona
// Si no viene ningún parámetro trae todas las personas activas
if ($method === 'GET') {
    try {
        
        $option = $_GET['option'];
        $pagina = $_GET['pagina'] ?? 1;
        $limite = $_GET['limite'] ?? 20;
        $id_persona = $_GET['id'] ?? null;

        if ($option === 'total') {
            $sql_query = $pdo->prepare("SELECT COUNT(*) AS total FROM persona");
            $sql_query->execute();
            $total = $sql_query->fetch(PDO::FETCH_ASSOC)['total'];
            http_response_code(200);
            echo json_encode(["total" => $total]);
            exit();
        } else if ($option === "activos") {
            $sql_query = $pdo->prepare("SELECT COUNT(*) AS total FROM persona WHERE activo = 1");
            $sql_query->execute();
            $total = $sql_query->fetch(PDO::FETCH_ASSOC)['total'];
            http_response_code(200);
            echo json_encode(["total_activos" => $total]);
            exit();
        } else if ($option === "inactivos") {
            $sql_query = $pdo->prepare("SELECT COUNT(*) AS total FROM persona WHERE activo = 0");
            $sql_query->execute();
            $total = $sql_query->fetch(PDO::FETCH_ASSOC)['total'];
            http_response_code(200);
            echo json_encode(["total_inactivos" => $total]);
            exit();
        }

        // Query base con todos los joins para traer
        // las descripciones en lugar de los ids foráneos
        $sql = "
            SELECT
                p.id_persona,
                p.numero_documento,
                td.descripcion           AS tipo_documento,
                p.primer_nombre,
                p.segundo_nombre,
                p.primer_apellido,
                p.segundo_apellido,
                DATE_FORMAT(p.fecha_nacimiento, '%m/%d/%Y') AS fecha_nacimiento,
                g.descripcion            AS genero,
                ec.descripcion           AS estado_civil,
                n.descripcion            AS nacionalidad,
                pr.descripcion           AS profesion,
                m.descripcion            AS municipio,
                p.direccion,
                p.correo,
                p.telefono_personal,
                p.telefono_casa,
                DATE_FORMAT(p.fecha_creacion,      '%d/%m/%Y %H:%i:%s') AS fecha_creacion,
                DATE_FORMAT(p.fecha_actualizacion, '%d/%m/%Y %H:%i:%s') AS fecha_actualizacion
            FROM persona p
            -- Unimos cada FK con su descripción en catalogo
            INNER JOIN catalogo td  ON td.id_catalogo  = p.id_tipo_documento
            INNER JOIN catalogo g   ON g.id_catalogo   = p.id_genero
            INNER JOIN catalogo ec  ON ec.id_catalogo  = p.id_estado_civil
            INNER JOIN catalogo n   ON n.id_catalogo   = p.id_nacionalidad
            LEFT  JOIN catalogo pr  ON pr.id_catalogo  = p.id_profesion
            INNER JOIN catalogo m   ON m.id_catalogo   = p.id_municipio
            WHERE 1=1
            AND p.activo = 1
        ";

        // Si nos pasan un id traemos solo esa persona
        if (!empty($id_persona)) {

            if (!is_numeric($id_persona) || intval($id_persona) <= 0) {
                http_response_code(400);
                echo json_encode(["error" => "Datos incorrectos. Por favor validar: id_persona"]);
                exit();
            }

            $sql       .= " AND p.id_persona = :id_persona";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_persona' => $id_persona]);
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si no encontramos nada avisamos
            if (!$persona) {
                http_response_code(404);
                echo json_encode(["error" => "Persona no encontrada con id: $id_persona"]);
                exit();
            }

            // Registramos en bitácora que se consultó esta persona
            $bitacora = $pdo->prepare("
                INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, ip_origen)
                VALUES (null, 'READ', 'persona', :id_registro, :ip_origen)
            ");
            $bitacora->execute([
                ':id_registro' => $id_persona,
                ':ip_origen'   => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            http_response_code(200);
            echo json_encode($persona);

        } else {
            // Sin filtro traemos todas las personas activas
            if ($pagina === 1) {
                $sql       .= " LIMIT 20";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                // $stmt->execute([
                //     ':limite' => $limite
                // ]);
            } else {
                $sql       .= " LIMIT :limite OFFSET :offset";
                $stmt = $pdo->prepare($sql);
            
                $stmt->execute([
                    ':limite' => $limite,
                    ':offset' => ($pagina - 1) * $limite
                ]);
            }
            
            
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Registramos en bitácora que se hizo una consulta general
            $bitacora = $pdo->prepare("
                INSERT INTO bitacora (id_usuario, accion, tabla_afectada, ip_origen)
                VALUES (null, 'READ', 'persona', :ip_origen)
            ");
            $bitacora->execute([
                ':ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            http_response_code(200);
            echo json_encode($personas);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener personas: " . $e->getMessage()]);
    }
    exit();
}


// POST Crear persona
if ($method === 'POST') {

    $errores = [];

    $requeridos = ['primer_nombre',
     'primer_apellido',
     'fecha_nacimiento',
     'id_tipo_documento',
     'numero_documento',
     'id_genero',
     'id_estado_civil',
     'id_nacionalidad',
     'id_municipio'];
    foreach ($requeridos as $campo) {
        if (empty($body[$campo])) $errores[] = $campo;
    }

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: " . implode(", ", $errores)]);
        exit();
    }

    $fecha = DateTime::createFromFormat('Y-m-d', $body['fecha_nacimiento']);
    if (!$fecha) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: fecha_nacimiento (formato esperado: YYYY-MM-DD)"]);
        exit();
    }
    $fechaSQL = $fecha->format('Y-m-d');

    if (!empty($body['correo']) && !filter_var($body['correo'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: correo"]);
        exit();
    }

    // $idTipoDoc = obtenerIdCatalogo($pdo, 'TIPO_DOCUMENTO', $body['tipo_documento']);
    // if (!$idTipoDoc) {
    //     http_response_code(400);
    //     echo json_encode(["error" => "Datos incorrectos. Por favor validar: tipo_documento (valor no encontrado: {$body['tipo_documento']})"]);
    //     exit();
    // }
    $idTipoDoc = $body['id_tipo_documento'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO persona (
                id_tipo_documento, numero_documento,
                primer_nombre,     segundo_nombre,
                primer_apellido,   segundo_apellido,
                fecha_nacimiento,  id_genero,
                id_estado_civil,   id_nacionalidad,
                id_municipio,      correo,
                telefono_personal
            ) VALUES (
                :id_tipo_documento, :numero_documento,
                :primer_nombre,     :segundo_nombre,
                :primer_apellido,   :segundo_apellido,
                :fecha_nacimiento,  :id_genero,
                :id_estado_civil,   :id_nacionalidad,
                :id_municipio,      :correo,
                :telefono_personal
            )
        ");
        $stmt->execute([
            ':id_tipo_documento' => $idTipoDoc,
            ':numero_documento'  => trim($body['numero_documento']),
            ':primer_nombre'     => trim($body['primer_nombre']),
            ':segundo_nombre'    => $body['segundo_nombre']    ?? null,
            ':primer_apellido'   => trim($body['primer_apellido']),
            ':segundo_apellido'  => $body['segundo_apellido']  ?? null,
            ':fecha_nacimiento'  => $fechaSQL,
            ':id_genero'         => (int) $body['id_genero'],
            ':id_estado_civil'   => (int) $body['id_estado_civil'],
            ':id_nacionalidad'   => (int) $body['id_nacionalidad'],
            ':id_municipio'      => (int) $body['id_municipio'],
            ':correo'            => $body['correo']            ?? null,
            ':telefono_personal' => $body['telefono_personal'] ?? null,
        ]);

        $id = (int) $pdo->lastInsertId();

        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_despues, ip_origen)
            VALUES (null, 'CREATE', 'persona', :id_registro, :datos_despues, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro'   => $id,
            ':datos_despues' => json_encode($body),
            ':ip_origen'     => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i:%s') AS fecha_creacion FROM persona WHERE id_persona = :id");
        $fetch->execute([':id' => $id]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode([
            "id"             => $id,
            "fecha_creacion" => $row['fecha_creacion']
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(["error" => "El correo o número de documento ya está registrado."]);
            exit();
        }
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar la persona."]);
    }
    exit();
}

// PUT Actualizar persona
if ($method === 'PUT') {

    $id_persona = $_GET['id'];
    echo $id_persona;

    if ($id_persona === null) {
        if(empty($body['id_persona']) || !is_numeric($body['id_persona'])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: id_persona (debe ser un número entero)"]);
        exit();
        }else{
            $id_persona = intval($body['id_persona']);
        }
    }


    $stmt = $pdo->prepare("SELECT id_persona FROM persona WHERE id_persona = :id AND activo = 1");
    $stmt->execute([':id' => $id_persona]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Persona no encontrada con id: $id_persona"]);
        exit();
    }

    $errores = [];

    if (isset($body['primer_nombre']) && empty(trim($body['primer_nombre'])))     $errores[] = "primer_nombre";
    if (isset($body['primer_apellido']) && empty(trim($body['primer_apellido']))) $errores[] = "primer_apellido";

    if (isset($body['fecha_nacimiento']) && !empty($body['fecha_nacimiento'])) {
        $fecha = DateTime::createFromFormat('d/m/Y', $body['fecha_nacimiento']);
        if (!$fecha) {
            $errores[] = "fecha_nacimiento (formato esperado: DD/MM/YYYY)";
        } else {
            $body['fecha_nacimiento'] = $fecha->format('Y-m-d');
        }
    }

    if (isset($body['correo']) && !filter_var($body['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = "correo";

    resolverCatalogos($pdo, $body, $errores);

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: " . implode(", ", $errores)]);
        exit();
    }

    $campos_permitidos = [
        'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
        'fecha_nacimiento', 'numero_documento', 'id_tipo_documento',
        'id_genero', 'id_estado_civil', 'id_nacionalidad', 'id_profesion',
        'id_municipio', 'direccion', 'correo', 'telefono_personal', 'telefono_casa'
    ];

    $sets   = [];
    $params = [':id' => $id_persona];

    foreach ($campos_permitidos as $campo) {
        if (array_key_exists($campo, $body)) {
            $sets[]            = "$campo = :$campo";
            $params[":$campo"] = $body[$campo];
        }
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(["error" => "No se enviaron campos para actualizar"]);
        exit();
    }

    try {
        $sql  = "UPDATE persona SET " . implode(", ", $sets) . " WHERE id_persona = :id AND activo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_despues, ip_origen)
            VALUES (null, 'UPDATE', 'persona', :id_registro, :datos_despues, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro'   => $id_persona,
            ':datos_despues' => json_encode($body),
            ':ip_origen'     => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i:%s') AS fecha_actualizacion FROM persona WHERE id_persona = :id");
        $fetch->execute([':id' => $id_persona]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "id_persona"          => $id_persona,
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

    $id_persona = $_GET['id'];

    if ($id_persona === null) {
        if(empty($body['id_persona']) || !is_numeric($body['id_persona'])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos. Por favor validar: id_persona (debe ser un número entero)"]);
        exit();
        }else{
            $id_persona = intval($body['id_persona']);
        }
    }

    // Verificamos que exista y que no haya sido eliminada antes
    // no tiene sentido eliminar algo que ya está inactivo
    $stmt = $pdo->prepare("SELECT id_persona FROM persona WHERE id_persona = :id AND activo = 1");
    $stmt->execute([':id' => $id_persona]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Persona no encontrada o ya fue eliminada con id: $id_persona"]);
        exit();
    }

    try {
        // Eliminación lógica: no borramos el registro,
        // solo cambiamos el flag activo de 1 a 0
        $stmt = $pdo->prepare("UPDATE persona SET activo = 0 WHERE id_persona = :id");
        $stmt->execute([':id' => $id_persona]);

        // Guardamos en bitácora el estado anterior
        // por si algún día hay que recuperarlo
        $bitacora = $pdo->prepare("
            INSERT INTO bitacora (id_usuario, accion, tabla_afectada, id_registro, datos_antes, ip_origen)
            VALUES (null, 'DELETE', 'persona', :id_registro, :datos_antes, :ip_origen)
        ");
        $bitacora->execute([
            ':id_registro' => $id_persona,
            ':datos_antes' => json_encode(["id_persona" => $id_persona, "activo" => 1]),
            ':ip_origen'   => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $fetch = $pdo->prepare("SELECT DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i:%s') AS fecha_actualizacion FROM persona WHERE id_persona = :id");
        $fetch->execute([':id' => $id_persona]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "id_persona"          => $id_persona,
            "activo"              => 0,
            "fecha_actualizacion" => $row['fecha_actualizacion']
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