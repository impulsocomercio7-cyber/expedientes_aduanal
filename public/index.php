<?php
require_once __DIR__ . '/../app/helpers.php';
require_login();
$pdo=db();
$stats=[];
$stats['total']=(int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE deleted_at IS NULL")->fetchColumn();
foreach (['Acreditado','En espera','Rechazado'] as $s) {
 $st=$pdo->prepare('SELECT COUNT(*) FROM clientes WHERE deleted_at IS NULL AND estatus=?'); $st->execute([$s]); $stats[$s]=(int)$st->fetchColumn();
}
$expiring=(int)$pdo->query("SELECT COUNT(*) FROM documentos_cliente d JOIN clientes c ON c.id=d.cliente_id WHERE c.deleted_at IS NULL AND d.es_version_actual=1 AND d.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$expired=(int)$pdo->query("SELECT COUNT(*) FROM documentos_cliente d JOIN clientes c ON c.id=d.cliente_id WHERE c.deleted_at IS NULL AND d.es_version_actual=1 AND d.fecha_vencimiento<CURDATE()")->fetchColumn();
render_header('Panel principal');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Panel principal</h1><p class="text-muted mb-0">Resumen de expedientes y documentación.</p></div><a class="btn btn-primary" href="<?=e(url('clientes/form.php'))?>">+ Agregar cliente</a></div>
<div class="row g-3 mb-4">
<?php foreach ([['Clientes',$stats['total'],'primary'],['Acreditados',$stats['Acreditado'],'success'],['En espera',$stats['En espera'],'warning'],['Rechazados',$stats['Rechazado'],'danger'],['Vencen en 30 días',$expiring,'info'],['Vencidos',$expired,'secondary']] as [$label,$value,$color]): ?>
<div class="col-6 col-md-4 col-xl-2"><div class="card card-stat h-100"><div class="card-body"><div class="text-muted small"><?=e($label)?></div><div class="display-6 text-<?=e($color)?>"><?=number_format($value)?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="card"><div class="card-body"><h2 class="h5">Accesos rápidos</h2><a class="btn btn-outline-primary me-2" href="<?=e(url('clientes/index.php'))?>">Consultar clientes</a><?php if(is_admin()): ?><a class="btn btn-outline-secondary" href="<?=e(url('usuarios/index.php'))?>">Administrar usuarios</a><?php endif; ?></div></div>
<?php render_footer(); ?>
