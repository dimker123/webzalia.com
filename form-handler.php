<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/smtp-config.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Parse input
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$nombre   = isset($data['nombre'])   ? strip_tags(trim($data['nombre']))   : '';
$telefono = isset($data['telefono']) ? strip_tags(trim($data['telefono'])) : '';
$email    = isset($data['email'])    ? strip_tags(trim($data['email']))    : '';
$servicio = isset($data['servicio']) ? strip_tags(trim($data['servicio'])) : '';
$web      = isset($data['web'])      ? strip_tags(trim($data['web']))      : '';
$mensaje  = isset($data['mensaje'])  ? strip_tags(trim($data['mensaje']))  : '';
$subject  = isset($data['subject'])  ? strip_tags(trim($data['subject']))  : 'Nuevo formulario - Webzalia';

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

// Build email body
$body  = "Has recibido un nuevo mensaje desde el formulario de Webzalia.\n";
$body .= str_repeat('=', 58) . "\n\n";
$body .= "Nombre:    $nombre\n";
$body .= "Teléfono:  $telefono\n";
$body .= "Email:     $email\n";
if ($servicio) $body .= "Servicio:  $servicio\n";
if ($web)      $body .= "Web:       $web\n";
if ($mensaje)  $body .= "\nMensaje:\n$mensaje\n";
$body .= "\n" . str_repeat('=', 58) . "\n";
$body .= "Fecha: " . date('d/m/Y H:i') . "\n";

// Send via SMTP
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress(MAIL_TO);
    $mail->addReplyTo($email, $nombre);

    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado correctamente!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar. Inténtalo de nuevo.']);
}
