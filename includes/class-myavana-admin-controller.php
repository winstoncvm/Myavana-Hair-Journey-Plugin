<?php
/**
 * Admin Controller for Myavana
 *
 * Handles admin menu registration, asset enqueueing, and REST API endpoints.
 *
 * @package Myavana_Hair_Journey
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Admin_Controller
{

    public function __construct()
    {
        // add_action('admin_menu', [$this, 'register_menu']);
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register Admin Menus
     */
    public function register_menu()
    {
        add_menu_page(
            'Myavana Intelligence',
            'Myavana',
            'manage_options',
            'myavana-intelligence-dashboard',
        [$this, 'render_dashboard'],
            'dashicons-chart-area',
            80
        );

        // Submenu pages (all routed to the same dashboard render, handled by React router later)
        $subpages = [
            'Overview Dashboard' => 'myavana-intelligence-dashboard',
            'Executive' => 'myavana-executive-intelligence',
            'Growth' => 'myavana-growth-intelligence',
            'Community' => 'myavana-community-intelligence',
            'Retention' => 'myavana-retention-intelligence',
            'AI Lab' => 'myavana-ai-intelligence',
            'Operations' => 'myavana-operations-intelligence',
            'Settings' => 'myavana-settings',
        ];

        foreach ($subpages as $title => $slug) {
            add_submenu_page(
                'myavana-intelligence-dashboard',
                $title,
                $title,
                'manage_options',
                $slug,
            [$this, 'render_dashboard']
            );
        }
    }

    /**
     * Render the main dashboard container
     */
    public function render_dashboard()
    {
        echo '<div id="myavana-admin-root"></div>';
    }

    /**
     * Enqueue Admin Assets
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'myavana') === false) {
            return;
        }

        // Enqueue React and dependencies
        $asset_file = MYAVANA_DIR . 'build/index.asset.php';

        // Check if build exists (for Phase 2), otherwise fallback or load nothing
        if (file_exists($asset_file)) {
            $assets = require $asset_file;
            wp_enqueue_script(
                'myavana-admin-app',
                MYAVANA_URL . 'build/index.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );

            wp_localize_script('myavana-admin-app', 'myavanaSettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);

            wp_enqueue_style(
                'myavana-admin-style',
                MYAVANA_URL . 'build/index.css',
            [],
                $assets['version']
            );
        }
        else {
        // Fallback for Phase 1 (until React build is ready)
        // We can maybe load the old PHP render if React isn't ready, 
        // but for now we are building the framework.
        }
    }

    /**
     * Register REST API Routes
     */
    public function register_rest_routes()
    {
        register_rest_route('myavana/v1', '/analytics/overview', [
            'methods' => 'GET',
            'callback' => [$this, 'get_overview_analytics'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route('myavana/v1', '/analytics/funnel', [
            'methods' => 'GET',
            'callback' => [$this, 'get_funnel_analytics'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route('myavana/v1', '/analytics/retention', [
            'methods' => 'GET',
            'callback' => [$this, 'get_retention_analytics'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    /**
     * API Callback: Overview
     */
    public function get_overview_analytics($request)
    {
        $date_from = $request->get_param('date_from') ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $request->get_param('date_to') ?: date('Y-m-d');
        $role = $request->get_param('role') ?: 'all';

        $data = Myavana_Analytics_Model::get_overview_metrics($date_from, $date_to, $role);
        return rest_ensure_response($data);
    }

    /**
     * API Callback: Funnel
     */
    public function get_funnel_analytics($request)
    {
        $date_from = $request->get_param('date_from') ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $request->get_param('date_to') ?: date('Y-m-d');
        $role = $request->get_param('role') ?: 'all';

        $data = Myavana_Analytics_Model::get_funnel_data($date_from, $date_to, $role);
        return rest_ensure_response($data);
    }

    /**
     * API Callback: Retention
     */
    public function get_retention_analytics($request)
    {
        $date_from = $request->get_param('date_from') ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $request->get_param('date_to') ?: date('Y-m-d');
        $role = $request->get_param('role') ?: 'all';

        $data = Myavana_Analytics_Model::get_cohort_retention($date_from, $date_to, $role);
        return rest_ensure_response($data);
    }

    /**
     * Permission Check
     */
    public function check_permissions()
    {
        return current_user_can('manage_options');
    }
}