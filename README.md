# Startup

Proyecto PHP y MySQL. Las páginas mantienen sus nombres, direcciones, diseño y funciones. Los archivos internos se agrupan por su función.

| Ubicación | Qué contiene |
| --- | --- |
| Raíz | Páginas y acciones que abre el navegador, como `index.html`, `login.php` y `recuperar.php`. |
| `css/` | Estilos de la página. |
| `imagenes/` | Imagen del menú que aparece en la portada. |
| `configuracion/` | Conexión con MySQL y plantilla de configuración privada. |
| `includes/` | Funciones compartidas, recuperación de contraseña y envío de correo. |
| `includes/PHPMailer/` | Biblioteca externa de correo y su licencia. |
| `base_de_datos/` | Esquema SQL y actualización de recuperación. |
| `documentacion/` | Guía de archivos, instalación y publicación. |

Lee [la guía de archivos e instalación](documentacion/GUIA.md) para saber qué hace cada archivo y cómo poner el proyecto en funcionamiento.

Los jugadores pueden entrar a **Explorar** desde su panel para consultar actividades, objetos, categorías, personajes NPC y tiendas. Incluye búsqueda, detalles, filtro de dificultad y navegación a los objetos de una categoría o tienda. Crear, editar y eliminar contenido sigue siendo exclusivo del administrador. Este apartado utiliza la base existente y no requiere volver a importarla.

Para empezar, copia `configuracion/config.example.php` como `configuracion/config.local.php` y configura MySQL, SMTP y `APP_URL`. No publiques ese archivo privado. Si tu instalación ya tiene `config.local.php` en la raíz, sigue siendo compatible; si existen ambos, tiene prioridad el de `configuracion/`.

Los archivos `.htaccess`, `.gitignore`, `.dockerignore` y `Dockerfile` son configuración técnica, no documentos de Word. Permanecen en las ubicaciones donde Apache, Git y el alojamiento los reconocen.

Código público: https://github.com/Zenkoo3212/startup-web. El alojamiento PHP/MySQL y los datos SMTP deben configurarse para usar cuentas y correos reales.
