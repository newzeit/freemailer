# FreeMailer

Librería ligera para envío de correos electrónicos compatible con PHP 5.4+.

## Estructura

| Archivo | Descripción |
|---------|-------------|
| `freemailer.php` | Librería principal de envío de correos |
| `config.json` | Configuración (SMTP, remitente, destinatarios, mensaje, adjuntos) |
| `config.php` | Editor web con PHP (formulario + editor JSON) |
| `config_editor.html` | Editor web standalone (solo HTML/CSS/JS, sin servidor) |
| `test.php` | Script de prueba que envía un correo usando `config.json` |

## Requisitos

- PHP 5.4 o superior (para el editor PHP y envío)
- Extensión OpenSSL (para SMTP con TLS/SSL)
- Navegador web (para el editor HTML standalone)

## Instalación

Incluye la librería en tu proyecto:

```php
require_once 'freemailer.php';
```

## Edición de Configuración

### Editor HTML (standalone)

Abre `config_editor.html` directamente en el navegador. No requiere PHP ni servidor. Permite:

- Editar todos los campos de `config.json` (SMTP, remitente, destinatarios, mensaje, adjuntos, ajustes)
- Importar un archivo `.json` existente
- Vista previa del JSON resultante
- Copiar al portapapeles o descargar como `config.json`

### Editor PHP

Accede a `config.php` desde un servidor con PHP. Ofrece:

- Formulario visual con pestañas (Formulario / JSON)
- Guardado directo en `config.json`
- Validación de JSON

## Uso Básico

### Envío con mail()

```php
$mail = new FreeMailer();
$mail->From('remitente@ejemplo.com', 'Nombre Remitente');
$mail->AddAddress('destinatario@ejemplo.com', 'Nombre Destinatario');
$mail->Subject('Asunto del correo');
$mail->Body('Contenido del mensaje');

$mail->Send();
```

### Envío con SMTP

```php
$mail = new FreeMailer();
$mail->IsSMTP();
$mail->Host('smtp.servidor.com');
$mail->Port(587);
$mail->SMTPAuth(true);
$mail->Username('usuario@ejemplo.com');
$mail->Password('contraseña');
$mail->SMTPSecure('tls');

$mail->From('remitente@ejemplo.com', 'Nombre Remitente');
$mail->AddAddress('destinatario@ejemplo.com');
$mail->Subject('Asunto');
$mail->Body('Mensaje');

$mail->Send();
```

### Envío desde config.json

```php
require_once 'freemailer.php';

$config = json_decode(file_get_contents('config.json'), true);

$mail = new FreeMailer();
$mail->IsSMTP();
$mail->Host($config['smtp']['host']);
$mail->Port((int)$config['smtp']['port']);
$mail->SMTPSecure($config['smtp']['secure']);
$mail->SMTPAuth($config['smtp']['auth']);
$mail->Username($config['smtp']['username']);
$mail->Password($config['smtp']['password']);
$mail->SMTPDebug((int)$config['settings']['debug']);

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

$mail->Send();
```

### Prueba rápida

Ejecuta `test.php` en un servidor con PHP para enviar un correo según la configuración actual de `config.json`.

## Métodos Disponibles

### Configuración del Remitente

| Método | Descripción |
|--------|-------------|
| `From($address, $name)` | Establece el remitente |
| `Sender($address)` | Establece el Sender (paraReturn-Path) |

### Destinatarios

| Método | Descripción |
|--------|-------------|
| `AddAddress($address, $name)` | Añade un destinatario |
| `AddCC($address, $name)` | Añade copia |
| `AddBCC($address, $name)` | Añade copia oculta |
| `AddReplyTo($address, $name)` | Establece reply-to |

### Contenido

| Método | Descripción |
|--------|-------------|
| `Subject($subject)` | Establece el asunto |
| `Body($body)` | Establece el cuerpo del mensaje |
| `AltBody($altbody)` | Establece versión texto plano |
| `IsHTML($ishtml)` | Activa/desactiva modo HTML |

### Adjuntos

| Método | Descripción |
|--------|-------------|
| `AddAttachment($path, $name)` | Añade un adjunto |

### Configuración SMTP

| Método | Descripción |
|--------|-------------|
| `IsSMTP()` | Usa SMTP en lugar de mail() |
| `IsMail()` | Usa la función mail() |
| `Host($host)` | Servidor SMTP |
| `Port($port)` | Puerto (default: 25) |
| `SMTPAuth($auth)` | Habilita autenticación |
| `Username($username)` | Usuario SMTP |
| `Password($password)` | Contraseña SMTP |
| `SMTPSecure($secure)` | Protocolo: tls o ssl |
| `SMTPDebug($debug)` | Activa depuración (0-2) |

### Utilidades

| Método | Descripción |
|--------|-------------|
| `ClearAddresses()` | Limpia destinatarios |
| `ClearCCs()` | Limpia CC |
| `ClearBCCs()` | Limpia BCC |
| `ClearReplyTos()` | Limpia Reply-To |
| `ClearAttachments()` | Limpia adjuntos |
| `ClearAll()` | Limpia todo |
| `GetError()` | Obtiene último error |

## Ejemplos

### Correo HTML con adjuntos

```php
$mail = new FreeMailer();
$mail->IsSMTP();
$mail->Host('smtp.gmail.com');
$mail->Port(587);
$mail->SMTPAuth(true);
$mail->Username('@gmail.com');
$mail->Password('password');
$mail->SMTPSecure('tls');

$mail->From('correo@ejemplo.com', 'Mi Aplicación');
$mail->AddAddress('destino@ejemplo.com');
$mail->AddReplyTo('soporte@ejemplo.com');

$mail->Subject('Correo con formato HTML');
$mail->IsHTML(true);
$mail->Body('<h1>Hola</h1><p>Este es un mensaje <b>HTML</b></p>');
$mail->AltBody('Este es el mensaje en texto plano');

$mail->AddAttachment('/ruta/archivo.pdf', 'documento.pdf');

$mail->Send();
```

### Múltiples destinatarios

```php
$mail = new FreeMailer();
$mail->From('remitente@ejemplo.com');

$mail->AddAddress('uno@ejemplo.com', 'Usuario Uno');
$mail->AddAddress('dos@ejemplo.com');
$mail->AddCC('copia@ejemplo.com');
$mail->AddBCC('oculto@ejemplo.com');

$mail->Subject('Asunto');
$mail->Body('Contenido');

$mail->Send();
```

## Códigos de error SMTP

| Código | Significado |
|--------|-------------|
| 220 | Servicio listo |
| 250 | Comando exitoso |
| 334 | Autenticación solicitada |
| 354 | Datos aceptados |

## Notas

- Para Gmail, usa puerto 587 con TLS o puerto 465 con SSL
- Hotmail/Outlook usa smtp.live.com con puerto 587 y TLS
- Asegúrate de que tu servidor permita conexiones SMTP salientes