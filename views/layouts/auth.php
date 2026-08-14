<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MoveOps</title>
    <link rel="icon" type="image/png" href="<?= Helpers::assetUrl('/favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Helpers::assetUrl('/css/app.css') ?>">
</head>
<body>
<?php require_once __DIR__ . '/../auth/login.php'; ?>
<script src="<?= Helpers::assetUrl('/js/app.js') ?>"></script>
</body>
</html>
