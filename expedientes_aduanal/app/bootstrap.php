<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('America/Matamoros');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function verify_csrf(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419); exit('La sesión del formulario expiró. Regrese e inténtelo nuevamente.');
    }
}
function flash(string $type, string $message): void { $_SESSION['_flash'] = ['type'=>$type, 'message'=>$message]; }
function get_flash(): ?array { $f = $_SESSION['_flash'] ?? null; unset($_SESSION['_flash']); return $f; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) redirect('login.php'); }
function is_admin(): bool { return (current_user()['rol'] ?? '') === 'Administrador'; }
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('No tiene permisos para realizar esta acción.'); } }
function audit(string $action, ?int $clientId = null, array $details = []): void {
    $u = current_user();
    $stmt = db()->prepare('INSERT INTO bitacora (usuario_id, cliente_id, accion, detalle, ip, creado_en) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$u['id'] ?? null, $clientId, $action, json_encode($details, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
}
function recompute_client_status(int $clientId): void {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT estatus_manual FROM clientes WHERE id=? AND deleted_at IS NULL');
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    if (!$client) return;
    if ($client['estatus_manual'] === 'Rechazado') {
        $status = 'Rechazado';
    } else {
        $q = $pdo->prepare("
            SELECT COUNT(*) total,
                   SUM(CASE WHEN dc.id IS NOT NULL THEN 1 ELSE 0 END) recibidos
            FROM tipos_documento td
            LEFT JOIN documentos_cliente dc
              ON dc.id = (
                 SELECT d2.id FROM documentos_cliente d2
                 WHERE d2.cliente_id = ? AND d2.tipo_documento_id = td.id AND d2.es_version_actual = 1
                 ORDER BY d2.id DESC LIMIT 1
              )
            WHERE td.activo=1 AND td.bloqueante=1
              AND (td.aplica_fisica=1 OR td.aplica_moral=1)
              AND ((SELECT tipo_persona FROM clientes WHERE id=?)='Fisica' AND td.aplica_fisica=1
                OR (SELECT tipo_persona FROM clientes WHERE id=?)='Moral' AND td.aplica_moral=1)
        ");
        $q->execute([$clientId, $clientId, $clientId]);
        $r = $q->fetch() ?: ['total'=>0,'recibidos'=>0];
        $status = ((int)$r['total'] > 0 && (int)$r['total'] === (int)$r['recibidos']) ? 'Acreditado' : 'En espera';
    }
    $upd = $pdo->prepare('UPDATE clientes SET estatus=?, actualizado_en=NOW() WHERE id=?');
    $upd->execute([$status, $clientId]);
}
function document_summary(int $clientId): array {
    $pdo = db();
    $stmt = $pdo->prepare("
      SELECT COUNT(*) total,
       SUM(CASE WHEN dc.id IS NOT NULL THEN 1 ELSE 0 END) recibidos
      FROM tipos_documento td
      JOIN clientes c ON c.id=?
      LEFT JOIN documentos_cliente dc ON dc.id=(
        SELECT d2.id FROM documentos_cliente d2
        WHERE d2.cliente_id=c.id AND d2.tipo_documento_id=td.id AND d2.es_version_actual=1
        ORDER BY d2.id DESC LIMIT 1
      )
      WHERE td.activo=1 AND td.bloqueante=1
        AND ((c.tipo_persona='Fisica' AND td.aplica_fisica=1) OR (c.tipo_persona='Moral' AND td.aplica_moral=1))
    ");
    $stmt->execute([$clientId]); $r = $stmt->fetch() ?: ['total'=>0,'recibidos'=>0];
    $total=(int)$r['total']; $received=(int)$r['recibidos'];
    return ['total'=>$total, 'recibidos'=>$received, 'porcentaje'=>$total ? (int)round($received*100/$total) : 0];
}
