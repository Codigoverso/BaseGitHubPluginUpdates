<?php
/**
 * GitHub Plugin Updater - Clase reusable
 *
 * Esta clase implementa la lógica para:
 * - Comprobar si hay actualizaciones disponibles en GitHub (Tags/Releases)
 * - Integrarse con las pantallas de actualizaciones de WordPress
 * - Proveer metadatos del plugin (versión, changelog, etc.)
 *
 * Uso:
 * new PUS_GitHub_Plugin_Updater([
 *   'github_user' => 'usuario',
 *   'github_repo' => 'repo',
 *   'plugin_file' => __FILE__,
 *   'use_releases' => true,
 *   'branch' => 'main',
 *   'access_token' => '', // opcional
 * ]);
 */

if (!defined('ABSPATH')) { exit; }

class PUS_GitHub_Plugin_Updater {
    private $github_user;
    private $github_repo;
    private $plugin_file;
    private $use_releases = true;
    private $branch = 'main';
    private $access_token = '';
    private $cache_ttl = 1800; // 30 min
    private $homepage = '';

    private $plugin_slug;
    private $plugin_basename;
    private $plugin_version;

    public function __construct(array $config) {
        $this->github_user  = $config['github_user'] ?? '';
        $this->github_repo  = $config['github_repo'] ?? '';
        $this->plugin_file  = $config['plugin_file'] ?? '';
        $this->use_releases = (bool)($config['use_releases'] ?? true);
        $this->branch       = $config['branch'] ?? 'main';
        $this->access_token = $config['access_token'] ?? '';
        $this->cache_ttl    = (int)($config['cache_ttl'] ?? 1800);
        $this->homepage     = $config['homepage'] ?? '';

        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->plugin_slug     = basename($this->plugin_basename, '.php');
        $this->plugin_version  = $this->read_plugin_version($this->plugin_file);

        // Hooks de actualización
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugins_api'], 10, 3);
        add_filter('upgrader_pre_install', [$this, 'upgrader_pre_install'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'upgrader_post_install'], 10, 3);
        add_filter('http_request_args', [$this, 'http_request_args'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
    }

    /* =====================
     * Integración de Updates
     * ===================== */

    public function check_for_update($transient) {
        if (empty($transient) || !isset($transient->checked)) {
            return $transient;
        }

        $remote = $this->get_remote_release_info();
        if (!$remote) {
            return $transient;
        }

        $remote_version = $remote['version'] ?? null;
        $download_url   = $remote['zip_url'] ?? null;
        if (!$remote_version || !$download_url) {
            return $transient;
        }

        $current_version = $this->plugin_version;
        if (version_compare($remote_version, $current_version, '>')) {
            $package = [
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $remote_version,
                'url'         => $remote['homepage'] ?? $this->homepage,
                'package'     => $download_url,
            ];
            $transient->response[$this->plugin_basename] = (object)$package;
        }

        return $transient;
    }

    public function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $remote = $this->get_remote_release_info();
        if (!$remote) { return $result; }

        $info = (object)[
            'name' => $this->plugin_slug,
            'slug' => $this->plugin_slug,
            'version' => $remote['version'] ?? $this->plugin_version,
            'author' => 'Alejandro Gimeno Martin',
            'homepage' => $remote['homepage'] ?? $this->homepage,
            'requires' => '5.8',
            'tested' => get_bloginfo('version'),
            'download_link' => $remote['zip_url'] ?? '',
            'sections' => [
                'description' => $remote['description'] ?? 'Actualización desde GitHub Releases.',
                'changelog' => $remote['changelog'] ?? '',
            ],
        ];
        return $info;
    }

    public function upgrader_pre_install($bool, $hook_extra, $result) {
        return $bool;
    }

    public function upgrader_post_install($bool, $hook_extra, $result) {
        // Asegura que el plugin se reinstale en su carpeta correcta si el zip tiene nombre distinto
        $proper_destination = WP_PLUGIN_DIR . '/' . dirname($this->plugin_basename);
        if (!empty($result['destination']) && $result['destination'] !== $proper_destination) {
            // mover archivos
            $this->recursive_move($result['destination'], $proper_destination);
            $result['destination'] = $proper_destination;
        }
        return $bool;
    }

    public function http_request_args($args, $url) {
        // Mejora de compat: añade token para API de GitHub si procede
        if ($this->access_token &&
            (strpos($url, 'api.github.com') !== false || strpos($url, 'github.com') !== false)) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }
        return $args;
    }

    public function plugin_row_meta($links, $file) {
        if ($file === $this->plugin_basename) {
            $links[] = '<a href="https://github.com/' . esc_attr($this->github_user) . '/' . esc_attr($this->github_repo) . '" target="_blank">GitHub</a>';
        }
        return $links;
    }

    /* =====================
     * Utilidades
     * ===================== */

    private function read_plugin_version($file) {
        $data = get_file_data($file, [
            'Version' => 'Version',
        ]);
        return $data['Version'] ?? '0.0.0';
    }

    private function get_remote_release_info() {
        $cache_key = 'pus_release_' . md5($this->github_user . '/' . $this->github_repo);
        $cached = get_transient($cache_key);
        if ($cached) { return $cached; }

        if (empty($this->github_user) || empty($this->github_repo)) {
            return null;
        }

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ];
        if ($this->access_token) {
            $headers['Authorization'] = 'token ' . $this->access_token;
        }

        // Construir URL para Releases o Tags
        $api_url = $this->use_releases
            ? 'https://api.github.com/repos/' . rawurlencode($this->github_user) . '/' . rawurlencode($this->github_repo) . '/releases/latest'
            : 'https://api.github.com/repos/' . rawurlencode($this->github_user) . '/' . rawurlencode($this->github_repo) . '/tags';

        $response = wp_remote_get($api_url, [ 'headers' => $headers, 'timeout' => 15 ]);
        if (is_wp_error($response)) { return null; }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code !== 200 || empty($body)) { return null; }
        $json = json_decode($body, true);

        if ($this->use_releases) {
            // releases/latest
            $version = $json['tag_name'] ?? null;
            $zip_url = $json['zipball_url'] ?? null;
            $changelog = $json['body'] ?? '';
        } else {
            // first tag
            $first = is_array($json) && !empty($json) ? $json[0] : null;
            $version = $first['name'] ?? null;
            // zip URL estándar del repo para el tag
            $zip_url = 'https://github.com/' . rawurlencode($this->github_user) . '/' . rawurlencode($this->github_repo) . '/archive/refs/tags/' . rawurlencode($version) . '.zip';
            $changelog = '';
        }

        if (!$version || !$zip_url) { return null; }

        $info = [
            'version' => ltrim($version, 'v'),
            'zip_url' => $zip_url,
            'changelog' => $changelog,
            'homepage' => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'description' => 'Actualización automática desde GitHub.',
        ];

        set_transient($cache_key, $info, $this->cache_ttl);
        return $info;
    }

    private function recursive_move($source, $dest) {
        if (!is_dir($dest)) { wp_mkdir_p($dest); }
        $dir = opendir($source);
        if (!$dir) { return; }
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') { continue; }
            $src = trailingslashit($source) . $file;
            $dst = trailingslashit($dest) . $file;
            if (is_dir($src)) {
                $this->recursive_move($src, $dst);
            } else {
                // mover/overwrite
                if (!@rename($src, $dst)) {
                    @copy($src, $dst);
                }
            }
        }
        closedir($dir);
        // intenta eliminar el origen
        @rmdir($source);
    }
}

