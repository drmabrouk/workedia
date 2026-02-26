<?php if (!defined('ABSPATH')) exit; ?>
<?php
global $wpdb;
$can_manage_members = current_user_can('manage_options');
$import_results = get_transient('workedia_import_results_' . get_current_user_id());
if ($import_results) {
    delete_transient('workedia_import_results_' . get_current_user_id());
}
?>
<div class="workedia-content-wrapper" dir="rtl">
    <?php if ($import_results): ?>
        <div style="background: #fff; border-radius: 12px; border: 1px solid var(--workedia-border-color); margin-bottom: 30px; overflow: hidden; box-shadow: var(--workedia-shadow);">
            <div style="background: var(--workedia-bg-light); padding: 15px 25px; border-bottom: 1px solid var(--workedia-border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; color: var(--workedia-dark-color); font-weight: 800;">تقرير استيراد الأعضاء الأخير</h4>
                <span style="font-size: 12px; color: #718096;">إجمالي السجلات المعالجة: <?php echo $import_results['total']; ?></span>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                    <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 1px solid #c6f6d5; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #2f855a;"><?php echo $import_results['success']; ?></div>
                        <div style="font-size: 12px; color: #38a169;">تم الاستيراد بنجاح</div>
                    </div>
                    <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #c05621;"><?php echo $import_results['warning']; ?></div>
                        <div style="font-size: 12px; color: #dd6b20;">تنبيهات (بيانات ناقصة)</div>
                    </div>
                    <div style="background: #fff5f5; padding: 15px; border-radius: 8px; border: 1px solid #fed7d7; text-align: center;">
                        <div style="font-size: 20px; font-weight: 800; color: #c53030;"><?php echo $import_results['error']; ?></div>
                        <div style="font-size: 12px; color: #e53e3e;">أخطاء (فشل الاستيراد)</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border: 1px solid var(--workedia-border-color); border-radius: var(--workedia-radius); margin-bottom: 30px; box-shadow: var(--workedia-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="workedia_tab" value="members">

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">بحث:</label>
                <input type="text" name="member_search" class="workedia-input" value="<?php echo esc_attr(isset($_GET['member_search']) ? $_GET['member_search'] : ''); ?>" placeholder="الاسم، الرقم القومي، رقم العضوية...">
            </div>

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">الدرجة الوظيفية:</label>
                <select name="grade_filter" class="workedia-select">
                    <option value="">كل الدرجات</option>
                    <?php foreach (Workedia_Settings::get_professional_grades() as $k => $v): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected(isset($_GET['grade_filter']) && $_GET['grade_filter'] == $k); ?>><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="workedia-form-group" style="margin-bottom:0;">
                <label class="workedia-label">التخصص:</label>
                <select name="spec_filter" class="workedia-select">
                    <option value="">كل التخصصات</option>
                    <?php foreach (Workedia_Settings::get_specializations() as $k => $v): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected(isset($_GET['spec_filter']) && $_GET['spec_filter'] == $k); ?>><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="workedia-btn">بحث</button>
                <a href="<?php echo add_query_arg('workedia_tab', 'members', remove_query_arg(['member_search', 'grade_filter', 'spec_filter', 'status_filter'])); ?>" class="workedia-btn workedia-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <?php if ($can_manage_members): ?>
    <div style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; align-items: center;">
        <button onclick="document.getElementById('add-single-member-modal').style.display='flex'" class="workedia-btn">+ إضافة عضو جديد</button>
        <button onclick="document.getElementById('csv-import-form').style.display='block'" class="workedia-btn workedia-btn-secondary">استيراد أعضاء (Excel)</button>
        <a href="<?php echo admin_url('admin-ajax.php?action=workedia_print&print_type=id_card'); ?>" target="_blank" class="workedia-btn workedia-btn-accent" style="background: #27ae60; text-decoration:none;">طباعة كافة البطاقات</a>
    </div>

    <!-- CSV Import Form -->
    <div id="csv-import-form" style="display:none; background: #f8fafc; padding: 30px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 30px;">
        <h3 style="margin-top:0; color:var(--workedia-secondary-color);">استيراد الأعضاء من ملف CSV / Excel</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">تأكد من أن الملف يحتوي على الأعمدة التالية بالترتيب: (الرقم القومي، الاسم، الدرجة الوظيفية، التخصص، المحافظة، رقم الهاتف، البريد الإلكتروني)</p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('workedia_admin_action', 'workedia_admin_nonce'); ?>
            <div style="display: flex; gap: 15px; align-items: center;">
                <input type="file" name="member_csv_file" accept=".csv" required style="flex: 1; padding: 10px; background: white; border: 1px solid #e2e8f0; border-radius: 8px;">
                <button type="submit" name="workedia_import_members_csv" class="workedia-btn" style="width: auto; background: #27ae60;">بدء الاستيراد الآن</button>
                <button type="button" onclick="document.getElementById('csv-import-form').style.display='none'" class="workedia-btn workedia-btn-outline" style="width: auto;">إلغاء</button>
            </div>
        </form>
        <div style="margin-top: 15px; font-size: 11px; color: #e53e3e;">* سيتم إنشاء حسابات مستخدمين تلقائياً لجميع الأعضاء المستوردين.</div>
    </div>
    <?php endif; ?>

    <div class="workedia-table-container">
        <table class="workedia-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select-all-members" onclick="toggleAllMembers(this)"></th>
                    <th>الرقم القومي</th>
                    <th>الاسم</th>
                    <th>الدرجة الوظيفية</th>
                    <th>التخصص</th>
                    <th>رقم العضوية</th>
                    <th>المبلغ المستحق</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $limit = 20;
                $offset = ($current_page - 1) * $limit;
                $members = Workedia_DB::get_members(array(
                    'search' => $_GET['member_search'] ?? '',
                    'professional_grade' => $_GET['grade_filter'] ?? '',
                    'specialization' => $_GET['spec_filter'] ?? '',
                    'limit' => $limit,
                    'offset' => $offset
                ));
                if (empty($members)): ?>
                    <tr><td colspan="9" style="padding: 60px; text-align: center;">لا يوجد أعضاء يطابقون البحث.</td></tr>
                <?php else:
                    $grades = Workedia_Settings::get_professional_grades();
                    $specs = Workedia_Settings::get_specializations();
                    $statuses = Workedia_Settings::get_membership_statuses();
                    foreach ($members as $member):
                        $finance = Workedia_Finance::calculate_member_dues($member->id);
                    ?>
                        <tr id="member-row-<?php echo $member->id; ?>">
                            <td><input type="checkbox" class="member-checkbox" value="<?php echo $member->id; ?>"></td>
                            <td style="font-weight: 700; color: var(--workedia-primary-color);"><?php echo esc_html($member->national_id); ?></td>
                            <td style="font-weight: 800;"><?php echo esc_html($member->name); ?></td>
                            <td><?php echo esc_html($grades[$member->professional_grade] ?? $member->professional_grade); ?></td>
                            <td><?php echo esc_html($specs[$member->specialization] ?? $member->specialization); ?></td>
                            <td><?php echo esc_html($member->membership_number); ?></td>
                            <td style="font-weight:700; color:<?php echo $finance['balance'] > 0 ? '#e53e3e' : '#38a169'; ?>;"><?php echo number_format($finance['balance'], 2); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="<?php echo add_query_arg('workedia_tab', 'member-profile'); ?>&member_id=<?php echo $member->id; ?>" class="workedia-btn workedia-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px; text-decoration:none; display:flex; align-items:center;">عرض</a>
                                    <?php if ($can_manage_members): ?>
                                        <button onclick='workediaEditMember(<?php echo json_encode($member); ?>)' class="workedia-btn workedia-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px; color: #2c3e50; border-color: #2c3e50;">تعديل</button>
                                        <button onclick='workediaOpenMemberAccountModal(<?php echo json_encode(["id" => $member->id, "wp_user_id" => $member->wp_user_id, "name" => $member->name, "email" => $member->email]); ?>)' class="workedia-btn" style="padding: 5px 12px; font-size: 12px; height: 32px; background: #2c3e50;">الحساب</button>
                                    <?php endif; ?>
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
    $total_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}workedia_members"); // Simplified count for now
    $limit = 20;
    $total_pages = ceil($total_members / $limit);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    if ($total_pages > 1):
    ?>
    <div class="workedia-pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo add_query_arg('paged', $i); ?>" class="workedia-btn <?php echo $i == $current_page ? '' : 'workedia-btn-outline'; ?>" style="padding: 5px 12px; min-width: 40px; text-align: center;"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if ($can_manage_members): ?>
    <div id="add-single-member-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 900px;">
            <div class="workedia-modal-header"><h3>تسجيل عضو جديد</h3><button class="workedia-modal-close" onclick="document.getElementById('add-single-member-modal').style.display='none'">&times;</button></div>
            <form id="add-member-form">
                <?php wp_nonce_field('workedia_add_member', 'workedia_nonce'); ?>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding:20px;">
                    <div class="workedia-form-group"><label class="workedia-label">الرقم القومي:</label><input name="national_id" type="text" class="workedia-input" required maxlength="14"></div>
                    <div class="workedia-form-group"><label class="workedia-label">الاسم الكامل:</label><input name="name" type="text" class="workedia-input" required></div>
                    <div class="workedia-form-group"><label class="workedia-label">الدرجة الوظيفية:</label><select name="professional_grade" class="workedia-select"><?php foreach (Workedia_Settings::get_professional_grades() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">التخصص:</label><select name="specialization" class="workedia-select"><?php foreach (Workedia_Settings::get_specializations() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المؤهل العلمي:</label><select name="academic_degree" class="workedia-select"><?php foreach (Workedia_Settings::get_academic_degrees() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المحافظة:</label><select name="governorate" class="workedia-select"><option value="">-- اختر المحافظة --</option><?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">رقم العضوية:</label><input name="membership_number" type="text" class="workedia-input"></div>
                    <div class="workedia-form-group"><label class="workedia-label">تاريخ بدء العضوية:</label><input name="membership_start_date" id="add_mem_start" type="date" class="workedia-input" onchange="workediaCalculateDateExpiry('add_mem_start', 'add_mem_expiry')"></div>
                    <div class="workedia-form-group"><label class="workedia-label">تاريخ انتهاء العضوية:</label><input name="membership_expiration_date" id="add_mem_expiry" type="date" class="workedia-input"></div>
                </div>
                <button type="submit" class="workedia-btn">إضافة العضو</button>
            </form>
        </div>
    </div>

    <div id="edit-member-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 900px;">
            <div class="workedia-modal-header"><h3>تعديل بيانات العضو</h3><button class="workedia-modal-close" onclick="document.getElementById('edit-member-modal').style.display='none'">&times;</button></div>
            <form id="edit-member-form">
                <?php wp_nonce_field('workedia_add_member', 'workedia_nonce'); ?>
                <input type="hidden" name="member_id" id="edit_member_id_hidden">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding:20px;">
                    <div class="workedia-form-group"><label class="workedia-label">الاسم الكامل:</label><input name="name" id="edit_name" type="text" class="workedia-input" required></div>
                    <div class="workedia-form-group"><label class="workedia-label">الدرجة الوظيفية:</label><select name="professional_grade" id="edit_grade" class="workedia-select"><?php foreach (Workedia_Settings::get_professional_grades() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">التخصص:</label><select name="specialization" id="edit_spec" class="workedia-select"><?php foreach (Workedia_Settings::get_specializations() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المؤهل العلمي:</label><select name="academic_degree" id="edit_degree" class="workedia-select"><?php foreach (Workedia_Settings::get_academic_degrees() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المحافظة:</label><select name="governorate" id="edit_gov" class="workedia-select"><?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">تاريخ بدء العضوية:</label><input name="membership_start_date" id="edit_mem_start_input" type="date" class="workedia-input" onchange="workediaCalculateDateExpiry('edit_mem_start_input', 'edit_mem_expiry_input')"></div>
                    <div class="workedia-form-group"><label class="workedia-label">تاريخ انتهاء العضوية:</label><input name="membership_expiration_date" id="edit_mem_expiry_input" type="date" class="workedia-input"></div>
                </div>
                <button type="submit" class="workedia-btn">تحديث البيانات</button>
            </form>
        </div>
    </div>
    <div id="member-account-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 500px;">
            <div class="workedia-modal-header">
                <h3>إعدادات حساب المستخدم: <span id="acc_member_name"></span></h3>
                <button class="workedia-modal-close" onclick="document.getElementById('member-account-modal').style.display='none'">&times;</button>
            </div>
            <form id="member-account-form">
                <?php wp_nonce_field('workedia_admin_action', 'workedia_nonce'); ?>
                <input type="hidden" name="member_id" id="acc_member_id">
                <input type="hidden" name="wp_user_id" id="acc_wp_user_id">
                <div style="padding: 20px;">
                    <div class="workedia-form-group">
                        <label class="workedia-label">البريد الإلكتروني:</label>
                        <input name="email" id="acc_email" type="email" class="workedia-input" required>
                    </div>
                    <div class="workedia-form-group">
                        <label class="workedia-label">كلمة مرور جديدة (اتركها فارغة إذا لم ترد التغيير):</label>
                        <input name="password" type="password" class="workedia-input">
                    </div>
                    <?php if (current_user_can('manage_options')): ?>
                    <div class="workedia-form-group">
                        <label class="workedia-label">الدور / الصلاحيات:</label>
                        <select name="role" id="acc_role" class="workedia-select">
                            <option value="subscriber">عضو Workedia (افتراضي)</option>
                            <option value="administrator">مسؤول Workedia</option>
                            <option value="administrator">مدير نظام</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" class="workedia-btn" style="flex: 1;">حفظ التغييرات</button>
                        <button type="button" class="workedia-btn workedia-btn-outline" style="flex: 1;" onclick="document.getElementById('member-account-modal').style.display='none'">إلغاء</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function() {
        window.workediaCalculateDateExpiry = function(startId, endId) {
            const startEl = document.getElementById(startId);
            const endEl = document.getElementById(endId);
            if (startEl && endEl && startEl.value) {
                const date = new Date(startEl.value);
                date.setFullYear(date.getFullYear() + 1);
                endEl.value = date.toISOString().split('T')[0];
            }
        };

        window.workediaEditMember = function(s) {
            document.getElementById('edit_member_id_hidden').value = s.id;
            document.getElementById('edit_name').value = s.name;
            document.getElementById('edit_grade').value = s.professional_grade;
            document.getElementById('edit_spec').value = s.specialization;
            document.getElementById('edit_degree').value = s.academic_degree;
            document.getElementById('edit_gov').value = s.governorate;
            document.getElementById('edit_mem_start_input').value = s.membership_start_date;
            document.getElementById('edit_mem_expiry_input').value = s.membership_expiration_date;
            document.getElementById('edit-member-modal').style.display = 'flex';
        };

        window.toggleAllMembers = function(master) {
            document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = master.checked);
        };

        window.workediaOpenMemberAccountModal = function(data) {
            document.getElementById('acc_member_id').value = data.id;
            document.getElementById('acc_wp_user_id').value = data.wp_user_id;
            document.getElementById('acc_member_name').innerText = data.name;
            document.getElementById('acc_email').value = data.email;

            // If role dropdown exists (for full admins)
            const roleSelect = document.getElementById('acc_role');
            if (roleSelect && data.wp_user_id) {
                // We'd ideally fetch the current role via AJAX or have it in the data
                // For now let's set it to default and maybe add a quick fetch
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=workedia_get_user_role&user_id=' + data.wp_user_id)
                .then(r => r.json()).then(res => {
                    if (res.success) roleSelect.value = res.data.role;
                });
            }

            document.getElementById('member-account-modal').style.display = 'flex';
        };

        // Form submissions...
        const addMemberForm = document.getElementById('add-member-form');
        if (addMemberForm) {
            addMemberForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'workedia_add_member_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => { if(res.success) location.reload(); else alert(res.data); });
            };
        }

        const accMemberForm = document.getElementById('member-account-form');
        if (accMemberForm) {
            accMemberForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'workedia_update_member_account_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => { if(res.success) { alert('تم التحديث بنجاح'); location.reload(); } else alert(res.data); });
            };
        }

        const editMemberForm = document.getElementById('edit-member-form');
        if (editMemberForm) {
            editMemberForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'workedia_update_member_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => { if(res.success) location.reload(); else alert(res.data); });
            };
        }
    })();
    </script>
</div>
