<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function render_header(string $title): void {
    $flash = get_flash();
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=e($title)?> · <?=e(APP_NAME)?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="<?=e(url('assets/app.css'))?>" rel="stylesheet"></head><body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary"><div class="container-fluid">
      <a class="navbar-brand" href="<?=e(url('index.php'))?>"><?=e(APP_NAME)?></a>
      <?php if (current_user()): ?><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMain"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navMain"><ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?=e(url('index.php'))?>">Panel</a></li>
        <li class="nav-item"><a class="nav-link" href="<?=e(url('clientes/index.php'))?>">Clientes</a></li>
        <?php if (is_admin()): ?><li class="nav-item"><a class="nav-link" href="<?=e(url('usuarios/index.php'))?>">Usuarios</a></li><?php endif; ?>
      </ul><span class="navbar-text me-3"><?=e(current_user()['nombre'])?> (<?=e(current_user()['rol'])?>)</span><a class="btn btn-outline-light btn-sm" href="<?=e(url('logout.php'))?>">Cerrar sesión</a></div><?php endif; ?>
    </div></nav><main class="container-fluid py-4">
    <?php if ($flash): ?><div class="alert alert-<?=e($flash['type'])?> alert-dismissible fade show" role="alert"><?=e($flash['message'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;
}
function render_footer(): void {
    ?><div class="small text-muted mt-4 mb-3">Sistema interno de expedientes · Proteja la información confidencial de los clientes.</div></main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script><script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="<?=e(url('assets/app.js'))?>"></script></body></html><?php
}
function require_client_access(int $id): array {
    $stmt = db()->prepare('SELECT * FROM clientes WHERE id=? AND deleted_at IS NULL');
    $stmt->execute([$id]); $client=$stmt->fetch();
    if (!$client) { http_response_code(404); exit('Cliente no encontrado.'); }
    return $client;
}
