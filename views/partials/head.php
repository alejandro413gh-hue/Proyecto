<?php
// views/partials/head.php
// NOTE: This file is inside views/partials/ so config is 2 levels up
$pageTitle = isset($pageTitle) ? $pageTitle . ' — Visión Real' : 'Visión Real';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($pageTitle)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/style.css">
</head>
<body>
<div id="toast-container"></div>
