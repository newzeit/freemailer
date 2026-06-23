<?php
/**
 * Editor de configuración para FreeMailer
 * Permite leer, editar y validar archivos de configuración JSON
 */

class ConfigEditor {
    private $configFile;
    private $configData;

    public function __construct($configFile = 'config.json') {
        $this->configFile = $configFile;
        $this->loadConfig();
    }

    private function loadConfig() {
        if (!file_exists($this->configFile)) {
            throw new Exception("Archivo de configuración no encontrado: " . $this->configFile);
        }

        $jsonContent = file_get_contents($this->configFile);
        $this->configData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Formato JSON inválido: " . json_last_error_msg());
        }
    }

    public function getConfig() {
        return $this->configData;
    }

    public function updateConfig($newConfig) {
        if (!is_array($newConfig)) {
            throw new Exception("La configuración debe ser un array");
        }

        $this->configData = $newConfig;
        $this->saveConfig();
    }

    public function updateValue($path, $value) {
        $keys = explode('.', $path);
        $config = &$this->configData;

        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                throw new Exception("Ruta no encontrada: " . $path);
            }
            $config = &$config[$key];
        }

        $config = $value;
        $this->saveConfig();
    }

    public function addSection($sectionName, $data) {
        if (isset($this->configData[$sectionName])) {
            throw new Exception("La sección ya existe: " . $sectionName);
        }

        $this->configData[$sectionName] = $data;
        $this->saveConfig();
    }

    public function removeSection($sectionName) {
        if (!isset($this->configData[$sectionName])) {
            throw new Exception("Sección no encontrada: " . $sectionName);
        }

        unset($this->configData[$sectionName]);
        $this->saveConfig();
    }

    private function saveConfig() {
        $jsonContent = json_encode($this->configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->configFile, $jsonContent);
    }

    public function validateConfig() {
        $requiredSections = ['smtp', 'sender', 'recipients', 'message', 'settings'];

        foreach ($requiredSections as $section) {
            if (!isset($this->configData[$section])) {
                throw new Exception("Falta sección requerida: " . $section);
            }
        }

        if (!isset($this->configData['smtp']['host'])) {
            throw new Exception("El servidor SMTP es requerido");
        }

        if (!isset($this->configData['sender']['email'])) {
            throw new Exception("El email del remitente es requerido");
        }

        if (empty($this->configData['recipients']['to']) && empty($this->configData['recipients']['bcc'])) {
            throw new Exception("Se requiere al menos un destinatario (para o bcc)");
        }

        if (!isset($this->configData['message']['subject'])) {
            throw new Exception("El asunto del mensaje es requerido");
        }

        return true;
    }

    public function exportConfig() {
        return json_encode($this->configData, JSON_PRETTY_PRINT);
    }

    public function importConfig($jsonContent) {
        $newConfig = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Formato JSON inválido: " . json_last_error_msg());
        }

        $this->configData = $newConfig;
        $this->saveConfig();
    }
}

// Ejemplo de uso
if (php_sapi_name() == 'cli') {
    $editor = new ConfigEditor();

    try {
        $editor->validateConfig();
        echo "¡La configuración es válida!" . PHP_EOL;

        $config = $editor->getConfig();
        echo "Configuración actual:" . PHP_EOL;
        print_r($config);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
?>