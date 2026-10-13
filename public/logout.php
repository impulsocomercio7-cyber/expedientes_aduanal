<?php
require_once __DIR__ . '/../app/helpers.php';
if (current_user()) audit('cierre_sesion');
$_SESSION=[]; session_destroy(); redirect('login.php');
