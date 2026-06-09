<?php
require_once __DIR__ . '/../app/controllers/PersonaController.php';
$controller = new PersonaController();
$action = $_GET['action'] ?? 'index';
switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'update':
        $controller->update();
        break;
    case 'destroy':
        $controller->destroy();
        break;
    case 'pdf':
        $controller->pdf();
        break;
    default:
        http_response_code(404);
        echo 'Acción no encontrada';
}
