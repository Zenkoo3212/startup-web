# Guía de archivos de Startup

## Qué se reorganizó

Se movieron los archivos internos a carpetas y se actualizaron sus rutas de carga. Las direcciones públicas de las páginas siguen en la raíz para conservar enlaces, formularios y enlaces de recuperación enviados por correo. El diseño, las reglas de recuperación y las funciones de las páginas se conservan.

## Páginas y acciones en la raíz

| Archivo | Función |
| --- | --- |
| `index.html` | Portada pública con la imagen del juego y botones de acceso. |
| `register.php` | Formulario y registro de cuentas. |
| `login.php` | Formulario e inicio de sesión. |
| `logout.php` | Cierre de sesión. |
| `dashboard.php` | Panel de la cuenta. |
| `recuperar.php` | Solicita el correo para recuperar la contraseña. |
| `restablecer.php` | Recibe el enlace y guarda la nueva contraseña. |
| `categorias.php` | Administración de categorías. |
| `objetos.php` | Administración del catálogo de objetos. |
| `npcs.php` | Administración del catálogo de personajes NPC. |
| `actividades.php` | Administración del catálogo de actividades. |
| `tiendas.php` | Administración del catálogo de tiendas. |
| `obtener_usuario.php` | Consulta protegida de los datos del usuario. |
| `actualizar_personaje.php` | Guarda las opciones de apariencia del usuario. |
| `eliminar_cuenta.php` | Elimina una cuenta tras validar la contraseña. |
| `descargar_bd.php` | Permite al administrador descargar el archivo del esquema SQL; no es una copia de los usuarios actuales. |
| `test_db.php` | Diagnóstico de conexión con MySQL. Apache bloquea su acceso público mediante las reglas incluidas. |

## Contenido de las carpetas

| Archivo | Qué contiene y cuándo editarlo |
| --- | --- |
| `css/estilo.css` | Estilos compartidos del panel y los catálogos. Se edita para cambiar su apariencia. Otras páginas también tienen estilos dentro de su HTML. |
| `imagenes/startup-menu.png` | La imagen proporcionada para la portada. |
| `configuracion/config.php` | Conexión PDO con MySQL. Lee la configuración privada o las variables del alojamiento. |
| `configuracion/config.example.php` | Plantilla con valores de ejemplo, sin contraseñas reales. Se copia para configurar una instalación. |
| `configuracion/config.local.php` | Configuración privada que debes crear en tu servidor; no se incluye en la entrega ni se sube a GitHub. |
| `includes/helpers.php` | Funciones de sesiones, permisos, validaciones y recuperación de contraseña. |
| `includes/correo.php` | Lectura de configuración, utilidades JSON, configuración SMTP y contenido del correo. |
| `includes/PHPMailer/PHPMailer.php` | Biblioteca externa que construye y envía los mensajes. No necesitas editarla para configurar tu correo. |
| `includes/PHPMailer/SMTP.php` | Implementación de la comunicación SMTP de la biblioteca. |
| `includes/PHPMailer/Exception.php` | Clase de errores de la biblioteca. |
| `includes/PHPMailer/LICENSE` | Licencia que debe acompañar la biblioteca. No es código de tu videojuego. |
| `base_de_datos/database.sql` | Esquema de la base para una instalación nueva y datos de ejemplo. |
| `base_de_datos/actualizar_recuperacion.sql` | Actualización para la base existente: crea los contadores de solicitudes y revoca enlaces antiguos. Se ejecuta una sola vez al actualizar. |
| `documentacion/GUIA.md` | Este documento de explicación e instalación. No participa en el funcionamiento de las páginas. |

Las carpetas internas `configuracion`, `includes`, `base_de_datos` y `documentacion` incluyen un `.htaccess` con `Require all denied`. Impide abrir directamente su contenido por HTTP en Apache; PHP puede seguir cargándolo desde el servidor.

## Qué son los otros archivos

| Archivo | Explicación |
| --- | --- |
| `README.md` | Presentación breve y mapa de carpetas. GitHub lo muestra al abrir el repositorio. |
| `.htaccess` de la raíz | Entrada de la web, bloqueo de archivos internos y cabeceras de seguridad para Apache. |
| `.gitignore` | Evita subir a GitHub claves privadas, registros y archivos temporales. No elimina secretos que ya se hubieran publicado. |
| `Dockerfile` | Instrucciones para ejecutar PHP con Apache en un alojamiento compatible con Docker. Se conserva en la raíz para su detección. No se necesita para XAMPP. |
| `.dockerignore` | Evita copiar secretos y archivos innecesarios al construir el contenedor. |

Las extensiones indican su propósito: `.php` es código del servidor, `.html` estructura de página, `.css` estilos, `.png` una imagen, `.sql` instrucciones de base de datos y `.md` documentación de texto. No debes importar los archivos de documentación a MySQL ni abrir PHP con doble clic para ejecutar el sitio.

El documento Word de aprendizaje entregado anteriormente describe la distribución anterior de carpetas. Esta guía corresponde a la organización actual.

## Instalar y configurar

1. Usa PHP 8.1 o superior con PDO MySQL, mbstring y OpenSSL, y MySQL o MariaDB. En XAMPP coloca la carpeta del proyecto, por ejemplo, en `htdocs/startup`.
2. Para una instalación nueva, importa `base_de_datos/database.sql` en phpMyAdmin. Si ya tienes la base original, selecciona esa base y ejecuta solamente `base_de_datos/actualizar_recuperacion.sql`, una vez. No importes el esquema nuevo sobre tus datos existentes.
3. Copia `configuracion/config.example.php` como `configuracion/config.local.php`. Completa los valores `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS`.
4. Define `APP_URL` con la dirección exacta del sitio: por ejemplo, `http://localhost/startup`. Para internet usa HTTPS e incluye la subcarpeta si existe, sin barra final.
5. Completa `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_AUTH`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` con los datos de tu proveedor. Normalmente se usa `tls` en 587 o `ssl` en 465. El remitente debe estar autorizado.
6. Abre `http://localhost/startup`, registra una cuenta y prueba el acceso. Luego solicita la recuperación, abre el enlace recibido, cambia la contraseña y comprueba el acceso con ella.

Las variables del entorno del alojamiento tienen prioridad sobre el archivo privado. Por compatibilidad se sigue leyendo `config.local.php` de la raíz si no existe el de `configuracion/`. No se requieren Composer ni descargas adicionales para usar esta entrega.

## Publicar

GitHub publica el código. Para ejecutar esta página necesitas alojamiento PHP/MySQL con salida SMTP; GitHub Pages no ejecuta PHP ni MySQL. En un alojamiento convencional sube el contenido a su carpeta pública y configura la base y el correo.

En Docker el contenedor escucha en el puerto 80 y MySQL es un servicio externo. Si el proveedor asigna el nombre de la base, importa el esquema omitiendo las instrucciones iniciales `CREATE DATABASE` y `USE` y seleccionando la base asignada.

Apache necesita `AllowOverride All`, `mod_rewrite` y `mod_headers` para aplicar las reglas incluidas; el Dockerfile los prepara. En Nginx u otro servidor configura reglas equivalentes para bloquear las carpetas internas, configuraciones, archivos SQL y `test_db.php`. El servidor `php -S` es solo para desarrollo.

La reorganización conserva el envío SMTP, el enlace de una hora y un solo uso, los límites por correo e IP y las transacciones del cambio de contraseña. Las pruebas locales no equivalen a comprobar la entrega en una bandeja real: para eso hace falta configurar el correo y el alojamiento.
