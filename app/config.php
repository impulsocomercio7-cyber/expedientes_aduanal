<?php
declare(strict_types=1);
// En Coolify los valores se configuran mediante variables de entorno.
// En instalaciones tradicionales se pueden editar los valores por defecto.
function app_env(string $key, string $default): string {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', app_env('DB_HOST', '127.0.0.1'));
define('DB_NAME', app_env('DB_NAME', 'expedientes_aduanal'));
define('DB_USER', app_env('DB_USER', 'expedientes_app'));
define('DB_PASS', app_env('DB_PASS', 'CAMBIAR_PASSWORD_MYSQL'));
define('APP_NAME', app_env('APP_NAME', 'Expedientes Aduanales'));
define('BASE_URL', app_env('BASE_URL', ''));
define('STORAGE_PATH', app_env('STORAGE_PATH', dirname(__DIR__) . '/storage'));
define('MAX_UPLOAD_BYTES', (int) app_env('MAX_UPLOAD_BYTES', (string)(10 * 1024 * 1024)));
define('SESSION_NAME', app_env('SESSION_NAME', 'expedientes_aduanal_session'));
