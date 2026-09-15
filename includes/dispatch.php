<?php

function site_public_pages() {
    return [
        "compagnia" => "compagnia.php",
        "archivio" => "archivio.php",
        "galleria" => "media.php",
        "contatti" => "contatti.php",
        "persona" => "persona.php",
        "spettacolo" => "spettacolo.php",
    ];
}

function site_base_path() {
    $script = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "/index.php");
    $dir = trim(dirname($script), "/");
    if ($dir !== "" && $dir !== ".") {
        return $dir;
    }

    $docRoot = str_replace("\\", "/", rtrim((string) ($_SERVER["DOCUMENT_ROOT"] ?? ""), "/"));
    $appRoot = str_replace("\\", "/", dirname(__DIR__));
    $docLen = strlen($docRoot);
    if ($docRoot !== "" && strncasecmp($appRoot, $docRoot, $docLen) === 0) {
        $rest = substr($appRoot, $docLen);
        if ($rest === "" || str_starts_with($rest, "/")) {
            return trim($rest, "/");
        }
    }

    return "";
}

function site_request_page() {
    $uri = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
    $page = trim(rawurldecode((string) $uri), "/");
    $base = site_base_path();
    if ($base !== "" && ($page === $base || str_starts_with($page, $base . "/"))) {
        $page = trim(substr($page, strlen($base)), "/");
    }
    if (str_ends_with($page, ".php")) {
        $page = substr($page, 0, -4);
    }
    return $page;
}

function site_is_admin_request($page) {
    return $page === "admin" || str_starts_with((string) $page, "admin/");
}

function site_serve_admin($page) {
    if (!site_is_admin_request($page)) {
        return false;
    }

    $uri = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
    if ($page === "admin" && !str_ends_with($uri, "/") && !str_ends_with($uri, ".php")) {
        $query = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_QUERY);
        header("Location: " . $uri . "/" . ($query ? "?" . $query : ""), true, 302);
        return true;
    }

    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . "admin";
    $relative = $page === "admin" ? "index" : substr((string) $page, 6);
    if ($relative === "" || str_contains($relative, "..")) {
        $relative = "index";
    }

    $file = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relative);
    if (!str_ends_with($file, ".php")) {
        $file .= ".php";
    }

    $base = basename($file);
    if ($base === "credentials.php" || !is_file($file)) {
        return false;
    }

    require $file;
    return true;
}

function site_dispatch() {
    $page = site_request_page();
    if ($page === "" || $page === "index") {
        return;
    }

    if (site_serve_admin($page)) {
        exit;
    }

    $pages = site_public_pages();
    if (isset($pages[$page])) {
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . $pages[$page];
        exit;
    }

    http_response_code(404);
    require_once __DIR__ . DIRECTORY_SEPARATOR . "layout.php";
    site_header("home", "Non trovato");
    echo '<header class="page-head"><h1>Pagina non trovata</h1><p>Questo indirizzo non è sul sito.</p><a class="btn" href="./" data-nav>Torna alla home</a></header>';
    site_footer();
    exit;
}
