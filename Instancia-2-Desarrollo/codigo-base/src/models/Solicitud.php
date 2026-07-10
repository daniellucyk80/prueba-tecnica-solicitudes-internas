<?php
require_once __DIR__ . '/Database.php';

class Solicitud {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll($filters = []) {
    $sql = 'SELECT * FROM solicitudes WHERE 1=1';
    $params = [];

    if (!empty($filters['estado'])) {
        $sql .= ' AND estado = ?';
        $params[] = $filters['estado'];
    }
    if (!empty($filters['prioridad'])) {
        $sql .= ' AND prioridad = ?';
        $params[] = $filters['prioridad'];
    }

    $sql .= " ORDER BY FIELD(prioridad, 'alta', 'media', 'baja'), created_at DESC";

    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->query('SELECT * FROM solicitudes WHERE id = ?', [$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = 'INSERT INTO solicitudes (titulo, descripcion, area_solicitante, area_destino, prioridad, estado)
                VALUES (?, ?, ?, ?, ?, ?)';
        $params = [
            $data['titulo'],
            $data['descripcion'],
            $data['area_solicitante'],
            $data['area_destino'],
            $data['prioridad'] ?? 'media',
            'pendiente'
        ];
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
   
    $actual = $this->getById($id);
    if (!$actual) {
        return ['ok' => false, 'error' => 'Solicitud no encontrada', 'status' => 404];
    }
    if ($actual['estado'] !== 'pendiente') {
        return ['ok' => false, 'error' => 'Solo se puede editar una solicitud pendiente. Estado actual: ' . $actual['estado'], 'status' => 409];
    }

    $sql = 'UPDATE solicitudes SET titulo = ?, descripcion = ?, area_solicitante = ?,
            area_destino = ?, prioridad = ? WHERE id = ?';
    $params = [
        $data['titulo'],
        $data['descripcion'],
        $data['area_solicitante'],
        $data['area_destino'],
        $data['prioridad'],
        $id
    ];
    $this->db->query($sql, $params);
    return ['ok' => true];
    }

    public function delete($id) {
        $actual = $this->getById($id);
        
        if (!$actual) {
            return ['ok' => false, 'error' => 'Solicitud no encontrada', 'status' => 404];
        }
        if ($actual['estado'] !== 'pendiente') {
            return ['ok' => false, 'error' => 'Solo se puede eliminar una solicitud pendiente. Estado actual: ' . $actual['estado'], 'status' => 409];
        }
        $this->db->query('DELETE FROM solicitudes WHERE id = ?', [$id]);
        return ['ok' => true];
    }

    public function cambiarEstado($id, $nuevoEstado) {
        $actual = $this->getById($id);
        if (!$actual) {
            return ['ok' => false, 'error' => 'Solicitud no encontrada', 'status' => 404];
        }

        $transiciones = [
            'pendiente'  => ['en_proceso'],
            'en_proceso' => ['resuelta', 'rechazada'],
            'resuelta'   => [],
            'rechazada'  => []
        ];

        $estadoActual = $actual['estado'];

        if (!in_array($nuevoEstado, $transiciones[$estadoActual])) {
            return ['ok' => false, 'error' => "No se puede pasar de '$estadoActual' a '$nuevoEstado'", 'status' => 409];
        }
        $this->db->query('UPDATE solicitudes SET estado = ? WHERE id = ?', [$nuevoEstado, $id]);
        return ['ok' => true];
    }

    public function validate($data) {
        $errors = [];
        if (empty($data['titulo'])) {
            $errors[] = 'El título es obligatorio';
        }
        if (empty($data['descripcion'])) {
            $errors[] = 'La descripción es obligatoria';
        }
        if (empty($data['area_solicitante'])) {
            $errors[] = 'El área solicitante es obligatoria';
        }
        if (empty($data['area_destino'])) {
            $errors[] = 'El área destinataria es obligatoria';
        }
        if (empty($data['prioridad'])) {
            $errors[] = 'La prioridad es obligatoria';
        }
        return $errors;
    }
}
?>
