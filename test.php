<?php
/**
 * New Zeit - https://newzeit.com.ar
 * Contacto: gonzalo.tapie@newzeit.com.ar
 */
require_once 'freemailer.php';

$config = json_decode(file_get_contents('config.json'), true);

if (!$config) {
    die("Error: No se pudo cargar config.json\n");
}

$mail = new FreeMailer();

if (!empty($config['smtp']['host'])) {
    $mail->IsSMTP();
    $mail->Host($config['smtp']['host']);
    $mail->Port((int)$config['smtp']['port']);
    $mail->SMTPSecure($config['smtp']['secure']);
    $mail->SMTPAuth($config['smtp']['auth']);
    $mail->Username($config['smtp']['username']);
    $mail->Password($config['smtp']['password']);
    $mail->SMTPDebug((int)$config['settings']['debug']);
}

$mail->CharSet = $config['settings']['charset'];
$mail->From($config['sender']['email'], $config['sender']['name']);

foreach ($config['recipients']['to'] as $r) {
    $mail->AddAddress($r['email'], $r['name']);
}
foreach ($config['recipients']['cc'] as $r) {
    $mail->AddCC($r['email'], $r['name']);
}
foreach ($config['recipients']['bcc'] as $r) {
    $mail->AddBCC($r['email'], $r['name']);
}

$mail->Subject($config['message']['subject']);
$mail->IsHTML($config['message']['is_html']);
$mail->Body($config['message']['body']);
$mail->AltBody($config['message']['alt_body']);

foreach ($config['attachments'] as $a) {
    $mail->AddAttachment($a['path'], $a['name']);
}

try {
    $mail->Send();
    echo "Correo enviado correctamente.\n";
} catch (Exception $e) {
    echo "Error al enviar: " . $e->getMessage() . "\n";
}

echo "\nNew Zeit - https://newzeit.com.ar\n";
