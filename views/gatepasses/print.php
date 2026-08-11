<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($gatepass['gatepass_code']) ?> — MoveOps Gatepass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_BASE ?>/public/css/app.css">
    <style>
        body {
            background: #e5e7eb;
            font-family: var(--font-body);
            color: var(--clr-text);
            margin: 0;
            padding: 32px 16px;
        }
        .gp-toolbar {
            max-width: 720px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .gp-sheet {
            max-width: 720px;
            margin: 0 auto;
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: var(--radius-lg);
            padding: 40px;
        }
        .gp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--clr-secondary);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .gp-header-brand {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .gp-header-brand span { color: var(--clr-primary); }
        .gp-header-sub {
            font-size: 12px;
            color: var(--clr-text-3);
            margin-top: 2px;
        }
        .gp-header-code {
            text-align: right;
        }
        .gp-header-code .code {
            font-family: var(--font-mono);
            font-size: 18px;
            font-weight: 600;
        }
        .gp-header-code .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--clr-text-3);
        }
        .gp-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--clr-text-3);
            margin: 22px 0 10px;
        }
        .gp-section-title:first-of-type { margin-top: 0; }
        .gp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 24px;
        }
        .gp-field-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--clr-text-3);
            margin-bottom: 2px;
        }
        .gp-field-value {
            font-size: 14px;
            font-weight: 500;
        }
        .gp-signature-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 48px;
        }
        .gp-signature-line {
            border-top: 1px solid var(--clr-secondary);
            padding-top: 6px;
            font-size: 11px;
            color: var(--clr-text-3);
            text-align: center;
        }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 14mm; }
            body { background: #fff; padding: 0; }
            .gp-sheet { border: none; border-radius: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>

<div class="gp-toolbar no-print">
    <a href="<?= Helpers::url(Auth::dashboardUrl()) ?>" class="btn btn-outline">← Back</a>
    <button type="button" class="btn btn-solid" onclick="window.print();">Print</button>
</div>

<div class="gp-sheet">

    <div class="gp-header">
        <div>
            <div class="gp-header-brand">Move<span>Ops</span></div>
            <div class="gp-header-sub">Vehicle Gate Pass</div>
        </div>
        <div class="gp-header-code">
            <div class="code"><?= Helpers::e($gatepass['gatepass_code']) ?></div>
            <div class="label">Reservation <?= Helpers::e($gatepass['reservation_code']) ?></div>
        </div>
    </div>

    <div class="gp-section-title">Vehicle &amp; Driver</div>
    <div class="gp-grid">
        <div>
            <div class="gp-field-label">Vehicle</div>
            <div class="gp-field-value">
                <?= $gatepass['plate_number']
                    ? Helpers::e($gatepass['plate_number'] . ' — ' . $gatepass['vehicle_brand'] . ' ' . $gatepass['vehicle_model'])
                    : '—' ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Driver</div>
            <div class="gp-field-value">
                <?= $gatepass['driver_first_name']
                    ? Helpers::e($gatepass['driver_first_name'] . ' ' . $gatepass['driver_last_name'])
                    : '—' ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Driver License No.</div>
            <div class="gp-field-value">
                <?= $gatepass['driver_license_number'] ? Helpers::e($gatepass['driver_license_number']) : '—' ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Purpose</div>
            <div class="gp-field-value"><?= Helpers::e($gatepass['purpose_name']) ?></div>
        </div>
    </div>

    <div class="gp-section-title">Trip</div>
    <div class="gp-grid">
        <div style="grid-column: 1 / -1;">
            <div class="gp-field-label">Destination</div>
            <div class="gp-field-value"><?= Helpers::e($gatepass['destination']) ?></div>
        </div>
        <div>
            <div class="gp-field-label">Departure</div>
            <div class="gp-field-value">
                <?= date('D, M d Y — g:i A', strtotime($gatepass['departure_datetime'])) ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Expected Return</div>
            <div class="gp-field-value">
                <?= date('D, M d Y — g:i A', strtotime($gatepass['return_datetime'])) ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Passengers</div>
            <div class="gp-field-value"><?= (int) $gatepass['passenger_count'] ?></div>
        </div>
        <div>
            <div class="gp-field-label">Cargo Weight</div>
            <div class="gp-field-value">
                <?= number_format((float) $gatepass['cargo_weight_kg'], 2) ?> kg
                <?= $gatepass['cargo_description'] ? ' — ' . Helpers::e($gatepass['cargo_description']) : '' ?>
            </div>
        </div>
    </div>

    <div class="gp-section-title">Approval</div>
    <div class="gp-grid">
        <div>
            <div class="gp-field-label">Approved By</div>
            <div class="gp-field-value">
                <?= $gatepass['reviewer_first_name']
                    ? Helpers::e($gatepass['reviewer_first_name'] . ' ' . $gatepass['reviewer_last_name'])
                    : '—' ?>
            </div>
        </div>
        <div>
            <div class="gp-field-label">Approved At</div>
            <div class="gp-field-value">
                <?= $gatepass['reviewed_at']
                    ? date('D, M d Y — g:i A', strtotime($gatepass['reviewed_at']))
                    : '—' ?>
            </div>
        </div>
    </div>

    <div class="gp-signature-row">
        <div class="gp-signature-line">Security Personnel — Signature over Printed Name</div>
        <div class="gp-signature-line">Date &amp; Time Checked</div>
    </div>

</div>

<script>
window.addEventListener('load', function () { window.print(); });
</script>

</body>
</html>
