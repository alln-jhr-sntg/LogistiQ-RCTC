<?php
// Standalone error page — deliberately independent of views/layouts/main.php.
// A controller's render() does `require_once` on main.php, so re-including it
// here after a mid-render failure would either silently render nothing (the
// require_once already happened) or fatal on redeclaring nav_is_active() (a
// plain require). main.php also runs session/DB-backed sidebar queries this
// page must not depend on. No Auth::/Helpers:: calls, no session reads —
// everything below must survive a bootstrap that died before those were
// available.
//
// Expects: $code (int), $title (string), $message (string),
//          $detail (string, DEBUG-only), $showLink (bool), $debug (bool)
$appBase = defined('APP_BASE') ? APP_BASE : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — MoveOps</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($appBase, ENT_QUOTES, 'UTF-8') ?>/public/css/app.css">
</head>
<body class="app-body">
<main class="main-content">
    <div class="card empty-state">
        <p class="section-title">Error <?= (int) $code ?></p>
        <h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($showLink): ?>
        <a class="btn btn-solid btn-mt" href="<?= htmlspecialchars($appBase, ENT_QUOTES, 'UTF-8') ?>/index.php">Back to dashboard</a>
        <?php endif; ?>
    </div>
    <?php if ($debug && $detail !== ''): ?>
    <div class="error-detail"><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</main>
</body>
</html>
