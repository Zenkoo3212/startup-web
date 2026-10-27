<?php
// Copia este archivo como config.local.php. Nunca lo subas con tus datos reales.
// Las variables del entorno del alojamiento tienen prioridad sobre este archivo.
return [
    'APP_URL' => 'http://localhost/startup', // En internet: https://tu-dominio.com
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306,
    'DB_NAME' => 'startup_db',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'SMTP_HOST' => 'smtp.tu-proveedor.com',
    'SMTP_PORT' => 587,
    'SMTP_ENCRYPTION' => 'tls', // tls para STARTTLS/587; ssl para SMTPS/465.
    'SMTP_AUTH' => true,
    'SMTP_USERNAME' => 'tu-correo@tu-dominio.com',
    'SMTP_PASSWORD' => 'CONFIGURA_LA_CLAVE_EN_EL_SERVIDOR',
    'MAIL_FROM_ADDRESS' => 'tu-correo@tu-dominio.com',
    'MAIL_FROM_NAME' => 'Startup',
];
