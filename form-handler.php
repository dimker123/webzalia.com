<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$to = 'info@webzalia.com';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    $data = $_POST;
}

// Campos esperados
$nombre   = isset($data['nombre'])   ? strip_tags(trim($data['nombre']))   : '';
$telefono = isset($data['telefono']) ? strip_tags(trim($data['telefono'])) : '';
$email    = isset($data['email'])    ? strip_tags(trim($data['email']))    : '';
$servicio = isset($data['servicio']) ? strip_tags(trim($data['servicio'])) : '';
$web      = isset($data['web'])      ? strip_tags(trim($data['web']))      : '';
$mensaje  = isset($data['mensaje'])  ? strip_tags(trim($data['mensaje']))  : '';
$subject  = isset($data['subject'])  ? strip_tags(trim($data['subject']))  : 'Nuevo formulario - Webzalia';

// Validación básica
if (empty($nombre) || empty($telefono) || empty($email)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

// Construir cuerpo del email
$body  = "Has recibido un nuevo mensaje desde el formulario de Webzalia.\n";
$body .= "==========================================================\n\n";
$body .= "Nombre:    $nombre\n";
$body .= "Teléfono:  $telefono\n";
$body .= "Email:     $email\n";
if ($servicio) $body .= "Servicio:  $servicio\n";
if ($web)      $body .= "Web:       $web\n";
if ($mensaje)  $body .= "\nMensaje:\n$mensaje\n";
$body .= "\n==========================================================\n";
$body .= "Enviado desde: " . ($_SERVER['HTTP_REFERER'] ?? 'desconocido') . "\n";
$body .= "Fecha: " . date('d/m/Y H:i') . "\n";

$headers  = "From: noreply@webzalia.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado correctamente!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje. Por favor, inténtalo de nuevo.']);
}
