# Sistema de Expedientes de Clientes — Agencia Aduanal

Aplicación base funcional en PHP 8.1+, MySQL 8+, Bootstrap 5, DataTables y JavaScript para instalar en un servidor local de la agencia.

## Incluye
- Inicio de sesión con contraseñas `password_hash()`.
- Roles: Administrador y Usuario común.
- Clientes con eliminación lógica (`deleted_at`), conservando expediente e historial.
- Expedientes para persona física y moral.
- Catálogo documental configurable por tipo de persona.
- Carga de PDF, JPG, JPEG y PNG con validación MIME, tamaño máximo configurable y nombre aleatorio.
- Archivos almacenados fuera de `public/` y servidos mediante un controlador PHP que valida sesión.
- Reemplazo de documentos conservando versiones anteriores.
- Fecha de emisión y vencimiento; vencimientos generan alertas, pero no cambian automáticamente el estatus de acreditación.
- Acreditación automática según documentos obligatorios aplicables, excluyendo documentos marcados como no bloqueantes.
- Historial de estatus y bitácora de acciones.
- Usuarios: el administrador puede crear, editar, activar/desactivar y cambiar roles.
- DataTables para listado de clientes y usuarios.

## Despliegue en Coolify / Ubuntu
Consulte `README_COOLIFY.md` para desplegar con Docker Compose en Coolify, configurar variables de entorno, dominio, volúmenes persistentes y respaldos. El proyecto incluye `Dockerfile`, `docker-compose.yml` y ajustes de PHP para cargas de documentos.

## Requisitos
- PHP 8.1 o superior con extensiones `pdo_mysql`, `fileinfo`, `mbstring`.
- MySQL 8.0 o MariaDB 10.5+.
- Apache recomendado (XAMPP, WampServer o LAMP).
- Servidor en red local con acceso restringido.

## Instalación local
1. Copie la carpeta `public` al directorio web, o configure el DocumentRoot de Apache para apuntar a `expedientes_aduanal/public`.
2. Mantenga `storage/` fuera del directorio público. No coloque archivos de clientes dentro de `public/`.
3. Cree la base de datos ejecutando `database/schema.sql` desde phpMyAdmin o consola MySQL.
4. Copie `app/config.example.php` como `app/config.php` y ajuste host, base, usuario y contraseña de MySQL.
5. Configure en `app/config.php` la ruta absoluta de `STORAGE_PATH` si mueve la carpeta.
6. Asegure permisos de escritura para el usuario de Apache sobre `storage/`.
7. Abra `http://IP-DEL-SERVIDOR/` o la URL configurada.
8. Inicie sesión con el usuario inicial descrito abajo y cambie inmediatamente la contraseña.

## Acceso inicial
- Usuario: `admin`
- Contraseña: `CambiarEstaClave!2026`

La cuenta inicial se crea al ejecutar el SQL. Cambie la contraseña inmediatamente desde Usuarios o mediante un hash generado con PHP. No exponga este sistema directamente a Internet.

## Configuración Apache recomendada
- Habilite HTTPS incluso en red local si se puede (certificado interno).
- Restrinja acceso por firewall/VLAN a usuarios autorizados.
- Deshabilite listado de directorios.
- Mantenga `storage/` fuera del DocumentRoot.
- Haga respaldos cifrados de base de datos y archivos; pruebe restauración periódicamente.

## Reglas de acreditación implementadas
- Se calcula por cliente el porcentaje de documentos obligatorios aplicables recibidos.
- Los documentos no bloqueantes no impiden la acreditación (fotografía de fachada y documentos de inmueble/escrituras/predial, según catálogo inicial).
- El estatus se marca como **Acreditado** si todos los documentos obligatorios aplicables están recibidos; de lo contrario **En espera**.
- **Rechazado** es un estatus manual asignado por Administrador. Mientras el rechazo esté vigente, la acreditación automática no lo reemplaza.
- Los documentos vencidos solo generan alertas y no afectan automáticamente el estatus.
- Persona física no requiere acta constitutiva ni poder notarial de persona moral. Persona moral sí requiere acta constitutiva; el poder notarial se marca obligatorio si no está incluido en el acta, y debe revisarse manualmente.

## Consideraciones importantes
Este proyecto es una base de trabajo que debe probarse y ajustarse a las políticas internas y los requisitos legales/documentales vigentes de la agencia. La lista inicial de documentos es configurable en `tipos_documento`; no constituye asesoría legal ni determina por sí sola el cumplimiento normativo.
