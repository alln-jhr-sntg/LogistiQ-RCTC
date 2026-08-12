<?php
// 'completed' uses badge-cancelled because it is the only grey badge in
// app.css — badge-completed exists but renders green, which would read as
// "live" next to the green active badge.
$statusBadge = [
    PROJ_PENDING   => ['class' => 'badge-pending',   'label' => 'Pending'],
    PROJ_ACTIVE    => ['class' => 'badge-available', 'label' => 'Active'],
    PROJ_COMPLETED => ['class' => 'badge-cancelled', 'label' => 'Completed'],
    PROJ_REJECTED  => ['class' => 'badge-rejected',  'label' => 'Rejected'],
];
$statusTabs = [
    ''             => 'All Statuses',
    PROJ_PENDING   => 'Pending',
    PROJ_ACTIVE    => 'Active',
    PROJ_COMPLETED => 'Completed',
    PROJ_REJECTED  => 'Rejected',
];
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Projects</h2>
        <p><?= $isEmployee
            ? 'Projects at your company, plus your own requests'
            : 'Active construction and development projects' ?></p>
    </div>
    <?php if ($isEmployee): ?>
    <a href="<?= Helpers::url('/projects/request') ?>" class="btn btn-solid">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Request Project
    </a>
    <?php else: ?>
    <a href="<?= Helpers::url('/projects/create') ?>" class="btn btn-solid">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> New Project
    </a>
    <?php endif; ?>
</div>

<?php if (!$isEmployee): ?>
<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="projects">
    <div class="filter-bar">
        <select class="filter-select" name="company_id" onchange="this.form.submit()">
            <option value="0" <?= $companyFilter === 0 ? 'selected' : '' ?>>All Companies</option>
            <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>"
                <?= $companyFilter === (int) $co['company_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($co['company_code']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <?php foreach ($statusTabs as $key => $label): ?>
            <option value="<?= Helpers::e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                <?= Helpers::e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<?php endif; ?>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Project</th>
            <th>Company</th>
            <th>Location</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($projects)): ?>
        <tr>
            <td colspan="6" class="td-muted" style="text-align:center;padding:24px;">
                No projects<?= $statusFilter !== '' ? ' with status "' . Helpers::e($statusFilter) . '"' : '' ?>.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($projects as $p): ?>
        <?php
        $badge     = $statusBadge[$p['status']] ?? ['class' => 'badge-pending', 'label' => $p['status']];
        // Review is offered only on a row awaiting a decision, and only to
        // an actor who may act on that company. ProjectController::review(),
        // approve() and reject() re-check this server-side.
        $canReview = $p['status'] === PROJ_PENDING
            && in_array((int) $p['company_id'], $reviewableCompanies, true);
        ?>
        <tr>
            <td>
                <strong><?= Helpers::e($p['project_name']) ?></strong>
                <?php if ($p['project_code']): ?>
                <br><span class="td-muted"><?= Helpers::e($p['project_code']) ?></span>
                <?php endif; ?>
                <?php if ($p['status'] === PROJ_REJECTED && $p['rejection_reason']): ?>
                <br><span class="td-muted">Reason: <?= Helpers::e($p['rejection_reason']) ?></span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= Helpers::e($p['company_code']) ?></td>
            <td class="td-muted"><?= $p['location'] ? Helpers::e($p['location']) : '—' ?></td>
            <td class="td-muted">
                <?php
                $start = $p['start_date'] ? date('M Y', strtotime($p['start_date'])) : null;
                $end   = $p['end_date']   ? date('M Y', strtotime($p['end_date']))   : null;
                if ($start && $end)      echo $start . ' — ' . $end;
                elseif ($start)          echo 'From ' . $start;
                elseif ($end)            echo 'Until ' . $end;
                else                     echo '—';
                ?>
            </td>
            <td>
                <span class="badge <?= $badge['class'] ?>"><?= Helpers::e($badge['label']) ?></span>
            </td>
            <td>
                <div class="td-actions">
                    <?php if ($canReview): ?>
                    <a href="<?= Helpers::url('/projects/' . $p['project_id'] . '/review') ?>"
                       class="btn btn-solid btn-sm">Review</a>
                    <?php endif; ?>
                    <?php if ($isEmployee): ?>
                    <span class="td-muted">—</span>
                    <?php else: ?>
                    <a href="<?= Helpers::url('/projects/' . $p['project_id'] . '/edit') ?>"
                       class="btn btn-outline btn-sm">Edit</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
