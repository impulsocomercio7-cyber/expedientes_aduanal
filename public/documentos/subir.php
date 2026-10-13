<?php
require_once __DIR__ . '/../../app/helpers.php'; require_login();
$pdo=db(); $clientId=(int)($_GET['cliente_id']??$_POST['cliente_id']??0); $c=require_client_access($clientId);
$typeId=(int)($_GET['tipo_id']??$_POST['tipo_documento_id']??0);
$st=$pdo->prepare("SELECT * FROM tipos_documento WHERE id=? AND activo=1 AND ((?='Fisica' AND aplica_fisica=1) OR (?='Moral' AND aplica_moral=1))");$st->execute([$typeId,$c['tipo_persona'],$c['tipo_persona']]);$type=$typeId?$st->fetch():null;
$all=$pdo->prepare("SELECT * FROM tipos_documento WHERE activo=1 AND ((?='Fisica' AND aplica_fisica=1) OR (?='Moral' AND aplica_moral=1)) ORDER BY orden,nombre");$all->execute([$c['tipo_persona'],$c['tipo_persona']]);$types=$all->fetchAll();
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $typeId=(int)($_POST['tipo_documento_id']??0);
 $st->execute([$typeId,$c['tipo_persona'],$c['tipo_persona']]);$type=$st->fetch();
 if(!$type)$errors[]='Seleccione un tipo de documento válido para esta persona.';
 $file=$_FILES['archivo']??null;
 if(!$file||$file['error']!==UPLOAD_ERR_OK)$errors[]='Seleccione un archivo válido.';
 elseif($file['size']>MAX_UPLOAD_BYTES)$errors[]='El archivo excede el tamaño máximo de 10 MB.';
 if(!$errors){
  $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name']);
  $allowed=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
  if(!isset($allowed[$mime]))$errors[]='Formato no permitido. Solo se acepta PDF, JPG, JPEG o PNG.';
  if(!$errors){
   $ext=$allowed[$mime]; $safeCode=preg_replace('/[^A-Za-z0-9_-]/','_', $c['codigo']);
   $dir=STORAGE_PATH.'/'.$safeCode;
   if(!is_dir($dir)&&!mkdir($dir,0750,true))$errors[]='No fue posible crear la carpeta del expediente.';
   if(!$errors){
    $random=bin2hex(random_bytes(18));$filename=$random.'.'.$ext;
    $relative=$safeCode.'/'.$filename;
    $target=$dir.'/'.$filename;
    if(!move_uploaded_file($file['tmp_name'],$target))$errors[]='No fue posible guardar el archivo.';
    else {
     $q=$pdo->prepare('SELECT COALESCE(MAX(version),0) FROM documentos_cliente WHERE cliente_id=? AND tipo_documento_id=?');$q->execute([$clientId,$typeId]);$version=(int)$q->fetchColumn()+1;
     $pdo->prepare('UPDATE documentos_cliente SET es_version_actual=0 WHERE cliente_id=? AND tipo_documento_id=?')->execute([$clientId,$typeId]);
     try {
      $stmt=$pdo->prepare('INSERT INTO documentos_cliente (cliente_id,tipo_documento_id,nombre_original,nombre_archivo,ruta_relativa,mime_type,extension,tamano_bytes,fecha_emision,fecha_vencimiento,observaciones,version,es_version_actual,cargado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?)');
      $stmt->execute([$clientId,$typeId,mb_substr(basename($file['name']),0,255),$filename,$relative,$mime,$ext,(int)$file['size'],($_POST['fecha_emision']??'')?:null,($_POST['fecha_vencimiento']??'')?:null,trim((string)($_POST['observaciones']??''))?:null,$version,current_user()['id']]);
      recompute_client_status($clientId);audit('cargar_documento',$clientId,['tipo'=>$type['nombre'],'version'=>$version,'mime'=>$mime]);flash('success','Documento guardado correctamente. La versión anterior se conservó en el historial.');redirect('clientes/detalle.php?id='.$clientId);
     } catch(Throwable $ex) { @unlink($target); throw $ex; }
    }
   }
  }
 }
}
render_header('Cargar documento');
?>
<h1 class="h3 mb-1">Cargar documento</h1><p class="text-muted mb-3"><?=e($c['codigo'])?> · <?=e($c['nombre'])?></p>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $er): ?><li><?=e($er)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><div class="card-body"><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="cliente_id" value="<?=$clientId?>">
<div class="mb-3"><label class="form-label">Tipo de documento *</label><select class="form-select" name="tipo_documento_id" required><option value="">Seleccione...</option><?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=((int)($type['id']??$typeId)===(int)$t['id'])?'selected':''?>><?=e($t['nombre'])?><?=$t['bloqueante']?' (obligatorio)':' (no bloqueante)'?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Archivo PDF o imagen *</label><input class="form-control" type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-max-bytes="<?=MAX_UPLOAD_BYTES?>" required><div class="form-text">PDF, JPG o PNG. Máximo 10 MB. No cargue archivos ejecutables ni documentos que no correspondan al cliente.</div></div>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Fecha de emisión</label><input class="form-control" type="date" name="fecha_emision" value="<?=e($_POST['fecha_emision']??'')?>"></div><div class="col-md-6"><label class="form-label">Fecha de vencimiento</label><input class="form-control" type="date" name="fecha_vencimiento" value="<?=e($_POST['fecha_vencimiento']??'')?>"><div class="form-text">El vencimiento genera alerta; no cambia automáticamente la acreditación.</div></div></div>
<div class="mt-3"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="3"><?=e($_POST['observaciones']??'')?></textarea></div>
<div class="mt-4"><button class="btn btn-primary">Guardar documento</button> <a class="btn btn-outline-secondary" href="<?=e(url('clientes/detalle.php?id='.$clientId))?>">Cancelar</a></div></form></div></div>
<?php render_footer(); ?>
