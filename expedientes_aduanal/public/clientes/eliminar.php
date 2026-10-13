<?php
require_once __DIR__ . '/../../app/helpers.php'; require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido');}
verify_csrf(); $id=(int)($_POST['id']??0); $c=require_client_access($id);
db()->prepare('UPDATE clientes SET deleted_at=NOW(), eliminado_por=? WHERE id=?')->execute([current_user()['id'],$id]);
audit('eliminacion_logica_cliente',$id,['codigo'=>$c['codigo']]); flash('success','Cliente archivado. Su expediente y documentos se conservaron.'); redirect('clientes/index.php');
