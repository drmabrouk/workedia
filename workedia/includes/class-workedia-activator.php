<?php

class Workedia_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $installed_ver = get_option('workedia_db_version');

        // Migration: Rename old tables if they exist
        if (version_compare($installed_ver, '97.3.0', '<')) {
            self::migrate_tables();
            self::migrate_settings();
        }

        $sql = "";

        // Members Table
        $table_name = $wpdb->prefix . 'workedia_members';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            national_id varchar(14) NOT NULL,
            member_code tinytext,
            name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            residence_street text,
            residence_city tinytext,
            residence_governorate tinytext,
            governorate tinytext,
            membership_number tinytext,
            membership_start_date date,
            membership_expiration_date date,
            membership_status tinytext,
            email tinytext,
            phone tinytext,
            alt_phone tinytext,
            notes text,
            photo_url text,
            wp_user_id bigint(20),
            officer_id bigint(20),
            registration_date date,
            sort_order int DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY national_id (national_id),
            KEY wp_user_id (wp_user_id),
            KEY officer_id (officer_id)
        ) $charset_collate;\n";


        // Messages Table
        $table_name = $wpdb->prefix . 'workedia_messages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            member_id mediumint(9),
            message text NOT NULL,
            file_url text,
            governorate varchar(50),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY member_id (member_id),
            KEY governorate (governorate)
        ) $charset_collate;\n";

        // Logs Table
        $table_name = $wpdb->prefix . 'workedia_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action tinytext NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Surveys Table
        $table_name = $wpdb->prefix . 'workedia_surveys';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title tinytext NOT NULL,
            questions text NOT NULL,
            recipients tinytext NOT NULL,
            status enum('active', 'completed', 'cancelled') DEFAULT 'active',
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $charset_collate;\n";

        // Survey Responses Table
        $table_name = $wpdb->prefix . 'workedia_survey_responses';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            survey_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            responses text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY survey_id (survey_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Update Requests Table
        $table_name = $wpdb->prefix . 'workedia_update_requests';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            requested_data text NOT NULL,
            status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            processed_at datetime,
            processed_by bigint(20),
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Digital Services Table
        $table_name = $wpdb->prefix . 'workedia_services';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            description text,
            required_fields text,
            selected_profile_fields text,
            status enum('active', 'suspended') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Service Requests Table
        $table_name = $wpdb->prefix . 'workedia_service_requests';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            member_id mediumint(9) NOT NULL,
            request_data text NOT NULL,
            status enum('pending', 'processing', 'approved', 'rejected') DEFAULT 'pending',
            processed_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY member_id (member_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Notification Templates Table
        $table_name = $wpdb->prefix . 'workedia_notification_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            days_before int DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;\n";

        // Notification Logs Table
        $table_name = $wpdb->prefix . 'workedia_notification_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9),
            notification_type varchar(50),
            recipient_email varchar(100),
            subject varchar(255),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20),
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY sent_at (sent_at)
        ) $charset_collate;\n";

        // Tickets Table
        $table_name = $wpdb->prefix . 'workedia_tickets';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            subject varchar(255) NOT NULL,
            category varchar(50),
            priority enum('low', 'medium', 'high') DEFAULT 'medium',
            status enum('open', 'in-progress', 'closed') DEFAULT 'open',
            province varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY status (status),
            KEY province (province)
        ) $charset_collate;\n";

        // Ticket Thread Table
        $table_name = $wpdb->prefix . 'workedia_ticket_thread';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ticket_id mediumint(9) NOT NULL,
            sender_id bigint(20) NOT NULL,
            message text NOT NULL,
            file_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY sender_id (sender_id)
        ) $charset_collate;\n";

        // Pages Table
        $table_name = $wpdb->prefix . 'workedia_pages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            shortcode varchar(50) NOT NULL,
            instructions text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;\n";

        // Articles Table
        $table_name = $wpdb->prefix . 'workedia_articles';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            image_url text,
            author_id bigint(20),
            status enum('publish', 'draft') DEFAULT 'publish',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alerts Table
        $table_name = $wpdb->prefix . 'workedia_alerts';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            severity enum('info', 'warning', 'critical') DEFAULT 'info',
            must_acknowledge tinyint(1) DEFAULT 0,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alert Views Table
        $table_name = $wpdb->prefix . 'workedia_alert_views';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            alert_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            acknowledged tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY alert_id (alert_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('workedia_db_version', WORKEDIA_VERSION);

        self::setup_roles();
        self::seed_notification_templates();
    }

    private static function seed_notification_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'workedia_notification_templates';
        $templates = [
            'membership_renewal' => [
                'subject' => 'تذكير: تجديد عضوية Workedia',
                'body' => "عزيزي العضو {member_name}،\n\nنود تذكيركم بقرب موعد تجديد عضويتكم السنوية لعام {year}.\nيرجى السداد لتجنب الغرامات.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'welcome_activation' => [
                'subject' => 'مرحباً بك في المنصة الرقمية لنقابتك',
                'body' => "أهلاً بك يا {member_name}،\n\nتم تفعيل حسابك بنجاح في المنصة الرقمية.\nيمكنك الآن الاستفادة من كافة الخدمات الإلكترونية.\n\nرقم عضويتك: {membership_number}",
                'days_before' => 0
            ],
            'admin_alert' => [
                'subject' => 'تنبيه إداري من Workedia',
                'body' => "عزيزي العضو {member_name}،\n\n{alert_message}\n\nشكراً لكم.",
                'days_before' => 0
            ]
        ];

        foreach ($templates as $type => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE template_type = %s", $type));
            if (!$exists) {
                $wpdb->insert($table, [
                    'template_type' => $type,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'days_before' => $data['days_before'],
                    'is_enabled' => 1
                ]);
            }
        }
    }

    private static function migrate_settings() {
        // Core info migration
        $old_info = get_option('sm_syndicate_info');
        if ($old_info && !get_option('workedia_info')) {
            $mapped_info = [];
            foreach ((array)$old_info as $key => $value) {
                $new_key = str_replace(['syndicate_', 'sm_'], 'workedia_', $key);
                $mapped_info[$new_key] = $value;
            }
            // Ensure essential keys are present
            if (isset($old_info['syndicate_name'])) $mapped_info['workedia_name'] = $old_info['syndicate_name'];
            if (isset($old_info['syndicate_officer_name'])) $mapped_info['workedia_officer_name'] = $old_info['syndicate_officer_name'];
            if (isset($old_info['syndicate_logo'])) $mapped_info['workedia_logo'] = $old_info['syndicate_logo'];

            update_option('workedia_info', $mapped_info);
        }

        // Settings migration
        $settings_to_migrate = [
            'sm_appearance'            => 'workedia_appearance',
            'sm_labels'                => 'workedia_labels',
            'sm_notification_settings' => 'workedia_notification_settings',
            'sm_last_backup_download'  => 'workedia_last_backup_download',
            'sm_last_backup_import'    => 'workedia_last_backup_import',
            'sm_plugin_version'        => 'workedia_plugin_version'
        ];

        foreach ($settings_to_migrate as $old => $new) {
            $val = get_option($old);
            if ($val !== false && get_option($new) === false) {
                update_option($new, $val);
            }
        }
    }

    private static function migrate_tables() {
        global $wpdb;
        // Rebranding Migration (sm_ -> workedia_)
        $mappings = array(
            'sm_members'                => 'workedia_members',
            'sm_messages'               => 'workedia_messages',
            'sm_logs'                   => 'workedia_logs',
            'sm_surveys'                => 'workedia_surveys',
            'sm_survey_responses'       => 'workedia_survey_responses',
            'sm_payments'               => 'workedia_payments',
            'sm_update_requests'        => 'workedia_update_requests',
            'sm_services'               => 'workedia_services',
            'sm_service_requests'       => 'workedia_service_requests',
            'sm_notification_templates' => 'workedia_notification_templates',
            'sm_notification_logs'      => 'workedia_notification_logs',
            'sm_documents'              => 'workedia_documents',
            'sm_document_logs'          => 'workedia_document_logs',
            'sm_pub_templates'          => 'workedia_pub_templates',
            'sm_pub_documents'          => 'workedia_pub_documents',
            'sm_tickets'                => 'workedia_tickets',
            'sm_ticket_thread'          => 'workedia_ticket_thread',
            'sm_pages'                  => 'workedia_pages',
            'sm_articles'               => 'workedia_articles',
            'sm_alerts'                 => 'workedia_alerts',
            'sm_alert_views'            => 'workedia_alert_views'
        );

        foreach ($mappings as $old => $new) {
            $old_table = $wpdb->prefix . $old;
            $new_table = $wpdb->prefix . $new;
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        $members_table = $wpdb->prefix . 'workedia_members';
        if ($wpdb->get_var("SHOW TABLES LIKE '$members_table'")) {
            // Fix Member ID to Member ID (legacy fix if needed)
            $col_member = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE 'member_id'");
            if (!empty($col_member)) {
                $wpdb->query("ALTER TABLE $members_table CHANGE member_id member_id mediumint(9)");
            }
        }
    }

    private static function setup_roles() {
        // Remove custom roles if they exist
        remove_role('workedia_system_admin');
        remove_role('workedia_admin');
        remove_role('workedia_member');
        remove_role('workedia_officer');
        remove_role('workedia_syndicate_admin');
        remove_role('workedia_syndicate_member');
        remove_role('sm_system_admin');
        remove_role('sm_syndicate_admin');
        remove_role('sm_syndicate_member');
        remove_role('sm_officer');
        remove_role('sm_member');
        remove_role('sm_parent');
        remove_role('sm_student');

        // Remove custom capabilities from administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = [
                'workedia_manage_system',
                'workedia_manage_users',
                'workedia_manage_members',
                'workedia_manage_finance',
                'workedia_manage_licenses',
                'workedia_print_reports',
                'workedia_full_access',
                'workedia_manage_archive'
            ];
            foreach ($custom_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }

        self::migrate_user_meta();
        self::migrate_user_roles();
        self::sync_missing_member_accounts();
        self::create_pages();
    }

    private static function migrate_user_meta() {
        global $wpdb;
        $meta_mappings = [
            'sm_governorate' => 'workedia_governorate',
            'sm_phone' => 'workedia_phone',
            'sm_account_status' => 'workedia_account_status',
            'sm_temp_pass' => 'workedia_temp_pass',
            'sm_recovery_otp' => 'workedia_recovery_otp',
            'sm_recovery_otp_time' => 'workedia_recovery_otp_time',
            'sm_recovery_otp_used' => 'workedia_recovery_otp_used'
        ];

        foreach ($meta_mappings as $old => $new) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}usermeta SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }
    }

    private static function create_pages() {
        global $wpdb;
        $pages = array(
            'workedia-login' => array(
                'title' => 'تسجيل الدخول للنظام',
                'content' => '[workedia_login]'
            ),
            'workedia-admin' => array(
                'title' => 'لوحة الإدارة النقابية',
                'content' => '[workedia_admin]'
            ),
            'home' => array(
                'title' => 'الرئيسية',
                'content' => '[workedia_home]',
                'shortcode' => 'workedia_home'
            ),
            'about-us' => array(
                'title' => 'عن Workedia',
                'content' => '[workedia_about]',
                'shortcode' => 'workedia_about'
            ),
            'contact-us' => array(
                'title' => 'اتصل بنا',
                'content' => '[workedia_contact]',
                'shortcode' => 'workedia_contact'
            ),
            'articles' => array(
                'title' => 'أخبار ومقالات',
                'content' => '[workedia_blog]',
                'shortcode' => 'workedia_blog'
            ),
            'services' => array(
                'title' => 'الخدمات الرقمية',
                'content' => '[workedia_services]',
                'shortcode' => 'workedia_services'
            )
        );

        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post(array(
                    'post_title'    => $data['title'],
                    'post_content'  => $data['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $slug
                ));
            }

            // Sync with workedia_pages table
            if (isset($data['shortcode'])) {
                $table = $wpdb->prefix . 'workedia_pages';
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($table, array(
                        'title' => $data['title'],
                        'slug' => $slug,
                        'shortcode' => $data['shortcode'],
                        'instructions' => 'تحرير بيانات هذه الصفحة من إعدادات النظام.',
                        'settings' => json_encode(['layout' => 'standard'])
                    ));
                }
            }
        }
    }

    private static function sync_missing_member_accounts() {
        global $wpdb;
        $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}workedia_members WHERE wp_user_id IS NULL OR wp_user_id = 0");
        foreach ($members as $m) {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $temp_pass = 'IRS' . $digits;
            $user_id = wp_insert_user([
                'user_login' => $m->national_id,
                'user_email' => $m->email ?: $m->national_id . '@irseg.org',
                'display_name' => $m->name,
                'user_pass' => $temp_pass,
                'role' => 'subscriber'
            ]);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'workedia_temp_pass', $temp_pass);
                if (!empty($m->governorate)) {
                    update_user_meta($user_id, 'workedia_governorate', $m->governorate);
                }
                $wpdb->update("{$wpdb->prefix}workedia_members", ['wp_user_id' => $user_id], ['id' => $m->id]);
            }
        }
    }

    private static function migrate_user_roles() {
        $role_migration = array(
            'sm_system_admin'           => 'administrator',
            'sm_syndicate_admin'        => 'administrator',
            'sm_syndicate_member'       => 'subscriber',
            'sm_officer'                => 'administrator',
            'sm_member'                 => 'subscriber',
            'sm_parent'                 => 'subscriber',
            'sm_student'                => 'subscriber',
            'workedia_system_admin'     => 'administrator',
            'workedia_admin'            => 'administrator',
            'workedia_member'           => 'subscriber',
            'workedia_syndicate_admin'  => 'administrator',
            'workedia_syndicate_member' => 'subscriber'
        );

        foreach ($role_migration as $old => $new) {
            $users = get_users(array('role' => $old));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->add_role($new);
                    $user->remove_role($old);
                }
            }
        }
    }
}
