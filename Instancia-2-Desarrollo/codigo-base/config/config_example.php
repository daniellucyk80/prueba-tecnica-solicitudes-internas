<?php
/**
 * Configuración de ejemplo — Sistema de Solicitudes Internas
 * Copiar como config.php y ajustar credenciales
 */
class Config {
    const DB_HOST = 'localhost';
    const DB_NAME = 'sistema_solicitudes';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    const DEBUG_MODE = true;
    const SHOW_ERRORS = true;
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
?>
