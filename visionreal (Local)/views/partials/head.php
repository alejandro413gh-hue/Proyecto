<?php
// views/partials/head.php
// NOTE: This file is inside views/partials/ so config is 2 levels up
$pageTitle = isset($pageTitle) ? $pageTitle . ' — Visión Real' : 'Visión Real';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
<meta http-equiv="Pragma" content="no-cache">
<title><?=htmlspecialchars($pageTitle)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/miestilo.css">
<script>window.BASE_URL='<?=BASE_URL?>';</script>
<script defer src="<?=BASE_URL?>/assets/js/app.js"></script>
</head>
<body>
<div id="toast-container"></div>
