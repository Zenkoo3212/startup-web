# Startup

Plataforma PHP y MySQL del proyecto Startup. Conserva las páginas, los perfiles y la administración del proyecto original e incorpora recuperación de contraseña mediante correo SMTP.

## Estado de esta entrega

El código de Startup está publicado en https://github.com/Zenkoo3212/startup-web. La recuperación está implementada y probada localmente. Para recibir correos reales y usar la aplicación en internet falta desplegarla en un alojamiento PHP/MySQL y configurar el servidor SMTP. El repositorio público no sustituye ese alojamiento.

## Activar la recuperación de contraseña

1. Usa PHP 8.1 o superior con PDO MySQL, OpenSSL y mbstring. Se ha probado con PHP 8.3 y MariaDB 10.11.
2. Coloca el proyecto en la carpeta de tu servidor Apache; por ejemplo, `htdocs/startup` en XAMPP.
3. Para una base nueva, importa `database.sql` en phpMyAdmin. Si ya tienes la base original, conserva tus datos y ejecuta **solo** `migrations/001_recuperacion_correo.sql`, seleccionando primero tu base. Ejecuta esta migración una sola vez al actualizar: revoca los enlaces anteriores que se mostraban en pantalla.
4. El ZIP entregado incluye `vendor/`. Al clonar desde GitHub, instala esa carpeta con `composer install --no-dev --prefer-dist`.
5. Copia `config.example.php` como `config.local.php` y completa los datos de MySQL y SMTP. También puedes configurar estas mismas claves como variables del entorno del alojamiento.
6. Define `APP_URL` con la dirección exacta donde vive el proyecto. Ejemplo local: `http://localhost/startup`. En internet usa tu dirección HTTPS, incluyendo la subcarpeta si existe, sin `/` al final. No se obtiene esta dirección del encabezado Host enviado por el visitante.
7. Configura `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_AUTH`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` con los datos de tu proveedor. Usa credenciales SMTP o una contraseña de aplicación cuando tu proveedor la requiera. El remitente debe estar autorizado por el proveedor.
8. Abre `recuperar.php`, escribe el correo de una cuenta registrada y revisa su bandeja de entrada y spam. Abre el enlace recibido, confirma la contraseña nueva e inicia sesión con ella.

No compartas tu contraseña de correo por chat ni la incluyas en GitHub. Configúrala directamente en el servidor. `config.local.php` y `.env` están excluidos del repositorio.

## Publicar en GitHub y dejar la página en internet

GitHub guarda y publica el **código**. GitHub Pages sirve páginas estáticas y no ejecuta PHP ni MySQL: https://docs.github.com/en/pages/getting-started-with-github-pages/creating-a-github-pages-site

Para que funcionen el registro, el inicio de sesión y el correo:

1. El repositorio público es https://github.com/Zenkoo3212/startup-web. Conserva en él `composer.lock` y los archivos de configuración de ejemplo. No subas `config.local.php`, `.env`, copias de bases con usuarios reales ni `vendor/`.
2. Usa un alojamiento con PHP + MySQL y salida SMTP, o uno que despliegue el `Dockerfile` desde el repositorio. El contenedor escucha en el puerto 80; MySQL es un servicio externo.
3. Crea una base y un usuario de MySQL en ese alojamiento. Para una base administrada cuyo nombre lo elige el proveedor, importa `database.sql` omitiendo sus instrucciones iniciales `CREATE DATABASE` y `USE`; selecciona la base asignada. No importes un esquema nuevo sobre datos existentes sin una copia de seguridad.
4. Configura las variables de MySQL y SMTP en el panel del alojamiento. Usa `APP_URL=https://direccion-publica-asignada` y despliega.
5. Comprueba el flujo con una cuenta tuya: solicitar correo → abrir enlace → guardar contraseña → iniciar sesión. Verifica que el mismo enlace se rechace al utilizarlo otra vez.

El archivo `.htaccess` protege la configuración y las carpetas internas en Apache. Activa `AllowOverride All`, `mod_rewrite` y `mod_headers` (incluidos en el Dockerfile). Si usas Nginx u otro servidor, configura reglas equivalentes: no servir `config*.php`, `test_db.php`, archivos SQL o de configuración, ni las carpetas `app`, `vendor`, `tests`, `migrations` y `.github`. El servidor integrado `php -S` es solo para desarrollo.

## Cómo funciona

- El enlace se envía al correo registrado; no se devuelve en la respuesta del navegador.
- Vence en una hora. Solo se almacena un hash SHA-256 del token aleatorio.
- Un nuevo envío aceptado invalida enlaces anteriores. Si SMTP rechaza el envío, la transacción se revierte y los enlaces previos siguen vigentes.
- El cambio de contraseña y la invalidación de los enlaces se realizan en una transacción con bloqueo, para impedir usos simultáneos del mismo enlace.
- Las contraseñas usan `password_hash`; la confirmación y las reglas se comprueban también en el servidor.
- Se limita la solicitud a 3 intentos por correo y 20 por IP en una hora; el límite se guarda en MySQL. Si hay un proxy inverso, configura la IP real del visitante en el servidor web con proxies de confianza.
- La respuesta de éxito no indica si existe una cuenta. Si falta configuración o falla SMTP, se devuelve un error y no se anuncia un envío exitoso.

La aceptación SMTP no garantiza que el proveedor entregue el mensaje en la bandeja principal. Configura la autenticación del dominio y consulta los registros de tu proveedor si un correo no llega.

## Verificación

El repositorio incluye un flujo de GitHub Actions para revisar PHP, instalar dependencias y ejecutar las pruebas de integración con MySQL y un receptor SMTP local. El receptor no envía mensajes a internet.

Para ejecutar las mismas pruebas necesitas PHP, Python 3 y una instancia MySQL/MariaDB local de prueba. Define `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME=startup_reset_test` y ejecuta:

```bash
composer install --no-dev --prefer-dist
python3 tests/integration.py
```

El usuario de prueba debe poder crear esa base. El script se niega a usar un nombre que no termine en `_test`; elimina y vuelve a crear las tablas de esa base de prueba. No lo ejecutes con una base de producción.

Dependencia de correo: PHPMailer, licencia LGPL-2.1: https://github.com/PHPMailer/PHPMailer
