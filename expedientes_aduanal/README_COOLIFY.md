# Despliegue de Expedientes Aduanales en Coolify (Ubuntu)

Este proyecto se ejecuta como dos contenedores: Apache/PHP y MySQL. Coolify puede construir la imagen a partir del `Dockerfile` y el `docker-compose.yml` incluido.

## Antes de empezar
- Tener Coolify instalado y accesible desde el navegador.
- Tener el proyecto en un repositorio Git accesible para Coolify, o cargarlo como recurso compatible con Docker Compose.
- Elegir contraseñas largas y únicas para `DB_PASSWORD` y `DB_ROOT_PASSWORD`.
- No publicar MySQL a Internet: el compose no expone el puerto 3306.

## Opción recomendada: desplegar desde Git
1. En la carpeta del proyecto, copie `.env.example` como `.env` si va a probar con Docker Compose directamente. Cambie las dos contraseñas antes de arrancar. Para Coolify, puede configurar estas variables en la sección Environment Variables del recurso.
2. Suba el proyecto a un repositorio Git privado, incluyendo `Dockerfile`, `docker-compose.yml`, `docker/uploads.ini`, `database/schema.sql` y el código PHP. **No suba un archivo `.env` real**.
3. En Coolify, cree un recurso nuevo desde el repositorio Git y seleccione Docker Compose.
4. Seleccione `docker-compose.yml` como archivo Compose si Coolify lo solicita.
5. Configure estas variables de entorno en Coolify:
   - `DB_NAME=expedientes_aduanal`
   - `DB_USER=expedientes_app`
   - `DB_PASSWORD` = contraseña larga y única
   - `DB_ROOT_PASSWORD` = otra contraseña larga y única
   - `APP_NAME=Expedientes Aduanales`
   - `BASE_URL=` (vacía si se publica en la raíz del dominio)
   - `MAX_UPLOAD_BYTES=10485760`
6. En el servicio `web`, configure el dominio o la URL que vaya a usar. El puerto interno de la aplicación es **80**. No es necesario exponer el puerto de MySQL.
7. Despliegue. En el primer arranque, MySQL crea las tablas y el catálogo documental a partir de `database/schema.sql`.
8. Abra la URL de la aplicación y pruebe el inicio de sesión.

## Cuenta inicial
El esquema SQL intenta crear `admin`. Por seguridad, verifique que el acceso inicial funciona en su copia antes de dar el sistema por instalado. Si no funciona, cree o actualice el hash con `password_hash()` de PHP y guárdelo en `usuarios.password_hash`; no almacene contraseñas en texto plano. Cambie la contraseña inicial en cuanto entre y cree cuentas individuales para el personal.

## Persistencia de datos
El Compose define dos volúmenes persistentes:
- `mysql_data`: base de datos MySQL.
- `documentos_storage`: documentos PDF/JPG/PNG cargados por los usuarios.

No elimine estos volúmenes al actualizar el contenedor. Una nueva versión de la aplicación debe desplegarse conservándolos. Antes de actualizaciones, haga respaldo tanto de MySQL como del volumen de documentos. Los scripts de `/docker-entrypoint-initdb.d/` solo se ejecutan automáticamente cuando MySQL inicializa un directorio de datos vacío; modificar `schema.sql` después de la instalación no actualiza automáticamente una base existente.

## Prueba local con Docker Compose (opcional)
1. `cp .env.example .env`
2. Edite `.env` y reemplace ambas contraseñas de ejemplo.
3. Ejecute `docker compose up -d --build`
4. Abra `http://IP_DEL_SERVIDOR:PUERTO` según el puerto publicado por su entorno. El Compose no publica un puerto del host por defecto; para prueba local puede agregar temporalmente `ports: ["8080:80"]` al servicio `web` y abrir `http://IP_DEL_SERVIDOR:8080`.
5. Revise los registros con `docker compose logs -f web db`.

## Seguridad y operación
- Limite el acceso a la URL mediante la red local, VPN o reglas de acceso de Coolify si es un sistema interno.
- Active HTTPS en el dominio configurado en Coolify cuando sea posible.
- No publique phpMyAdmin ni MySQL sin controles adicionales.
- Configure respaldos programados y pruebe una restauración real.
- Los recursos Bootstrap/DataTables se cargan desde CDN, por lo que el navegador necesita acceso a Internet para esos estilos y componentes.
- No se ha validado este paquete en el servidor Ubuntu del usuario; después del primer despliegue, revisar los logs y probar inicio de sesión, carga/visualización de documentos, versiones e historial.
