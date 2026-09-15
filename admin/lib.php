<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "data.php";

const ADMIN_CREDENTIALS = __DIR__ . DIRECTORY_SEPARATOR . "credentials.php";
const ADMIN_MAX_UPLOAD = 33554432;

function admin_boot() {
    $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    $script = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "/admin/index.php");
    if (preg_match("#^(.*?/admin)(?:/|$)#", $script, $match)) {
        $path = $match[1];
    } else {
        $path = rtrim(dirname($script), "/");
        if ($path === "") {
            $path = "/";
        }
    }

    session_name("ohana_admin");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => $path,
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();

    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: same-origin");
    header("Cache-Control: no-store");
}

function admin_h($value) {
    return data_h($value);
}

function admin_credentials() {
    if (!is_file(ADMIN_CREDENTIALS)) {
        return null;
    }
    $data = include ADMIN_CREDENTIALS;
    if (!is_array($data) || empty($data["username"]) || empty($data["password_hash"])) {
        return null;
    }
    return $data;
}

function admin_logged_in() {
    return !empty($_SESSION["admin_ok"]);
}

function admin_require_login() {
    if (!admin_credentials()) {
        header("Location: setup.php");
        exit;
    }
    if (!admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function admin_csrf_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function admin_csrf_field() {
    return '<input type="hidden" name="csrf" value="' . admin_h(admin_csrf_token()) . '">';
}

function admin_csrf_check() {
    $token = $_POST["csrf"] ?? "";
    if (!is_string($token) || !hash_equals($_SESSION["csrf"] ?? "", $token)) {
        http_response_code(403);
        admin_flash("error", "Il modulo è scaduto. Riprova.");
        header("Location: " . admin_safe_return());
        exit;
    }
}

function admin_safe_return() {
    $fallback = "index.php";
    $target = $_POST["return"] ?? $_SERVER["HTTP_REFERER"] ?? $fallback;
    if (!is_string($target)) {
        return $fallback;
    }
    $path = parse_url($target, PHP_URL_PATH) ?? "";
    $file = basename($path);
    if (!preg_match("/^[a-z]+\.php$/", $file)) {
        return $fallback;
    }
    $query = parse_url($target, PHP_URL_QUERY);
    return $file . ($query ? "?" . $query : "");
}

function admin_flash($type, $message) {
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function admin_take_flash() {
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    return is_array($flash) ? $flash : null;
}

function admin_write_credentials($username, $password) {
    $payload = var_export([
        "username" => $username,
        "password_hash" => password_hash($password, PASSWORD_DEFAULT),
    ], true);
    $ok = file_put_contents(ADMIN_CREDENTIALS, "<?php\nreturn {$payload};\n", LOCK_EX);
    if ($ok === false) {
        return false;
    }
    @chmod(ADMIN_CREDENTIALS, 0600);
    return true;
}

function admin_ini_bytes($value) {
    $value = trim((string) $value);
    if ($value === "") {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === "g") {
        return (int) ($number * 1024 * 1024 * 1024);
    }
    if ($unit === "m") {
        return (int) ($number * 1024 * 1024);
    }
    if ($unit === "k") {
        return (int) ($number * 1024);
    }
    return (int) $number;
}

function admin_upload_limits_note() {
    $uploadMax = ini_get("upload_max_filesize") ?: "?";
    $postMax = ini_get("post_max_size") ?: "?";
    return "Limiti server: upload_max_filesize={$uploadMax}, post_max_size={$postMax}.";
}

function admin_reject_if_post_too_large($returnTo) {
    if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
        return;
    }
    $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
    $postMax = admin_ini_bytes(ini_get("post_max_size"));
    if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax && empty($_POST) && empty($_FILES)) {
        admin_flash("error", "Il file è più grande di post_max_size. " . admin_upload_limits_note());
        header("Location: " . $returnTo);
        exit;
    }
}

function admin_upload_ok($file, $label = "file") {
    if (!is_array($file) || (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $code = (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        return [null, admin_upload_error_message($code, $label)];
    }
    if ((int) ($file["size"] ?? 0) > ADMIN_MAX_UPLOAD) {
        return [null, "Il {$label} supera i 32 MB. Comprimilo o alza il limite su Aruba."];
    }
    return [$file, null];
}

function admin_upload_error_message($code, $label = "file") {
    $note = admin_upload_limits_note();
    switch ((int) $code) {
        case UPLOAD_ERR_INI_SIZE:
            return "Il {$label} supera upload_max_filesize. {$note}";
        case UPLOAD_ERR_FORM_SIZE:
            return "Il {$label} supera il limite del modulo.";
        case UPLOAD_ERR_PARTIAL:
            return "Il {$label} è arrivato solo in parte. Riprova.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Manca la cartella temporanea sul server.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Il server non ha potuto scrivere il {$label}.";
        default:
            return "Impossibile caricare il {$label} (codice {$code}). {$note}";
    }
}

function admin_store_upload($file, $dir, $basename) {
    [$ok, $error] = admin_upload_ok($file, "immagine");
    if ($error) {
        return [null, $error];
    }
    if ($ok === null) {
        return [null, null];
    }
    return data_store_image($ok["tmp_name"], $dir, $basename);
}

function admin_store_multi($files, $dir, $prefix) {
    $saved = [];
    if (!is_array($files) || empty($files["name"]) || !is_array($files["name"])) {
        return [$saved, null];
    }
    $count = count($files["name"]);
    for ($i = 0; $i < $count; $i += 1) {
        $one = [
            "name" => $files["name"][$i],
            "type" => $files["type"][$i] ?? "",
            "tmp_name" => $files["tmp_name"][$i] ?? "",
            "error" => $files["error"][$i] ?? UPLOAD_ERR_NO_FILE,
            "size" => $files["size"][$i] ?? 0,
        ];
        $base = $prefix . "-" . ($i + 1) . "-" . substr(bin2hex(random_bytes(3)), 0, 6);
        [$image, $error] = admin_store_upload($one, $dir, $base);
        if ($error) {
            return [$saved, $error];
        }
        if ($image) {
            $saved[] = $image["src"];
        }
    }
    return [$saved, null];
}

function admin_layout($title, $body, $options = []) {
    $flash = admin_take_flash();
    $nav = !empty($options["nav"]);
    $wide = !empty($options["wide"]);

    header("Content-Type: text/html; charset=utf-8");
    echo '<!doctype html><html lang="it"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . admin_h($title) . " — Ohana Admin</title>";
    echo '<link rel="icon" href="../img/brand/favicon.png" type="image/png">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="../css/fonts.css">';
    echo '<link rel="stylesheet" href="./admin.css">';
    echo "</head><body class=\"admin" . ($wide ? " admin--wide" : "") . '">';

    if ($nav) {
        echo '<header class="admin-bar">';
        echo '<a class="admin-bar__mark" href="index.php">Ohana<span>admin</span></a>';
        echo '<nav class="admin-bar__nav">';
        echo '<a href="password.php">Password</a>';
        echo '<a href="../" target="_blank" rel="noopener">Vedi sito</a>';
        echo '<form method="post" action="logout.php">' . admin_csrf_field() . '<button type="submit">Esci</button></form>';
        echo "</nav></header>";
    }

    if (!empty($options["bare"])) {
        echo $body;
        echo "</body></html>";
        return;
    }

    echo '<main class="admin-main">';
    if ($flash) {
        echo '<p class="admin-flash admin-flash--' . admin_h($flash["type"]) . '" role="status">' . admin_h($flash["message"]) . "</p>";
    }
    echo $body;
    echo "</main>";
    echo '<script src="./admin.js" defer></script>';
    echo "</body></html>";
}

function admin_people_used_in($personId) {
    $titles = [];
    foreach (data_shows(true) as $show) {
        foreach (["cast", "staff"] as $group) {
            foreach ($show[$group] ?? [] as $row) {
                if (($row["personId"] ?? "") === $personId) {
                    $titles[] = $show["titolo"] ?? $show["id"];
                    break 2;
                }
            }
        }
    }
    return $titles;
}

function admin_collect_rows($idsKey, $map) {
    $ids = $_POST[$idsKey] ?? [];
    if (!is_array($ids)) {
        return [];
    }
    $rows = [];
    foreach ($ids as $index => $id) {
        $row = [];
        foreach ($map as $field => $postKey) {
            $values = $_POST[$postKey] ?? [];
            $row[$field] = trim((string) ($values[$index] ?? ""));
        }
        $empty = true;
        foreach ($row as $value) {
            if ($value !== "") {
                $empty = false;
                break;
            }
        }
        if (!$empty) {
            $rows[] = $row;
        }
    }
    return $rows;
}
