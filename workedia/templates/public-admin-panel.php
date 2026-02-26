<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * WORKEDIA - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const WORKEDIA_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'workedia-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            console.log('Opening tab:', tabId);
            const target = document.getElementById(tabId);
            if (!target || !element) {
                console.error('Target or element not found:', tabId, element);
                return;
            }
            const container = target.parentElement;
            container.querySelectorAll('.workedia-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.workedia-tab-btn').forEach(b => b.classList.remove('workedia-active'));
            element.classList.add('workedia-active');
        }
    };

    window.workediaShowNotification = WORKEDIA_UI.showNotification;
    window.workediaOpenInternalTab = WORKEDIA_UI.openInternalTab;

    window.workediaViewLogDetails = function(log) {
        const detailsBody = document.getElementById('log-details-body');
        let detailsText = log.details;

        if (log.details.startsWith('ROLLBACK_DATA:')) {
            try {
                const data = JSON.parse(log.details.replace('ROLLBACK_DATA:', ''));
                detailsText = `<pre style="background:#f4f4f4; padding:10px; border-radius:5px; font-size:11px; overflow-x:auto;">${JSON.stringify(data, null, 2)}</pre>`;
            } catch(e) {
                detailsText = log.details;
            }
        }

        detailsBody.innerHTML = `
            <div style="display:grid; gap:15px;">
                <div><strong>المشغل:</strong> ${log.display_name || 'نظام'}</div>
                <div><strong>الوقت:</strong> ${log.created_at}</div>
                <div><strong>الإجراء:</strong> <span class="workedia-badge workedia-badge-low">${log.action}</span></div>
                <div><strong>بيانات العملية:</strong><br>${detailsText}</div>
            </div>
        `;
        document.getElementById('log-details-modal').style.display = 'flex';
    };

    window.workediaRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;

        const fd = new FormData();
        fd.append('action', 'workedia_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };

    // MEDIA UPLOADER FOR LOGO
    window.workediaDeleteGovData = function() {
        const gov = document.getElementById('workedia_gov_action_target').value;
        if (!gov) return alert('يرجى اختيار المحافظة أولاً');
        if (!confirm('هل أنت متأكد من حذف كافة بيانات محافظة ' + gov + '؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'workedia_delete_gov_data_ajax');
        fd.append('governorate', gov);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تم حذف بيانات المحافظة بنجاح.');
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    };

    window.workediaMergeGovData = function(input) {
        const gov = document.getElementById('workedia_gov_action_target').value;
        if (!gov) return alert('يرجى اختيار المحافظة أولاً لدمج البيانات إليها');
        if (!input.files.length) return;

        const fd = new FormData();
        fd.append('action', 'workedia_merge_gov_data_ajax');
        fd.append('governorate', gov);
        fd.append('backup_file', input.files[0]);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تم دمج البيانات بنجاح. التفاصيل: ' + res.data);
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    };

    window.workediaResetSystem = function() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;

        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'workedia_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };


    window.workediaDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'workedia_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.workediaDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'workedia_clear_all_logs');
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.workediaOpenMediaUploader = function(inputId) {
        const frame = wp.media({
            title: 'اختر شعار Workedia',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    };

    window.workediaToggleUserDropdown = function() {
        const menu = document.getElementById('workedia-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('workedia-profile-view').style.display = 'block';
            document.getElementById('workedia-profile-edit').style.display = 'none';
            const notif = document.getElementById('workedia-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.workediaToggleNotifications = function() {
        const menu = document.getElementById('workedia-notifications-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('workedia-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.workediaEditProfile = function() {
        document.getElementById('workedia-profile-view').style.display = 'none';
        document.getElementById('workedia-profile-edit').style.display = 'block';
    };

    window.workediaSaveProfile = function() {
        const name = document.getElementById('workedia_edit_display_name').value;
        const email = document.getElementById('workedia_edit_user_email').value;
        const pass = document.getElementById('workedia_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("workedia_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'workedia_update_profile_ajax');
        formData.append('display_name', name);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                workediaShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.workedia-user-dropdown');
        const menu = document.getElementById('workedia-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('settings_saved')) {
            workediaShowNotification('تم حفظ الإعدادات بنجاح');
        }
    });

    window.workediaEditPageSettings = function(page) {
        document.getElementById('edit-page-id').value = page.id;
        document.getElementById('page-edit-name').innerText = page.title;
        document.getElementById('edit-page-title').value = page.title;
        document.getElementById('edit-page-instructions').value = page.instructions;
        document.getElementById('workedia-edit-page-modal').style.display = 'flex';
    };

    document.getElementById('workedia-edit-page-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'workedia_save_page_settings');
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { workediaShowNotification('تم تحديث الصفحة'); location.reload(); }
            else alert(res.data);
        });
    });

    window.workediaOpenAddArticleModal = function() {
        document.getElementById('workedia-add-article-modal').style.display = 'flex';
    };

    document.getElementById('workedia-add-article-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'workedia_add_article');
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { workediaShowNotification('تم نشر المقال'); location.reload(); }
            else alert(res.data);
        });
    });

    window.workediaDeleteArticle = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
        const fd = new FormData();
        fd.append('action', 'workedia_delete_article');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    }

    window.workediaOpenAddAlertModal = function() {
        document.getElementById('workedia-alert-form').reset();
        document.getElementById('edit-alert-id').value = '';
        document.getElementById('workedia-alert-modal-title').innerText = 'إنشاء تنبيه نظام جديد';
        document.getElementById('workedia-alert-modal').style.display = 'flex';
    };

    window.workediaEditAlert = function(al) {
        const f = document.getElementById('workedia-alert-form');
        document.getElementById('edit-alert-id').value = al.id;
        f.title.value = al.title;
        f.message.value = al.message;
        f.severity.value = al.severity;
        f.status.value = al.status;
        f.must_acknowledge.checked = al.must_acknowledge == 1;
        document.getElementById('workedia-alert-modal-title').innerText = 'تعديل التنبيه';
        document.getElementById('workedia-alert-modal').style.display = 'flex';
    };

    window.workediaDeleteAlert = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا التنبيه؟')) return;
        const fd = new FormData();
        fd.append('action', 'workedia_delete_alert');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    };

    const alertTemplates = {
        payment: { title: 'تذكير بسداد الرسوم', message: 'نود تذكيركم بضرورة سداد رسوم العضوية المتأخرة لتجنب غرامات التأخير ولضمان استمرار الخدمات.', severity: 'warning', must_acknowledge: 1 },
        expiry: { title: 'تنبيه: انتهاء صلاحية العضوية', message: 'عضويتكم ستنتهي قريباً، يرجى التوجه لقسم المالية أو السداد إلكترونياً لتجديد العضوية.', severity: 'critical', must_acknowledge: 1 },
        maintenance: { title: 'إعلان صيانة النظام', message: 'سيتم إيقاف النظام مؤقتاً لأعمال الصيانة الدورية يوم الجمعة القادم من الساعة 2 صباحاً وحتى 6 صباحاً.', severity: 'info', must_acknowledge: 0 },
        docs: { title: 'تذكير باستكمال الوثائق', message: 'يرجى مراجعة ملفكم الشخصي ورفع الوثائق المطلوبة لاستكمال ملف العضوية الرقمي.', severity: 'info', must_acknowledge: 0 },
        urgent: { title: 'قرار إداري عاجل', message: 'بناءً على اجتماع مجلس الإدارة الأخير، تقرر البدء في تنفيذ الآلية الجديدة لتوزيع الحوافز المهنية.', severity: 'critical', must_acknowledge: 1 }
    };

    window.workediaApplyAlertTemplate = function(type) {
        const t = alertTemplates[type];
        if(!t) return;
        const f = document.getElementById('workedia-alert-form');
        f.title.value = t.title;
        f.message.value = t.message;
        f.severity.value = t.severity;
        f.must_acknowledge.checked = t.must_acknowledge == 1;
        document.getElementById('workedia-alert-modal-title').innerText = 'إنشاء تنبيه من قالب';
        document.getElementById('workedia-alert-modal').style.display = 'flex';
    };

    document.getElementById('workedia-alert-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'workedia_save_alert');
        fd.append('nonce', '<?php echo wp_create_nonce("workedia_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { workediaShowNotification('تم حفظ التنبيه'); location.reload(); }
            else alert(res.data);
        });
    });

})(window);
</script>

<?php
global $wpdb;
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('administrator', $roles);
$is_administrator = in_array('administrator', $roles);
$is_subscriber = in_array('subscriber', $roles);
$is_member = in_array('subscriber', $roles);
$is_officer = $is_administrator;

$active_tab = isset($_GET['workedia_tab']) ? sanitize_text_field($_GET['workedia_tab']) : 'summary';
$is_restricted = $is_subscriber;
if ($is_restricted && !in_array($active_tab, ['my-profile', 'member-profile', 'messaging', 'surveys'])) {
    $active_tab = 'my-profile';
}

$workedia = Workedia_Settings::get_workedia_info();
$labels = Workedia_Settings::get_labels();
$appearance = Workedia_Settings::get_appearance();
$stats = array();

if ($active_tab === 'summary') {
    $stats = Workedia_DB::get_statistics();
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="workedia-admin-dashboard" dir="rtl" style="font-family: 'Rubik', sans-serif; background: <?php echo $appearance['bg_color']; ?>; border: 1px solid var(--workedia-border-color); border-radius: 12px; overflow: hidden; color: <?php echo $appearance['font_color']; ?>; font-size: <?php echo $appearance['font_size']; ?>; font-weight: <?php echo $appearance['font_weight']; ?>; line-height: <?php echo $appearance['line_spacing']; ?>;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="workedia-main-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($workedia['workedia_logo'])): ?>
                <div style="background: white; padding: 5px; border: 1px solid var(--workedia-border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <img src="<?php echo esc_url($workedia['workedia_logo']); ?>" style="height: 45px; width: auto; object-fit: contain; display: block;">
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; padding: 5px; border: 1px solid var(--workedia-border-color); border-radius: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--workedia-dark-color); font-weight: 800; font-size: 1.3em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($workedia['workedia_name']); ?>
                </h1>
                <div style="display: inline-flex; flex-direction: column; align-items: center; padding: 5px 15px; background: #f0f4f8; color: #111F35; border-radius: 12px; font-size: 11px; font-weight: 700; margin-top: 6px; border: 1px solid #cbd5e0; line-height: 1.4;">
                    <div>
                        <?php
                        if ($is_admin || $is_sys_admin) echo 'مدير النظام';
                        elseif ($is_administrator) echo 'مسؤول Workedia';
                        elseif ($is_subscriber) echo 'عضو Workedia';
                        elseif ($is_member) echo 'عضو';
                        else echo 'مستخدم النظام';
                        ?>
                    </div>
                    <?php
                    $my_gov_key = get_user_meta($user->ID, 'workedia_governorate', true);
                    $govs = Workedia_Settings::get_governorates();
                    $my_gov_label = $govs[$my_gov_key] ?? '';
                    if ($my_gov_label): ?>
                        <div style="width: 100%; height: 1px; background: #cbd5e0; margin: 3px 0;"></div>
                        <div style="color: var(--workedia-primary-color); font-size: 10px;"><?php echo esc_html($my_gov_label); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="workedia-header-info-box" style="text-align: right; border-left: 1px solid var(--workedia-border-color); padding-left: 15px;">
                <div style="font-size: 0.85em; font-weight: 700; color: var(--workedia-dark-color);"><?php echo date_i18n('l j F Y'); ?></div>
            </div>

            <div style="display: flex; gap: 15px; align-items: center; border-left: 1px solid var(--workedia-border-color); padding-left: 20px;">
                <!-- Messages Icon -->
                <a href="<?php echo add_query_arg('workedia_tab', 'messaging'); ?>" class="workedia-header-circle-icon" title="المراسلات والشكاوى">
                    <span class="dashicons dashicons-email"></span>
                    <?php
                    $unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}workedia_messages WHERE receiver_id = %d AND is_read = 0", $user->ID));
                    if ($unread_msgs > 0): ?>
                        <span class="workedia-icon-badge" style="background: #e53e3e;"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Notifications Icon -->
                <div class="workedia-notifications-dropdown" style="position: relative;">
                    <a href="javascript:void(0)" onclick="workediaToggleNotifications()" class="workedia-header-circle-icon" title="التنبيهات">
                        <span class="dashicons dashicons-bell"></span>
                        <?php
                        $notif_alerts = [];
                        if ($is_restricted) {
                            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, last_paid_membership_year FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $user->ID));
                            if ($member_by_wp) {
                                if ($member_by_wp->last_paid_membership_year < date('Y')) {
                                    $notif_alerts[] = ['text' => 'يوجد متأخرات في تجديد العضوية السنوية', 'type' => 'warning'];
                                }
                            }
                        }
                        if (current_user_can('manage_options')) {
                            $pending_updates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}workedia_update_requests WHERE status = 'pending'");
                            if ($pending_updates > 0) {
                                $notif_alerts[] = ['text' => 'يوجد ' . $pending_updates . ' طلبات تحديث بيانات بانتظار المراجعة', 'type' => 'info'];
                            }
                        }

                        // Integrated System Alerts
                        $sys_alerts = Workedia_DB::get_active_alerts_for_user($user->ID);
                        foreach($sys_alerts as $sa) {
                            $notif_alerts[] = ['text' => $sa->title, 'type' => 'system', 'id' => $sa->id];
                        }

                        if (count($notif_alerts) > 0): ?>
                            <span class="workedia-icon-dot" style="background: #f6ad55;"></span>
                        <?php endif; ?>
                    </a>
                    <div id="workedia-notifications-menu" style="display: none; position: absolute; top: 150%; left: 0; background: white; border: 1px solid var(--workedia-border-color); border-radius: 8px; width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4>
                        <?php if (empty($notif_alerts)): ?>
                            <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px;">لا توجد تنبيهات جديدة حالياً</div>
                        <?php else: ?>
                            <?php foreach ($notif_alerts as $a): ?>
                                <div style="font-size: 12px; padding: 8px; border-bottom: 1px solid #f9fafb; color: #4a5568; display: flex; gap: 8px; align-items: flex-start;">
                                    <span class="dashicons <?php echo $a['type'] == 'system' ? 'dashicons-megaphone' : 'dashicons-warning'; ?>" style="font-size: 16px; color: <?php echo $a['type'] == 'system' ? 'var(--workedia-primary-color)' : '#d69e2e'; ?>;"></span>
                                    <span>
                                        <?php echo $a['text']; ?>
                                        <?php if($a['type'] == 'system'): ?>
                                            <br><a href="javascript:location.reload()" style="font-size:10px; color:var(--workedia-primary-color); font-weight:700;">عرض التفاصيل</a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="workedia-user-dropdown" style="position: relative;">
                <div class="workedia-user-profile-nav" onclick="workediaToggleUserDropdown()" style="display: flex; align-items: center; gap: 12px; background: white; padding: 6px 12px; border-radius: 50px; border: 1px solid var(--workedia-border-color); cursor: pointer;">
                    <div style="text-align: right;">
                        <div style="font-size: 0.85em; font-weight: 700; color: var(--workedia-dark-color);"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <div style="font-size: 0.7em; color: #38a169;">متصل الآن <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px;"></span></div>
                    </div>
                    <?php echo get_avatar($user->ID, 32, '', '', array('style' => 'border-radius: 50%; border: 2px solid var(--workedia-primary-color);')); ?>
                </div>
                <div id="workedia-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--workedia-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: workediaFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="workedia-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px;">
                            <div style="font-weight: 800; color: var(--workedia-dark-color);"><?php echo $user->display_name; ?></div>
                            <div style="font-size: 11px; color: var(--workedia-text-gray);"><?php echo $user->user_email; ?></div>
                        </div>
                        <?php if (!$is_member): ?>
                            <a href="javascript:workediaEditProfile()" class="workedia-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_member): ?>
                            <a href="javascript:workediaEditProfile()" class="workedia-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>" class="workedia-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="workedia-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="workedia-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="workedia-form-group" style="margin-bottom: 10px;">
                            <label class="workedia-label" style="font-size: 11px;">الاسم المفضل:</label>
                            <input type="text" id="workedia_edit_display_name" class="workedia-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->display_name); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="workedia-form-group" style="margin-bottom: 10px;">
                            <label class="workedia-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="workedia_edit_user_email" class="workedia-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="workedia-form-group" style="margin-bottom: 15px;">
                            <label class="workedia-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="workedia_edit_user_pass" class="workedia-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="workediaSaveProfile()" class="workedia-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('workedia-profile-edit').style.display='none'; document.getElementById('workedia-profile-view').style.display='block';" class="workedia-btn workedia-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo wp_logout_url(home_url('/workedia-login')); ?>" class="workedia-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="workedia-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <?php $is_restricted = $is_subscriber; ?>
        <div class="workedia-sidebar" style="width: 280px; flex-shrink: 0; background: <?php echo $appearance['sidebar_bg_color']; ?>; border-left: 1px solid var(--workedia-border-color); padding: 20px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">

                <?php if (!$is_restricted): ?>
                <li class="workedia-sidebar-item <?php echo $active_tab == 'summary' ? 'workedia-active' : ''; ?>">
                    <a href="<?php echo add_query_arg('workedia_tab', 'summary'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-dashboard"></span> <?php echo $labels['tab_summary']; ?></a>
                </li>
                <?php endif; ?>

                <?php if ($is_restricted): ?>
                    <li class="workedia-sidebar-item <?php echo in_array($active_tab, ['my-profile', 'member-profile']) ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('workedia_tab', 'my-profile'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_my_profile']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if (!$is_restricted && ($is_admin || $is_sys_admin || $is_administrator)): ?>
                    <li class="workedia-sidebar-item <?php echo in_array($active_tab, ['users-management', 'update-requests']) ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('workedia_tab', 'users-management'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_users_management']; ?></a>
                        <ul class="workedia-sidebar-dropdown" style="display: <?php echo in_array($active_tab, ['users-management', 'update-requests']) ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'users-management'); ?>" class="<?php echo $active_tab == 'users-management' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> قائمة المستخدمين</a></li>
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'update-requests'); ?>" class="<?php echo $active_tab == 'update-requests' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-edit"></span> طلبات التحديث</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin || $is_administrator || $is_subscriber || $is_member): ?>
                    <li class="workedia-sidebar-item <?php echo $active_tab == 'digital-services' ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('workedia_tab', 'digital-services'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-cloud"></span> الخدمات الرقمية</a>
                    </li>
                    <li class="workedia-sidebar-item <?php echo $active_tab == 'surveys' ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('workedia_tab', 'surveys'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-clipboard"></span> <?php echo $labels['tab_surveys']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin || $is_administrator): ?>
                    <li class="workedia-sidebar-item <?php echo $active_tab == 'global-settings' ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>" class="workedia-sidebar-link"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <ul class="workedia-sidebar-dropdown" style="display: <?php echo $active_tab == 'global-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>&sub=init" class="<?php echo (!isset($_GET['sub']) || $_GET['sub'] == 'init') ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> تهيئة النظام</a></li>
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>&sub=notifications" class="<?php echo ($_GET['sub'] ?? '') == 'notifications' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-email"></span> التنبيهات والبريد</a></li>
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>&sub=design" class="<?php echo ($_GET['sub'] ?? '') == 'design' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-art"></span> التصميم والمظهر</a></li>
                            <li><a href="<?php echo add_query_arg('workedia_tab', 'global-settings'); ?>&sub=pages" class="<?php echo ($_GET['sub'] ?? '') == 'pages' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-page"></span> تخصيص الصفحات</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin): ?>
                    <li class="workedia-sidebar-item <?php echo $active_tab == 'advanced-settings' ? 'workedia-active' : ''; ?>">
                        <a href="<?php echo add_query_arg(['workedia_tab' => 'advanced-settings', 'sub' => 'alerts']); ?>" class="workedia-sidebar-link" style="color: #c53030 !important;"><span class="dashicons dashicons-shield-alt"></span> الإعدادات المتقدمة</a>
                        <ul class="workedia-sidebar-dropdown" style="display: <?php echo $active_tab == 'advanced-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg(['workedia_tab' => 'advanced-settings', 'sub' => 'alerts']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'alerts' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-megaphone"></span> تنبيهات النظام (System Alerts)</a></li>
                            <li><a href="<?php echo add_query_arg(['workedia_tab' => 'advanced-settings', 'sub' => 'backup']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'backup' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-database-export"></span> مركز النسخ الاحتياطي</a></li>
                            <li><a href="<?php echo add_query_arg(['workedia_tab' => 'advanced-settings', 'sub' => 'logs']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'logs' ? 'workedia-sub-active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> سجل النشاطات (Activity Log)</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- CONTENT AREA -->
        <div class="workedia-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">

            <?php
            switch ($active_tab) {
                case 'summary':
                    include WORKEDIA_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    break;

                case 'users-management':
                    if ($is_admin || current_user_can('manage_options')) {
                        include WORKEDIA_PLUGIN_DIR . 'templates/admin-users-management.php';
                    }
                    break;

                case 'messaging':
                    include WORKEDIA_PLUGIN_DIR . 'templates/messaging-center.php';
                    break;


                case 'member-profile':
                case 'my-profile':
                    if ($active_tab === 'my-profile') {
                        $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", get_current_user_id()));
                        if ($member_by_wp) $_GET['member_id'] = $member_by_wp->id;
                    }
                    include WORKEDIA_PLUGIN_DIR . 'templates/admin-member-profile.php';
                    break;


                case 'surveys':
                    if ($is_admin || $is_sys_admin || $is_administrator) {
                        include WORKEDIA_PLUGIN_DIR . 'templates/admin-surveys.php';
                    } elseif ($is_subscriber || $is_member) {
                        // Members see only active surveys to participate
                        include WORKEDIA_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    }
                    break;

                case 'digital-services':
                    include WORKEDIA_PLUGIN_DIR . 'templates/admin-services.php';
                    break;

                case 'advanced-settings':
                    if ($is_admin || $is_sys_admin) {
                        $sub = $_GET['sub'] ?? 'alerts';
                        ?>
                        <div class="workedia-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="workedia-tab-btn <?php echo ($sub == 'alerts') ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('system-alerts-settings', this)">تنبيهات النظام</button>
                            <button class="workedia-tab-btn <?php echo ($sub == 'backup') ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                            <button class="workedia-tab-btn <?php echo ($sub == 'logs') ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('activity-logs', this)">سجل النشاطات</button>
                        </div>

                        <div id="system-alerts-settings" class="workedia-internal-tab" style="display: <?php echo ($sub == 'alerts') ? 'block' : 'none'; ?>;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:20px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <h4 style="margin:0;">إدارة تنبيهات النظام الشاملة</h4>
                                    <button onclick="workediaOpenAddAlertModal()" class="workedia-btn" style="width:auto; padding:8px 20px;">+ إنشاء تنبيه جديد</button>
                                </div>

                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:20px;">
                                    <button onclick="workediaApplyAlertTemplate('payment')" class="workedia-btn workedia-btn-outline" style="font-size:12px;">قالب: تذكير بالسداد</button>
                                    <button onclick="workediaApplyAlertTemplate('expiry')" class="workedia-btn workedia-btn-outline" style="font-size:12px;">قالب: تنبيه انتهاء العضوية</button>
                                    <button onclick="workediaApplyAlertTemplate('maintenance')" class="workedia-btn workedia-btn-outline" style="font-size:12px;">قالب: صيانة النظام</button>
                                    <button onclick="workediaApplyAlertTemplate('docs')" class="workedia-btn workedia-btn-outline" style="font-size:12px;">قالب: تذكير الوثائق</button>
                                    <button onclick="workediaApplyAlertTemplate('urgent')" class="workedia-btn workedia-btn-outline" style="font-size:12px;">قالب: قرار إداري عاجل</button>
                                </div>

                                <div class="workedia-table-container" style="margin:0;">
                                    <table class="workedia-table">
                                        <thead>
                                            <tr>
                                                <th>العنوان</th>
                                                <th>المستوى</th>
                                                <th>الإقرار</th>
                                                <th>الحالة</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $alerts = Workedia_DB::get_alerts();
                                            if (empty($alerts)): ?>
                                                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد تنبيهات نشطة حالياً.</td></tr>
                                            <?php else: foreach($alerts as $al):
                                                $severity_map = ['info' => 'عادي (White)', 'warning' => 'تحذير (Orange)', 'critical' => 'هام جداً (Red)'];
                                                $severity_color = ['info' => '#64748b', 'warning' => '#f59e0b', 'critical' => '#e53e3e'];
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($al->title); ?></strong></td>
                                                    <td><span style="color:<?php echo $severity_color[$al->severity]; ?>; font-weight:700;"><?php echo $severity_map[$al->severity]; ?></span></td>
                                                    <td><?php echo $al->must_acknowledge ? '✅ نعم' : '❌ لا'; ?></td>
                                                    <td>
                                                        <span class="workedia-badge <?php echo $al->status == 'active' ? 'workedia-badge-high' : 'workedia-badge-low'; ?>">
                                                            <?php echo $al->status == 'active' ? 'نشط' : 'معطل'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display:flex; gap:5px;">
                                                            <button onclick='workediaEditAlert(<?php echo json_encode($al); ?>)' class="workedia-btn workedia-btn-outline" style="padding:4px 10px; font-size:11px;">تعديل</button>
                                                            <button onclick="workediaDeleteAlert(<?php echo $al->id; ?>)" class="workedia-btn" style="background:#e53e3e; padding:4px 10px; font-size:11px;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div id="backup-settings" class="workedia-internal-tab" style="display: <?php echo ($sub == 'backup') ? 'block' : 'none'; ?>;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = Workedia_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--workedia-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--workedia-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات الأعضاء بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
                                                <button type="submit" name="workedia_download_backup" class="workedia-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="workedia_restore_backup" class="workedia-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>

                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">إدارة بيانات محافظة محددة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">حذف أو دمج بيانات محافظة واحدة فقط دون المساس ببقية المحافظات.</p>
                                        <div style="display:flex; flex-direction:column; gap:10px;">
                                            <select id="workedia_gov_action_target" class="workedia-select" style="font-size:12px;">
                                                <option value="">-- اختر المحافظة --</option>
                                                <?php foreach(Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                                            </select>
                                            <div style="display:flex; gap:8px;">
                                                <button onclick="workediaDeleteGovData()" class="workedia-btn" style="background:#e53e3e; width:auto; font-size:11px;">حذف بيانات المحافظة</button>
                                                <button onclick="document.getElementById('workedia_gov_merge_file').click()" class="workedia-btn" style="background:#805ad5; width:auto; font-size:11px;">دمج بيانات (JSON)</button>
                                                <input type="file" id="workedia_gov_merge_file" style="display:none;" onchange="workediaMergeGovData(this)">
                                            </div>
                                        </div>
                                    </div>

                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:1px solid #feb2b2; grid-column: 1 / -1;">
                                        <h5 style="margin-top:0; color:#c53030;">منطقة الخطر: إعادة تهيئة النظام</h5>
                                        <p style="font-size:12px; color:#c53030; margin-bottom:15px;">سيقوم هذا الإجراء بمسح كافة بيانات الأعضاء، الحسابات، والنشاطات بشكل نهائي ولا يمكن التراجع عنه.</p>
                                        <button onclick="workediaResetSystem()" class="workedia-btn" style="background:#e53e3e; width:auto; font-weight:800;">إعادة تهيئة النظام بالكامل (Reset)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="activity-logs" class="workedia-internal-tab" style="display: <?php echo ($sub == 'logs') ? 'block' : 'none'; ?>;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:15px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                    <div>
                                        <h4 style="margin:0; font-size:16px;">سجل نشاطات النظام الشامل</h4>
                                        <div style="font-size:11px; color:#718096;">آخر 200 نشاط مسجل في النظام.</div>
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <form method="get" style="display:flex; gap:5px;">
                                            <input type="hidden" name="workedia_tab" value="advanced-settings">
                                            <input type="hidden" name="sub" value="logs">
                                            <input type="text" name="log_search" value="<?php echo esc_attr($_GET['log_search'] ?? ''); ?>" placeholder="بحث في السجلات..." class="workedia-input" style="width:200px; padding:5px 10px; font-size:12px;">
                                            <button type="submit" class="workedia-btn" style="width:auto; padding:5px 15px; font-size:12px;">بحث</button>
                                        </form>
                                        <button onclick="workediaDeleteAllLogs()" class="workedia-btn" style="background:#e53e3e; width:auto; font-size:12px; padding:5px 15px;">تفريغ السجل</button>
                                    </div>
                                </div>
                                <div class="workedia-table-container" style="margin:0; overflow-x:auto;">
                                    <table class="workedia-table" style="font-size:12px; width:100%;">
                                        <thead>
                                            <tr style="background:#f8fafc;">
                                                <th style="padding:8px; width:140px;">الوقت</th>
                                                <th style="padding:8px; width:120px;">المستخدم</th>
                                                <th style="padding:8px; width:120px;">الإجراء</th>
                                                <th style="padding:8px;">التفاصيل</th>
                                                <th style="padding:8px; width:100px;">إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $limit = 25;
                                            $page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                                            $offset = ($page_num - 1) * $limit;
                                            $search = sanitize_text_field($_GET['log_search'] ?? '');
                                            $all_logs = Workedia_Logger::get_logs($limit, $offset, $search);
                                            $total_logs = Workedia_Logger::get_total_logs($search);
                                            $total_pages = ceil($total_logs / $limit);

                                            if (empty($all_logs)): ?>
                                                <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد سجلات تطابق البحث</td></tr>
                                            <?php endif;

                                            foreach ($all_logs as $log):
                                                $can_rollback = strpos($log->details, 'ROLLBACK_DATA:') === 0;
                                                $details_display = $can_rollback ? 'عملية تتضمن بيانات للاستعادة' : esc_html($log->details);
                                            ?>
                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                    <td style="padding:6px 8px; color: #718096;"><?php echo esc_html($log->created_at); ?></td>
                                                    <td style="padding:6px 8px; font-weight: 600;"><?php echo esc_html($log->display_name ?: 'نظام'); ?></td>
                                                    <td style="padding:6px 8px;"><span style="background:<?php echo $appearance['primary_color']; ?>15; color:<?php echo $appearance['primary_color']; ?>; padding:2px 6px; border-radius:4px; font-weight:700;"><?php echo esc_html($log->action); ?></span></td>
                                                    <td style="padding:6px 8px; color:#4a5568; line-height:1.4;"><?php echo mb_strimwidth($details_display, 0, 100, "..."); ?></td>
                                                    <td style="padding:6px 8px;">
                                                        <div style="display:flex; gap:5px;">
                                                            <button onclick='workediaViewLogDetails(<?php echo json_encode($log); ?>)' class="workedia-btn workedia-btn-outline" style="padding:2px 8px; font-size:10px;">التفاصيل</button>
                                                            <?php if ($can_rollback): ?>
                                                                <button onclick="workediaRollbackLog(<?php echo $log->id; ?>)" class="workedia-btn" style="padding:2px 8px; font-size:10px; background:#38a169;">استعادة</button>
                                                            <?php endif; ?>
                                                            <button onclick="workediaDeleteLog(<?php echo $log->id; ?>)" class="workedia-btn" style="padding:2px 8px; font-size:10px; background:#e53e3e;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages > 1): ?>
                                    <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                                        <?php if ($page_num > 1): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num - 1); ?>" class="workedia-btn workedia-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">السابق</a>
                                        <?php endif; ?>
                                        <span style="align-self:center; font-size:13px;">صفحة <?php echo $page_num; ?> من <?php echo $total_pages; ?></span>
                                        <?php if ($page_num < $total_pages): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num + 1); ?>" class="workedia-btn workedia-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">التالي</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                    break;

                case 'update-requests':
                    if ($is_admin || $is_sys_admin || $is_administrator) {
                        include WORKEDIA_PLUGIN_DIR . 'templates/admin-update-requests.php';
                    }
                    break;

                case 'global-settings':
                    if ($is_admin || $is_sys_admin || $is_administrator) {
                        $sub = $_GET['sub'] ?? 'init';
                        ?>
                        <div class="workedia-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="workedia-tab-btn <?php echo $sub == 'init' ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('workedia-settings', this)">تهيئة النظام</button>
                            <button class="workedia-tab-btn <?php echo $sub == 'notifications' ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('notification-settings', this)">التنبيهات والبريد</button>
                            <button class="workedia-tab-btn <?php echo $sub == 'design' ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('design-settings', this)">التصميم والمظهر</button>
                            <button class="workedia-tab-btn <?php echo $sub == 'pages' ? 'workedia-active' : ''; ?>" onclick="workediaOpenInternalTab('page-customization', this)">تخصيص الصفحات</button>
                        </div>

                        <div id="workedia-settings" class="workedia-internal-tab" style="display: <?php echo ($sub == 'init') ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>

                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--workedia-shadow);">
                                    <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--workedia-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-groups"></span> بيانات Workedia (Union Data)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                                        <div class="workedia-form-group"><label class="workedia-label">اسم Workedia كاملاً:</label><input type="text" name="workedia_name" value="<?php echo esc_attr($workedia['workedia_name']); ?>" class="workedia-input"></div>
                                        <div class="workedia-form-group"><label class="workedia-label">اسم رئيس Workedia / المسؤول:</label><input type="text" name="workedia_officer_name" value="<?php echo esc_attr($workedia['workedia_officer_name'] ?? ''); ?>" class="workedia-input"></div>
                                        <div class="workedia-form-group"><label class="workedia-label">رقم التواصل الموحد:</label><input type="text" name="workedia_phone" value="<?php echo esc_attr($workedia['phone']); ?>" class="workedia-input"></div>
                                        <div class="workedia-form-group"><label class="workedia-label">البريد الإلكتروني الرسمي:</label><input type="email" name="workedia_email" value="<?php echo esc_attr($workedia['email']); ?>" class="workedia-input"></div>
                                        <div class="workedia-form-group"><label class="workedia-label">العنوان الجغرافي للمقر الرئيسي:</label><input type="text" name="workedia_address" value="<?php echo esc_attr($workedia['address']); ?>" class="workedia-input"></div>
                                        <div class="workedia-form-group"><label class="workedia-label">رابط خرائط جوجل (Map Link):</label><input type="url" name="workedia_map_link" value="<?php echo esc_attr($workedia['map_link'] ?? ''); ?>" class="workedia-input" placeholder="https://goo.gl/maps/..."></div>
                                        <div class="workedia-form-group" style="grid-column: span 2;"><label class="workedia-label">تفاصيل إضافية / نبذة عن Workedia:</label><textarea name="workedia_extra_details" class="workedia-textarea" rows="3"><?php echo esc_textarea($workedia['extra_details'] ?? ''); ?></textarea></div>
                                        <div class="workedia-form-group" style="grid-column: span 2;">
                                            <label class="workedia-label">شعار Workedia الرسمي (Official Logo):</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="workedia_logo" id="workedia_logo_url" value="<?php echo esc_attr($workedia['workedia_logo']); ?>" class="workedia-input">
                                                <button type="button" onclick="workediaOpenMediaUploader('workedia_logo_url')" class="workedia-btn" style="width:auto; font-size:12px; background:#4a5568;">اختيار الشعار</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                    <h4 style="margin-top:0; border-bottom:2px solid #cbd5e0; padding-bottom:12px; color: var(--workedia-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-admin-settings"></span> مسميات أقسام النظام (Section Labels)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:15px;">
                                        <?php foreach($labels as $key => $val): ?>
                                            <div class="workedia-form-group">
                                                <label class="workedia-label" style="font-size:11px;"><?php echo str_replace('tab_', '', $key); ?>:</label>
                                                <input type="text" name="<?php echo $key; ?>" value="<?php echo esc_attr($val); ?>" class="workedia-input" style="padding:10px; font-size:13px; border-color: #cbd5e0;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px 0; border-top: 1px solid #eee; z-index: 10;">
                                    <button type="submit" name="workedia_save_settings_unified" class="workedia-btn" style="width:auto; height:50px; padding: 0 50px; font-size: 1.1em; font-weight: 800; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">حفظ كافة الإعدادات والتهيئة</button>
                                </div>
                            </form>
                        </div>



                        <div id="page-customization" class="workedia-internal-tab" style="display: <?php echo $sub == 'pages' ? 'block' : 'none'; ?>;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--workedia-dark-color);">إدارة صفحات النظام والوسوم (Shortcodes)</h4>

                                <div class="workedia-table-container">
                                    <table class="workedia-table">
                                        <thead>
                                            <tr>
                                                <th>اسم الصفحة</th>
                                                <th>الوسم (Shortcode)</th>
                                                <th>الرابط</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach(Workedia_DB::get_pages() as $p): ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($p->title); ?></strong></td>
                                                    <td><code>[<?php echo $p->shortcode; ?>]</code></td>
                                                    <td><a href="<?php echo home_url('/' . $p->slug); ?>" target="_blank">معاينة</a></td>
                                                    <td>
                                                        <button onclick='workediaEditPageSettings(<?php echo json_encode($p); ?>)' class="workedia-btn workedia-btn-outline" style="padding: 5px 10px; font-size: 11px;">تعديل التصميم</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h4 style="margin: 0;">إدارة الأخبار والمقالات (Blog)</h4>
                                    <button onclick="workediaOpenAddArticleModal()" class="workedia-btn" style="width: auto;">+ إضافة مقال جديد</button>
                                </div>

                                <div class="workedia-table-container">
                                    <table class="workedia-table">
                                        <thead>
                                            <tr>
                                                <th>عنوان المقال</th>
                                                <th>التاريخ</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $articles = Workedia_DB::get_articles(50);
                                            if (empty($articles)): ?>
                                                <tr><td colspan="3" style="text-align:center; padding:20px;">لا توجد مقالات حالياً.</td></tr>
                                            <?php else: foreach($articles as $art): ?>
                                                <tr>
                                                    <td><?php echo esc_html($art->title); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($art->created_at)); ?></td>
                                                    <td>
                                                        <button onclick="workediaDeleteArticle(<?php echo $art->id; ?>)" class="workedia-btn" style="background: #e53e3e; padding: 5px 10px; font-size: 11px;">حذف</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="design-settings" class="workedia-internal-tab" style="display: <?php echo $sub == 'design' ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إعدادات الألوان والمظهر الشاملة</h4>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                                    <div class="workedia-form-group"><label class="workedia-label">الأساسي:</label><input type="color" name="primary_color" value="<?php echo esc_attr($appearance['primary_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">الثانوي:</label><input type="color" name="secondary_color" value="<?php echo esc_attr($appearance['secondary_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">التمييز:</label><input type="color" name="accent_color" value="<?php echo esc_attr($appearance['accent_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">الهيدر:</label><input type="color" name="dark_color" value="<?php echo esc_attr($appearance['dark_color']); ?>" class="workedia-input" style="height:40px;"></div>

                                    <div class="workedia-form-group"><label class="workedia-label">خلفية النظام:</label><input type="color" name="bg_color" value="<?php echo esc_attr($appearance['bg_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">خلفية السايدبار:</label><input type="color" name="sidebar_bg_color" value="<?php echo esc_attr($appearance['sidebar_bg_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">لون الخط:</label><input type="color" name="font_color" value="<?php echo esc_attr($appearance['font_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">لون الحدود:</label><input type="color" name="border_color" value="<?php echo esc_attr($appearance['border_color']); ?>" class="workedia-input" style="height:40px;"></div>
                                </div>

                                <h4 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">الخطوط والخطوط المطبعية (Typography)</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
                                    <div class="workedia-form-group"><label class="workedia-label">حجم الخط (مثال: 15px):</label><input type="text" name="font_size" value="<?php echo esc_attr($appearance['font_size']); ?>" class="workedia-input"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">وزن الخط (400, 700...):</label><input type="text" name="font_weight" value="<?php echo esc_attr($appearance['font_weight']); ?>" class="workedia-input"></div>
                                    <div class="workedia-form-group"><label class="workedia-label">تباعد الأسطر (1.5...):</label><input type="text" name="line_spacing" value="<?php echo esc_attr($appearance['line_spacing']); ?>" class="workedia-input"></div>
                                </div>

                                <button type="submit" name="workedia_save_appearance" class="workedia-btn" style="width:auto; margin-top:20px;">حفظ كافة تعديلات التصميم</button>
                            </form>
                        </div>

                        <?php
                    }
                    break;

            }
            ?>

        </div>
    </div>
</div>

<!-- Alert Management Modal -->
<div id="workedia-alert-modal" class="workedia-modal-overlay">
    <div class="workedia-modal-content" style="max-width: 600px;">
        <div class="workedia-modal-header"><h3><span id="workedia-alert-modal-title">إنشاء تنبيه جديد</span></h3><button class="workedia-modal-close" onclick="document.getElementById('workedia-alert-modal').style.display='none'">&times;</button></div>
        <form id="workedia-alert-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-alert-id">
            <div class="workedia-form-group"><label class="workedia-label">عنوان التنبيه:</label><input type="text" name="title" class="workedia-input" required></div>
            <div class="workedia-form-group"><label class="workedia-label">نص الرسالة:</label><textarea name="message" class="workedia-textarea" rows="4" required></textarea></div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="workedia-form-group">
                    <label class="workedia-label">مستوى الخطورة:</label>
                    <select name="severity" class="workedia-select">
                        <option value="info">عادي (White)</option>
                        <option value="warning">تحذير (Orange)</option>
                        <option value="critical">هام (Red)</option>
                    </select>
                </div>
                <div class="workedia-form-group">
                    <label class="workedia-label">الحالة:</label>
                    <select name="status" class="workedia-select">
                        <option value="active">نشط</option>
                        <option value="inactive">معطل</option>
                    </select>
                </div>
            </div>
            <div class="workedia-form-group">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="must_acknowledge" value="1"> يتطلب إقرار بالاستلام من العضو قبل الإغلاق
                </label>
            </div>
            <button type="submit" class="workedia-btn" style="width: 100%; margin-top:10px;">حفظ ونشر التنبيه</button>
        </form>
    </div>
</div>

<!-- Page Edit Modal -->
<div id="workedia-edit-page-modal" class="workedia-modal-overlay">
    <div class="workedia-modal-content">
        <div class="workedia-modal-header"><h3>تعديل الصفحة: <span id="page-edit-name"></span></h3><button class="workedia-modal-close" onclick="document.getElementById('workedia-edit-page-modal').style.display='none'">&times;</button></div>
        <form id="workedia-edit-page-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-page-id">
            <div class="workedia-form-group"><label class="workedia-label">عنوان الصفحة (يظهر في الهيدر):</label><input type="text" name="title" id="edit-page-title" class="workedia-input" required></div>
            <div class="workedia-form-group"><label class="workedia-label">معلومات/تعليمات الصفحة:</label><textarea name="instructions" id="edit-page-instructions" class="workedia-textarea" rows="4"></textarea></div>
            <button type="submit" class="workedia-btn" style="width: 100%;">حفظ التعديلات</button>
        </form>
    </div>
</div>

<!-- Add Article Modal -->
<div id="workedia-add-article-modal" class="workedia-modal-overlay">
    <div class="workedia-modal-content">
        <div class="workedia-modal-header"><h3>إضافة مقال/خبر جديد</h3><button class="workedia-modal-close" onclick="document.getElementById('workedia-add-article-modal').style.display='none'">&times;</button></div>
        <form id="workedia-add-article-form" style="padding: 20px;">
            <div class="workedia-form-group"><label class="workedia-label">عنوان المقال:</label><input type="text" name="title" class="workedia-input" required></div>
            <div class="workedia-form-group"><label class="workedia-label">رابط صورة المقال:</label><input type="text" name="image_url" class="workedia-input"></div>
            <div class="workedia-form-group"><label class="workedia-label">المحتوى:</label><textarea name="content" class="workedia-textarea" rows="6" required></textarea></div>
            <button type="submit" class="workedia-btn" style="width: 100%;">نشر المقال</button>
        </form>
    </div>
</div>

<!-- Global Detailed Finance Modal -->
<div id="log-details-modal" class="workedia-modal-overlay">
    <div class="workedia-modal-content" style="max-width: 700px;">
        <div class="workedia-modal-header">
            <h3>تفاصيل العملية المسجلة</h3>
            <button class="workedia-modal-close" onclick="document.getElementById('log-details-modal').style.display='none'">&times;</button>
        </div>
        <div id="log-details-body" style="padding: 20px;"></div>
    </div>
</div>


<style>
.workedia-sidebar-item { border-bottom: 1px solid rgba(0,0,0,0.05); transition: 0.2s; position: relative; }
.workedia-sidebar-link {
    padding: 15px 25px;
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    width: 100%;
}
.workedia-sidebar-item:hover { background: rgba(0,0,0,0.02); }
.workedia-sidebar-item.workedia-active {
    background: rgba(0,0,0,0.02) !important;
}
.workedia-sidebar-item.workedia-active > .workedia-sidebar-link {
    color: var(--workedia-primary-color) !important;
    font-weight: 700;
}

.workedia-sidebar-badge {
    position: absolute; left: 15px; top: 15px;
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.workedia-sidebar-dropdown {
    list-style: none; padding: 0; margin: 0; background: rgba(0,0,0,0.04); display: none;
}
.workedia-sidebar-dropdown li a {
    display: flex; align-items: center; gap: 12px; padding: 10px 25px;
    font-size: 13px; color: #4a5568 !important; text-decoration: none !important;
    transition: 0.2s;
}
.workedia-sidebar-dropdown li a:hover {
    background: rgba(255,255,255,0.3);
}
.workedia-sidebar-dropdown li a.workedia-sub-active {
    background: var(--workedia-dark-color) !important; color: #fff !important; font-weight: 600;
}
.workedia-sidebar-dropdown li a .dashicons { font-size: 16px; width: 16px; height: 16px; }

.workedia-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--workedia-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.workedia-dropdown-item:hover { background: var(--workedia-bg-light); color: var(--workedia-primary-color) !important; }

@keyframes workediaFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.workedia-admin-dashboard .workedia-main-tab-panel {
    width: 100% !important;
}
.workedia-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.workedia-tab-btn.workedia-active { background: var(--workedia-primary-color) !important; color: #fff !important; border-bottom: none; }
.workedia-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.workedia-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.workedia-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }

.workedia-header-circle-icon {
    width: 40px; height: 40px; background: #ffffff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--workedia-dark-color); text-decoration: none !important; position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    transition: 0.3s;
}
.workedia-header-circle-icon:hover { background: #edf2f7; color: var(--workedia-primary-color); }
.workedia-header-circle-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

.workedia-admin-dashboard .workedia-btn { background-color: <?php echo $appearance['btn_color']; ?>; }
.workedia-admin-dashboard .workedia-table th { border-color: <?php echo $appearance['border_color']; ?>; }
.workedia-admin-dashboard .workedia-input, .workedia-admin-dashboard .workedia-select, .workedia-admin-dashboard .workedia-textarea { border-color: <?php echo $appearance['border_color']; ?>; }

.workedia-icon-badge {
    position: absolute; top: -5px; right: -5px; color: white; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center;
    justify-content: center; font-weight: 800; border: 2px solid white;
}
.workedia-icon-dot {
    position: absolute; top: 0; right: 0; width: 10px; height: 10px;
    border-radius: 50%; border: 2px solid white;
}

@media (max-width: 992px) {
    .workedia-hide-mobile { display: none; }
}
</style>
