<?php
/**
 * Plugin Name: Plugin Updater Skeleton
 * Description: Plugin vacío con lógica de actualización desde GitHub, reutilizable en otros plugins.
 * Version: 1.0.2
 * Author: Alejandro Gimeno Martin
 * License: GPLv2 or later
 * Text Domain: plugin-updater-skeleton
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author URI: https://codigoverso.es
 *
 * Este plugin es un esqueleto sin funcionalidad de negocio. Incluye un sistema de actualizaciones
 * compatible con GitHub Releases para simular el comportamiento del repositorio de WordPress.
 * Puedes copiar esta carpeta a otro plugin y reutilizar la clase de updater.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants
if (!defined('PUS_PLUGIN_FILE')) {
    define('PUS_PLUGIN_FILE', __FILE__);
}
if (!defined('PUS_PLUGIN_BASENAME')) {
    define('PUS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('PUS_PLUGIN_DIR')) {
    define('PUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Autoload simple (por si se copia la clase a otra ruta)
require_once PUS_PLUGIN_DIR . 'includes/class-github-plugin-updater.php';

// Configuración del Updater: ajusta estos valores para tu repo
add_action('init', function () {
    // Estos valores deberían apuntar a tu repositorio específico
    $config = [
        // Propietario y repositorio
        'github_user'       => 'Codigoverso',         // ej: codigoverso
        'github_repo'       => 'BaseGitHubPluginUpdates',            // ej: campos-personalizados

        // Opcional: si el repo es privado, puedes usar un token en constantes/filters
        'access_token'      => defined('GITHUB_ACCESS_TOKEN') ? GITHUB_ACCESS_TOKEN : '',

        // Archivo principal del plugin (para version y slug)
        'plugin_file'       => PUS_PLUGIN_FILE,

        // Branch por defecto para leer el readme e info
        'branch'            => 'feat/PruebasPlugin',

        // Si usas releases, habilita esto
        'use_releases'      => true,

        // Cache TTL en segundos para llamadas a la API
        'cache_ttl'         => 30,

        // Campos opcionales que se usarán para los metadatos del plugin
        'homepage'          => 'https://codigoverso.es',
    ];

    // Permite sobrescribir configuración mediante filtros
    $config = apply_filters('pus_updater_config', $config);

    // Inicializa el updater
    if (class_exists('PUS_GitHub_Plugin_Updater')) {
        new PUS_GitHub_Plugin_Updater($config);
    }
});

// Sin funcionalidad adicional: solo el updater.

