<?php
require_once __DIR__ . '/../../app/helpers.php'; require_admin();
$pdo=db();$id=(int)($_GET['id']??0);$u=['nombre'=>'','usuario'=>'','correo'=>'','rol'=>'Usuario común','activo'=>1];if($id){$s=$pdo->prepare('SELECT * FROM usuarios WHERE id=?');$s->execute([$id]);$u=$s->fetch();if(!$u){http_response_code(404);exit('Usuario no encontrado');}}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$u['nombre']=trim((string)($_POST['nombre']??''));$u['usuario']=trim((string)($_POST['usuario']??''));$u['correo']=trim((string)($_POST['correo']??''));$u['rol']=($_POST['rol']??'')==='Administrador'?'Administrador':'Usuario común';$u['activo']=isset($_POST['activo'])?1:0;$password=(string)($_POST['password']??'');
 if($u['nombre']===''||$u['usuario']==='')$errors[]='Nombre y usuario son obligatorios.';
 if($u['correo']!==''&&!filter_var($u['correo'],FILTER_VALIDATE_EMAIL))$errors[]='Correo inválido.';
 $s=$pdo->prepare('SELECT id FROM usuarios WHERE usuario=? AND id<>?');$s->execute([$u['usuario'],$id]);if($s->fetch())$errors[]='El nombre de usuario ya existe.';
 if(!$id&&strlen($password)<12)$errors[]='La contraseña inicial debe tener al menos 12 caracteres.';
 if($password!==''&&strlen($password)<12)$errors[]='La contraseña debe tener al menos 12 caracteres.';
 if($id===(int)current_user()['id']&&!$u['activo'])$errors[]='No puede desactivar su propia cuenta.';
 if(!$errors){
  if($id){
   if($password!=='')$pdo->prepare('UPDATE usuarios SET nombre=?,usuario=?,correo=?,rol=?,activo=?,password_hash=?,actualizado_en=NOW() WHERE id=?')->execute([$u['nombre'],$u['usuario'],$u['correo']?:null,$u['rol'],$u['activo'],password_hash($password,PASSWORD_DEFAULT),$id]);
   else $pdo->prepare('UPDATE usuarios SET nombre=?,usuario=?,correo=?,rol=?,activo=?,actualizado_en=NOW() WHERE id=?')->execute([$u['nombre'],$u['usuario'],$u['correo']?:null,$u['rol'],$u['activo'],$id]);
   audit('editar_usuario',null,['usuario_id'=>$id]);flash('success','Usuario actualizado.');
  }else{$pdo->prepare('INSERT INTO usuarios (nombre,usuario,correo,password_hash,rol,activo) VALUES (?,?,?,?,?,?)')->execute([$u['nombre'],$u['usuario'],$u['correo']?:null,password_hash($password,PASSWORD_DEFAULT),$u['rol'],$u['activo']]);audit('crear_usuario',null,['usuario'=>$u['usuario']]);flash('success','Usuario creado.');}
  redirect('usuarios/index.php');
 }
}
render_header($id?'Editar usuario':'Nuevo usuario');
?>
<h1 class="h3 mb-3"><?=$id?'Editar usuario':'Nuevo usuario'?></h1><?php if($errors): ?><div class="alert alert-danger"><ul><?php foreach($errors as $er): ?><li><?=e($er)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><div class="card-body"><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><div class="row g-3"><div class="col-md-6"><label class="form-label">Nombre completo *</label><input class="form-control" name="nombre" value="<?=e($u['nombre'])?>" required></div><div class="col-md-6"><label class="form-label">Usuario *</label><input class="form-control" name="usuario" value="<?=e($u['usuario'])?>" required></div><div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" type="email" name="correo" value="<?=e($u['correo'])?>"></div><div class="col-md-6"><label class="form-label">Rol</label><select class="form-select" name="rol"><option <?=$u['rol']==='Usuario común'?'selected':''?>>Usuario común</option><option <?=$u['rol']==='Administrador'?'selected':''?>>Administrador</option></select></div><div class="col-md-6"><label class="form-label"><?=$id?'Nueva contraseña (opcional)':'Contraseña inicial *'?></label><input class="form-control" type="password" name="password" minlength="12" <?=$id?'':'required'?> autocomplete="new-password"><div class="form-text">Mínimo 12 caracteres. Si se deja vacío al editar, no cambia.</div></div><div class="col-md-6 d-flex align-items-center"><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="activo" id="activo" <?=$u['activo']?'checked':''?>><label class="form-check-label" for="activo">Cuenta activa</label></div></div></div><div class="mt-4"><button class="btn btn-primary">Guardar</button> <a class="btn btn-outline-secondary" href="<?=e(url('usuarios/index.php'))?>">Cancelar</a></div></form></div></div>
<?php render_footer(); ?>
