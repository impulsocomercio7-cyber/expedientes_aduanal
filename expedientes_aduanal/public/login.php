<?php
require_once __DIR__ . '/../app/helpers.php';
if (current_user()) redirect('index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $username=trim((string)($_POST['usuario']??'')); $password=(string)($_POST['password']??'');
 $stmt=db()->prepare('SELECT * FROM usuarios WHERE usuario=? AND activo=1 LIMIT 1'); $stmt->execute([$username]); $u=$stmt->fetch();
 if ($u && password_verify($password,$u['password_hash'])) {
   session_regenerate_id(true);
   $_SESSION['user']=['id'=>(int)$u['id'],'nombre'=>$u['nombre'],'usuario'=>$u['usuario'],'rol'=>$u['rol']];
   audit('inicio_sesion'); redirect('index.php');
 }
 $error='Usuario o contraseña incorrectos.';
}
render_header('Iniciar sesión');
?>
<div class="row justify-content-center"><div class="col-sm-10 col-md-6 col-lg-4"><div class="card shadow-sm"><div class="card-body p-4"><h1 class="h4 mb-3">Iniciar sesión</h1><p class="text-muted">Acceso al expediente digital de clientes.</p>
<?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><div class="mb-3"><label class="form-label">Usuario</label><input class="form-control" name="usuario" required autocomplete="username"></div><div class="mb-3"><label class="form-label">Contraseña</label><input class="form-control" type="password" name="password" required autocomplete="current-password"></div><button class="btn btn-primary w-100">Entrar</button></form>
</div></div></div></div>
<?php render_footer(); ?>
