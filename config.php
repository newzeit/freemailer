<?php
/**
 * New Zeit - https://newzeit.com.ar
 * Contacto: gonzalo.tapie@newzeit.com.ar
 */
require_once 'config_editor.php';

$editor = new ConfigEditor();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $config = json_decode(file_get_contents('config.json'), true);

    $config['smtp']['host'] = $_POST['smtp_host'] ?? '';
    $config['smtp']['port'] = (int)($_POST['smtp_port'] ?? 25);
    $config['smtp']['secure'] = $_POST['smtp_secure'] ?? '';
    $config['smtp']['auth'] = isset($_POST['smtp_auth']);
    $config['smtp']['username'] = $_POST['smtp_username'] ?? '';
    $config['smtp']['password'] = $_POST['smtp_password'] ?? '';

    $config['sender']['email'] = $_POST['sender_email'] ?? '';
    $config['sender']['name'] = $_POST['sender_name'] ?? '';

    $config['message']['subject'] = $_POST['message_subject'] ?? '';
    $config['message']['body'] = $_POST['message_body'] ?? '';
    $config['message']['alt_body'] = $_POST['message_alt_body'] ?? '';
    $config['message']['is_html'] = isset($_POST['message_is_html']);

    $config['settings']['debug'] = (int)($_POST['settings_debug'] ?? 0);
    $config['settings']['charset'] = $_POST['settings_charset'] ?? 'UTF-8';

    $config['recipients']['to'] = [];
    $config['recipients']['cc'] = [];
    $config['recipients']['bcc'] = [];
    if (isset($_POST['to_email'])) {
        foreach ($_POST['to_email'] as $i => $email) {
            $email = trim($email);
            if ($email !== '') {
                $config['recipients']['to'][] = [
                    'email' => $email,
                    'name' => trim($_POST['to_name'][$i] ?? ''),
                ];
            }
        }
    }
    if (isset($_POST['cc_email'])) {
        foreach ($_POST['cc_email'] as $i => $email) {
            $email = trim($email);
            if ($email !== '') {
                $config['recipients']['cc'][] = [
                    'email' => $email,
                    'name' => trim($_POST['cc_name'][$i] ?? ''),
                ];
            }
        }
    }
    if (isset($_POST['bcc_email'])) {
        foreach ($_POST['bcc_email'] as $i => $email) {
            $email = trim($email);
            if ($email !== '') {
                $config['recipients']['bcc'][] = [
                    'email' => $email,
                    'name' => trim($_POST['bcc_name'][$i] ?? ''),
                ];
            }
        }
    }

    $config['attachments'] = [];
    if (isset($_POST['att_path'])) {
        foreach ($_POST['att_path'] as $i => $path) {
            $path = trim($path);
            if ($path !== '') {
                $config['attachments'][] = [
                    'path' => $path,
                    'name' => trim($_POST['att_name'][$i] ?? ''),
                ];
            }
        }
    }

    file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: config.php?saved=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_json') {
    $json = $_POST['json_content'] ?? '{}';
    $decoded = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        file_put_contents('config.json', json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: config.php?saved=1');
        exit;
    } else {
        header('Location: config.php?error=json_invalid');
        exit;
    }
}

$config = $editor->getConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Configuracion - FreeMailer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .section { margin-bottom: 25px; border: 1px solid #ddd; border-radius: 5px; padding: 20px; background: #f9f9f9; }
        .section h2 { margin-top: 0; color: #2e6da4; }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; color: #555; }
        input[type="text"], input[type="number"], input[type="password"], textarea, select {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        textarea { min-height: 80px; font-family: monospace; }
        .row { display: flex; gap: 15px; align-items: flex-end; }
        .row .form-group { flex: 1; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .checkbox-group label { display: inline; margin-bottom: 0; }
        button { background: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #45a049; }
        button.secondary { background: #6c757d; }
        button.secondary:hover { background: #5a6268; }
        button.danger { background: #dc3545; padding: 6px 12px; font-size: 12px; }
        button.danger:hover { background: #c82333; }
        button.add { background: #17a2b8; padding: 6px 14px; font-size: 13px; }
        button.add:hover { background: #138496; }
        .msg-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .msg-error { color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; border: 1px solid transparent; border-bottom: none; margin-bottom: -2px; }
        .tab.active { background: #fff; border-color: #dee2e6; border-bottom: 2px solid #fff; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        textarea.json-editor { min-height: 400px; }
        .item-row { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; background: #fff; padding: 8px 10px; border: 1px solid #e0e0e0; border-radius: 4px; }
        .item-row input { flex: 1; }
        .item-row .btn-remove { flex: 0 0 auto; }
        .items-list { margin-bottom: 10px; }
        .items-header { font-weight: bold; color: #555; margin-bottom: 8px; }
        .branding { text-align: center; padding: 30px 0 10px; margin-top: 20px; border-top: 1px solid #eee; }
        .branding a { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #888; font-size: 13px; }
        .branding a:hover { color: #333; }
        .branding img { height: 26px; opacity: 0.5; }
        .branding a:hover img { opacity: 1; }
    </style>
</head>
<body>
<div class="container">
    <h1>Editor de Configuracion - FreeMailer</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="msg-success">Configuracion guardada correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="msg-error">Error: JSON invalido.</div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" onclick="showTab('form')">Formulario</div>
        <div class="tab" onclick="showTab('json')">JSON</div>
    </div>

    <div id="tab-form" class="tab-content active">
        <form method="post" action="config.php">
            <input type="hidden" name="action" value="save">

            <div class="section">
                <h2>SMTP</h2>
                <div class="row">
                    <div class="form-group">
                        <label>Servidor</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($config['smtp']['host']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Puerto</label>
                        <input type="number" name="smtp_port" value="<?php echo (int)$config['smtp']['port']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Seguridad</label>
                        <select name="smtp_secure">
                            <option value="" <?php if ($config['smtp']['secure'] === '') echo 'selected'; ?>>Ninguna</option>
                            <option value="tls" <?php if ($config['smtp']['secure'] === 'tls') echo 'selected'; ?>>TLS</option>
                            <option value="ssl" <?php if ($config['smtp']['secure'] === 'ssl') echo 'selected'; ?>>SSL</option>
                        </select>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="smtp_auth" id="smtp_auth" <?php if ($config['smtp']['auth']) echo 'checked'; ?>>
                    <label for="smtp_auth">Autenticacion</label>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($config['smtp']['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Contrasena</label>
                        <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($config['smtp']['password']); ?>">
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Remitente</h2>
                <div class="row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="sender_email" value="<?php echo htmlspecialchars($config['sender']['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="sender_name" value="<?php echo htmlspecialchars($config['sender']['name']); ?>">
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Destinatarios</h2>

                <div class="items-header">Para</div>
                <div class="items-list" id="list-to">
                    <?php foreach ($config['recipients']['to'] as $r): ?>
                    <div class="item-row">
                        <input type="email" name="to_email[]" placeholder="email@ejemplo.com" value="<?php echo htmlspecialchars($r['email']); ?>">
                        <input type="text" name="to_name[]" placeholder="Nombre" value="<?php echo htmlspecialchars($r['name']); ?>">
                        <button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add" onclick="addRecipient('to')">+ Agregar To</button>

                <br><br>
                <div class="items-header">Copia (CC)</div>
                <div class="items-list" id="list-cc">
                    <?php foreach ($config['recipients']['cc'] as $r): ?>
                    <div class="item-row">
                        <input type="email" name="cc_email[]" placeholder="email@ejemplo.com" value="<?php echo htmlspecialchars($r['email']); ?>">
                        <input type="text" name="cc_name[]" placeholder="Nombre" value="<?php echo htmlspecialchars($r['name']); ?>">
                        <button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add" onclick="addRecipient('cc')">+ Agregar CC</button>

                <br><br>
                <div class="items-header">Copia Oculta (BCC)</div>
                <div class="items-list" id="list-bcc">
                    <?php foreach ($config['recipients']['bcc'] as $r): ?>
                    <div class="item-row">
                        <input type="email" name="bcc_email[]" placeholder="email@ejemplo.com" value="<?php echo htmlspecialchars($r['email']); ?>">
                        <input type="text" name="bcc_name[]" placeholder="Nombre" value="<?php echo htmlspecialchars($r['name']); ?>">
                        <button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add" onclick="addRecipient('bcc')">+ Agregar BCC</button>
            </div>

            <div class="section">
                <h2>Adjuntos</h2>
                <div class="items-list" id="list-attachments">
                    <?php foreach ($config['attachments'] as $a): ?>
                    <div class="item-row">
                        <input type="text" name="att_path[]" placeholder="/ruta/archivo.pdf" value="<?php echo htmlspecialchars($a['path']); ?>">
                        <input type="text" name="att_name[]" placeholder="nombre.pdf" value="<?php echo htmlspecialchars($a['name']); ?>">
                        <button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add" onclick="addAttachment()">+ Agregar adjunto</button>
            </div>

            <div class="section">
                <h2>Mensaje</h2>
                <div class="form-group">
                    <label>Asunto</label>
                    <input type="text" name="message_subject" value="<?php echo htmlspecialchars($config['message']['subject']); ?>">
                </div>
                <div class="form-group">
                    <label>Cuerpo HTML</label>
                    <textarea name="message_body"><?php echo htmlspecialchars($config['message']['body']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Cuerpo texto plano</label>
                    <textarea name="message_alt_body"><?php echo htmlspecialchars($config['message']['alt_body']); ?></textarea>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="message_is_html" id="message_is_html" <?php if ($config['message']['is_html']) echo 'checked'; ?>>
                    <label for="message_is_html">Es HTML</label>
                </div>
            </div>

            <div class="section">
                <h2>Configuracion</h2>
                <div class="row">
                    <div class="form-group">
                        <label>Debug</label>
                        <select name="settings_debug">
                            <option value="0" <?php if ((int)$config['settings']['debug'] === 0) echo 'selected'; ?>>0</option>
                            <option value="1" <?php if ((int)$config['settings']['debug'] === 1) echo 'selected'; ?>>1</option>
                            <option value="2" <?php if ((int)$config['settings']['debug'] === 2) echo 'selected'; ?>>2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Charset</label>
                        <input type="text" name="settings_charset" value="<?php echo htmlspecialchars($config['settings']['charset']); ?>">
                    </div>
                </div>
            </div>

            <button type="submit">Guardar</button>
        </form>
    </div>

    <div id="tab-json" class="tab-content">
        <form method="post" action="config.php">
            <input type="hidden" name="action" value="save_json">
            <div class="form-group">
                <label>JSON</label>
                <textarea name="json_content" class="json-editor"><?php echo htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
            </div>
            <button type="submit">Guardar JSON</button>
        </form>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
    event.target.classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
}

function addRecipient(type) {
    var list = document.getElementById('list-' + type);
    var div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML =
        '<input type="email" name="' + type + '_email[]" placeholder="email@ejemplo.com">' +
        '<input type="text" name="' + type + '_name[]" placeholder="Nombre">' +
        '<button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>';
    list.appendChild(div);
}

function addAttachment() {
    var list = document.getElementById('list-attachments');
    var div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML =
        '<input type="text" name="att_path[]" placeholder="/ruta/archivo.pdf">' +
        '<input type="text" name="att_name[]" placeholder="nombre.pdf">' +
        '<button type="button" class="danger btn-remove" onclick="this.parentElement.remove()">X</button>';
    list.appendChild(div);
}
</script>
<div class="branding">
    <a href="https://newzeit.com.ar" target="_blank">
        <img src="tmp/logo.png" alt="New Zeit">
        <span>New Zeit</span>
    </a>
</div>
</body>
</html>
