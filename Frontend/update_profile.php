<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../Backend/models/Database.php';

if (!isset($_SESSION['user']['id'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesion'
    ]);

    exit;
}

// obtener datos json
$input = json_decode(file_get_contents('php://input'), true);

$field = $input['field'] ?? null;
$value = trim($input['value'] ?? '');

if (!$field) {

    echo json_encode([
        'success' => false,
        'message' => 'Campo no enviado'
    ]);

    exit;
}

// campos permitidos
$validFields = [
    'name',
    'description'
];

if (!in_array($field, $validFields)) {

    echo json_encode([
        'success' => false,
        'message' => 'Campo no permitido'
    ]);

    exit;
}

try {

    $db = Database::getInstance()->getConnection();

    // actualizar usuario
    $query = "
        UPDATE users
        SET {$field} = ?
        WHERE id = ?
    ";

    $stmt = $db->prepare($query);

    $stmt->execute([
        $value,
        $_SESSION['user']['id']
    ]);

    // actualizar sesion
    if ($field === 'name') {
        $_SESSION['user']['name'] = $value;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Perfil actualizado correctamente'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'error' => $e->getMessage()
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Ocurrio un error inesperado',
        'error' => $e->getMessage()
    ]);

}