<?php
require_once __DIR__ . '/../../app/helpers.php'; require_login();
$sql="SELECT c.*, u.nombre creador FROM clientes c LEFT JOIN usuarios u ON u.id=c.creado_por WHERE c.deleted_at IS NULL ORDER BY c.creado_en DESC";
$clients=db()->query($sql)->fetchAll();
render_header('Clientes');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h1 class="h3 mb-1">Clientes</h1><p class="text-muted mb-0">Listado de expedientes registrados.</p></div><a class="btn btn-primary" href="<?=e(url('clientes/form.php'))?>">+ Agregar cliente</a></div>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle datatable"><thead><tr><th>Código</th><th>Nombre</th><th>RFC</th><th>Tipo</th><th>Estatus</th><th>Integración</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($clients as $c): $sum=document_summary((int)$c['id']); ?>
<tr><td><?=e($c['codigo'])?></td><td><?=e($c['nombre'])?></td><td><?=e($c['rfc'])?></td><td><?=e($c['tipo_persona'])?></td><td><span class="badge text-bg-<?=$c['estatus']==='Acreditado'?'success':($c['estatus']==='Rechazado'?'danger':'warning')?>"><?=e($c['estatus'])?></span></td><td><div class="progress" style="min-width:100px"><div class="progress-bar" style="width:<?=$sum['porcentaje']?>%"><?=$sum['porcentaje']?>%</div></div></td>
<td class="table-actions"><a class="btn btn-sm btn-outline-primary" href="<?=e(url('clientes/detalle.php?id='.$c['id']))?>">Ver detalles</a> <a class="btn btn-sm btn-outline-secondary" href="<?=e(url('clientes/form.php?id='.$c['id']))?>">Editar</a>
<?php if(is_admin()): ?><form class="d-inline" method="post" action="<?=e(url('clientes/eliminar.php'))?>"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Se ocultará el cliente del listado, conservando su expediente. ¿Continuar?">Borrar</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div></div></div>
<?php render_footer(); ?>
