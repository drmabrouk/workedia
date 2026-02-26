<?php if (!defined('ABSPATH')) exit;

$member_id = intval($_GET['member_id'] ?? 0);
$member = Workedia_DB::get_member_by_id($member_id);

if (!$member) {
    echo '<div class="error"><p>العضو غير موجود.</p></div>';
    return;
}

$user = wp_get_current_user();
$is_sys_manager = in_array('administrator', (array)$user->roles);
$is_administrator = in_array('administrator', (array)$user->roles);
$is_subscriber = in_array('subscriber', (array)$user->roles);

// IDOR CHECK: Restricted users can only see their own profile
if ($is_subscriber && !current_user_can('manage_options')) {
    if ($member->wp_user_id != $user->ID) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>⚠️ عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>لا يمكنك استعراض بيانات الأعضاء الآخرين.</p></div>';
        return;
    }
}

// GEOGRAPHIC ACCESS CHECK
if ($is_administrator) {
    $my_gov = get_user_meta($user->ID, 'workedia_governorate', true);
    if ($my_gov && $member->governorate !== $my_gov) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>⚠️ عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>هذا العضو يتبع لمحافظة أخرى غير المسجلة في حسابك.</p></div>';
        return;
    }
}

$govs = Workedia_Settings::get_governorates();
$statuses = Workedia_Settings::get_membership_statuses();
?>

<div class="workedia-member-profile-view" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--workedia-border-color); box-shadow: var(--workedia-shadow);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="position: relative;">
                <div id="member-photo-container" style="width: 80px; height: 80px; background: #f0f4f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; border: 3px solid var(--workedia-primary-color); overflow: hidden;">
                    <?php if ($member->photo_url): ?>
                        <img src="<?php echo esc_url($member->photo_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <button onclick="workediaTriggerPhotoUpload()" style="position: absolute; bottom: 0; right: 0; background: var(--workedia-primary-color); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <span class="dashicons dashicons-camera" style="font-size: 14px; width: 14px; height: 14px;"></span>
                </button>
                <input type="file" id="member-photo-input" style="display:none;" accept="image/*" onchange="workediaUploadMemberPhoto(<?php echo $member->id; ?>)">
            </div>
            <div>
                <h2 style="margin:0; color: var(--workedia-dark-color);"><?php echo esc_html($member->name); ?></h2>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <span class="workedia-badge" style="background: #e2e8f0; color: #4a5568;"><?php echo $govs[$member->governorate] ?? $member->governorate; ?></span>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if ($is_subscriber): ?>
                <button onclick="workediaOpenUpdateMemberRequestModal()" class="workedia-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> طلب تحديث بياناتي</button>
            <?php elseif (!$is_member): ?>
                <button onclick="workediaEditMember(JSON.parse(this.dataset.member))" data-member='<?php echo esc_attr(wp_json_encode($member)); ?>' class="workedia-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> تعديل البيانات</button>
            <?php endif; ?>

            <?php if (!$is_subscriber || current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('admin-ajax.php?action=workedia_print&print_type=id_card&member_id='.$member->id); ?>" target="_blank" class="workedia-btn" style="background: #27ae60; width: auto; text-decoration:none; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-id-alt"></span> طباعة الكارنيه</a>
            <?php endif; ?>
            <?php if ($is_sys_manager): ?>
                <button onclick="deleteMember(<?php echo $member->id; ?>, '<?php echo esc_js($member->name); ?>')" class="workedia-btn" style="background: #e53e3e; width: auto;"><span class="dashicons dashicons-trash"></span> حذف العضو</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="workedia-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="workedia-tab-btn workedia-active" onclick="workediaOpenInternalTab('profile-info', this)"><span class="dashicons dashicons-admin-users"></span> بيانات العضوية</button>
        <button class="workedia-tab-btn" onclick="workediaOpenInternalTab('member-chat', this); setTimeout(() => selectConversation(<?php echo $member->id; ?>, '<?php echo esc_js($member->name); ?>', <?php echo $member->wp_user_id ?: 0; ?>), 100);"><span class="dashicons dashicons-email"></span> المراسلات والشكاوى</button>
    </div>

    <div id="profile-info" class="workedia-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Basic Info -->
                <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--workedia-border-color); box-shadow: var(--workedia-shadow);">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">البيانات الأساسية</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="workedia-label">الرقم القومي:</label> <div class="workedia-value"><?php echo esc_html($member->national_id); ?></div></div>
                    <div><label class="workedia-label">كود العضوية:</label> <div class="workedia-value"><?php echo esc_html($member->membership_number); ?></div></div>
                    <div><label class="workedia-label">رقم الهاتف:</label> <div class="workedia-value"><?php echo esc_html($member->phone); ?></div></div>
                    <div><label class="workedia-label">البريد الإلكتروني:</label> <div class="workedia-value"><?php echo esc_html($member->email); ?></div></div>
                </div>


                <h4 style="margin: 20px 0 10px 0; color: var(--workedia-primary-color);">بيانات السكن والاتصال</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="workedia-label">محافظة السكن:</label> <div class="workedia-value"><?php echo esc_html($govs[$member->residence_governorate] ?? $member->residence_governorate); ?></div></div>
                    <div><label class="workedia-label">المدينة / المركز:</label> <div class="workedia-value"><?php echo esc_html($member->residence_city); ?></div></div>
                    <div style="grid-column: span 2;"><label class="workedia-label">العنوان (الشارع / القرية):</label> <div class="workedia-value"><?php echo esc_html($member->residence_street); ?></div></div>
                    <div><label class="workedia-label">محافظة الفرع (Workedia):</label> <div class="workedia-value"><?php echo esc_html($govs[$member->governorate] ?? $member->governorate); ?></div></div>
                    <?php if ($member->wp_user_id): ?>
                        <?php $temp_pass = get_user_meta($member->wp_user_id, 'workedia_temp_pass', true); if ($temp_pass): ?>
                            <div style="grid-column: span 2; background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 10px;">
                                <label class="workedia-label" style="color: #744210;">كلمة المرور المؤقتة للنظام:</label>
                                <div style="font-family: monospace; font-size: 1.2em; font-weight: 700; color: #975a16;"><?php echo esc_html($temp_pass); ?></div>
                                <small style="color: #975a16;">* يرجى تزويد العضو بهذه الكلمة ليتمكن من الدخول لأول مرة.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Communication Tab -->
    <div id="member-chat" class="workedia-internal-tab" style="display: none;">
        <div style="height: 600px; border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff;">
            <?php
            // Reuse messaging-center but in a compact way
            include WORKEDIA_PLUGIN_DIR . 'templates/messaging-center.php';
            ?>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div id="edit-member-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 900px;">
            <div class="workedia-modal-header"><h3>تعديل بيانات العضو</h3><button class="workedia-modal-close" onclick="document.getElementById('edit-member-modal').style.display='none'">&times;</button></div>
            <form id="edit-member-form">
                <?php wp_nonce_field('workedia_add_member', 'workedia_nonce'); ?>
                <input type="hidden" name="member_id" id="edit_member_id_hidden">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding:20px;">
                    <div class="workedia-form-group"><label class="workedia-label">الاسم الكامل:</label><input name="name" id="edit_name" type="text" class="workedia-input" required></div>
                    <div class="workedia-form-group"><label class="workedia-label">الرقم القومي:</label><input name="national_id" id="edit_national_id" type="text" class="workedia-input" required maxlength="14"></div>

                    <div class="workedia-form-group"><label class="workedia-label">محافظة السكن:</label><select name="residence_governorate" id="edit_res_gov" class="workedia-select"><?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المدينة / المركز:</label><input name="residence_city" id="edit_res_city" type="text" class="workedia-input"></div>
                    <div class="workedia-form-group"><label class="workedia-label">محافظة الفرع:</label><select name="governorate" id="edit_gov" class="workedia-select"><?php foreach (Workedia_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>

                    <div class="workedia-form-group" style="grid-column: span 3;"><label class="workedia-label">العنوان (الشارع / القرية):</label><input name="residence_street" id="edit_res_street" type="text" class="workedia-input"></div>

                    <div class="workedia-form-group"><label class="workedia-label">رقم الهاتف:</label><input name="phone" id="edit_phone" type="text" class="workedia-input"></div>
                    <div class="workedia-form-group"><label class="workedia-label">البريد الإلكتروني:</label><input name="email" id="edit_email" type="email" class="workedia-input"></div>
                    <div class="workedia-form-group" style="grid-column: span 3;"><label class="workedia-label">ملاحظات:</label><textarea name="notes" id="edit_notes" class="workedia-input" rows="2"></textarea></div>
                </div>
                <button type="submit" class="workedia-btn">تحديث البيانات الآن</button>
            </form>
        </div>
    </div>

    <!-- Member Update Request Modal -->
    <div id="member-update-request-modal" class="workedia-modal-overlay">
        <div class="workedia-modal-content" style="max-width: 800px;">
            <div class="workedia-modal-header">
                <h3>طلب تحديث بيانات العضوية</h3>
                <button class="workedia-modal-close" onclick="document.getElementById('member-update-request-modal').style.display='none'">&times;</button>
            </div>
            <div style="padding: 20px; background: #fffaf0; border-bottom: 1px solid #feebc8; font-size: 13px; color: #744210;">
                <span class="dashicons dashicons-info" style="font-size: 16px;"></span> سيتم إرسال طلبك للمراجعة من قبل Workedia قبل اعتماده رسمياً في النظام.
            </div>
            <form id="member-update-request-form">
                <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px;">
                    <div class="workedia-form-group"><label class="workedia-label">الاسم الكامل:</label><input type="text" name="name" class="workedia-input" value="<?php echo esc_attr($member->name); ?>" required></div>
                    <div class="workedia-form-group"><label class="workedia-label">الرقم القومي:</label><input type="text" name="national_id" class="workedia-input" value="<?php echo esc_attr($member->national_id); ?>" required maxlength="14"></div>

                    <div class="workedia-form-group"><label class="workedia-label">محافظة السكن:</label><select name="residence_governorate" class="workedia-select"><?php foreach ($govs as $k => $v) echo "<option value='$k' ".selected($member->residence_governorate, $k, false).">$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">المدينة / المركز:</label><input name="residence_city" type="text" class="workedia-input" value="<?php echo esc_attr($member->residence_city); ?>"></div>
                    <div class="workedia-form-group" style="grid-column: span 2;"><label class="workedia-label">العنوان (الشارع / القرية):</label><input name="residence_street" type="text" class="workedia-input" value="<?php echo esc_attr($member->residence_street); ?>"></div>

                    <div class="workedia-form-group"><label class="workedia-label">محافظة الفرع:</label><select name="governorate" class="workedia-select"><?php foreach ($govs as $k => $v) echo "<option value='$k' ".selected($member->governorate, $k, false).">$v</option>"; ?></select></div>
                    <div class="workedia-form-group"><label class="workedia-label">رقم الهاتف:</label><input type="text" name="phone" class="workedia-input" value="<?php echo esc_attr($member->phone); ?>"></div>
                    <div class="workedia-form-group"><label class="workedia-label">البريد الإلكتروني:</label><input type="email" name="email" class="workedia-input" value="<?php echo esc_attr($member->email); ?>"></div>
                    <div class="workedia-form-group" style="grid-column: span 2;"><label class="workedia-label">سبب التحديث / ملاحظات إضافية:</label><textarea name="notes" class="workedia-input" rows="2"></textarea></div>
                </div>
                <div style="padding: 0 25px 25px;">
                    <button type="submit" class="workedia-btn" style="width: 100%; height: 45px; font-weight: 700;">إرسال طلب التحديث للمراجعة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function workediaTriggerPhotoUpload() {
    document.getElementById('member-photo-input').click();
}

function workediaUploadMemberPhoto(memberId) {
    const file = document.getElementById('member-photo-input').files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'workedia_update_member_photo');
    formData.append('member_id', memberId);
    formData.append('member_photo', file);
    formData.append('workedia_photo_nonce', '<?php echo wp_create_nonce("workedia_photo_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('member-photo-container').innerHTML = `<img src="${res.data.photo_url}" style="width:100%; height:100%; object-fit:cover;">`;
            workediaShowNotification('تم تحديث الصورة الشخصية');
        } else {
            alert('فشل الرفع: ' + res.data);
        }
    });
}

function workediaOpenUpdateMemberRequestModal() {
    document.getElementById('member-update-request-modal').style.display = 'flex';
}

document.getElementById('member-update-request-form').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'workedia_submit_update_request_ajax');
    formData.append('nonce', '<?php echo wp_create_nonce("workedia_update_request"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            workediaShowNotification('تم إرسال طلب التحديث بنجاح. سنقوم بمراجعته قريباً.');
            document.getElementById('member-update-request-modal').style.display = 'none';
        } else {
            alert('خطأ: ' + res.data);
        }
    });
};

function deleteMember(id, name) {
    if (!confirm('هل أنت متأكد من حذف العضو: ' + name + ' نهائياً من النظام؟ لا يمكن التراجع عن هذا الإجراء.')) return;
    const formData = new FormData();
    formData.append('action', 'workedia_delete_member_ajax');
    formData.append('member_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("workedia_delete_member"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = '<?php echo add_query_arg('workedia_tab', 'users-management'); ?>';
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

window.workediaEditMember = function(s) {
    document.getElementById('edit_member_id_hidden').value = s.id;
    document.getElementById('edit_name').value = s.name;
    document.getElementById('edit_national_id').value = s.national_id;
    document.getElementById('edit_res_gov').value = s.residence_governorate || '';
    document.getElementById('edit_res_city').value = s.residence_city || '';
    document.getElementById('edit_res_street').value = s.residence_street || '';
    document.getElementById('edit_gov').value = s.governorate;
    document.getElementById('edit_phone').value = s.phone;
    document.getElementById('edit_email').value = s.email;
    document.getElementById('edit_notes').value = s.notes || '';
    document.getElementById('edit-member-modal').style.display = 'flex';
};

document.getElementById('edit-member-form').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'workedia_update_member_ajax');
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json()).then(res => {
        if(res.success) {
            workediaShowNotification('تم تحديث البيانات بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            alert(res.data);
        }
    });
};
</script>
