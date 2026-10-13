<?php
require_once __DIR__ . '/../../app/helpers.php'; require_admin();
$users=db()->query('SELECT id,nombre,usuario,correo,rol,activo,creado_en FROM usuarios ORDER BY nombre')->fetchAll();
render_header('Usuarios');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3">Usuarios</h1><p class="text-muted mb-0">Administración de cuentas y permisos.</p></div><a class="btn btn-primary" href="<?=e(url('usuarios/form.php'))?>">+ Nuevo usuario</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-striped datatable"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?=e($u['nombre'])?></td><td><?=e($u['usuario'])?></td><td><?=e($u['correo'])?></td><td><?=e($u['rol'])?></td><td><?=$u['activo']?'Activo':'Inactivo'?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?=e(url('usuarios/form.php?id='.$u['id']))?>">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php render_footer(); ?>
