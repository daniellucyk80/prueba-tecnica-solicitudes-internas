<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../models/Solicitud.php';

class SolicitudAPI {
    private $solicitud;

    public function __construct() {
        $this->solicitud = new Solicitud();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        try {
            switch ($method) {
                case 'GET':
                    if ($id) {
                        $this->getOne($id);
                    } else {
                        $this->getAll();
                    }
                    break;
                case 'POST':
                    $this->create();
                    break;
                case 'PUT':
                    if ($id) {
                        $this->update($id);
                    } else {
                        $this->sendError('ID requerido para actualizar', 400);
                    }
                    break;
                case 'PATCH':
                    if ($id && isset($_GET['action']) && $_GET['action'] === 'cambiar_estado') {
                        $this->cambiarEstado($id);
                    } else {
                        $this->sendError('Parámetros inválidos para PATCH', 400);
                    }
                    break;
                case 'DELETE':
                    if ($id) {
                        $this->delete($id);
                    } else {
                        $this->sendError('ID requerido para eliminar', 400);
                    }
                    break;
                default:
                    $this->sendError('Método no permitido', 405);
            }
        } catch (Exception $e) {
            $this->sendError('Error del servidor: ' . $e->getMessage(), 500);

        }
    }

    private function getAll() {
        $filters = [
            'estado' => $_GET['estado'] ?? null,
            'prioridad' => $_GET['prioridad'] ?? null
        ];
        $data = $this->solicitud->getAll($filters);
        $this->sendResponse($data);
    }

    private function getOne($id) {
        $item = $this->solicitud->getById($id);
        if ($item) {
            $this->sendResponse($item);
        } else {
            $this->sendError('Solicitud no encontrada', 404);
        }
    }

    private function create() {
        $data = $this->getInputData();
        $errors = $this->solicitud->validate($data);
        if (!empty($errors)) {
            $this->sendError($errors, 400);
            return;
        }
        $id = $this->solicitud->create($data);
        $this->sendResponse(['id' => $id, 'message' => 'Solicitud creada'], 201);
    }

    private function update($id) {
        $data = $this->getInputData();
        if (!$this->solicitud->getById($id)) {
            $this->sendError('Solicitud no encontrada', 404);
            return;
        }
        $errors = $this->solicitud->validate($data);
        if (!empty($errors)) {
            $this->sendError($errors, 400);
            return;
        }
        $ok = $this->solicitud->update($id, $data);
        if ($ok) {
            $this->sendResponse(['message' => 'Solicitud actualizada']);
        } else {
            $this->sendError('Error al actualizar', 500);
        }
    }

    private function delete($id) {
        $ok = $this->solicitud->delete($id);
        if ($ok) {
            $this->sendResponse(['message' => 'Solicitud eliminada']);
        } else {
            $this->sendError('Solicitud no encontrada', 404);
        }
    }

    private function cambiarEstado($id) {
        $data = $this->getInputData();
        if (empty($data['estado'])) {
            $this->sendError('Estado requerido', 400);
            return;
        }
        $ok = $this->solicitud->cambiarEstado($id, $data['estado']);
        if ($ok) {
            $this->sendResponse(['message' => 'Estado actualizado']);
        } else {
            $this->sendError('No se pudo cambiar el estado', 400);
        }
    }

    private function getInputData() {
        if (!empty($_POST)) {
            return $_POST;
        }
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode(['success' => true, 'data' => $data]);
    }

    private function sendError($message, $status = 400) {
        http_response_code($status);
        echo json_encode(['success' => false, 'error' => $message]);
    }
}

$api = new SolicitudAPI();
$api->handleRequest();
?>
