<?php
require_once __DIR__ . '/../../app/helpers.php'; require_login();
$id=(int)($_GET['id']??0); $c=require_client_access($id); $pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); require_admin();
 $new=($_POST['estatus_manual']??'')==='Rechazado'?'Rechazado':'Automatico';
 $old=$c['estatus'];
 $pdo->prepare('UPDATE clientes SET estatus_manual=? WHERE id=?')->execute([$new,$id]);
 if($new==='Rechazado') $pdo->prepare("UPDATE clientes SET estatus='Rechazado' WHERE id=?")->execute([$id]); else recompute_client_status($id);
 $now=$pdo->prepare('SELECT estatus FROM clientes WHERE id=?');$now->execute([$id]);$newStatus=$now->fetchColumn();
 $pdo->prepare('INSERT INTO historial_estatus (cliente_id,estatus_anterior,estatus_nuevo,motivo,usuario_id) VALUES (?,?,?,?,?)')->execute([$id,$old,$newStatus,trim((string)($_POST['motivo']??'')),current_user()['id']]);
 audit('cambio_estatus',$id,['estatus'=>$newStatus]); flash('success','Estatus actualizado.'); redirect('clientes/detalle.php?id='.$id);
}
$sum=document_summary($id);
$types=$pdo->prepare("SELECT td.*, d.id doc_id,d.nombre_original,d.fecha_emision,d.fecha_vencimiento,d.creado_en,d.version,d.mime_type,d.tamano_bytes
FROM tipos_documento td
LEFT JOIN documentos_cliente d ON d.id=(SELECT d2.id FROM documentos_cliente d2 WHERE d2.cliente_id=? AND d2.tipo_documento_id=td.id AND d2.es_version_actual=1 ORDER BY d2.id DESC LIMIT 1)
WHERE td.activo=1 AND ((?='Fisica' AND td.aplica_fisica=1) OR (?='Moral' AND td.aplica_moral=1))
ORDER BY td.orden,td.nombre");
$types->execute([$id,$c['tipo_persona'],$c['tipo_persona']]); $docs=$types->fetchAll();
$versions=$pdo->prepare("SELECT d.*,td.nombre tipo_nombre,u.nombre usuario_nombre FROM documentos_cliente d JOIN tipos_documento td ON td.id=d.tipo_documento_id LEFT JOIN usuarios u ON u.id=d.cargado_por WHERE d.cliente_id=? ORDER BY d.tipo_documento_id,d.version DESC");$versions->execute([$id]);$history=$versions->fetchAll();
$alerts=array_filter($docs,fn($d)=>!empty($d['fecha_vencimiento'])&&$d['fecha_vencimiento']<date('Y-m-d'));
render_header('Expediente '.$c['codigo']);
?>
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><div><h1 class="h3 mb-1"><?=e($c['nombre'])?></h1><div class="text-muted"><?=e($c['codigo'])?> · RFC <?=e($c['rfc'])?> · Persona <?=e($c['tipo_persona'])?></div></div><div><a class="btn btn-outline-secondary" href="<?=e(url('clientes/form.php?id='.$id))?>">Editar datos</a> <a class="btn btn-primary" href="<?=e(url('documentos/subir.php?cliente_id='.$id))?>">+ Cargar documento</a></div></div>
<?php if($alerts): ?><div class="alert alert-warning"><strong>Alerta de vencimiento:</strong> hay <?=count($alerts)?> documento(s) vencido(s). Esta alerta no cambia automáticamente el estatus del cliente.</div><?php endif; ?>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted">Estatus</div><span class="badge text-bg-<?=$c['estatus']==='Acreditado'?'success':($c['estatus']==='Rechazado'?'danger':'warning')?> fs-6"><?=e($c['estatus'])?></span></div></div></div><div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted">Documentos obligatorios</div><strong><?=$sum['recibidos']?> / <?=$sum['total']?></strong></div></div></div><div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted">Integración documental</div><div class="progress mt-2"><div class="progress-bar" style="width:<?=$sum['porcentaje']?>%"><?=$sum['porcentaje']?>%</div></div></div></div></div></div>
<div class="row g-3 align-items-start">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Datos del cliente</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8"><?=nl2br(e($c['direccion']))?></dd>
          <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8"><?=e($c['telefono']?:'—')?></dd>
          <dt class="col-sm-4">Correo</dt><dd class="col-sm-8"><?=e($c['correo']?:'—')?></dd>
          <dt class="col-sm-4">Alta</dt><dd class="col-sm-8"><?=e($c['creado_en'])?></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
        <span>Checklist documental</span>
        <span class="text-muted small">Documentos aplicables a persona <?=e(strtolower($c['tipo_persona']))?></span>
      </div>
      <div class="card-body checklist-scroll">
        <?php foreach($docs as $d): $received=!empty($d['doc_id']); $expired=$received&&!empty($d['fecha_vencimiento'])&&$d['fecha_vencimiento']<date('Y-m-d'); ?>
        <div class="doc-row">
          <div class="d-flex justify-content-between gap-2">
            <div>
              <strong><?=e($d['nombre'])?></strong>
              <?php if(!$d['bloqueante']): ?> <span class="badge text-bg-secondary">No bloqueante</span><?php endif; ?>
              <div class="small text-muted">
                <?php if($received): ?>Versión <?=$d['version']?> · Cargado <?=e($d['creado_en'])?><?php if($d['fecha_vencimiento']): ?> · Vence <?=e($d['fecha_vencimiento'])?><?php endif; ?><?php else: ?>Sin archivo registrado<?php endif; ?>
              </div>
            </div>
            <div class="text-end">
              <?php if($received): ?><span class="badge text-bg-<?=$expired?'warning':'success'?>"><?=$expired?'Vencido':'Recibido'?></span><?php else: ?><span class="badge text-bg-<?=$d['bloqueante']?'danger':'secondary'?>"><?=$d['bloqueante']?'Faltante':'Opcional'?></span><?php endif; ?>
            </div>
          </div>
          <?php if($received): ?>
          <div class="mt-2"><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?=e(url('documentos/ver.php?id='.$d['doc_id']))?>">Ver documento</a> <a class="btn btn-sm btn-outline-secondary" href="<?=e(url('documentos/subir.php?cliente_id='.$id.'&tipo_id='.$d['id']))?>">Reemplazar / nueva versión</a></div>
          <?php else: ?>
          <div class="mt-2"><a class="btn btn-sm btn-primary" href="<?=e(url('documentos/subir.php?cliente_id='.$id.'&tipo_id='.$d['id']))?>">Cargar documento</a></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php if(is_admin()): ?>
<div class="card mt-3 status-control-card">
  <div class="card-header">Control de estatus</div>
  <div class="card-body">
    <form method="post" class="row g-3 align-items-end">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
      <div class="col-md-4">
        <label class="form-label">Estatus manual</label>
        <select class="form-select" name="estatus_manual">
          <option value="Automatico" <?=$c['estatus_manual']==='Automatico'?'selected':''?>>Automático según documentos</option>
          <option value="Rechazado" <?=$c['estatus_manual']==='Rechazado'?'selected':''?>>Rechazado</option>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Motivo / observación</label>
        <textarea class="form-control" name="motivo" rows="2" placeholder="Escriba el motivo del cambio (opcional)"></textarea>
      </div>
      <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100">Actualizar estatus</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card mt-4">
  <div class="card-header">Historial de versiones de documentos</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-striped datatable">
        <thead><tr><th>Documento</th><th>Versión</th><th>Archivo</th><th>Fecha</th><th>Usuario</th><th>Acción</th></tr></thead>
        <tbody>
          <?php foreach($history as $h): ?>
          <tr><td><?=e($h['tipo_nombre'])?></td><td><?=$h['version']?> <?=$h['es_version_actual']?'(actual)':''?></td><td><?=e($h['nombre_original'])?></td><td><?=e($h['creado_en'])?></td><td><?=e($h['usuario_nombre']??'—')?></td><td><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?=e(url('documentos/ver.php?id='.$h['id']))?>">Ver</a></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php render_footer(); ?>
