<div class="page-header">
    <div class="page-header-left">
        <h2>Projects</h2>
        <p>Active construction and development projects</p>
    </div>
    <a href="<?= Helpers::url('/projects/create') ?>" class="btn btn-solid">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> New Project
    </a>
</div>

<form method="GET" action="/lvms/index.php">
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
    </div>
</form>

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
                No projects yet.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($projects as $p): ?>
        <tr>
            <td>
                <strong><?= Helpers::e($p['project_name']) ?></strong>
                <?php if ($p['project_code']): ?>
                <br><span class="td-muted"><?= Helpers::e($p['project_code']) ?></span>
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
                <?php if ((int) $p['is_active'] === 1): ?>
                    <span class="badge badge-available">Active</span>
                <?php else: ?>
                    <span class="badge badge-cancelled">Inactive</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/projects/' . $p['project_id'] . '/edit') ?>"
                       class="btn btn-outline btn-sm">Edit</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
