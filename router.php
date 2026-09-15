<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "dispatch.php";

$uri = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$path = rawurldecode($uri);
$page = site_request_page();

$pages = site_public_pages();
if (isset($pages[$page])) {
    require __DIR__ . DIRECTORY_SEPARATOR . $pages[$page];
    return true;
}

if (site_serve_admin($page)) {
    return true;
}

$file = __DIR__ . str_replace("/", DIRECTORY_SEPARATOR, $path);
if ($path !== "/" && is_file($file)) {
    return false;
}

require __DIR__ . DIRECTORY_SEPARATOR . "index.php";
