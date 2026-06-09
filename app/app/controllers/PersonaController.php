<?php
// require_once __DIR__ . '/../models/Persona.php';
// require_once __DIR__ . '/../config/Database.php';
class PersonaController
{
  public static $url = "http://mxx.60c.mytemp.website/projecto/api/persona.php";
  public static $url_catalogo = "http://mxx.60c.mytemp.website/projecto/api/catalogo.php";
 
  public function persona($id = 0){
    if($id != 0){
      $url = self::$url."?id=". $id;
    }else{
      $url = self::$url;
    }
   $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json', 
        'Accept: application/json',
        'User-Agent: personas_test/1.0' // Many APIs require a User-Agent header
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);

    // 4. Check for errors and handle the response
    if ($response === false) {
        $error = curl_error($ch);
        echo "cURL Error: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode === 200) {
            // Decode the JSON string into an associative PHP array
            $data = json_decode($response, true);
            return $data;
        } else {
            echo "API returned status code: " . $httpCode;
        }
    }
  }
  public function index()
  {
    // $q = trim($_GET['q'] ?? '');
    // $personas = Persona::all($q);
    $personas = $this->persona();
    
    $view = __DIR__ . '/../views/personas/index.php';
    require __DIR__ . '/../views/layouts/header_personas.php';
    include $view;
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function catalogos($catalogo_tipo){
    
    $ch = curl_init(self::$url_catalogo.'?tipo='.$catalogo_tipo);
    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json', 
        'Accept: application/json',
        'User-Agent: personas_test/1.0' // Many APIs require a User-Agent header
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);

    // 4. Check for errors and handle the response
    if ($response === false) {
        $error = curl_error($ch);
        echo "cURL Error: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            // Decode the JSON string into an associative PHP array
            $data = json_decode($response, true);
            return $data;
        } else {
            echo "API returned status code: " . $httpCode;
        }
    }
  }
  public function create()
  {
    $tiposDocumento = $this->catalogos('TIPO_DOCUMENTO');
    $generos = $this->catalogos('GENERO');
    $estadosCiviles = $this->catalogos('ESTADO_CIVIL');
    $nacionalidades = $this->catalogos('NACIONALIDAD');
    $municipios = $this->catalogos('MUNICIPIO');
    $profesiones = $this->catalogos('PROFESION');

    $view = __DIR__ . '/../views/personas/create.php';
    require __DIR__ . '/../views/layouts/header_personas.php';
    include $view;
    require __DIR__ . '/../views/layouts/footer.php';
  }
  public function store()
  {
    $json = [
    'id_tipo_documento' => ($_POST['id_tipo_documento'] ?? ''),
    'numero_documento' => ($_POST['numero_documento'] ?? ''),
    'primer_nombre' => ($_POST['primer_nombre'] ?? ''),
    'segundo_nombre' => ($_POST['segundo_nombre'] ?? ''),
    'primer_apellido' => ($_POST['primer_apellido'] ?? ''),
    'segundo_apellido' => ($_POST['segundo_apellido'] ?? ''),
    'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? ''),
    'id_genero' => ($_POST['id_genero'] ?? ''),
    'direccion' => ($_POST['direccion'] ?? ''),
    'id_estado_civil' => ($_POST['id_estado_civil'] ?? ''),
    'id_nacionalidad' => ($_POST['id_nacionalidad'] ?? ''),
    'id_profesion' => ($_POST['id_profesion'] ?? ''),
    'id_municipio' => ($_POST['id_municipio'] ?? ''),
    'correo' => ($_POST['correo'] ?? ''),
    'telefono_personal' => ($_POST['telefono_personal'] ?? ''),
    'telefono_casa' => ($_POST['telefono_casa'] ?? '')
    ];
    if (!filter_var($json['correo'], FILTER_VALIDATE_EMAIL)) {
      header('Location: index.php?error=correo');
      return;
    }
    $ch = curl_init(self::$url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($json),
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json', 
        'Accept: application/json',
        'User-Agent: personas_test/1.0' // Many APIs require a User-Agent header
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        echo "cURL Error: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 201) {
            header('Location: index.php?ok=creado');
        } else {
            echo "API returned status code: " . $httpCode;
        }
    }
  }
  public function edit()
  {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
      http_response_code(400);
      echo 'ID inválido';
      return;
    }
    $u =$this->persona($id);

    if (!$u) {
      http_response_code(404);
      echo 'No encontrado';
      return;
    }

    $tiposDocumento = $this->catalogos('TIPO_DOCUMENTO');
    $generos = $this->catalogos('GENERO');
    $estadosCiviles = $this->catalogos('ESTADO_CIVIL');
    $nacionalidades = $this->catalogos('NACIONALIDAD');
    $municipios = $this->catalogos('MUNICIPIO');
    $profesiones = $this->catalogos('PROFESION');

    $view = __DIR__ . '/../views/personas/edit.php';
    $persona = $u;
    require __DIR__ . '/../views/layouts/header_personas.php';
    include $view;
    require __DIR__ . '/../views/layouts/footer.php';
  }
  public function update()
  {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      header('Location: index.php?error=id');
      return;
    }
     $json = [
    'id_tipo_documento' => ($_POST['id_tipo_documento'] ?? ''),
    'numero_documento' => ($_POST['numero_documento'] ?? ''),
    'primer_nombre' => ($_POST['primer_nombre'] ?? ''),
    'segundo_nombre' => ($_POST['segundo_nombre'] ?? ''),
    'primer_apellido' => ($_POST['primer_apellido'] ?? ''),
    'segundo_apellido' => ($_POST['segundo_apellido'] ?? ''),
    'fecha_nacimiento' => ($_POST['fecha_nacimiento'] ?? ''),
    'id_genero' => ($_POST['id_genero'] ?? ''),
    'direccion' => ($_POST['direccion'] ?? ''),
    'id_estado_civil' => ($_POST['id_estado_civil'] ?? ''),
    'id_nacionalidad' => ($_POST['id_nacionalidad'] ?? ''),
    'id_profesion' => ($_POST['id_profesion'] ?? ''),
    'id_municipio' => ($_POST['id_municipio'] ?? ''),
    'correo' => ($_POST['correo'] ?? ''),
    'telefono_personal' => ($_POST['telefono_personal'] ?? ''),
    'telefono_casa' => ($_POST['telefono_casa'] ?? '')
    ];
    if (!filter_var($json['correo'], FILTER_VALIDATE_EMAIL)) {
      header('Location: index.php?error=correo');
      return;
    }
    $url = self::$url."?id=". $id;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => json_encode($json),
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json', 
        'Accept: application/json',
        'User-Agent: personas_test/1.0' // Many APIs require a User-Agent header
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        echo "cURL Error: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            header('Location: index.php?ok=actualizado');
        } else {
            echo "API returned status code: " . $httpCode;
        }
    }
    
  }
  public function destroy()
  {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      header('Location: index.php?error=id');
      return;
    }
    $url = self::$url."?id=". $id;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_CUSTOMREQUEST => "DELETE",
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json', 
        'Accept: application/json',
        'User-Agent: personas_test/1.0' // Many APIs require a User-Agent header
      ],
      CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        echo "cURL Error: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            header('Location: index.php?ok=actualizado');
        } else {
            echo "API returned status code: " . $httpCode;
        }
    }
    header('Location: index.php?ok=eliminado');
  }
  public function pdf()
  {
    ob_start();
    $pdo = Database::pdo();
    require_once __DIR__ . '/../libraries/FPDF/fpdf.php';
    $st = $pdo->query("SELECT id,nombres,apellidos,correo,telefono,tipo_persona FROM personas ORDER BY id ASC");
    $rows = $st->fetchAll();
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, mb_convert_encoding('Reporte de Personas (MVC)', 'ISO-8859-1'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, mb_convert_encoding('Generado: ', 'ISO-8859-1') . date('Y-m-d H:i'), 0, 1, 'C');
    $pdf->Ln(2);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $enc = ['ID', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Tipo de Persona'];
    $w = [10, 50, 50, 35, 20, 30];
    foreach ($enc as $i => $h) {
      $pdf->Cell($w[$i], 8, mb_convert_encoding($h, 'ISO-8859-1'), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $r) {
      $pdf->Cell($w[0], 7, $r['id'], 1, 0, 'C');
      $pdf->Cell($w[1], 7, mb_convert_encoding($r['nombres'], 'ISO-8859-1'), 1);
      $pdf->Cell($w[2], 7, mb_convert_encoding($r['apellidos'], 'ISO-8859-1'), 1);
      $pdf->Cell($w[3], 7, mb_convert_encoding($r['correo'], 'ISO-8859-1'), 1);
      $pdf->Cell($w[4], 7, mb_convert_encoding($r['telefono'], 'ISO-8859-1'), 1);
      $pdf->Cell($w[5], 7, mb_convert_encoding($r['tipo_persona'], 'ISO-8859-1'), 1, 1);
    }
    $pdf->Output('I', 'personas_mvc.pdf');
    ob_end_flush();
  }
}
