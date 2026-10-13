<?php
require_once __DIR__ . '/../../app/helpers.php'; require_login();
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT d.*,c.deleted_at FROM documentos_cliente d JOIN clientes c ON c.id=d.cliente_id WHERE d.id=?');$stmt->execute([$id]);$d=$stmt->fetch();
if(!$d){http_response_code(404);exit('Documento no encontrado.');}
$path=STORAGE_PATH.'/'.$d['ruta_relativa'];
if(!is_file($path)){http_response_code(404);exit('El archivo físico no está disponible.');}
$real=realpath($path);$base=realpath(STORAGE_PATH);
if(!$real||!$base||strpos($real,$base.DIRECTORY_SEPARATOR)!==0){http_response_code(403);exit('Ruta no válida.');}
$allowed=['application/pdf','image/jpeg','image/png'];
if(!in_array($d['mime_type'],$allowed,true)){http_response_code(415);exit('Tipo de archivo no permitido.');}
$inline=($_GET['download']??'')!=='1';
header('X-Content-Type-Options: nosniff');
header('Content-Type: '.$d['mime_type']);
header('Content-Length: '.filesize($real));
header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="documento-'.(int)$d['id'].'.'.preg_replace('/[^a-z0-9]/i','',$d['extension']).'"');
header('Cache-Control: private, no-store, max-age=0');
readfile($real);exit;
