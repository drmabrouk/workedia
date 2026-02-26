<?php

class Workedia {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'workedia';
        $this->version = WORKEDIA_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-loader.php';
        require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-db.php';
        require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-settings.php';
        require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-logger.php';
        require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-notifications.php';
        require_once WORKEDIA_PLUGIN_DIR . 'admin/class-workedia-admin.php';
        require_once WORKEDIA_PLUGIN_DIR . 'public/class-workedia-public.php';
        $this->loader = new Workedia_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Workedia_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new Workedia_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_footer', $plugin_public, 'inject_global_alerts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_action('wp_ajax_workedia_get_member', $plugin_public, 'ajax_get_member');
        $this->loader->add_action('wp_ajax_workedia_search_members', $plugin_public, 'ajax_search_members');
        $this->loader->add_action('wp_ajax_workedia_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_workedia_update_member_photo', $plugin_public, 'ajax_update_member_photo');
        $this->loader->add_action('wp_ajax_workedia_send_message_ajax', $plugin_public, 'ajax_send_message');
        $this->loader->add_action('wp_ajax_workedia_get_conversation_ajax', $plugin_public, 'ajax_get_conversation');
        $this->loader->add_action('wp_ajax_workedia_get_conversations_ajax', $plugin_public, 'ajax_get_conversations');
        $this->loader->add_action('wp_ajax_workedia_mark_read', $plugin_public, 'ajax_mark_read');
        $this->loader->add_action('wp_ajax_workedia_get_tickets', $plugin_public, 'ajax_get_tickets');
        $this->loader->add_action('wp_ajax_workedia_create_ticket', $plugin_public, 'ajax_create_ticket');
        $this->loader->add_action('wp_ajax_workedia_get_ticket_details', $plugin_public, 'ajax_get_ticket_details');
        $this->loader->add_action('wp_ajax_workedia_add_ticket_reply', $plugin_public, 'ajax_add_ticket_reply');
        $this->loader->add_action('wp_ajax_workedia_close_ticket', $plugin_public, 'ajax_close_ticket');
        $this->loader->add_action('wp_ajax_workedia_print', $plugin_public, 'handle_print');
        $this->loader->add_action('wp_ajax_workedia_add_member_ajax', $plugin_public, 'ajax_add_member');
        $this->loader->add_action('wp_ajax_workedia_update_member_ajax', $plugin_public, 'ajax_update_member');
        $this->loader->add_action('wp_ajax_workedia_delete_member_ajax', $plugin_public, 'ajax_delete_member');
        $this->loader->add_action('wp_ajax_workedia_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_workedia_add_staff_ajax', $plugin_public, 'ajax_add_staff');
        $this->loader->add_action('wp_ajax_workedia_update_staff_ajax', $plugin_public, 'ajax_update_staff');
        $this->loader->add_action('wp_ajax_workedia_delete_staff_ajax', $plugin_public, 'ajax_delete_staff');
        $this->loader->add_action('wp_ajax_workedia_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_workedia_add_survey', $plugin_public, 'ajax_add_survey');
        $this->loader->add_action('wp_ajax_workedia_cancel_survey', $plugin_public, 'ajax_cancel_survey');
        $this->loader->add_action('wp_ajax_workedia_submit_survey_response', $plugin_public, 'ajax_submit_survey_response');
        $this->loader->add_action('wp_ajax_workedia_get_survey_results', $plugin_public, 'ajax_get_survey_results');
        $this->loader->add_action('wp_ajax_workedia_export_survey_results', $plugin_public, 'ajax_export_survey_results');
        $this->loader->add_action('wp_ajax_workedia_delete_gov_data_ajax', $plugin_public, 'ajax_delete_gov_data');
        $this->loader->add_action('wp_ajax_workedia_merge_gov_data_ajax', $plugin_public, 'ajax_merge_gov_data');
        $this->loader->add_action('wp_ajax_workedia_reset_system_ajax', $plugin_public, 'ajax_reset_system');
        $this->loader->add_action('wp_ajax_workedia_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_workedia_delete_log', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_workedia_clear_all_logs', $plugin_public, 'ajax_clear_all_logs');
        $this->loader->add_action('wp_ajax_workedia_get_user_role', $plugin_public, 'ajax_get_user_role');
        $this->loader->add_action('wp_ajax_workedia_update_member_account_ajax', $plugin_public, 'ajax_update_member_account');
        $this->loader->add_action('wp_ajax_workedia_add_service', $plugin_public, 'ajax_add_service');
        $this->loader->add_action('wp_ajax_workedia_update_service', $plugin_public, 'ajax_update_service');
        $this->loader->add_action('wp_ajax_workedia_get_services_html', $plugin_public, 'ajax_get_services_html');
        $this->loader->add_action('wp_ajax_workedia_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_nopriv_workedia_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_workedia_delete_service', $plugin_public, 'ajax_delete_service');
        $this->loader->add_action('wp_ajax_workedia_submit_service_request', $plugin_public, 'ajax_submit_service_request');
        $this->loader->add_action('wp_ajax_workedia_process_service_request', $plugin_public, 'ajax_process_service_request');
        $this->loader->add_action('wp_ajax_workedia_submit_update_request_ajax', $plugin_public, 'ajax_submit_update_request_ajax');
        $this->loader->add_action('wp_ajax_workedia_process_update_request_ajax', $plugin_public, 'ajax_process_update_request_ajax');
        $this->loader->add_action('wp_ajax_nopriv_workedia_forgot_password_otp', $plugin_public, 'ajax_forgot_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_workedia_reset_password_otp', $plugin_public, 'ajax_reset_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_workedia_activate_account_step1', $plugin_public, 'ajax_activate_account_step1');
        $this->loader->add_action('wp_ajax_nopriv_workedia_activate_account_final', $plugin_public, 'ajax_activate_account_final');
        $this->loader->add_action('wp_ajax_workedia_get_template_ajax', $plugin_public, 'ajax_get_template_ajax');
        $this->loader->add_action('wp_ajax_workedia_save_page_settings', $plugin_public, 'ajax_save_page_settings');
        $this->loader->add_action('wp_ajax_workedia_add_article', $plugin_public, 'ajax_add_article');
        $this->loader->add_action('wp_ajax_workedia_delete_article', $plugin_public, 'ajax_delete_article');
        $this->loader->add_action('wp_ajax_workedia_save_alert', $plugin_public, 'ajax_save_alert');
        $this->loader->add_action('wp_ajax_workedia_delete_alert', $plugin_public, 'ajax_delete_alert');
        $this->loader->add_action('wp_ajax_workedia_acknowledge_alert', $plugin_public, 'ajax_acknowledge_alert');
        $this->loader->add_action('workedia_daily_maintenance', 'Workedia_DB', 'delete_expired_messages');
        $this->loader->add_action('workedia_daily_maintenance', 'Workedia_Notifications', 'run_daily_checks');
    }

    public function run() {
        add_action('plugins_loaded', array($this, 'check_version_updates'));
        $this->loader->add_action('init', $this, 'schedule_maintenance_cron');
        $this->loader->run();
    }

    public function schedule_maintenance_cron() {
        if (function_exists('wp_next_scheduled') && !wp_next_scheduled('workedia_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'workedia_daily_maintenance');
        }
    }

    public function check_version_updates() {
        $db_version = get_option('workedia_plugin_version', '1.0.0');
        if (version_compare($db_version, WORKEDIA_VERSION, '<')) {
            require_once WORKEDIA_PLUGIN_DIR . 'includes/class-workedia-activator.php';
            Workedia_Activator::activate();
            update_option('workedia_plugin_version', WORKEDIA_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
