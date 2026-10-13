<?php
require_once __DIR__ . '/../../app/helpers.php'; require_login();
$pdo=db(); $id=(int)($_GET['id']??0); $c=['codigo'=>'','tipo_persona'=>'Moral','rfc'=>'','nombre'=>'','direccion'=>'','telefono'=>'','correo'=>''];
if($id){$c=require_client_access($id);}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $c=[
 'codigo'=>strtoupper(trim((string)($_POST['codigo']??''))),
 'tipo_persona'=>(($_POST['tipo_persona']??'')==='Fisica'?'Fisica':'Moral'),
 'rfc'=>strtoupper(trim((string)($_POST['rfc']??''))),
 'nombre'=>trim((string)($_POST['nombre']??'')),
 'direccion'=>trim((string)($_POST['direccion']??'')),
 'telefono'=>trim((string)($_POST['telefono']??'')),
 'correo'=>trim((string)($_POST['correo']??'')),
 ];
 if($c['codigo']===''||$c['rfc']===''||$c['nombre']===''||$c['direccion']==='') $errors[]='Código, RFC, nombre y domicilio son obligatorios.';
 if(!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/',$c['rfc'])) $errors[]='El RFC no tiene el formato esperado. Verifique la captura.';
 if($c['correo']!==''&&!filter_var($c['correo'],FILTER_VALIDATE_EMAIL)) $errors[]='El correo electrónico no tiene un formato válido.';
 $q=$pdo->prepare('SELECT id FROM clientes WHERE codigo=? AND id<>?');$q->execute([$c['codigo'],$id]);if($q->fetch())$errors[]='El código ya está registrado.';
 $q=$pdo->prepare('SELECT id FROM clientes WHERE rfc=? AND id<>? AND deleted_at IS NULL');$q->execute([$c['rfc'],$id]);if($q->fetch())$errors[]='El RFC ya existe en otro expediente activo.';
 if(!$errors){
  if($id){
   $old=require_client_access($id); $pdo->prepare('UPDATE clientes SET codigo=?,tipo_persona=?,rfc=?,nombre=?,direccion=?,telefono=?,correo=?,actualizado_en=NOW() WHERE id=?')->execute([$c['codigo'],$c['tipo_persona'],$c['rfc'],$c['nombre'],$c['direccion'],$c['telefono'],$c['correo'],$id]);
   if($old['estatus_manual']!=='Rechazado') recompute_client_status($id);
   audit('editar_cliente',$id,['codigo'=>$c['codigo']]); flash('success','Cliente actualizado correctamente.');
  } else {
   $pdo->prepare("INSERT INTO clientes (codigo,tipo_persona,rfc,nombre,direccion,telefono,correo,creado_por) VALUES (?,?,?,?,?,?,?,?)")->execute([$c['codigo'],$c['tipo_persona'],$c['rfc'],$c['nombre'],$c['direccion'],$c['telefono'],$c['correo'],current_user()['id']]);
   $id=(int)$pdo->lastInsertId(); @mkdir(STORAGE_PATH.'/'.preg_replace('/[^A-Za-z0-9_-]/','_', $c['codigo']),0750,true);
   recompute_client_status($id); audit('crear_cliente',$id,['codigo'=>$c['codigo']]); flash('success','Cliente registrado. Ahora agregue los documentos del expediente.');
  }
  redirect('clientes/detalle.php?id='.$id);
 }
}
render_header($id?'Editar cliente':'Agregar cliente');
?>
<h1 class="h3 mb-3"><?=$id?'Editar cliente':'Agregar cliente'?></h1>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $er): ?><li><?=e($er)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><div class="card-body"><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
<div class="row g-3"><div class="col-md-4"><label class="form-label">Código *</label><input class="form-control" name="codigo" value="<?=e($c['codigo'])?>" required></div>
<div class="col-md-4"><label class="form-label">Tipo de persona *</label><select class="form-select" name="tipo_persona"><option value="Fisica" <?=$c['tipo_persona']==='Fisica'?'selected':''?>>Persona física</option><option value="Moral" <?=$c['tipo_persona']==='Moral'?'selected':''?>>Persona moral</option></select></div>
<div class="col-md-4"><label class="form-label">RFC *</label><input class="form-control text-uppercase" name="rfc" maxlength="13" value="<?=e($c['rfc'])?>" required></div>
<div class="col-md-12"><label class="form-label">Nombre / razón social *</label><input class="form-control" name="nombre" value="<?=e($c['nombre'])?>" required></div>
<div class="col-12"><label class="form-label">Dirección fiscal *</label><textarea class="form-control" name="direccion" rows="2" required><?=e($c['direccion'])?></textarea></div>
<div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=e($c['telefono'])?>"></div>
<div class="col-md-6"><label class="form-label">Correo electrónico</label><input class="form-control" type="email" name="correo" value="<?=e($c['correo'])?>"></div></div>
<div class="mt-4"><button class="btn btn-primary">Guardar</button> <a class="btn btn-outline-secondary" href="<?=e(url('clientes/index.php'))?>">Cancelar</a></div></form></div></div>
<?php render_footer(); ?>
