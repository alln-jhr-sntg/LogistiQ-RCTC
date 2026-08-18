<?php
$aiRecommendedId = (int) ($reservation['ai_recommended_vehicle_id'] ?? 0);

// Separate scored vs disqualified for display
$scored       = array_filter($aiLogs, fn($l) => !(int) $l['disqualified']);
$disqualified = array_filter($aiLogs, fn($l)  => (int)  $l['disqualified']);

// Lookup for the vehicle <select> — score + disqualified flag per vehicle,
// so the dropdown can be ordered best-first and show the score inline
// instead of making the admin cross-reference the table above it.
$scoreByVehicle = [];
foreach ($aiLogs as $log) {
    $scoreByVehicle[(int) $log['vehicle_id']] = [
        'score'        => (int) $log['score'],
        'disqualified' => (bool) $log['disqualified'],
    ];
}
$vehicleOptions = $vehicles;
usort($vehicleOptions, function ($a, $b) use ($scoreByVehicle) {
    $sa = $scoreByVehicle[(int) $a['vehicle_id']]['score'] ?? -1;
    $sb = $scoreByVehicle[(int) $b['vehicle_id']]['score'] ?? -1;
    return $sb <=> $sa;
});

// Renders one sub-score cell: a mini bar for an int 0-100, an em dash for
// null (criterion not applicable to this vehicle/request pair).
// --score-pct is the one deliberate inline style left on this page — the
// fill width is a genuinely per-row computed value with no fixed set of
// CSS classes that could express it without quantizing the bar.
function scoreBar(?int $score): string
{
    if ($score === null) {
        return '<span class="score-bar--na">—</span>';
    }
    $tier = $score >= 70 ? 'good' : ($score >= 40 ? 'mid' : 'poor');
    return '<div class="score-bar">'
         . '<div class="score-bar-track"><div class="score-bar-fill score-bar-fill--' . $tier . '" style="--score-pct:' . $score . '"></div></div>'
         . '<span class="score-bar-value">' . $score . '</span>'
         . '</div>';
}
?>
<div class="page-header">
    <div class="page-header-left">
        <p>
            <?= Helpers::e($reservation['requester_first_name'] . ' ' . $reservation['requester_last_name']) ?>
            — <?= Helpers::e($reservation['destination']) ?>
            — <?= date('M d, Y g:i A', strtotime($reservation['departure_datetime'])) ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id']) ?>"
       class="btn btn-outline">← Detail</a>
</div>

<div class="review-grid">

<!-- ── Left: Recommendation Panel ───────────────────────── -->
<div class="detail-card-stack">
    <div class="detail-card">
        <div class="detail-card-title">Request Summary</div>
        <div class="detail-field-grid">
            <div class="detail-field">
                <div class="detail-field-label">Destination</div>
                <div class="detail-field-value"><?= Helpers::e($reservation['destination']) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Purpose</div>
                <div class="detail-field-value"><?= Helpers::e($reservation['purpose_name']) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Departure</div>
                <div class="detail-field-value"><?= date('M d, Y g:i A', strtotime($reservation['departure_datetime'])) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Return</div>
                <div class="detail-field-value"><?= date('M d, Y g:i A', strtotime($reservation['return_datetime'])) ?></div>
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
    <div class="detail-card">
        <div class="detail-card-title">Vehicle Scores</div>

        <?php
            $top = null;
            foreach ($scored as $log) {
                if ((int) $log['vehicle_id'] === $aiRecommendedId) {
                    $top = $log;
                    break;
                }
            }
        ?>
        <?php if ($top): ?>
        <div class="ai-score-grid">
            <div class="ai-score-row">
                <span class="ai-score-label">Top pick</span>
                <span><strong><?= Helpers::e($top['plate_number']) ?></strong> — <?= Helpers::e($top['brand'] . ' ' . $top['model']) ?></span>
            </div>
            <div class="ai-score-row">
                <span class="ai-score-label">Score</span>
                <span><strong><?= (int) $top['score'] ?></strong> / 100</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-wrap">
        <table class="data-table data-table--compact">
            <thead>
                <tr>
                    <th>Score</th>
                    <th>Vehicle</th>
                    <th>Capacity</th>
                    <th>Cargo</th>
                    <th>Schedule</th>
                    <th>Purpose</th>
                    <th>Maint.</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($scored as $log):
                $isTop = (int) $log['vehicle_id'] === $aiRecommendedId;
            ?>
            <tr class="<?= $isTop ? 'score-row--top' : '' ?>">
                <td><strong><?= (int) $log['score'] ?></strong></td>
                <td>
                    <?php if ($isTop): ?>
                    <span class="badge badge-top">TOP</span>
                    <?php endif; ?>
                    <strong><?= Helpers::e($log['plate_number']) ?></strong><br>
                    <span class="td-muted"><?= Helpers::e($log['brand'] . ' ' . $log['model']) ?></span>
                </td>
                <td><?= scoreBar($log['capacity_score']      !== null ? (int) $log['capacity_score']      : null) ?></td>
                <td><?= scoreBar($log['cargo_score']         !== null ? (int) $log['cargo_score']         : null) ?></td>
                <td><?= scoreBar($log['schedule_score']      !== null ? (int) $log['schedule_score']      : null) ?></td>
                <td><?= scoreBar($log['purpose_fit_score']   !== null ? (int) $log['purpose_fit_score']   : null) ?></td>
                <td><?= scoreBar($log['maintenance_score']   !== null ? (int) $log['maintenance_score']   : null) ?></td>
                <td><?= scoreBar($log['weight_coding_score'] !== null ? (int) $log['weight_coding_score'] : null) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php else: ?>
    <div class="detail-card detail-card-danger">
        <div class="detail-card-title text-danger">No Eligible Vehicles</div>
        <p class="detail-card-text">
            All available vehicles were disqualified. See the disqualification list below,
            or override manually by selecting a vehicle in the approval form.
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($disqualified)): ?>
    <details class="detail-card">
        <summary class="detail-card-title">Disqualified Vehicles (<?= count($disqualified) ?>)</summary>
        <div class="table-wrap">
        <table class="data-table data-table--compact">
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
    </details>
    <?php endif; ?>
</div>

<!-- ── Right: Decision Form ─────────────────────────────────── -->
<div class="review-sticky">
    <div class="form-card">
        <div class="tab-toggle">
            <button type="button" id="btnApprove" class="btn btn-solid"
                    onclick="showSection('approve')">Approve</button>
            <button type="button" id="btnReject"  class="btn btn-outline"
                    onclick="showSection('reject')">Reject</button>
        </div>

        <!-- Approve form -->
        <div id="sectionApprove">
            <form method="POST" action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/approve') ?>">
                <?= Csrf::field() ?>
                <div class="form-group">
                    <label class="form-label">Vehicle <span class="required">*</span></label>
                    <select class="form-select" name="assigned_vehicle_id" required>
                        <option value="">— Select Vehicle —</option>
                        <?php foreach ($vehicleOptions as $v):
                            $vid        = (int) $v['vehicle_id'];
                            $info       = $scoreByVehicle[$vid] ?? null;
                            $scoreLabel = ($info && !$info['disqualified']) ? ' — score ' . $info['score'] : '';
                        ?>
                        <option value="<?= $vid ?>"
                            <?= $vid === $aiRecommendedId ? 'selected' : '' ?>>
                            <?= Helpers::e($v['plate_number'] . ' — ' . $v['brand'] . ' ' . $v['model']
                                . ' (' . $v['passenger_capacity'] . ' pax)' . $scoreLabel) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($aiRecommendedId): ?>
                    <p class="form-hint">
                        Recommended: <?php
                        foreach ($scored as $log) {
                            if ((int) $log['vehicle_id'] === $aiRecommendedId) {
                                echo Helpers::e($log['plate_number'] . ' — score ' . (int) $log['score']);
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
        <div id="sectionReject" class="hidden">
            <form method="POST" action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/reject') ?>">
                <?= Csrf::field() ?>
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

</div>

<script>
function showSection(which) {
    document.getElementById('sectionApprove').classList.toggle('hidden', which !== 'approve');
    document.getElementById('sectionReject').classList.toggle('hidden', which !== 'reject');
    document.getElementById('btnApprove').classList.toggle('btn-solid', which === 'approve');
    document.getElementById('btnApprove').classList.toggle('btn-outline', which !== 'approve');
    document.getElementById('btnReject').classList.toggle('btn-solid', which === 'reject');
    document.getElementById('btnReject').classList.toggle('btn-outline', which !== 'reject');
}
</script>
