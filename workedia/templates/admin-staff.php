<?php if (!defined('ABSPATH')) exit; ?>
<div class="workedia-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; border:none; padding:0;">إدارة مستخدمي النظام</h3>
        <?php if (current_user_can('workedia_manage_users') || current_user_can('manage_options')): ?>
            <div style="display:flex; gap:10px;">
                <button onclick="executeBulkDeleteUsers()" class="workedia-btn" style="width:auto; background:#e53e3e;">حذف المستخدمين المحددين</button>
                <button onclick="document.getElementById('staff-csv-import-form').style.display='block'" class="workedia-btn" style="width:auto; background:var(--workedia-secondary-color);">استيراد جماعي (CSV)</button>
                <button onclick="document.getElementById('add-staff-modal').style.display='flex'" class="workedia-btn" style="width:auto;">+ إضافة مستخدم جديد</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="staff-csv-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--workedia-secondary-color);">دليل استيراد مستخدمي النظام (CSV)</h3>
        
        <div style="background:#fff; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:700; margin-bottom:10px;">هيكل ملف المستخدمين الصحيح:</p>
            <table style="width:100%; font-size:11px; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr style="background:#edf2f7;">
                        <th style="border:1px solid #cbd5e0; padding:5px;">اسم المستخدم</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">البريد</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الاسم الكامل</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">الرقم القومي / كود المستخدم</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">المسمى</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">رقم الجوال</th>
                        <th style="border:1px solid #cbd5e0; padding:5px;">كلمة المرور</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1px solid #cbd5e0; padding:5px;">staff_member</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">user@workedia.com</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">الاسم الكامل</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">S101</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">عضو Workedia</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">050000000</td>
                        <td style="border:1px solid #cbd5e0; padding:5px;">123456</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
            <div class="workedia-form-group">
                <label class="workedia-label">اختر ملف CSV للمستخدمين:</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="workedia_import_staffs_csv" class="workedia-btn" style="width:auto; background:#27ae60;">استيراد القائمة الآن</button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="workedia-btn" style="width:auto; background:var(--workedia-text-gray);">إلغاء</button>
            </div>
        </form>
    </div>

    <?php
    $current_user = wp_get_current_user();
    $is_sys_manager = in_array('workedia_system_admin', (array)$current_user->roles);
    $is_workedia_admin = in_array('workedia_admin', (array)$current_user->roles);
    $my_gov = get_user_meta($current_user->ID, 'workedia_governorate', true);
    ?>

    <?php if ($is_sys_manager): ?>
    <div class="workedia-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee;">
        <a href="<?php echo remove_query_arg('role_filter'); ?>" class="workedia-tab-btn <?php echo empty($_GET['role_filter']) ? 'workedia-active' : ''; ?>" style="text-decoration:none;">الكل</a>
        <a href="<?php echo add_query_arg('role_filter', 'workedia_system_admin'); ?>" class="workedia-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'workedia_system_admin' ? 'workedia-active' : ''; ?>" style="text-decoration:none;">مدير النظام</a>
        <a href="<?php echo add_query_arg('role_filter', 'workedia_admin'); ?>" class="workedia-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'workedia_admin' ? 'workedia-active' : ''; ?>" style="text-decoration:none;">مسؤول Workedia</a>
        <a href="<?php echo add_query_arg('role_filter', 'workedia_member'); ?>" class="workedia-tab-btn <?php echo ($_GET['role_filter'] ?? '') == 'workedia_member' ? 'workedia-active' : ''; ?>" style="text-decoration:none;">عضو Workedia</a>
    </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border: 1px solid var(--workedia-border-color); border-radius: var(--workedia-radius); margin-bottom: 30px; box-shadow: var(--workedia-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end;">
            <input type="hidden" name="page" value="workedia-dashboard">
            <input type="hidden" name="workedia_tab" value="staff">

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">بحث عن مستخدم (اسم/بريد/كود):</label>
                <input type="text" name="staff_search" class="workedia-input" value="<?php echo esc_attr(isset($_GET['staff_search']) ? $_GET['staff_search'] : ''); ?>" placeholder="أدخل بيانات البحث...">
            </div>

            <?php if ($is_sys_manager): ?>
            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">تصفية حسب الدور:</label>
                <select name="role_filter" class="workedia-select">
                    <option value="">كل الأدوار</option>
                    <option value="workedia_system_admin" <?php selected($_GET['role_filter'] ?? '', 'workedia_system_admin'); ?>>مدير النظام</option>
                    <option value="workedia_admin" <?php selected($_GET['role_filter'] ?? '', 'workedia_admin'); ?>>مسؤول Workedia</option>
                    <option value="workedia_member" <?php selected($_GET['role_filter'] ?? '', 'workedia_member'); ?>>عضو Workedia</option>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="workedia-btn">تطبيق البحث</button>
                <a href="<?php echo add_query_arg(array('workedia_tab'=>'staff'), remove_query_arg(array('staff_search', 'role_filter'))); ?>" class="workedia-btn workedia-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="workedia-table-container">
        <table class="workedia-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" onclick="toggleAllUsers(this)"></th>
                    <th>الرقم القومي / كود المستخدم</th>
                    <th>الاسم الكامل</th>
                    <th>الدور / الرتبة</th>
                    <th>المحافظة</th>
                    <th>رقم التواصل</th>
                    <th>البريد الإلكتروني</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $role_labels = array(
                    'workedia_system_admin' => 'مدير النظام',
                    'workedia_admin' => 'مسؤول Workedia',
                    'workedia_member' => 'عضو Workedia'
                );

                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $limit = 20;
                $offset = ($current_page - 1) * $limit;

                $args = array(
                    'number' => $limit,
                    'offset' => $offset
                );
                if (!empty($_GET['role_filter'])) {
                    $args['role'] = sanitize_text_field($_GET['role_filter']);
                }

                if (!empty($_GET['staff_search'])) {
                    $args['search'] = '*' . esc_attr($_GET['staff_search']) . '*';
                    $args['search_columns'] = array('user_login', 'display_name', 'user_email');
                }

                $users = Workedia_DB::get_staff($args);
                if (empty($users)): ?>
                    <tr><td colspan="6" style="padding: 40px; text-align: center;">لا يوجد مستخدمون يطابقون البحث.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $role = (array)$u->roles;
                        $role_slug = reset($role);
                        if ($u->ID === get_current_user_id()) continue; // Skip current user
                    ?>
                        <tr class="user-row" data-user-id="<?php echo $u->ID; ?>">
                            <td><input type="checkbox" class="user-cb" value="<?php echo $u->ID; ?>"></td>
                            <td style="font-family: 'Rubik', sans-serif; font-weight: 700; color: var(--workedia-primary-color);"><?php echo esc_html(get_user_meta($u->ID, 'workediaMemberIdAttr', true) ?: $u->user_login); ?></td>
                            <td style="font-weight: 800; color: var(--workedia-dark-color);"><?php echo esc_html($u->display_name); ?></td>
                            <td><span class="workedia-badge workedia-badge-low"><?php echo $role_labels[$role_slug] ?? $role_slug; ?></span></td>
                            <td><?php echo Workedia_Settings::get_governorates()[get_user_meta($u->ID, 'workedia_governorate', true)] ?? 'غير محدد'; ?></td>
                            <td dir="ltr" style="text-align: right;"><?php echo esc_html(get_user_meta($u->ID, 'workedia_phone', true)); ?></td>
                            <td><?php echo esc_html($u->user_email); ?></td>
                            <td>
                                <div class="workedia-actions-dropdown">
                                    <button class="workedia-actions-trigger">الإجراءات <span class="dashicons dashicons-arrow-down-alt2"></span></button>
                                    <div class="workedia-actions-content">
                                        <?php
                                        $assigned = get_user_meta($u->ID, 'workedia_assigned_specializations', true) ?: (get_user_meta($u->ID, 'workedia_supervised_grades', true) ?: array());
                                        ?>
                                        <a href="javascript:void(0)" onclick="editSmUser(JSON.parse(this.parentElement.dataset.user))" class="workedia-action-item">
                                            <span class="dashicons dashicons-edit"></span> تعديل البيانات
                                        </a>
                                        <a href="javascript:void(0)" onclick="deleteSmUser(<?php echo $u->ID; ?>, '<?php echo esc_js($u->display_name); ?>')" class="workedia-action-item workedia-delete" style="color:#e53e3e;">
                                            <span class="dashicons dashicons-trash"></span> حذف الحساب
                                        </a>
                                        <div style="display:none;" data-user='<?php echo esc_attr(wp_json_encode(array(
                                            "id" => $u->ID,
                                            "name" => $u->display_name,
                                            "email" => $u->user_email,
                                            "login" => $u->user_login,
                                            "role" => $role_slug,
                                            "assigned" => $assigned,
                                            "officer_id" => get_user_meta($u->ID, "workediaMemberIdAttr", true),
                                            "phone" => get_user_meta($u->ID, "workedia_phone", true),
                                            "governorate" => get_user_meta($u->ID, "workedia_governorate", true),
                                            "status" => get_user_meta($u->ID, "workedia_account_status", true) ?: "active"
                                        ))); ?>'></div>
                                    </div>
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


    <div id="edit-staff-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content">
            <div class="workedia-modal-header">
                <h3>تعديل بيانات الحساب</h3>
                <button class="workedia-modal-close" onclick="document.getElementById('edit-staff-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-staff-form">
                <?php wp_nonce_field('workediaMemberAction', 'workedia_nonce'); ?>
                <input type="hidden" name="edit_officer_id" id="edit_off_db_id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="workedia-form-group">
                        <label class="workedia-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" id="edit_off_display_name" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">الرقم القومي / كود المستخدم:</label>
                        <input type="text" name="officer_id" id="edit_off_code" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">رقم الهاتف:</label>
                        <input type="text" name="phone" id="edit_off_phone" class="workedia-input">
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" id="edit_off_email" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">تغيير الدور:</label>
                        <select name="role" id="edit_off_role" class="workedia-select">
                            <?php if ($is_sys_manager): ?>
                                <option value="workedia_system_admin">مدير النظام</option>
                                <option value="workedia_admin">مسؤول Workedia</option>
                            <?php endif; ?>
                            <option value="workedia_member">عضو Workedia</option>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">المحافظة:</label>
                        <select name="governorate" id="edit_off_gov" class="workedia-select">
                            <option value="">-- اختر المحافظة --</option>
                            <?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">حالة الحساب:</label>
                        <select name="account_status" id="edit_off_status" class="workedia-select">
                            <option value="active">نشط</option>
                            <option value="restricted">مقيد (لا يمكنه الدخول)</option>
                        </select>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">كلمة مرور جديدة (اختياري):</label>
                        <input type="password" name="user_pass" class="workedia-input" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                </div>
                <button type="submit" class="workedia-btn" style="margin-top:20px;">حفظ التغييرات</button>
            </form>
        </div>
    </div>

    <div id="add-staff-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content">
            <div class="workedia-modal-header">
                <h3>إضافة حساب مستخدم جديد</h3>
                <button class="workedia-modal-close" onclick="document.getElementById('add-staff-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-staff-form">
                <?php wp_nonce_field('workediaMemberAction', 'workedia_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="workedia-form-group">
                        <label class="workedia-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">الرقم القومي / كود المستخدم:</label>
                        <input type="text" name="officer_id" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">اختيار الدور:</label>
                        <select name="role" class="workedia-select">
                            <?php if ($is_sys_manager): ?>
                                <option value="workedia_system_admin">مدير النظام</option>
                                <option value="workedia_admin">مسؤول Workedia</option>
                            <?php endif; ?>
                            <option value="workedia_member">عضو Workedia</option>
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
                    <div class="workedia-form-group" style="grid-column: span 2;">
                        <label class="workedia-label">كلمة المرور (اترك فارغاً للتوليد التلقائي 10 أرقام):</label>
                        <input type="password" name="user_pass" class="workedia-input" placeholder="********">
                    </div>
                </div>
                <button type="submit" class="workedia-btn" style="margin-top:20px;">إنشاء الحساب الآن</button>
            </form>
        </div>
    </div>

    <script>
    function toggleAllUsers(master) {
        document.querySelectorAll('.user-cb').forEach(cb => cb.checked = master.checked);
    }

    window.deleteSmUser = function(id, name) {
        if (!confirm('هل أنت متأكد من حذف حساب: ' + name + '؟')) return;
        const formData = new FormData();
        formData.append('action', 'workedia_delete_staff_ajax');
        formData.append('user_id', id);
        formData.append('nonce', '<?php echo wp_create_nonce("workediaMemberAction"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف المستخدم بنجاح');
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

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف المستخدمين بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    (function() {
        window.editSmUser = function(u) {
            document.getElementById('edit_off_db_id').value = u.id;
            document.getElementById('edit_off_display_name').value = u.name;
            document.getElementById('edit_off_code').value = u.officer_id;
            document.getElementById('edit_off_phone').value = u.phone;
            document.getElementById('edit_off_email').value = u.email;
            document.getElementById('edit_off_status').value = u.status || 'active';
            document.getElementById('edit_off_role').value = u.role;
            document.getElementById('edit_off_gov').value = u.governorate || '';
            document.getElementById('edit-staff-modal').style.display = 'flex';
        };

        const addForm = document.getElementById('add-staff-form');
        if (addForm) {
            addForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'workedia_add_staff_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة المستخدم بنجاح');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                });
            });
        }

        const editForm = document.getElementById('edit-staff-form');
        if (editForm) {
            editForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'workedia_update_staff_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم تحديث بيانات المستخدم');
                        setTimeout(() => location.reload(), 500);
                    }
                });
            });
        }
    })();
    </script>
</div>
