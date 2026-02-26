<?php

class Workedia_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function hide_admin_bar_for_non_admins($show) {
        if (!current_user_can('administrator')) {
            return false;
        }
        return $show;
    }

    private function can_manage_user($target_user_id) {
        if (current_user_can('manage_options')) return true;
        return false;
    }

    private function can_access_member($member_id) {
        if (current_user_can('manage_options')) return true;

        $member = Workedia_DB::get_member_by_id($member_id);
        if (!$member) return false;

        $user = wp_get_current_user();

        // Members can access their own record
        if ($member->wp_user_id == $user->ID) {
            return true;
        }

        return false;
    }

    public function restrict_admin_access() {
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'workedia_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/workedia-login?login=failed'));
                exit;
            }
        }

        if (is_admin() && !defined('DOING_AJAX') && !current_user_can('manage_options')) {
            wp_redirect(home_url('/workedia-admin'));
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_style($this->plugin_name, WORKEDIA_PLUGIN_URL . 'assets/css/workedia-public.css', array('dashicons'), $this->version, 'all');

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
            .workedia-admin-dashboard { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        // New Shortcodes
        add_shortcode('workedia_login', array($this, 'shortcode_login'));
        add_shortcode('workedia_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('workedia_verify', array($this, 'shortcode_verify'));
        add_shortcode('workedia_home', array($this, 'shortcode_home'));
        add_shortcode('workedia_about', array($this, 'shortcode_about'));
        add_shortcode('workedia_contact', array($this, 'shortcode_contact'));
        add_shortcode('workedia_blog', array($this, 'shortcode_blog'));
        add_shortcode('workedia_services', array($this, 'shortcode_services'));

        // Backward Compatibility Mapping
        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('verify', array($this, 'shortcode_verify'));
        add_shortcode('smhome', array($this, 'shortcode_home'));
        add_shortcode('smabout', array($this, 'shortcode_about'));
        add_shortcode('smcontact', array($this, 'shortcode_contact'));
        add_shortcode('smblog', array($this, 'shortcode_blog'));
        add_shortcode('services', array($this, 'shortcode_services'));

        add_filter('authenticate', array($this, 'custom_authenticate'), 20, 3);
        add_filter('auth_cookie_expiration', array($this, 'custom_auth_cookie_expiration'), 10, 3);
    }

    public function custom_auth_cookie_expiration($expiration, $user_id, $remember) {
        if ($remember) {
            return 30 * DAY_IN_SECONDS; // 30 days
        }
        return $expiration;
    }

    public function custom_authenticate($user, $username, $password) {
        if (empty($username) || empty($password)) return $user;

        // If already authenticated by standard means, return
        if ($user instanceof WP_User) return $user;

        // 1. Check for Workedia Admin/Member ID Code (meta)
        $code_query = new WP_User_Query(array(
            'meta_query' => array(
                array('key' => 'workediaMemberIdAttr', 'value' => $username)
            ),
            'number' => 1
        ));
        $found = $code_query->get_results();
        if (!empty($found)) {
            $u = $found[0];
            if (wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        // 2. Check for National ID in workedia_members table (if user_login is different)
        global $wpdb;
        $member_wp_id = $wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}workedia_members WHERE national_id = %s", $username));
        if ($member_wp_id) {
            $u = get_userdata($member_wp_id);
            if ($u && wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        return $user;
    }

    public function shortcode_verify() {
        ob_start();
        include WORKEDIA_PLUGIN_DIR . 'templates/public-verification.php';
        return ob_get_clean();
    }

    public function shortcode_services() {
        $services = Workedia_DB::get_services(['status' => 'active']);
        $is_logged_in = is_user_logged_in();
        $login_url = home_url('/workedia-login');

        ob_start();
        ?>
        <div class="workedia-public-page" dir="rtl">
            <div class="workedia-page-header">
                <h2>الخدمات الرقمية</h2>
                <p>مجموعة من الخدمات الإلكترونية المتاحة لأعضاء Workedia</p>
            </div>
            <div class="workedia-content-container">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-top: 50px;">
                    <?php if (empty($services)): ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #94a3b8;">لا توجد خدمات متاحة حالياً.</div>
                    <?php else: ?>
                        <?php foreach ($services as $s): ?>
                            <div class="workedia-service-card" style="background: #fff; border: 1px solid var(--workedia-border-color); border-radius: 20px; padding: 30px; display: flex; flex-direction: column; transition: 0.3s; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                                <div style="width: 60px; height: 60px; background: var(--workedia-primary-color); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #fff; margin-bottom: 25px;">
                                    <span class="dashicons dashicons-cloud" style="font-size: 30px; width: 30px; height: 30px;"></span>
                                </div>
                                <h3 style="margin: 0 0 15px 0; font-weight: 800; color: var(--workedia-dark-color); font-size: 1.4em;"><?php echo esc_html($s->name); ?></h3>
                                <p style="font-size: 14px; color: #64748b; line-height: 1.8; margin-bottom: 25px; flex: 1;"><?php echo esc_html($s->description); ?></p>

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                    <?php if ($is_logged_in): ?>
                                        <a href="<?php echo add_query_arg('workedia_tab', 'digital-services', home_url('/workedia-admin')); ?>" class="workedia-btn" style="width: auto; padding: 10px 25px; border-radius: 10px;">طلب الخدمة</a>
                                    <?php else: ?>
                                        <button onclick="window.location.href='<?php echo $login_url; ?>'" class="workedia-btn" style="width: auto; padding: 10px 25px; border-radius: 10px;">تسجيل الدخول للطلب</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_home() {
        $workedia = Workedia_Settings::get_workedia_info();
        $page = Workedia_DB::get_page_by_shortcode('workedia_home');
        ob_start();
        ?>
        <div class="workedia-public-page workedia-home-page" dir="rtl">
            <div class="workedia-hero-section">
                <?php if ($workedia['workedia_logo']): ?>
                    <img src="<?php echo esc_url($workedia['workedia_logo']); ?>" alt="Logo" class="workedia-hero-logo">
                <?php endif; ?>
                <h1><?php echo esc_html($workedia['workedia_name']); ?></h1>
                <p class="workedia-hero-subtitle"><?php echo esc_html($page->instructions ?? 'مرحباً بكم في البوابة الرسمية'); ?></p>
            </div>
            <div class="workedia-content-container">
                <div class="workedia-info-grid">
                    <div class="workedia-info-card">
                        <span class="dashicons dashicons-admin-site"></span>
                        <h4>من نحن</h4>
                        <p>نعمل على تقديم أفضل الخدمات لأعضاء Workedia وتطوير المنظومة المهنية.</p>
                    </div>
                    <div class="workedia-info-card">
                        <span class="dashicons dashicons-awards"></span>
                        <h4>أهدافنا</h4>
                        <p>الارتقاء بالمستوى المهني والاجتماعي لكافة الأعضاء المسجلين.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_about() {
        $workedia = Workedia_Settings::get_workedia_info();
        $page = Workedia_DB::get_page_by_shortcode('workedia_about');
        ob_start();
        ?>
        <div class="workedia-public-page workedia-about-page" dir="rtl">
            <div class="workedia-page-header">
                <h2><?php echo esc_html($page->title ?? 'عن Workedia'); ?></h2>
            </div>
            <div class="workedia-content-container">
                <div class="workedia-about-content">
                    <h3><?php echo esc_html($workedia['workedia_name']); ?></h3>
                    <div class="workedia-text-block">
                        <?php echo nl2br(esc_html($workedia['extra_details'] ?: 'تفاصيل Workedia الرسمية والرؤية المستقبلية للمهنة.')); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_contact() {
        $workedia = Workedia_Settings::get_workedia_info();
        $page = Workedia_DB::get_page_by_shortcode('workedia_contact');
        ob_start();
        ?>
        <div class="workedia-public-page workedia-contact-page" dir="rtl">
            <div class="workedia-page-header">
                <h2><?php echo esc_html($page->title ?? 'اتصل بنا'); ?></h2>
            </div>
            <div class="workedia-content-container">
                <div class="workedia-contact-grid">
                    <div class="workedia-contact-info">
                        <h3>بيانات التواصل</h3>
                        <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($workedia['address']); ?></p>
                        <p><span class="dashicons dashicons-phone"></span> <?php echo esc_html($workedia['phone']); ?></p>
                        <p><span class="dashicons dashicons-email"></span> <?php echo esc_html($workedia['email']); ?></p>
                    </div>
                    <div class="workedia-contact-form-wrapper">
                        <form class="workedia-public-form">
                            <div class="workedia-form-group"><input type="text" placeholder="الاسم الكامل" class="workedia-input"></div>
                            <div class="workedia-form-group"><input type="email" placeholder="البريد الإلكتروني" class="workedia-input"></div>
                            <div class="workedia-form-group"><textarea placeholder="رسالتك" class="workedia-textarea" rows="5"></textarea></div>
                            <button type="button" class="workedia-btn" onclick="alert('شكراً لتواصلك معنا، تم استلام رسالتك.')">إرسال الرسالة</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_blog() {
        $articles = Workedia_DB::get_articles(12);
        $page = Workedia_DB::get_page_by_shortcode('workedia_blog');
        ob_start();
        ?>
        <div class="workedia-public-page workedia-blog-page" dir="rtl">
            <div class="workedia-page-header">
                <h2><?php echo esc_html($page->title ?? 'أخبار ومقالات'); ?></h2>
            </div>
            <div class="workedia-content-container">
                <?php if (empty($articles)): ?>
                    <p style="text-align:center; padding:50px; color:#718096;">لا توجد مقالات منشورة حالياً.</p>
                <?php else: ?>
                    <div class="workedia-blog-grid">
                        <?php foreach($articles as $a): ?>
                            <div class="workedia-blog-card">
                                <?php if($a->image_url): ?>
                                    <div class="workedia-blog-image" style="background-image: url('<?php echo esc_url($a->image_url); ?>');"></div>
                                <?php endif; ?>
                                <div class="workedia-blog-content">
                                    <span class="workedia-blog-date"><?php echo date('Y-m-d', strtotime($a->created_at)); ?></span>
                                    <h4><?php echo esc_html($a->title); ?></h4>
                                    <p><?php echo mb_strimwidth(strip_tags($a->content), 0, 120, '...'); ?></p>
                                    <a href="#" class="workedia-read-more">اقرأ المزيد ←</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/workedia-admin'));
            exit;
        }
        $workedia = Workedia_Settings::get_workedia_info();
        $output = '<div class="workedia-login-container" style="display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; background: #f8fafc;">';
        $output .= '<div class="workedia-login-box" style="width: 100%; max-width: 420px; background: #ffffff; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #f1f5f9;" dir="rtl">';

        $output .= '<div style="background: var(--workedia-dark-color); padding: 35px 25px; text-align: center; color: #fff;">';
        $output .= '<h3 style="margin: 0 0 10px 0; font-size: 0.9em; opacity: 0.8; font-weight: 400;">أهلاً بك مجدداً</h3>';
        $output .= '<h2 style="margin: 0; font-weight: 900; color: #fff; font-size: 1.6em; letter-spacing: -0.5px;">'.esc_html($workedia['workedia_name']).'</h2>';
        $output .= '<p style="margin: 8px 0 0 0; color: #e2e8f0; font-size: 0.85em;">المنصة الرقمية للخدمات النقابية الموحدة</p>';
        $output .= '</div>';

        $output .= '<div style="padding: 30px 30px;">';
        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            $output .= '<div style="background: #fff5f5; color: #c53030; padding: 10px; border-radius: 8px; border: 1px solid #feb2b2; margin-bottom: 20px; font-size: 0.85em; text-align: center; font-weight: 600;">⚠️ بيانات الدخول غير صحيحة</div>';
        }

        $output .= '<style>
            #workedia_login_form p { margin-bottom: 15px; }
            #workedia_login_form label { display: none; }
            #workedia_login_form input[type="text"], #workedia_login_form input[type="password"] {
                width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px;
                background: #fcfcfc; font-size: 14px; transition: 0.3s; font-family: "Rubik", sans-serif;
            }
            #workedia_login_form input:focus { border-color: var(--workedia-primary-color); outline: none; background: #fff; }
            #workedia_login_form .login-remember { display: flex; align-items: center; gap: 8px; font-size: 0.8em; color: #64748b; margin-top: -5px; }
            #workedia_login_form input[type="submit"] {
                width: 100%; padding: 14px; background: var(--workedia-primary-color); color: #fff; border: none;
                border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s;
            }
            #workedia_login_form input[type="submit"]:hover { opacity: 0.9; transform: translateY(-1px); }
            .workedia-login-footer-links { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .workedia-footer-btn { text-decoration: none !important; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 700; text-align: center; transition: 0.2s; border: 1px solid #e2e8f0; color: #4a5568; box-shadow: none !important; }
            .workedia-footer-btn:hover { background: #f8fafc; border-color: #cbd5e0; }
            .workedia-footer-btn-primary { background: #f1f5f9; color: var(--workedia-dark-color) !important; border: 1px solid #e2e8f0; }
            .workedia-footer-btn-primary:hover { background: #e2e8f0; }
        </style>';

        $args = array(
            'echo' => false,
            'redirect' => home_url('/workedia-admin'),
            'form_id' => 'workedia_login_form',
            'label_remember' => 'تذكرني',
            'label_log_in' => 'دخول النظام',
            'remember' => true
        );
        $form = wp_login_form($args);

        // Inject placeholders
        $form = str_replace('name="log"', 'name="log" placeholder="الرقم القومي أو اسم المستخدم"', $form);
        $form = str_replace('name="pwd"', 'name="pwd" placeholder="كلمة المرور"', $form);

        $output .= $form;

        $output .= '<div class="workedia-login-footer-links" style="grid-template-columns: 1fr;">';
        $output .= '<a href="javascript:void(0)" onclick="workediaToggleActivation()" class="workedia-footer-btn">تفعيل حساب</a>';
        $output .= '<a href="javascript:void(0)" onclick="workediaToggleRecovery()" style="color: #64748b; font-size: 12px; text-decoration: none; text-align: center; margin-top: 10px;">نسيت كلمة المرور؟</a>';
        $output .= '</div>';

        // Recovery Modal
        $output .= '<div id="workedia-recovery-modal" class="workedia-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; padding:20px;">';
        $output .= '<div class="workedia-modal-content" style="background:white; width:100%; max-width:400px; padding:35px; border-radius:20px; position:relative;">';
        $output .= '<button onclick="workediaToggleRecovery()" style="position:absolute; top:20px; left:20px; border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>';
        $output .= '<h3 style="margin-top:0; margin-bottom:25px; text-align:center; font-weight:800;">استعادة كلمة المرور</h3>';
        $output .= '<div id="recovery-step-1">';
        $output .= '<p style="font-size:14px; color:#64748b; margin-bottom:20px; line-height:1.6;">أدخل الرقم القومي الخاص بك للتحقق وإرسال رمز الاستعادة.</p>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:20px;"><label class="workedia-label">الرقم القومي:</label><input type="text" id="rec_national_id" class="workedia-input" placeholder="14 رقم" maxlength="14" style="width:100%;"></div>';
        $output .= '<button onclick="workediaRequestOTP()" class="workedia-btn" style="width:100%;">إرسال رمز التحقق</button>';
        $output .= '</div>';
        $output .= '<div id="recovery-step-2" style="display:none;">';
        $output .= '<p style="font-size:13px; color:#38a169; margin-bottom:15px;">تم إرسال الرمز بنجاح. يرجى التحقق من بريدك.</p>';
        $output .= '<input type="text" id="rec_otp" class="workedia-input" placeholder="رمز التحقق (6 أرقام)" style="margin-bottom:10px; width:100%;">';
        $output .= '<input type="password" id="rec_new_pass" class="workedia-input" placeholder="كلمة المرور الجديدة" style="margin-bottom:20px; width:100%;">';
        $output .= '<button onclick="workediaResetPassword()" class="workedia-btn" style="width:100%;">تغيير كلمة المرور</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        // Activation Modal (3-Step Sequential Workflow)
        $output .= '<div id="workedia-activation-modal" class="workedia-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; padding:20px;">';
        $output .= '<div class="workedia-modal-content" style="background:white; width:100%; max-width:450px; padding:40px; border-radius:24px; position:relative;">';
        $output .= '<button onclick="workediaToggleActivation()" style="position:absolute; top:20px; left:20px; border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>';
        $output .= '<div style="text-align:center; margin-bottom:30px;"><h3 style="margin:0; font-weight:900;">تفعيل الحساب الرقمي</h3><p style="color:#64748b; font-size:13px; margin-top:5px;">خطوات بسيطة للوصول لخدماتك الإلكترونية</p></div>';

        // Step 1: Verification
        $output .= '<div id="activation-step-1">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:var(--workedia-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">1</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">2</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الأولى: التحقق من الهوية بالسجلات</p>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:15px;"><input type="text" id="act_national_id" class="workedia-input" placeholder="الرقم القومي (14 رقم)" style="width:100%;"></div>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:15px;"><input type="text" id="act_mem_no" class="workedia-input" placeholder="رقم القيد النقابي" style="width:100%;"></div>';
        $output .= '<button onclick="workediaActivateStep1()" class="workedia-btn" style="width:100%;">تحقق وانتقل للخطوة التالية</button>';
        $output .= '</div>';

        // Step 2: Contact Confirmation
        $output .= '<div id="activation-step-2" style="display:none;">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:var(--workedia-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">2</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الثانية: تأكيد بيانات التواصل</p>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:15px;"><input type="email" id="act_email" class="workedia-input" placeholder="البريد الإلكتروني المعتمد" style="width:100%;"></div>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:15px;"><input type="text" id="act_phone" class="workedia-input" placeholder="رقم الهاتف الحالي" style="width:100%;"></div>';
        $output .= '<button onclick="workediaActivateStep2()" class="workedia-btn" style="width:100%;">تأكيد البيانات</button>';
        $output .= '</div>';

        // Step 3: Account Completion
        $output .= '<div id="activation-step-3" style="display:none;">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:var(--workedia-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الثالثة: تعيين كلمة المرور</p>';
        $output .= '<div class="workedia-form-group" style="margin-bottom:20px;"><input type="password" id="act_pass" class="workedia-input" placeholder="كلمة المرور (10 خانات على الأقل)" style="width:100%;"></div>';
        $output .= '<button onclick="workediaActivateFinal()" class="workedia-btn" style="width:100%;">إكمال التنشيط والدخول</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        $output .= '<script>
        function workediaToggleRecovery() {
            const m = document.getElementById("workedia-recovery-modal");
            m.style.display = m.style.display === "none" ? "flex" : "none";
        }
        function workediaToggleActivation() {
            const m = document.getElementById("workedia-activation-modal");
            m.style.display = m.style.display === "none" ? "flex" : "none";
            document.getElementById("activation-step-1").style.display = "block";
            document.getElementById("activation-step-2").style.display = "none";
        }
        function workediaRequestOTP() {
            const nid = document.getElementById("rec_national_id").value;
            const fd = new FormData(); fd.append("action", "workedia_forgot_password_otp"); fd.append("national_id", nid);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    document.getElementById("recovery-step-1").style.display="none";
                    document.getElementById("recovery-step-2").style.display="block";
                } else alert(res.data);
            });
        }
        function workediaActivateStep2() {
            const email = document.getElementById("act_email").value;
            const phone = document.getElementById("act_phone").value;
            if(!/^\S+@\S+\.\S+$/.test(email)) return alert("يرجى إدخال بريد إلكتروني صحيح");
            if(phone.length < 10) return alert("يرجى إدخال رقم هاتف صحيح");
            document.getElementById("activation-step-2").style.display="none";
            document.getElementById("activation-step-3").style.display="block";
        }
        function workediaResetPassword() {
            const nid = document.getElementById("rec_national_id").value;
            const otp = document.getElementById("rec_otp").value;
            const pass = document.getElementById("rec_new_pass").value;
            const fd = new FormData(); fd.append("action", "workedia_reset_password_otp");
            fd.append("national_id", nid); fd.append("otp", otp); fd.append("new_password", pass);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { alert(res.data); location.reload(); } else alert(res.data);
            });
        }
        function workediaActivateStep1() {
            const nid = document.getElementById("act_national_id").value;
            const mem = document.getElementById("act_mem_no").value;
            if(!/^[0-9]{14}$/.test(nid)) return alert("يرجى إدخال رقم قومي صحيح (14 رقم)");
            const fd = new FormData(); fd.append("action", "workedia_activate_account_step1");
            fd.append("national_id", nid); fd.append("membership_number", mem);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    document.getElementById("activation-step-1").style.display="none";
                    document.getElementById("activation-step-2").style.display="block";
                } else alert(res.data);
            });
        }
        function workediaActivateFinal() {
            const nid = document.getElementById("act_national_id").value;
            const mem = document.getElementById("act_mem_no").value;
            const email = document.getElementById("act_email").value;
            const phone = document.getElementById("act_phone").value;
            const pass = document.getElementById("act_pass").value;
            if(!/^\S+@\S+\.\S+$/.test(email)) return alert("يرجى إدخال بريد إلكتروني صحيح");
            if(pass.length < 10) return alert("كلمة المرور يجب أن تكون 10 أحرف على الأقل");
            const fd = new FormData(); fd.append("action", "workedia_activate_account_final");
            fd.append("national_id", nid); fd.append("membership_number", mem);
            fd.append("email", email); fd.append("phone", phone); fd.append("password", pass);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { alert(res.data); location.reload(); } else alert(res.data);
            });
        }

        </script>';

        $output .= '</div>'; // End padding
        $output .= '</div>'; // End box
        $output .= '</div>'; // End container
        return $output;
    }

    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['workedia_tab']) ? sanitize_text_field($_GET['workedia_tab']) : 'summary';

        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('administrator', $roles);
        $is_administrator = in_array('administrator', $roles);
        $is_subscriber = in_array('subscriber', $roles);

        // Fetch data
        $stats = Workedia_DB::get_statistics();

        ob_start();
        include WORKEDIA_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        Workedia_Logger::log('تسجيل دخول', "المستخدم: $user_login");
    }

    public function ajax_get_member() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $member = Workedia_DB::get_member_by_national_id($national_id);
        if ($member) {
            if (!$this->can_access_member($member->id)) wp_send_json_error('Access denied');
            wp_send_json_success($member);
        } else {
            wp_send_json_error('Member not found');
        }
    }

    public function ajax_search_members() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        $members = Workedia_DB::get_members(array('search' => $query));
        wp_send_json_success($members);
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array('stats' => Workedia_DB::get_statistics()));
    }

    public function ajax_update_member_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_photo_action', 'workedia_photo_nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('member_photo', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error($attachment_id->get_error_message());

        $photo_url = wp_get_attachment_url($attachment_id);
        $member_id = intval($_POST['member_id']);
        Workedia_DB::update_member_photo($member_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }

    public function ajax_add_staff() {
        global $wpdb;
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['workedia_nonce'], 'workediaMemberAction')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $display_name = sanitize_text_field($_POST['display_name']);
        $role = sanitize_text_field($_POST['role']);

        if (empty($username)) wp_send_json_error('اسم المستخدم مطلوب');
        if (empty($email)) wp_send_json_error('البريد الإلكتروني مطلوب');
        if (empty($display_name)) wp_send_json_error('الاسم الكامل مطلوب');
        if (empty($role)) wp_send_json_error('الدور مطلوب');

        if (username_exists($username)) wp_send_json_error('اسم المستخدم موجود مسبقاً');
        if (email_exists($email)) wp_send_json_error('البريد الإلكتروني مسجل لمستخدم آخر');

        $pass = !empty($_POST['user_pass']) ? $_POST['user_pass'] : 'IRS' . sprintf("%010d", mt_rand(0, 9999999999));

        // Prevent role escalation
        if ($role === 'administrator' && !current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions to assign this role');
        }

        if ($role === 'subscriber') {
            // Unified Add for Member
            $member_data = [
                'national_id' => sanitize_text_field($_POST['officer_id'] ?: $username),
                'name' => $display_name,
                'email' => $email,
                'phone' => sanitize_text_field($_POST['phone']),
                'governorate' => sanitize_text_field($_POST['governorate']),
                'membership_number' => sanitize_text_field($_POST['membership_number'] ?? ''),
                'membership_status' => sanitize_text_field($_POST['membership_status'] ?? 'active')
            ];
            // Workedia_DB::add_member handles WP User creation too.
            // But we already checked for exists.
            $res = Workedia_DB::add_member($member_data);
            if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
            $user_id = $wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}workedia_members WHERE id = %d", $res));
        } else {
            // Standard Staff
            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'display_name' => $display_name,
                'user_pass' => $pass,
                'role' => $role
            ));
            if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());

            update_user_meta($user_id, 'workedia_temp_pass', $pass);
            update_user_meta($user_id, 'workediaMemberIdAttr', sanitize_text_field($_POST['officer_id']));
            update_user_meta($user_id, 'workedia_phone', sanitize_text_field($_POST['phone']));
            update_user_meta($user_id, 'workedia_account_status', 'active');
            update_user_meta($user_id, 'workedia_governorate', sanitize_text_field($_POST['governorate']));
        }

        Workedia_Logger::log('إضافة مستخدم (موحد)', "الاسم: $display_name الدور: $role");
        wp_send_json_success($user_id);
    }

    public function ajax_delete_staff() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'workediaMemberAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['user_id']);
        if ($user_id === get_current_user_id()) wp_send_json_error('Cannot delete yourself');
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        // Check if it's a member
        global $wpdb;
        $member_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user_id));
        if ($member_id) {
            Workedia_DB::delete_member($member_id);
        } else {
            wp_delete_user($user_id);
        }

        wp_send_json_success('Deleted');
    }

    public function ajax_update_staff() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['workedia_nonce'], 'workediaMemberAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_officer_id']);
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        $role = sanitize_text_field($_POST['role']);

        // Prevent role escalation
        if ($role === 'administrator' && !current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions to assign this role');
        }

        $user_data = array('ID' => $user_id, 'display_name' => sanitize_text_field($_POST['display_name']), 'user_email' => sanitize_email($_POST['user_email']));
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'workedia_temp_pass', $_POST['user_pass']);
        }
        wp_update_user($user_data);

        $u = new WP_User($user_id);
        $u->set_role($role);

        update_user_meta($user_id, 'workediaMemberIdAttr', sanitize_text_field($_POST['officer_id']));
        update_user_meta($user_id, 'workedia_phone', sanitize_text_field($_POST['phone']));

        $gov = sanitize_text_field($_POST['governorate'] ?? '');
        if (current_user_can('manage_options')) {
            // Full admins can update governorate
            update_user_meta($user_id, 'workedia_governorate', $gov);
        } else {
            // Local admins can't change governorate (keep current)
            $gov = get_user_meta($user_id, 'workedia_governorate', true);
        }

        update_user_meta($user_id, 'workedia_account_status', sanitize_text_field($_POST['account_status']));

        // Sync to workedia_members if it's a member
        if ($role === 'subscriber') {
            global $wpdb;
            $wpdb->update("{$wpdb->prefix}workedia_members", [
                'name' => sanitize_text_field($_POST['display_name']),
                'email' => sanitize_email($_POST['user_email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'governorate' => $gov
            ], ['wp_user_id' => $user_id]);
        }

        Workedia_Logger::log('تحديث مستخدم (موحد)', "الاسم: {$_POST['display_name']}");
        wp_send_json_success('Updated');
    }

    public function ajax_add_member() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_add_member', 'workedia_nonce');
        $res = Workedia_DB::add_member($_POST);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
        else wp_send_json_success($res);
    }

    public function ajax_update_member() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_add_member', 'workedia_nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        Workedia_DB::update_member($member_id, $_POST);
        wp_send_json_success('Updated');
    }

    public function ajax_delete_member() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_delete_member', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        Workedia_DB::delete_member($member_id);
        wp_send_json_success('Deleted');
    }

    public function ajax_delete_gov_data() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        global $wpdb;
        $gov = sanitize_text_field($_POST['governorate']);
        if (!$gov) wp_send_json_error('محافظة غير محددة');

        // 1. Get member IDs for this gov
        $member_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE governorate = %s", $gov));
        if (empty($member_ids)) wp_send_json_success('لا توجد بيانات لهذه المحافظة');

        // 2. Delete WP Users
        $wp_user_ids = $wpdb->get_col($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}workedia_members WHERE governorate = %s AND wp_user_id IS NOT NULL", $gov));
        if (!empty($wp_user_ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($wp_user_ids as $uid) wp_delete_user($uid);
        }

        // 4. Delete members
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}workedia_members WHERE governorate = %s", $gov));

        Workedia_Logger::log('حذف بيانات محافظة', "تم مسح كافة بيانات محافظة: $gov");
        wp_send_json_success();
    }

    public function ajax_merge_gov_data() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $gov = sanitize_text_field($_POST['governorate']);
        if (empty($_FILES['backup_file']['tmp_name'])) wp_send_json_error('الملف غير موجود');

        $json = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($json, true);
        if (!$data || !isset($data['members'])) wp_send_json_error('تنسيق الملف غير صحيح');

        $success = 0; $skipped = 0;
        foreach ($data['members'] as $row) {
            // Only merge members belonging to the TARGET governorate if specified in the row,
            // OR force them to the target governorate.
            // Requirement says "data for a single governorate only"
            if ($row['governorate'] !== $gov) {
                $skipped++;
                continue;
            }

            if (Workedia_DB::member_exists($row['national_id'])) {
                $skipped++;
                continue;
            }

            // Clean data for insertion
            unset($row['id']);

            // Re-create WP User if needed
            $digits = ''; for ($i = 0; $i < 10; $i++) $digits .= mt_rand(0, 9);
            $temp_pass = 'IRS' . $digits;
            $wp_user_id = wp_insert_user([
                'user_login' => $row['national_id'],
                'user_email' => $row['email'] ?: $row['national_id'] . '@irseg.org',
                'display_name' => $row['name'],
                'user_pass' => $temp_pass,
                'role' => 'subscriber'
            ]);

            if (!is_wp_error($wp_user_id)) {
                $row['wp_user_id'] = $wp_user_id;
                update_user_meta($wp_user_id, 'workedia_temp_pass', $temp_pass);
                update_user_meta($wp_user_id, 'workedia_governorate', $gov);
            }

            global $wpdb;
            if ($wpdb->insert("{$wpdb->prefix}workedia_members", $row)) $success++;
            else $skipped++;
        }

        Workedia_Logger::log('دمج بيانات محافظة', "تم دمج $success عضواً لمحافظة $gov (تخطى $skipped)");
        wp_send_json_success("تم بنجاح دمج $success عضواً وتجاهل $skipped عضواً مسجلين مسبقاً.");
    }

    public function ajax_reset_system() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $password = $_POST['admin_password'] ?? '';
        $current_user = wp_get_current_user();
        if (!wp_check_password($password, $current_user->user_pass, $current_user->ID)) {
            wp_send_json_error('كلمة المرور غير صحيحة. يرجى إدخال كلمة مرور مدير النظام للمتابعة.');
        }

        global $wpdb;
        $tables = [
            'workedia_members', 'workedia_logs', 'workedia_messages',
            'workedia_surveys', 'workedia_survey_responses', 'workedia_update_requests'
        ];

        // 1. Delete WordPress Users associated with members
        $member_wp_ids = $wpdb->get_col("SELECT wp_user_id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id IS NOT NULL");
        if (!empty($member_wp_ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($member_wp_ids as $uid) {
                wp_delete_user($uid);
            }
        }

        // 2. Truncate Tables
        foreach ($tables as $t) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}$t");
        }

        Workedia_Logger::log('إعادة تهيئة النظام', "تم مسح كافة البيانات وتصفير النظام بالكامل");
        wp_send_json_success();
    }

    public function ajax_rollback_log() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}workedia_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('لا توجد بيانات استعادة لهذه العملية');
        }

        $json = str_replace('ROLLBACK_DATA:', '', $log->details);
        $rollback_info = json_decode($json, true);

        if (!$rollback_info || !isset($rollback_info['table'])) {
            wp_send_json_error('تنسيق بيانات الاستعادة غير صحيح');
        }

        $table = $rollback_info['table'];
        $data = $rollback_info['data'];

        if ($table === 'members') {
            // Re-insert into workedia_members
            $wp_user_id = $data['wp_user_id'] ?? null;

            // Check if user login already exists
            if (!empty($data['national_id']) && username_exists($data['national_id'])) {
                wp_send_json_error('لا يمكن الاستعادة: اسم المستخدم (الرقم القومي) موجود بالفعل');
            }

            // Re-create WP User if it was deleted
            if ($wp_user_id && !get_userdata($wp_user_id)) {
                $digits = ''; for ($i = 0; $i < 10; $i++) $digits .= mt_rand(0, 9);
                $temp_pass = 'IRS' . $digits;
                $wp_user_id = wp_insert_user([
                    'user_login' => $data['national_id'],
                    'user_email' => $data['email'] ?: $data['national_id'] . '@irseg.org',
                    'display_name' => $data['name'],
                    'user_pass' => $temp_pass,
                    'role' => 'subscriber'
                ]);
                if (is_wp_error($wp_user_id)) wp_send_json_error($wp_user_id->get_error_message());
                update_user_meta($wp_user_id, 'workedia_temp_pass', $temp_pass);
                if (!empty($data['governorate'])) {
                    update_user_meta($wp_user_id, 'workedia_governorate', $data['governorate']);
                }
            }

            unset($data['id']);
            $data['wp_user_id'] = $wp_user_id;

            $res = $wpdb->insert("{$wpdb->prefix}workedia_members", $data);
            if ($res) {
                Workedia_Logger::log('استعادة بيانات', "تم استعادة العضو: " . $data['name']);
                wp_send_json_success();
            } else {
                wp_send_json_error('فشل في إدراج البيانات في قاعدة البيانات: ' . $wpdb->last_error);
            }
        } elseif ($table === 'services') {
            unset($data['id']);
            $res = $wpdb->insert("{$wpdb->prefix}workedia_services", $data);
            if ($res) {
                Workedia_Logger::log('استعادة بيانات', "تم استعادة الخدمة: " . $data['name']);
                wp_send_json_success();
            } else {
                wp_send_json_error('فشل في إدراج البيانات في قاعدة البيانات: ' . $wpdb->last_error);
            }
        }

        wp_send_json_error('نوع الاستعادة غير مدعوم حالياً');
    }

    public function ajax_add_survey() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        $id = Workedia_DB::add_survey($_POST['title'], $_POST['questions'], $_POST['recipients'], get_current_user_id());
        wp_send_json_success($id);
    }

    public function ajax_cancel_survey() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}workedia_surveys", ['status' => 'cancelled'], ['id' => intval($_POST['id'])]);
        wp_send_json_success();
    }

    public function ajax_submit_survey_response() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_survey_action', 'nonce');
        Workedia_DB::save_survey_response(intval($_POST['survey_id']), get_current_user_id(), json_decode(stripslashes($_POST['responses']), true));
        wp_send_json_success();
    }

    public function ajax_get_survey_results() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(Workedia_DB::get_survey_results(intval($_GET['id'])));
    }

    public function ajax_delete_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}workedia_logs", ['id' => intval($_POST['log_id'])]);
        wp_send_json_success();
    }

    public function ajax_clear_all_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}workedia_logs");
        wp_send_json_success();
    }

    public function ajax_get_user_role() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $user_id = intval($_GET['user_id']);
        $user = get_userdata($user_id);
        if ($user) {
            $role = !empty($user->roles) ? $user->roles[0] : '';
            wp_send_json_success(['role' => $role]);
        }
        wp_send_json_error('User not found');
    }

    public function ajax_update_member_account() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'workedia_nonce');

        $member_id = intval($_POST['member_id']);
        $wp_user_id = intval($_POST['wp_user_id']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        // Update email in WP User and SM Members table
        $user_data = ['ID' => $wp_user_id, 'user_email' => $email];
        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }

        $res = wp_update_user($user_data);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        // Handle role change (only for full admins)
        if (!empty($role) && (current_user_can('manage_options'))) {
            $user = new WP_User($wp_user_id);
            $user->set_role($role);
        }

        // Sync email to members table
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}workedia_members", ['email' => $email], ['id' => $member_id]);

        Workedia_Logger::log('تحديث حساب عضو', "تم تحديث بيانات الحساب للعضو ID: $member_id");
        wp_send_json_success();
    }

    public function ajax_add_service() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        // Validation
        if (empty($_POST['name'])) wp_send_json_error('اسم الخدمة مطلوب');

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'status' => in_array($_POST['status'], ['active', 'suspended']) ? $_POST['status'] : 'active',
            'required_fields' => stripslashes($_POST['required_fields'] ?? '[]'),
            'selected_profile_fields' => stripslashes($_POST['selected_profile_fields'] ?? '[]')
        ];

        $res = Workedia_DB::add_service($data);
        if ($res) wp_send_json_success();
        else wp_send_json_error('Failed to add service');
    }

    public function ajax_update_service() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        $id = intval($_POST['id']);

        $data = [];
        if (isset($_POST['name'])) {
            if (empty($_POST['name'])) wp_send_json_error('اسم الخدمة مطلوب');
            $data['name'] = sanitize_text_field($_POST['name']);
        }
        if (isset($_POST['description'])) $data['description'] = sanitize_textarea_field($_POST['description']);
        if (isset($_POST['status'])) {
            $data['status'] = in_array($_POST['status'], ['active', 'suspended']) ? $_POST['status'] : 'active';
        }
        if (isset($_POST['required_fields'])) $data['required_fields'] = stripslashes($_POST['required_fields']);
        if (isset($_POST['selected_profile_fields'])) $data['selected_profile_fields'] = stripslashes($_POST['selected_profile_fields']);

        if (Workedia_DB::update_service($id, $data)) wp_send_json_success();
        else wp_send_json_error('Failed to update service');
    }

    public function ajax_get_services_html() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        ob_start();
        include WORKEDIA_PLUGIN_DIR . 'templates/admin-services.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function ajax_verify_document() {
        $val = sanitize_text_field($_POST['search_value'] ?? '');
        $type = sanitize_text_field($_POST['search_type'] ?? 'all');

        if (empty($val)) wp_send_json_error('يرجى إدخال قيمة للبحث');

        $member = null;
        $results = [];

        switch ($type) {
            case 'membership':
                $member = Workedia_DB::get_member_by_membership_number($val);
                if ($member) {
                    $results['membership'] = [
                        'label' => 'بيانات العضوية',
                        'name' => $member->name,
                        'number' => $member->membership_number,
                        'status' => $member->membership_status,
                        'expiry' => $member->membership_expiration_date
                    ];
                }
                break;
            default: // 'all' - National ID or Username
                if (preg_match('/^[0-9]{14}$/', $val)) {
                    $member = Workedia_DB::get_member_by_national_id($val);
                } else {
                    $member = Workedia_DB::get_member_by_username($val);
                }

                if ($member) {
                    $results['membership'] = [
                        'label' => 'بيانات العضوية',
                        'name' => $member->name,
                        'number' => $member->membership_number,
                        'status' => $member->membership_status,
                        'expiry' => $member->membership_expiration_date
                    ];
                }
                break;
        }

        if (empty($results)) {
            wp_send_json_error('عذراً، لم يتم العثور على أي بيانات مطابقة لمدخلات البحث.');
        }

        wp_send_json_success($results);
    }

    public function ajax_delete_service() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        if (Workedia_DB::delete_service(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete service');
    }

    public function ajax_submit_service_request() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_service_action', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $res = Workedia_DB::submit_service_request($_POST);
        if ($res) {
            Workedia_Logger::log('طلب خدمة رقمية', "العضو ID: $member_id طلب خدمة ID: {$_POST['service_id']}");
            wp_send_json_success();
        } else wp_send_json_error('Failed to submit request');
    }

    public function ajax_process_service_request() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}workedia_service_requests WHERE id = %d", $id));
        if (!$req) wp_send_json_error('Request not found');

        $res = Workedia_DB::update_service_request_status($id, $status);
        if ($res) {
             wp_send_json_success();
        } else wp_send_json_error('Failed to process request');
    }

    public function ajax_export_survey_results() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $id = intval($_GET['id']);
        $results = Workedia_DB::get_survey_results($id);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey-'.$id.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Question', 'Answer', 'Count']);
        foreach ($results as $r) {
            foreach ($r['answers'] as $ans => $count) {
                fputcsv($out, [$r['question'], $ans, $count]);
            }
        }
        fclose($out);
        exit;
    }

    public function handle_form_submission() {
        if (isset($_POST['workedia_import_members_csv'])) {
            $this->handle_member_csv_import();
        }
        if (isset($_POST['workedia_import_staffs_csv'])) {
            $this->handle_staff_csv_import();
        }
        if (isset($_POST['workedia_save_appearance'])) {
            check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');
            $data = Workedia_Settings::get_appearance();
            foreach ($data as $k => $v) {
                if (isset($_POST[$k])) $data[$k] = sanitize_text_field($_POST[$k]);
            }
            Workedia_Settings::save_appearance($data);
            wp_redirect(add_query_arg('workedia_tab', 'global-settings', wp_get_referer()));
            exit;
        }
        if (isset($_POST['workedia_save_labels'])) {
            check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');
            $labels = Workedia_Settings::get_labels();
            foreach ($labels as $k => $v) {
                if (isset($_POST[$k])) $labels[$k] = sanitize_text_field($_POST[$k]);
            }
            Workedia_Settings::save_labels($labels);
            wp_redirect(add_query_arg('workedia_tab', 'global-settings', wp_get_referer()));
            exit;
        }

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

    }

    private function handle_member_csv_import() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');

        if (empty($_FILES['member_csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['member_csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        $results = ['total' => 0, 'success' => 0, 'warning' => 0, 'error' => 0];

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $results['total']++;
            if (count($data) < 2) { $results['error']++; continue; }

            $member_data = [
                'national_id' => sanitize_text_field($data[0]),
                'name' => sanitize_text_field($data[1]),
                'governorate' => sanitize_text_field($data[2] ?? ''),
                'phone' => sanitize_text_field($data[3] ?? ''),
                'email' => sanitize_email($data[4] ?? '')
            ];

            $res = Workedia_DB::add_member($member_data);
            if (is_wp_error($res)) {
                $results['error']++;
            } else {
                $results['success']++;
            }
        }
        fclose($handle);

        set_transient('workedia_import_results_' . get_current_user_id(), $results, 3600);
        wp_redirect(add_query_arg('workedia_tab', 'users-management', wp_get_referer()));
        exit;
    }

    private function handle_staff_csv_import() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('workedia_admin_action', 'workedia_admin_nonce');

        if (empty($_FILES['csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 4) continue;

            $username = sanitize_user($data[0]);
            $email = sanitize_email($data[1]);
            $name = sanitize_text_field($data[2]);
            $officer_id = sanitize_text_field($data[3]);
            $role_label = sanitize_text_field($data[4] ?? 'عضو Workedia');
            $phone = sanitize_text_field($data[5] ?? '');

            $pass = !empty($data[6]) ? $data[6] : 'IRS' . sprintf("%010d", mt_rand(0, 9999999999));

            $role = 'subscriber';
            if (strpos($role_label, 'مدير') !== false) $role = 'administrator';
            elseif (strpos($role_label, 'مسؤول') !== false) $role = 'administrator';

            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email ?: $username . '@irseg.org',
                'display_name' => $name,
                'user_pass' => $pass,
                'role' => $role
            ]);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'workedia_temp_pass', $pass);
                update_user_meta($user_id, 'workediaMemberIdAttr', $officer_id);
                update_user_meta($user_id, 'workedia_phone', $phone);
                // If it's a subscriber/member, ensure it's in members table too
                if ($role === 'subscriber') {
                    Workedia_DB::add_member([
                        'national_id' => $officer_id ?: $username,
                        'name' => $name,
                        'email' => $email ?: $username . '@irseg.org',
                        'phone' => $phone,
                        'wp_user_id' => $user_id
                    ]);
                }
            }
        }
        fclose($handle);

        wp_redirect(add_query_arg('workedia_tab', 'users-management', wp_get_referer()));
        exit;
    }


    public function ajax_bulk_delete_users() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'workediaMemberAction')) wp_send_json_error('Security check failed');

        $ids = explode(',', $_POST['user_ids']);
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id === get_current_user_id()) continue;
            if (!$this->can_manage_user($id)) continue;
            wp_delete_user($id);
        }
        wp_send_json_success();
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_message_action', 'nonce');

        $sender_id = get_current_user_id();
        $member_id = intval($_POST['member_id'] ?? 0);

        if (!$member_id) {
            // Try to find member_id from current user if they are a member
            global $wpdb;
            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $sender_id));
            if ($member_by_wp) $member_id = $member_by_wp->id;
        }

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $member = Workedia_DB::get_member_by_id($member_id);
        if (!$member) wp_send_json_error('Invalid member context');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $governorate = $member->governorate;

        $file_url = null;
        if (!empty($_FILES['message_file']['name'])) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['message_file']['type'], $allowed_types)) {
                wp_send_json_error('نوع الملف غير مسموح به. يسمح فقط بملفات PDF والصور.');
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('message_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        Workedia_DB::send_message($sender_id, $receiver_id, $message, $member_id, $file_url, $governorate);
        wp_send_json_success();
    }

    public function ajax_get_conversation() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_message_action', 'nonce');

        $member_id = intval($_POST['member_id'] ?? 0);
        if (!$member_id) {
            $sender_id = get_current_user_id();
            global $wpdb;
            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $sender_id));
            if ($member_by_wp) $member_id = $member_by_wp->id;
        }

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        wp_send_json_success(Workedia_DB::get_ticket_messages($member_id));
    }

    public function ajax_get_conversations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_message_action', 'nonce');

        $user = wp_get_current_user();
        $gov = get_user_meta($user->ID, 'workedia_governorate', true);
        $has_full_access = current_user_can('manage_options');

        if (!$gov && !$has_full_access) wp_send_json_error('No governorate assigned');

        if (in_array('subscriber', (array)$user->roles)) {
             // Members see officials of their governorate
             $officials = Workedia_DB::get_governorate_officials($gov);
             $data = [];
             foreach($officials as $o) {
                 $data[] = [
                     'official' => [
                         'ID' => $o->ID,
                         'display_name' => $o->display_name,
                         'avatar' => get_avatar_url($o->ID)
                     ]
                 ];
             }
             wp_send_json_success(['type' => 'member_view', 'officials' => $data]);
        } else {
             // Officials see members' tickets
             // If System Admin/WP Admin, pass null to see all governorates
             $target_gov = $has_full_access ? null : $gov;
             $conversations = Workedia_DB::get_governorate_conversations($target_gov);
             foreach($conversations as &$c) {
                 $c['member']->avatar = $c['member']->photo_url ?: get_avatar_url($c['member']->wp_user_id ?: 0);
             }
             wp_send_json_success(['type' => 'official_view', 'conversations' => $conversations]);
        }
    }

    public function ajax_mark_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_message_action', 'nonce');
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}workedia_messages", ['is_read' => 1], ['receiver_id' => get_current_user_id(), 'sender_id' => intval($_POST['other_user_id'])]);
        wp_send_json_success();
    }


    public function handle_print() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $type = sanitize_text_field($_GET['print_type'] ?? '');
        $member_id = intval($_GET['member_id'] ?? 0);

        if ($member_id && !$this->can_access_member($member_id)) wp_die('Access denied');

        switch($type) {
            case 'id_card':
                include WORKEDIA_PLUGIN_DIR . 'templates/print-id-cards.php';
                break;
            case 'credentials':
                include WORKEDIA_PLUGIN_DIR . 'templates/print-member-credentials.php';
                break;
            default:
                wp_die('Invalid print type');
        }
        exit;
    }

    public function ajax_submit_update_request_ajax() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول');
        check_ajax_referer('workedia_update_request', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('لا تملك صلاحية تعديل هذا العضو');

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'national_id' => sanitize_text_field($_POST['national_id']),
            'residence_governorate' => sanitize_text_field($_POST['residence_governorate']),
            'residence_city' => sanitize_text_field($_POST['residence_city']),
            'residence_street' => sanitize_textarea_field($_POST['residence_street']),
            'governorate' => sanitize_text_field($_POST['governorate']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $res = Workedia_DB::add_update_request($member_id, $data);
        if ($res) {
            wp_send_json_success();
        } else {
            wp_send_json_error('فشل في إرسال الطلب');
        }
    }

    public function ajax_process_update_request_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_update_request', 'nonce');

        $request_id = intval($_POST['request_id']);
        $status = sanitize_text_field($_POST['status']); // 'approved' or 'rejected'

        if (Workedia_DB::process_update_request($request_id, $status)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('فشل في معالجة الطلب');
        }
    }

    public function ajax_forgot_password_otp() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $member = Workedia_DB::get_member_by_national_id($national_id);
        if (!$member || !$member->wp_user_id) {
            wp_send_json_error('الرقم القومي غير مسجل في النظام');
        }

        $user = get_userdata($member->wp_user_id);
        $otp = sprintf("%06d", mt_rand(1, 999999));

        update_user_meta($user->ID, 'workedia_recovery_otp', $otp);
        update_user_meta($user->ID, 'workedia_recovery_otp_time', time());
        update_user_meta($user->ID, 'workedia_recovery_otp_used', 0);

        $workedia = Workedia_Settings::get_workedia_info();
        $subject = "رمز استعادة كلمة المرور - " . $workedia['workedia_name'];
        $message = "عزيزي العضو " . $member->name . ",\n\n";
        $message .= "رمز التحقق الخاص بك هو: " . $otp . "\n";
        $message .= "هذا الرمز صالح لمدة 10 دقائق فقط ولمرة واحدة.\n\n";
        $message .= "إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.\n";

        wp_mail($member->email, $subject, $message);

        wp_send_json_success('تم إرسال رمز التحقق إلى بريدك الإلكتروني المسجل');
    }

    public function ajax_reset_password_otp() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';

        $member = Workedia_DB::get_member_by_national_id($national_id);
        if (!$member || !$member->wp_user_id) wp_send_json_error('بيانات غير صحيحة');

        $user_id = $member->wp_user_id;
        $saved_otp = get_user_meta($user_id, 'workedia_recovery_otp', true);
        $otp_time = get_user_meta($user_id, 'workedia_recovery_otp_time', true);
        $otp_used = get_user_meta($user_id, 'workedia_recovery_otp_used', true);

        if ($otp_used || $saved_otp !== $otp || (time() - $otp_time) > 600) {
            update_user_meta($user_id, 'workedia_recovery_otp_used', 1); // Mark as attempt made
            wp_send_json_error('رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        if (strlen($new_pass) < 10 || !preg_match('/^[a-zA-Z0-9]+$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تكون 10 أحرف على الأقل وتتكون من حروف وأرقام فقط بدون رموز');
        }

        wp_set_password($new_pass, $user_id);
        update_user_meta($user_id, 'workedia_recovery_otp_used', 1);

        wp_send_json_success('تمت إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');
    }

    public function ajax_activate_account_step1() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $membership_number = sanitize_text_field($_POST['membership_number'] ?? '');

        $member = Workedia_DB::get_member_by_national_id($national_id);
        if (!$member) wp_send_json_error('الرقم القومي غير موجود في السجلات.');

        if ($member->membership_number !== $membership_number) {
            wp_send_json_error('بيانات التحقق غير صحيحة، يرجى مراجعة رقم العضوية.');
        }

        wp_send_json_success('تم التحقق بنجاح. يرجى إكمال بيانات الحساب');
    }

    public function ajax_get_template_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $type = sanitize_text_field($_POST['type']);
        $template = Workedia_Notifications::get_template($type);
        if ($template) wp_send_json_success($template);
        else wp_send_json_error('Template not found');
    }

    public function ajax_activate_account_final() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $membership_number = sanitize_text_field($_POST['membership_number'] ?? '');
        $new_email = sanitize_email($_POST['email'] ?? '');
        $new_phone = sanitize_text_field($_POST['phone'] ?? '');
        $new_pass = $_POST['password'] ?? '';

        $member = Workedia_DB::get_member_by_national_id($national_id);
        if (!$member || $member->membership_number !== $membership_number) {
            wp_send_json_error('فشل التحقق من الهوية');
        }

        if (strlen($new_pass) < 10 || !preg_match('/^[a-zA-Z0-9]+$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تكون 10 أحرف على الأقل وتتكون من حروف وأرقام فقط');
        }

        if (!is_email($new_email)) wp_send_json_error('بريد إلكتروني غير صحيح');

        // Update member record
        Workedia_DB::update_member($member->id, ['email' => $new_email, 'phone' => $new_phone]);

        // Update WP User
        if ($member->wp_user_id) {
            wp_update_user([
                'ID' => $member->wp_user_id,
                'user_email' => $new_email,
                'user_pass' => $new_pass
            ]);
            update_user_meta($member->wp_user_id, 'workedia_phone', $new_phone);
            delete_user_meta($member->wp_user_id, 'workedia_temp_pass');
        }

        wp_send_json_success('تم تفعيل الحساب بنجاح. يمكنك الآن تسجيل الدخول');

        // Send Welcome Notification
        Workedia_Notifications::send_template_notification($member->id, 'welcome_activation');
    }

    public function ajax_save_page_settings() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $id = intval($_POST['id']);
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'instructions' => sanitize_textarea_field($_POST['instructions']),
            'settings' => stripslashes($_POST['settings'] ?? '{}')
        ];

        if (Workedia_DB::update_page($id, $data)) wp_send_json_success();
        else wp_send_json_error('Failed to update page');
    }

    public function ajax_add_article() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'content' => wp_kses_post($_POST['content']),
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'status' => 'publish'
        ];

        if (Workedia_DB::add_article($data)) wp_send_json_success();
        else wp_send_json_error('Failed to add article');
    }

    public function ajax_delete_article() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        if (Workedia_DB::delete_article(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete article');
    }

    public function ajax_save_alert() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');

        $data = [
            'id' => !empty($_POST['id']) ? intval($_POST['id']) : null,
            'title' => sanitize_text_field($_POST['title']),
            'message' => wp_kses_post($_POST['message']),
            'severity' => sanitize_text_field($_POST['severity']),
            'must_acknowledge' => !empty($_POST['must_acknowledge']) ? 1 : 0,
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];

        if (Workedia_DB::save_alert($data)) wp_send_json_success();
        else wp_send_json_error('Failed to save alert');
    }

    public function ajax_delete_alert() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_admin_action', 'nonce');
        if (Workedia_DB::delete_alert(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete alert');
    }

    public function ajax_acknowledge_alert() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $alert_id = intval($_POST['alert_id']);
        if (Workedia_DB::acknowledge_alert($alert_id, get_current_user_id())) wp_send_json_success();
        else wp_send_json_error('Failed to acknowledge alert');
    }


    // Ticketing System AJAX Handlers
    public function ajax_get_tickets() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_ticket_action', 'nonce');
        $args = array(
            'status' => $_GET['status'] ?? '',
            'category' => $_GET['category'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'province' => $_GET['province'] ?? '',
            'search' => $_GET['search'] ?? ''
        );
        $tickets = Workedia_DB::get_tickets($args);
        wp_send_json_success($tickets);
    }

    public function ajax_create_ticket() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_ticket_action', 'nonce');

        $user = wp_get_current_user();
        global $wpdb;
        $member = $wpdb->get_row($wpdb->prepare("SELECT id, governorate FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user->ID));

        if (!$member) wp_send_json_error('Member profile not found');

        $file_url = null;
        if (!empty($_FILES['attachment']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('attachment', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        $data = array(
            'member_id' => $member->id,
            'subject' => sanitize_text_field($_POST['subject']),
            'category' => sanitize_text_field($_POST['category']),
            'priority' => sanitize_text_field($_POST['priority'] ?? 'medium'),
            'message' => sanitize_textarea_field($_POST['message']),
            'province' => $member->governorate,
            'file_url' => $file_url
        );

        $ticket_id = Workedia_DB::create_ticket($data);
        if ($ticket_id) wp_send_json_success($ticket_id);
        else wp_send_json_error('Failed to create ticket');
    }

    public function ajax_get_ticket_details() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_ticket_action', 'nonce');
        $id = intval($_GET['id']);
        $ticket = Workedia_DB::get_ticket($id);

        if (!$ticket) wp_send_json_error('Ticket not found');

        // Check permission
        $user = wp_get_current_user();
        $is_sys_admin = in_array('administrator', $user->roles);
        $is_officer = in_array('administrator', $user->roles);

        if (!$is_sys_admin) {
             if ($is_officer) {
                 $gov = get_user_meta($user->ID, 'workedia_governorate', true);
                 if ($gov && $ticket->province !== $gov) wp_send_json_error('Access denied');
             } else {
                 global $wpdb;
                 $member_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user->ID));
                 if ($ticket->member_id != $member_id) wp_send_json_error('Access denied');
             }
        }

        $thread = Workedia_DB::get_ticket_thread($id);
        wp_send_json_success(array('ticket' => $ticket, 'thread' => $thread));
    }

    public function ajax_add_ticket_reply() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_ticket_action', 'nonce');

        $ticket_id = intval($_POST['ticket_id']);

        $file_url = null;
        if (!empty($_FILES['attachment']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('attachment', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        $data = array(
            'ticket_id' => $ticket_id,
            'sender_id' => get_current_user_id(),
            'message' => sanitize_textarea_field($_POST['message']),
            'file_url' => $file_url
        );

        $reply_id = Workedia_DB::add_ticket_reply($data);
        if ($reply_id) {
            // If officer replies, set status to in-progress
            if (!in_array('subscriber', wp_get_current_user()->roles)) {
                Workedia_DB::update_ticket_status($ticket_id, 'in-progress');
            }
            wp_send_json_success($reply_id);
        } else wp_send_json_error('Failed to add reply');
    }

    public function ajax_close_ticket() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('workedia_ticket_action', 'nonce');

        $id = intval($_POST['id']);
        if (Workedia_DB::update_ticket_status($id, 'closed')) wp_send_json_success();
        else wp_send_json_error('Failed to close ticket');
    }

    public function inject_global_alerts() {
        if (!is_user_logged_in()) return;

        $user_id = get_current_user_id();
        $alerts = Workedia_DB::get_active_alerts_for_user($user_id);

        if (empty($alerts)) return;

        foreach ($alerts as $alert) {
            $severity_class = 'workedia-alert-' . $alert->severity;
            $bg_color = '#fff';
            $border_color = '#e2e8f0';
            $text_color = '#1a202c';

            if ($alert->severity === 'warning') {
                $bg_color = '#fffaf0';
                $border_color = '#f6ad55';
            } elseif ($alert->severity === 'critical') {
                $bg_color = '#fff5f5';
                $border_color = '#feb2b2';
            }

            ?>
            <div id="workedia-global-alert-<?php echo $alert->id; ?>" class="workedia-alert-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); z-index:99999; display:flex; align-items:center; justify-content:center; animation: workediaFadeIn 0.3s ease-out;">
                <div class="workedia-alert-modal" style="background:<?php echo $bg_color; ?>; border:2px solid <?php echo $border_color; ?>; border-radius:15px; width:90%; max-width:500px; padding:30px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position:relative; text-align:center; direction:rtl; font-family:'Rubik', sans-serif;">
                    <div style="font-size:40px; margin-bottom:15px;">
                        <?php
                        if ($alert->severity === 'info') echo 'ℹ️';
                        elseif ($alert->severity === 'warning') echo '⚠️';
                        elseif ($alert->severity === 'critical') echo '🚨';
                        ?>
                    </div>
                    <h2 style="margin:0 0 15px 0; color:#2d3748; font-weight:800; font-size:1.5em;"><?php echo esc_html($alert->title); ?></h2>
                    <div style="color:#4a5568; line-height:1.6; margin-bottom:25px; font-size:1.1em;"><?php echo wp_kses_post($alert->message); ?></div>
                    <div style="font-size:11px; color:#a0aec0; margin-bottom:20px;"><?php echo date_i18n('j F Y, H:i', strtotime($alert->created_at)); ?></div>

                    <button onclick="workediaAcknowledgeAlert(<?php echo $alert->id; ?>, <?php echo $alert->must_acknowledge ? 'true' : 'false'; ?>)" class="workedia-btn" style="width:100%; height:45px; font-weight:800; background:<?php echo ($alert->severity === 'critical' ? '#e53e3e' : ($alert->severity === 'warning' ? '#dd6b20' : 'var(--workedia-primary-color)')); ?>;">
                        <?php echo $alert->must_acknowledge ? 'إقرار واستمرار' : 'إغلاق'; ?>
                    </button>
                </div>
            </div>
            <?php
        }
        ?>
        <script>
        function workediaAcknowledgeAlert(alertId, mustAck) {
            const fd = new FormData();
            fd.append('action', 'workedia_acknowledge_alert');
            fd.append('alert_id', alertId);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('workedia-global-alert-' + alertId).remove();
                } else if (!mustAck) {
                    document.getElementById('workedia-global-alert-' + alertId).remove();
                }
            });
        }
        </script>
        <?php
    }

}
