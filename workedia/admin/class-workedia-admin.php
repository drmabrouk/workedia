<?php

class Workedia_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        add_menu_page(
            'Workedia',
            'Workedia',
            'read', // Allow all roles to see top level
            'workedia-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-welcome-learn-more',
            6
        );

        add_submenu_page(
            'workedia-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            'read',
            'workedia-dashboard',
            array($this, 'display_dashboard')
        );


        add_submenu_page(
            'workedia-dashboard',
            'إدارة مستخدمي النظام',
            'إدارة مستخدمي النظام',
            'manage_options',
            'workedia-users',
            array($this, 'display_users_management')
        );

        add_submenu_page(
            'workedia-dashboard',
            'إعدادات النظام',
            'إعدادات النظام',
            'manage_options',
            'workedia-settings',
            array($this, 'display_settings')
        );

        add_submenu_page(
            'workedia-dashboard',
            'الإعدادات المتقدمة',
            'الإعدادات المتقدمة',
            'manage_options',
            'workedia-advanced',
            array($this, 'display_advanced_settings')
        );
    }

    public function display_advanced_settings() {
        $_GET['workedia_tab'] = 'advanced-settings';
        $this->display_settings();
    }

    public function enqueue_styles() {
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        wp_enqueue_style($this->plugin_name, WORKEDIA_PLUGIN_URL . 'assets/css/workedia-admin.css', array(), $this->version, 'all');

        $appearance = Workedia_Settings::get_appearance();
        $custom_css = "
            :root {
                --workedia-primary-color: {$appearance['primary_color']};
                --workedia-secondary-color: {$appearance['secondary_color']};
                --workedia-accent-color: {$appearance['accent_color']};
                --workedia-dark-color: {$appearance['dark_color']};
                --workedia-radius: {$appearance['border_radius']};
            }
            .workedia-content-wrapper, .workedia-admin-dashboard, .workedia-container,
            .workedia-content-wrapper *:not(.dashicons), .workedia-admin-dashboard *:not(.dashicons), .workedia-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .workedia-content-wrapper { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function display_dashboard() {
        $_GET['workedia_tab'] = 'summary';
        $this->display_settings();
    }


    public function display_settings() {
        if (isset($_POST['workedia_save_settings_unified'])) {
            check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');

            // 1. Save Workedia Info
            $info = Workedia_Settings::get_workedia_info();
            $info['workedia_name'] = sanitize_text_field($_POST['workedia_name']);
            $info['workedia_officer_name'] = sanitize_text_field($_POST['workedia_officer_name']);
            $info['phone'] = sanitize_text_field($_POST['workedia_phone']);
            $info['email'] = sanitize_email($_POST['workedia_email']);
            $info['workedia_logo'] = esc_url_raw($_POST['workedia_logo']);
            $info['address'] = sanitize_text_field($_POST['workedia_address']);
            $info['map_link'] = esc_url_raw($_POST['workedia_map_link'] ?? '');
            $info['extra_details'] = sanitize_textarea_field($_POST['workedia_extra_details'] ?? '');

            Workedia_Settings::save_workedia_info($info);

            // 2. Save Section Labels
            $labels = Workedia_Settings::get_labels();
            foreach($labels as $key => $val) {
                if (isset($_POST[$key])) {
                    $labels[$key] = sanitize_text_field($_POST[$key]);
                }
            }
            Workedia_Settings::save_labels($labels);

            wp_redirect(add_query_arg(['workedia_tab' => 'global-settings', 'sub' => 'init', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }

        if (isset($_GET['settings_saved'])) {
            echo '<div class="updated notice is-dismissible"><p>تم حفظ الإعدادات بنجاح.</p></div>';
        }

        if (isset($_POST['workedia_save_appearance'])) {
            check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');
            Workedia_Settings::save_appearance(array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'accent_color' => sanitize_hex_color($_POST['accent_color']),
                'dark_color' => sanitize_hex_color($_POST['dark_color']),
                'font_size' => sanitize_text_field($_POST['font_size']),
                'border_radius' => sanitize_text_field($_POST['border_radius']),
                'table_style' => sanitize_text_field($_POST['table_style']),
                'button_style' => sanitize_text_field($_POST['button_style'])
            ));
            wp_redirect(add_query_arg(['workedia_tab' => 'global-settings', 'sub' => 'design', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }



        $member_filters = array();
        $stats = Workedia_DB::get_statistics();
        $members = Workedia_DB::get_members();
        include WORKEDIA_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }

    public function display_users_management() {
        $_GET['workedia_tab'] = 'users-management';
        $this->display_settings();
    }

}
