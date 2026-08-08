<?php
$aiRecommendedId = (int) ($reservation['ai_recommended_vehicle_id'] ?? 0);

// Separate scored vs disqualified for display
$scored       = array_filter($aiLogs, fn($l) => !(int) $l['disqualified']);
$disqualified = array_filter($aiLogs, fn($l)  => (int)  $l['disqualified']);

function scoreBar(float $score): string {
    $pct   = (int) round($score * 100);
    $color = $score >= 0.7 ? '#27ae60' : ($score >= 0.4 ? '#e67e22' : '#e74c3c');
    return "<div style='display:flex;align-items:center;gap:6px;'>"
         . "<div style='flex:1;height:6px;background:#eee;border-radius:3px;overflow:hidden;'>"
         . "<div style='width:{$pct}%;height:100%;background:{$color};'></div></div>"
         . "<span style='font-size:12px;color:var(--clr-text-3);min-width:30px;'>{$score}</span>"
         . "</div>";
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Review <?= Helpers::e($reservation['reservation_code']) ?></h2>
        <p>
            <?= Helpers::e($reservation['requester_first_name'] . ' ' . $reservation['requester_last_name']) ?>
            — <?= Helpers::e($reservation['destination']) ?>
            — <?= date('M d, Y g:i A', strtotime($reservation['departure_datetime'])) ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id']) ?>"
       class="btn btn-outline">← Detail</a>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start;">

<!-- ── Left: AI Recommendation Panel ───────────────────────── -->
<div>
    <div class="detail-card" style="margin-bottom:20px;">
        <div class="detail-card-title">Request Summary</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
            <div class="detail-field">
                <div class="detail-field-label">Purpose</div>
                <div class="detail-field-value"><?= Helpers::e($reservation['purpose_name']) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Passengers</div>
                <div class="detail-field-value"><?= (int) $reservation['passenger_count'] ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Cargo</div>
                <div class="detail-field-value"><?= number_format((float) $reservation['cargo_weight_kg'], 0) ?> kg</div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Department</div>
                <div class="detail-field-value"><?= Helpers::e($reservation['company_code'] . ' — ' . $reservation['department_name']) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($scored)): ?>
    <div class="detail-card" style="margin-bottom:20px;">
        <div class="detail-card-title">Vehicle Scores</div>
        <div class="table-wrap">
        <table class="data-table" style="font-size:13px;">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Capacity</th>
                    <th>Cargo</th>
                    <th>Avail.</th>
                    <th>Purpose</th>
                    <th>Maint.</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($scored as $log):
                $isTop = (int) $log['vehicle_id'] === $aiRecommendedId;
            ?>
            <tr style="<?= $isTop ? 'background:var(--clr-accent);' : '' ?>">
                <td>
                    <?php if ($isTop): ?>
                    <span style="font-size:10px;background:var(--clr-primary);color:#fff;
                                 padding:1px 5px;border-radius:3px;margin-right:4px;">
                        TOP
                    </span>
                    <?php endif; ?>
                    <strong><?= Helpers::e($log['plate_number']) ?></strong><br>
                    <span class="td-muted"><?= Helpers::e($log['brand'] . ' ' . $log['model']) ?></span>
                </td>
                <td><?= scoreBar((float) $log['capacity_score']) ?></td>
                <td><?= scoreBar((float) $log['cargo_score']) ?></td>
                <td><?= scoreBar((float) $log['availability_score']) ?></td>
                <td><?= scoreBar((float) $log['purpose_fit_score']) ?></td>
                <td><?= scoreBar((float) $log['maintenance_score']) ?></td>
                <td><strong><?= $log['score'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php else: ?>
    <div class="detail-card" style="margin-bottom:20px;border-color:#f5a09a;">
        <div class="detail-card-title" style="color:#c0392b;">No Eligible Vehicles</div>
        <p style="font-size:14px;color:var(--clr-text-2);margin:0;">
            All available vehicles were disqualified. See the disqualification table below,
            or override manually by selecting a vehicle in the approval form.
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($disqualified)): ?>
    <div class="detail-card">
        <div class="detail-card-title">Disqualified Vehicles</div>
        <div class="table-wrap">
        <table class="data-table" style="font-size:13px;">
            <thead>
                <tr><th>Vehicle</th><th>Reason</th></tr>
            </thead>
            <tbody>
            <?php foreach ($disqualified as $log): ?>
            <tr>
                <td>
                    <strong><?= Helpers::e($log['plate_number']) ?></strong><br>
                    <span class="td-muted"><?= Helpers::e($log['brand'] . ' ' . $log['model']) ?></span>
                </td>
                <td class="td-muted"><?= Helpers::e($log['disqualify_reason']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Right: Decision Form ─────────────────────────────────── -->
<div>
    <!-- Tab toggle -->
    <div style="display:flex;gap:8px;margin-bottom:16px;">
        <button type="button" id="btnApprove" class="btn btn-solid"
                onclick="showSection('approve')">Approve</button>
        <button type="button" id="btnReject"  class="btn btn-outline"
                onclick="showSection('reject')">Reject</button>
    </div>

    <!-- Approve form -->
    <div id="sectionApprove" class="form-card">
        <div class="form-section-title">Assign Vehicle &amp; Driver</div>
        <form method="POST" action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/approve') ?>">
            <div class="form-group">
                <label class="form-label">Vehicle <span class="required">*</span></label>
                <select class="form-select" name="assigned_vehicle_id" required>
                    <option value="">— Select Vehicle —</option>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?= (int) $v['vehicle_id'] ?>"
                        <?= (int) $v['vehicle_id'] === $aiRecommendedId ? 'selected' : '' ?>>
                        <?= Helpers::e($v['plate_number'] . ' — ' . $v['brand'] . ' ' . $v['model']
                            . ' (' . $v['passenger_capacity'] . ' pax)') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($aiRecommendedId): ?>
                <p class="form-hint">
                    AI recommended: <?php
                    foreach ($scored as $log) {
                        if ((int) $log['vehicle_id'] === $aiRecommendedId) {
                            echo Helpers::e($log['plate_number'] . ' — score ' . $log['score']);
                            break;
                        }
                    }
                    ?> (pre-selected, override allowed)
                </p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Driver <span class="required">*</span></label>
                <select class="form-select" name="assigned_driver_id" required>
                    <option value="">— Select Driver —</option>
                    <?php if (empty($drivers)): ?>
                    <option disabled>No available drivers</option>
                    <?php else: ?>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int) $d['user_id'] ?>">
                        <?= Helpers::e($d['first_name'] . ' ' . $d['last_name'])
                            . ($d['employee_id'] ? ' (' . $d['employee_id'] . ')' : '') ?>
                    </option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-solid">Approve Reservation</button>
            </div>
        </form>
    </div>

    <!-- Reject form (hidden by default) -->
    <div id="sectionReject" class="form-card" style="display:none;">
        <div class="form-section-title">Rejection Reason</div>
        <form method="POST" action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/reject') ?>">
            <div class="form-group">
                <label class="form-label">Reason <span class="required">*</span></label>
                <textarea class="form-textarea" name="rejection_reason" rows="4" required
                          placeholder="Explain why this reservation is being rejected..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Reject Reservation</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
function showSection(which) {
    document.getElementById('sectionApprove').style.display = which === 'approve' ? '' : 'none';
    document.getElementById('sectionReject').style.display  = which === 'reject'  ? '' : 'none';
    document.getElementById('btnApprove').className = which === 'approve' ? 'btn btn-solid' : 'btn btn-outline';
    document.getElementById('btnReject').className  = which === 'reject'  ? 'btn btn-solid' : 'btn btn-outline';
}
</script>
