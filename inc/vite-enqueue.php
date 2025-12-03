<?php
// inc/vite-enqueue.php

define('VITE_SERVER', 'http://localhost:3000');
define('VITE_ENTRY_POINT', '/src/js/main.js');

// Track which handles are Vite assets (need type="module")
global $vite_script_handles;
$vite_script_handles = [];

function vite_is_dev_server_running()
{
    static $is_dev = null;
    if ($is_dev !== null) return $is_dev;

    // Check if dev server is running
    $is_dev = false;
    $handle = @fsockopen('localhost', 3000, $errno, $errstr, 0.1);
    if ($handle) {
        fclose($handle);
        $is_dev = true;
    }
    return $is_dev;
}

/**
 * Register or enqueue a Vite asset
 * 
 * @param string $handle       Script handle
 * @param string $entry_point  Entry point path (e.g., '/src/js/main.js')
 * @param array  $dependencies Script dependencies
 * @param bool   $enqueue      Whether to enqueue (true) or just register (false)
 */
function vite_enqueue_asset($handle, $entry_point, $dependencies = [], $enqueue = true)
{
    global $vite_script_handles;
    $vite_script_handles[] = $handle;
    
    $is_dev = vite_is_dev_server_running();

    if ($is_dev) {
        // Dev Mode: Load from Vite Server
        // Vite Client for HMR (only once)
        if (!wp_script_is('vite-client', 'registered')) {
            wp_register_script('vite-client', VITE_SERVER . '/@vite/client', [], null, true);
            $vite_script_handles[] = 'vite-client';
        }
        
        // Add vite-client as a dependency for dev mode
        $dev_dependencies = array_merge(['vite-client'], $dependencies);
        
        // Register the script with vite-client as dependency
        wp_register_script($handle, VITE_SERVER . $entry_point, $dev_dependencies, null, true);
        
        // Enqueue if requested
        if ($enqueue) {
            wp_enqueue_script($handle);
        }
    } else {
        // Production: Load from Manifest
        $manifest_path = get_theme_file_path('/dist/.vite/manifest.json');
        if (!file_exists($manifest_path)) {
            // Try alternate location
            $manifest_path = get_theme_file_path('/dist/manifest.json');
        }
        if (!file_exists($manifest_path)) return;

        $manifest = json_decode(file_get_contents($manifest_path), true);
        // Remove leading slash for manifest lookup
        $manifest_key = ltrim($entry_point, '/');

        if (isset($manifest[$manifest_key])) {
            $file = $manifest[$manifest_key]['file'];
            
            // Register the script
            wp_register_script($handle, get_theme_file_uri('/dist/' . $file), $dependencies, null, true);

            // Register CSS associated with this entry if it exists
            if (!empty($manifest[$manifest_key]['css'])) {
                foreach ($manifest[$manifest_key]['css'] as $css_file) {
                    wp_register_style($handle . '-style', get_theme_file_uri('/dist/' . $css_file));
                    if ($enqueue) {
                        wp_enqueue_style($handle . '-style');
                    }
                }
            }
            
            // Enqueue if requested
            if ($enqueue) {
                wp_enqueue_script($handle);
            }
        }
    }
}

/**
 * Register a Vite asset without enqueueing (for blocks)
 */
function vite_register_asset($handle, $entry_point, $dependencies = [])
{
    vite_enqueue_asset($handle, $entry_point, $dependencies, false);
}

// Add type="module" to all Vite-registered scripts (both dev and production)
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    global $vite_script_handles;
    
    if (in_array($handle, $vite_script_handles)) {
        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
    return $tag;
}, 10, 3);
