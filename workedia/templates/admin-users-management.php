<?php if (!defined('ABSPATH')) exit; ?>
<div class="workedia-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; border:none; padding:0;">إدارة مستخدمي النظام</h3>
        <?php if (current_user_can('manage_options')): ?>
            <div style="display:flex; gap:10px; flex-wrap: wrap;">
                <button onclick="executeBulkDeleteUsers()" class="workedia-btn" style="width:auto; background:#e53e3e;">حذف المستخدمين المحددين</button>
                <button onclick="document.getElementById('unified-import-form').style.display='block'" class="workedia-btn" style="width:auto; background:var(--workedia-secondary-color);">استيراد جماعي (CSV)</button>
                <button onclick="document.getElementById('add-user-modal').style.display='flex'" class="workedia-btn" style="width:auto;">+ إضافة مستخدم جديد</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Unified Import Form -->
    <div id="unified-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--workedia-secondary-color);">مركز استيراد المستخدمين والأعضاء</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin-top:0;">استيراد أعضاء (Members)</h4>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">(الرقم القومي، الاسم، المحافظة، رقم الهاتف، البريد الإلكتروني)</p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
                    <input type="file" name="member_csv_file" accept=".csv" required style="margin-bottom:10px; width:100%;">
                    <button type="submit" name="workedia_import_members_csv" class="workedia-btn" style="background:#27ae60; width:100%;">بدء استيراد الأعضاء</button>
                </form>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin-top:0;">استيراد مستخدمين/مسؤولين (Staff)</h4>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">(اسم المستخدم، البريد، الاسم، الكود، المسمى، الهاتف، كلمة المرور)</p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
                    <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:10px; width:100%;">
                    <button type="submit" name="workedia_import_staffs_csv" class="workedia-btn" style="background:var(--workedia-primary-color); width:100%;">بدء استيراد المسؤولين</button>
                </form>
            </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <button type="button" onclick="document.getElementById('unified-import-form').style.display='none'" class="workedia-btn workedia-btn-outline" style="width: auto;">إغلاق نافذة الاستيراد</button>
        </div>
    </div>

    <?php
    $current_user = wp_get_current_user();
    $is_sys_manager = in_array('administrator', (array)$current_user->roles);
    ?>

    <div style="background: white; padding: 30px; border: 1px solid var(--workedia-border-color); border-radius: var(--workedia-radius); margin-bottom: 30px; box-shadow: var(--workedia-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 20px; align-items: end;">
            <input type="hidden" name="workedia_tab" value="users-management">

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">بحث عن مستخدم (اسم/بريد/كود/رقم قومي):</label>
                <input type="text" name="user_search" class="workedia-input" value="<?php echo esc_attr(isset($_GET['user_search']) ? $_GET['user_search'] : ''); ?>" placeholder="أدخل بيانات البحث...">
            </div>

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">تصفية حسب الدور:</label>
                <select name="role_filter" class="workedia-select">
                    <option value="">كل المستخدمين</option>
                    <option value="administrator" <?php selected($_GET['role_filter'] ?? '', 'administrator'); ?>>مديرو النظام (Administrators)</option>
                    <option value="subscriber" <?php selected($_GET['role_filter'] ?? '', 'subscriber'); ?>>الأعضاء (Members/Subscribers)</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="workedia-btn">تطبيق البحث</button>
                <a href="<?php echo add_query_arg(array('workedia_tab'=>'users-management'), remove_query_arg(array('user_search', 'role_filter', 'paged'))); ?>" class="workedia-btn workedia-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="workedia-table-container">
        <table class="workedia-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" onclick="toggleAllUsers(this)"></th>
                    <th>الرقم القومي / الكود</th>
                    <th>الاسم الكامل</th>
                    <th>الدور</th>
                    <th>المحافظة</th>
                    <th>رقم التواصل</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $role_labels = array(
                    'administrator' => 'مدير نظام',
                    'subscriber'    => 'عضو'
                );

                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $limit = 20;
                $offset = ($current_page - 1) * $limit;

                $args = array(
                    'number' => $limit,
                    'offset' => $offset,
                    'role__in' => array('administrator', 'subscriber')
                );

                if (!empty($_GET['role_filter'])) {
                    $args['role'] = sanitize_text_field($_GET['role_filter']);
                }

                if (!empty($_GET['user_search'])) {
                    $args['search'] = '*' . esc_attr($_GET['user_search']) . '*';
                    $args['search_columns'] = array('user_login', 'display_name', 'user_email');
                }

                $users = Workedia_DB::get_staff($args); // This already handles gov filtering for local admins

                if (empty($users)): ?>
                    <tr><td colspan="7" style="padding: 40px; text-align: center;">لا يوجد مستخدمون يطابقون البحث.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $role = (array)$u->roles;
                        $role_slug = reset($role);
                        $member_id = null;
                        if ($role_slug === 'subscriber') {
                            global $wpdb;
                            $member_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}workedia_members WHERE wp_user_id = %d", $u->ID));
                        }
                    ?>
                        <tr class="user-row" data-user-id="<?php echo $u->ID; ?>">
                            <td><input type="checkbox" class="user-cb" value="<?php echo $u->ID; ?>"></td>
                            <td style="font-weight: 700; color: var(--workedia-primary-color);">
                                <?php echo esc_html(get_user_meta($u->ID, 'workediaMemberIdAttr', true) ?: $u->user_login); ?>
                            </td>
                            <td style="font-weight: 800;"><?php echo esc_html($u->display_name); ?></td>
                            <td><span class="workedia-badge <?php echo $role_slug == 'administrator' ? 'workedia-badge-high' : 'workedia-badge-low'; ?>"><?php echo $role_labels[$role_slug] ?? $role_slug; ?></span></td>
                            <td><?php echo Workedia_Settings::get_governorates()[get_user_meta($u->ID, 'workedia_governorate', true)] ?? 'غير محدد'; ?></td>
                            <td dir="ltr" style="text-align: right;"><?php echo esc_html(get_user_meta($u->ID, 'workedia_phone', true)); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <?php if ($member_id): ?>
                                        <a href="<?php echo add_query_arg(['workedia_tab' => 'member-profile', 'member_id' => $member_id]); ?>" class="workedia-btn workedia-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px; text-decoration:none;">الملف</a>
                                    <?php endif; ?>
                                    <button onclick='workediaEditUser(<?php echo esc_attr(wp_json_encode(array(
                                        "id" => $u->ID,
                                        "name" => $u->display_name,
                                        "email" => $u->user_email,
                                        "login" => $u->user_login,
                                        "role" => $role_slug,
                                        "member_id_attr" => get_user_meta($u->ID, "workediaMemberIdAttr", true),
                                        "phone" => get_user_meta($u->ID, "workedia_phone", true),
                                        "governorate" => get_user_meta($u->ID, "workedia_governorate", true),
                                        "status" => get_user_meta($u->ID, "workedia_account_status", true) ?: "active"
                                    ))); ?>)' class="workedia-btn workedia-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px;">تعديل</button>
                                    <button onclick="workediaDeleteUser(<?php echo $u->ID; ?>, '<?php echo esc_js($u->display_name); ?>')" class="workedia-btn" style="background:#e53e3e; padding: 5px 12px; font-size: 12px; height: 32px;">حذف</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    $total_users = count(Workedia_DB::get_staff(array_merge($args, ['number' => -1, 'offset' => 0])));
    $total_pages = ceil($total_users / $limit);
    if ($total_pages > 1):
    ?>
    <div class="workedia-pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo add_query_arg('paged', $i); ?>" class="workedia-btn <?php echo $i == $current_page ? '' : 'workedia-btn-outline'; ?>" style="padding: 5px 12px; min-width: 40px; text-align: center;"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 800px;">
            <div class="workedia-modal-header">
                <h3>إضافة مستخدم جديد للنظام</h3>
                <button class="workedia-modal-close" onclick="document.getElementById('add-user-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-user-form">
                <?php wp_nonce_field('workediaMemberAction', 'workedia_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; padding: 25px;">
                    <div class="workedia-form-group">
                        <label class="workedia-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">الرقم القومي / الكود:</label>
                        <input type="text" name="officer_id" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">اختيار الدور:</label>
                        <select name="role" class="workedia-select" onchange="toggleMemberFields(this.value)">
                            <option value="subscriber">عضو (Subscriber)</option>
                            <?php if ($is_sys_manager): ?>
                                <option value="administrator">مدير نظام (Administrator)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">المحافظة:</label>
                        <select name="governorate" class="workedia-select">
                            <option value="">-- اختر المحافظة --</option>
                            <?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">رقم الهاتف:</label>
                        <input type="text" name="phone" class="workedia-input">
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">اسم المستخدم (Login):</label>
                        <input type="text" name="user_login" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">كلمة المرور (اختياري):</label>
                        <input type="password" name="user_pass" class="workedia-input" placeholder="********">
                    </div>
                </div>
                <div id="member-specific-fields" style="display: block; padding: 0 25px 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h4 style="margin-top:0;">بيانات العضوية (اختياري)</h4>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="workedia-form-group"><label class="workedia-label">رقم العضوية:</label><input name="membership_number" type="text" class="workedia-input"></div>
                        <div class="workedia-form-group"><label class="workedia-label">حالة العضوية:</label>
                            <select name="membership_status" class="workedia-select">
                                <?php foreach (Workedia_Settings::get_membership_statuses() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div style="padding: 25px; background: #f8fafc; text-align: left;">
                    <button type="submit" class="workedia-btn" style="width: auto; padding: 10px 40px;">إنشاء الحساب الآن</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 700px;">
            <div class="workedia-modal-header">
                <h3>تعديل بيانات الحساب</h3>
                <button class="workedia-modal-close" onclick="document.getElementById('edit-user-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-user-form">
                <?php wp_nonce_field('workediaMemberAction', 'workedia_nonce'); ?>
                <input type="hidden" name="edit_officer_id" id="edit_user_db_id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; padding: 25px;">
                    <div class="workedia-form-group">
                        <label class="workedia-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" id="edit_user_display_name" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">الرقم القومي / الكود:</label>
                        <input type="text" name="officer_id" id="edit_user_code" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">رقم الهاتف:</label>
                        <input type="text" name="phone" id="edit_user_phone" class="workedia-input">
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" id="edit_user_email" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">تغيير الدور:</label>
                        <select name="role" id="edit_user_role" class="workedia-select">
                            <option value="subscriber">عضو (Subscriber)</option>
                            <?php if ($is_sys_manager): ?>
                                <option value="administrator">مدير النظام (Administrator)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">المحافظة:</label>
                        <select name="governorate" id="edit_user_gov" class="workedia-select">
                            <option value="">-- اختر المحافظة --</option>
                            <?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">حالة الحساب:</label>
                        <select name="account_status" id="edit_user_status" class="workedia-select">
                            <option value="active">نشط</option>
                            <option value="restricted">مقيد (لا يمكنه الدخول)</option>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">كلمة مرور جديدة (اختياري):</label>
                        <input type="password" name="user_pass" class="workedia-input" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                </div>
                <div style="padding: 25px; background: #f8fafc; text-align: left;">
                    <button type="submit" class="workedia-btn" style="width: auto; padding: 10px 40px;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleAllUsers(master) {
        document.querySelectorAll('.user-cb').forEach(cb => cb.checked = master.checked);
    }

    function toggleMemberFields(role) {
        const div = document.getElementById('member-specific-fields');
        div.style.display = (role === 'subscriber') ? 'block' : 'none';
    }

    window.workediaDeleteUser = function(id, name) {
        if (!confirm('هل أنت متأكد من حذف حساب: ' + name + '؟')) return;
        const formData = new FormData();
        formData.append('action', 'workedia_delete_staff_ajax');
        formData.append('user_id', id);
        formData.append('nonce', '<?php echo wp_create_nonce("workediaMemberAction"); ?>');

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تم حذف المستخدم بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };

    function executeBulkDeleteUsers() {
        const ids = Array.from(document.querySelectorAll('.user-cb:checked')).map(cb => cb.value);
        if (ids.length === 0) {
            alert('يرجى تحديد مستخدمين أولاً');
            return;
        }
        if (!confirm('هل أنت متأكد من حذف ' + ids.length + ' مستخدم؟')) return;

        const formData = new FormData();
        formData.append('action', 'workedia_bulk_delete_users_ajax');
        formData.append('user_ids', ids.join(','));
        formData.append('nonce', '<?php echo wp_create_nonce("workediaMemberAction"); ?>');

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تم حذف المستخدمين بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    window.workediaEditUser = function(u) {
        document.getElementById('edit_user_db_id').value = u.id;
        document.getElementById('edit_user_display_name').value = u.name;
        document.getElementById('edit_user_code').value = u.member_id_attr;
        document.getElementById('edit_user_phone').value = u.phone;
        document.getElementById('edit_user_email').value = u.email;
        document.getElementById('edit_user_status').value = u.status || 'active';
        document.getElementById('edit_user_role').value = u.role;
        document.getElementById('edit_user_gov').value = u.governorate || '';
        document.getElementById('edit-user-modal').style.display = 'flex';
    };

    document.getElementById('add-user-form').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'workedia_add_staff_ajax');
        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تمت إضافة المستخدم بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                workediaShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.getElementById('edit-user-form').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'workedia_update_staff_ajax');
        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                workediaShowNotification('تم تحديث بيانات المستخدم');
                setTimeout(() => location.reload(), 500);
            }
        });
    };
    </script>
</div>
