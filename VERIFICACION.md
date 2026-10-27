# Verificación de la recuperación de contraseña

Fecha: 27 de octubre de 2026.

Se ejecutaron 24 comprobaciones de integración con PHP 8.3, MariaDB 10.11 y un receptor SMTP local, todas correctas. También se revisó la sintaxis PHP de los archivos de la aplicación y el JavaScript de recuperación/restablecimiento. Composer no informó vulnerabilidades conocidas para la dependencia instalada.

Se comprobó el registro original, la aceptación del mensaje por SMTP, la URL absoluta, el almacenamiento del hash del token, el cambio de contraseña y el inicio de sesión con la contraseña nueva. Se rechazaron contraseñas inválidas, confirmaciones diferentes, enlaces vencidos o utilizados y tokens inventados. Los límites por correo/IP, la reversión ante un fallo SMTP y el consumo único ante dos solicitudes simultáneas también pasaron.

Las pruebas no enviaron mensajes a una bandeja de correo real. La conexión SMTP autenticada y cifrada del proveedor, la entrega a una cuenta real, el despliegue del contenedor y la publicación en GitHub deben comprobarse al disponer de las cuentas y de la configuración del alojamiento.
