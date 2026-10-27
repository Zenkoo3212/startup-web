# Startup

Página en PHP y MySQL. Esta versión conserva las páginas y la carpeta `css` del proyecto original y añade el envío del correo para recuperar la contraseña.

## Carpetas y archivos

- `css/`: los estilos originales de la página.
- `PHPMailer/`: biblioteca externa para enviar correos SMTP. Incluye únicamente sus tres clases necesarias y su licencia. No necesitas editarla.
- Los archivos PHP de las páginas permanecen en la raíz, como antes.
- `helpers.php`: conserva las utilidades originales y reúne las funciones de recuperación.
- `correo.php`: configura SMTP y redacta el correo con el enlace.
- `config.php`: conecta con MySQL.
- `config.example.php`: plantilla para configurar MySQL, correo y dirección del sitio. Cópiala como `config.local.php` y completa tus datos en el servidor.
- `database.sql`: esquema para instalar una base nueva.
- `actualizar_recuperacion.sql`: actualización para una base existente. No elimina cuentas ni cambia sus contraseñas.
- `.htaccess`: protege archivos internos cuando se usa Apache con `AllowOverride All`, `mod_rewrite` y `mod_headers`.
- `.gitignore`: evita subir la configuración privada a GitHub.
- `Dockerfile` y `.dockerignore`: permiten desplegar en un alojamiento compatible con Docker. No hacen falta para usar XAMPP o un alojamiento PHP convencional.

Se quitaron las carpetas `app`, `tests`, `migrations`, `.github` y `vendor`, y los archivos de Composer. No hace falta instalar dependencias para utilizar el ZIP. Las pruebas de esta simplificación se ejecutan fuera de la carpeta entregada.

## Activar la recuperación de contraseña

1. Usa PHP 8.1 o superior con PDO MySQL, mbstring y OpenSSL, y MySQL o MariaDB. Por ejemplo, coloca el contenido en `htdocs/startup` si usas XAMPP.
2. Si es una instalación nueva, importa `database.sql`. Si ya tienes la base del proyecto, selecciona esa base en phpMyAdmin y ejecuta **solo** `actualizar_recuperacion.sql`, una vez. Este archivo revoca los enlaces anteriores que se mostraban en pantalla. No importes un esquema nuevo sobre tus datos existentes.
3. Copia `config.example.php` como `config.local.php` y completa los datos de MySQL. Las mismas claves también pueden configurarse como variables del entorno del alojamiento, que tienen prioridad sobre el archivo.
4. En esa configuración, define `APP_URL` con la dirección exacta del sitio: por ejemplo, `http://localhost/startup`. En internet usa HTTPS e incluye la subcarpeta si existe, sin barra final.
5. Completa `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_AUTH`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` con los datos de tu proveedor de correo. Normalmente se usa `tls` con el puerto 587, o `ssl` con el 465. El remitente debe estar autorizado por tu proveedor.
6. Registra una cuenta y solicita su recuperación en `recuperar.php`. Abre el enlace recibido y guarda la nueva contraseña en `restablecer.php`. Comprueba que puedes iniciar sesión con ella y que el enlace usado ya no funciona.

`config.local.php` contiene datos privados: configúralo directamente en el servidor y no lo publiques en GitHub.

El enlace vence en una hora y solo se usa una vez. La base almacena el hash del token. Un nuevo envío aceptado invalida los anteriores; si SMTP falla, se conserva el enlace anterior. Se mantienen la validación de contraseña, su confirmación, las transacciones y los límites de solicitudes por correo e IP.

## Publicación

El código está en https://github.com/Zenkoo3212/startup-web. Para que funcionen el registro, el acceso y el correo, el alojamiento debe ejecutar PHP, tener una base MySQL configurada y permitir la salida SMTP. GitHub Pages no ejecuta PHP ni MySQL.

En un alojamiento con Docker, el contenedor escucha en el puerto 80 y la base MySQL se configura como servicio externo. Para una base cuyo nombre sea asignado por el proveedor, importa el esquema omitiendo las instrucciones iniciales `CREATE DATABASE` y `USE` y seleccionando la base asignada. Configura `APP_URL` y las credenciales en el panel del alojamiento.

En Apache se aplican las reglas de `.htaccess`. En Nginx u otro servidor, configura reglas equivalentes para impedir el acceso público a `PHPMailer/`, `config*.php`, `helpers.php`, `correo.php`, `test_db.php`, archivos SQL y archivos de configuración. El servidor `php -S` es solo para desarrollo.

El código está preparado para enviar correo, pero los correos reales requieren la configuración SMTP y un alojamiento activo. Esta simplificación no crea un nuevo alojamiento.
