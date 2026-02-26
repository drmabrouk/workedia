<?php
if (!defined('ABSPATH')) exit;

$status_filter = $_GET['status'] ?? 'pending';
$requests = Workedia_DB::get_update_requests($status_filter);
$govs = Workedia_Settings::get_governorates();
?>

<div class="workedia-admin-dashboard" dir="rtl">
    <div class="workedia-header">
        <div class="workedia-header-title">
            <span class="dashicons dashicons-update"></span>
            <div>
                <h1>طلبات تحديث البيانات</h1>
                <p>مراجعة واعتماد طلبات التعديل المقدمة من الأعضاء</p>
            </div>
        </div>
    </div>

    <div class="workedia-filters-bar" style="margin-bottom: 20px;">
        <a href="?workedia_tab=update_requests&status=pending" class="workedia-btn <?php echo $status_filter === 'pending' ? '' : 'workedia-btn-outline'; ?>">قيد الانتظار</a>
        <a href="?workedia_tab=update_requests&status=approved" class="workedia-btn <?php echo $status_filter === 'approved' ? '' : 'workedia-btn-outline'; ?>" style="margin-right: 10px;">تم الاعتماد</a>
        <a href="?workedia_tab=update_requests&status=rejected" class="workedia-btn <?php echo $status_filter === 'rejected' ? '' : 'workedia-btn-outline'; ?>" style="margin-right: 10px;">مرفوضة</a>
    </div>

    <div class="workedia-card">
        <table class="workedia-table">
            <thead>
                <tr>
                    <th>تاريخ الطلب</th>
                    <th>العضو</th>
                    <th>البيانات المطلوبة</th>
                    <th>ملاحظات</th>
                    <?php if ($status_filter === 'pending'): ?>
                        <th>الإجراءات</th>
                    <?php else: ?>
                        <th>الحالة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">لا توجد طلبات حالياً</td></tr>
                <?php else: foreach ($requests as $req):
                    $data = json_decode($req->requested_data, true);
                    $member = Workedia_DB::get_member_by_id($req->member_id);
                ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($req->created_at)); ?></td>
                        <td>
                            <strong><?php echo esc_html($req->member_name); ?></strong><br>
                            <small style="color: #64748b;"><?php echo esc_html($req->national_id); ?></small>
                        </td>
                        <td style="font-size: 0.85em;">
                            <?php
                            foreach ($data as $k => $v) {
                                if ($k === 'notes') continue;
                                $old_val = $member->$k ?? '';
                                if ($old_val != $v) {
                                    $label = '';
                                    switch($k) {
                                        case 'name': $label = 'الاسم'; break;
                                        case 'national_id': $label = 'الرقم القومي'; break;
                                        case 'phone': $label = 'الهاتف'; break;
                                        case 'email': $label = 'البريد'; break;
                                        case 'governorate': $label = 'المحافظة'; $v = $govs[$v] ?? $v; $old_val = $govs[$old_val] ?? $old_val; break;
                                    }
                                    if ($label) echo "<div><strong>$label:</strong> <span style='color: #c53030; text-decoration: line-through;'>$old_val</span> &larr; <span style='color: #2f855a;'>$v</span></div>";
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($data['notes'] ?? ''); ?></td>
                        <td>
                            <?php if ($req->status === 'pending'): ?>
                                <button onclick="processRequest(<?php echo $req->id; ?>, 'approved')" class="workedia-btn workedia-btn-sm" style="background: #2f855a;">اعتماد</button>
                                <button onclick="processRequest(<?php echo $req->id; ?>, 'rejected')" class="workedia-btn workedia-btn-sm workedia-btn-outline" style="color: #c53030; border-color: #c53030;">رفض</button>
                            <?php else: ?>
                                <span class="workedia-status-badge <?php echo $req->status === 'approved' ? 'status-active' : 'status-expired'; ?>">
                                    <?php echo $req->status === 'approved' ? 'تم الاعتماد' : 'مرفوض'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function processRequest(id, status) {
    if (!confirm('هل أنت متأكد من ' + (status === 'approved' ? 'اعتماد' : 'رفض') + ' هذا الطلب؟')) return;

    const formData = new FormData();
    formData.append('action', 'workedia_process_update_request_ajax');
    formData.append('request_id', id);
    formData.append('status', status);
    formData.append('nonce', '<?php echo wp_create_nonce("workedia_update_request"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}
</script>
