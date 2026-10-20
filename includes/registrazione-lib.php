<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "data.php";

function reg_boot() {
    header("X-Robots-Tag: noindex, nofollow");

    $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    $base = trim(str_replace("\\", "/", site_base_path()), "/");
    session_name("ohana_reg");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => $base === "" ? "/" : "/" . $base,
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

function reg_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function reg_token_ok() {
    $token = $_POST["csrf"] ?? "";
    return is_string($token) && hash_equals($_SESSION["csrf"] ?? "", $token);
}

function reg_line($value, $max) {
    $value = str_replace(["\r", "\n"], " ", trim((string) $value));
    $value = preg_replace("/\s+/", " ", $value) ?? $value;
    if (function_exists("mb_substr")) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function reg_store_photo($file, $basename) {
    if (!is_array($file) || (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $code = (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        return [null, "La foto non è arrivata. Prova con un file più piccolo."];
    }
    if ((int) ($file["size"] ?? 0) > 16777216) {
        return [null, "La foto supera i 16 MB."];
    }
    $tmp = (string) ($file["tmp_name"] ?? "");
    if ($tmp === "" || !is_uploaded_file($tmp)) {
        return [null, "Impossibile leggere la foto."];
    }
    return data_store_image($tmp, "media/compagnia", $basename);
}

function reg_post_too_large() {
    $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
    return $contentLength > 0 && empty($_POST) && empty($_FILES);
}

function reg_try_unlock(array $access, &$error) {
    $fails = (int) ($_SESSION["reg_fails"] ?? 0);
    if ($fails >= 8) {
        $error = "Troppi tentativi. Aspetta un minuto e riprova.";
        return false;
    }
    if ($fails > 2) {
        usleep(400000 * $fails);
    }
    $password = (string) ($_POST["password"] ?? "");
    if (password_verify($password, (string) $access["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["reg_ok"] = 1;
        $_SESSION["reg_fails"] = 0;
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        return true;
    }
    $_SESSION["reg_fails"] = $fails + 1;
    $error = "Password non corretta.";
    return false;
}

function reg_collect_values(array $source = null) {
    $source = $source ?? $_POST;
    $bio = trim((string) ($source["bio"] ?? ""));
    return [
        "nome" => reg_line($source["nome"] ?? "", 120),
        "soprannome" => reg_line($source["soprannome"] ?? "", 80),
        "pronomi" => reg_line($source["pronomi"] ?? "", 40),
        "ruoli" => array_values(array_intersect(array_keys(PERSON_ROLES), (array) ($source["ruoli"] ?? []))),
        "attivoDal" => reg_line($source["attivoDal"] ?? "", 12),
        "attivoAl" => reg_line($source["attivoAl"] ?? "", 12),
        "bio" => function_exists("mb_substr") ? mb_substr($bio, 0, 5000) : substr($bio, 0, 5000),
    ];
}

function reg_values_from_person(array $person) {
    return [
        "nome" => (string) ($person["nome"] ?? ""),
        "soprannome" => (string) ($person["soprannome"] ?? ""),
        "pronomi" => (string) ($person["pronomi"] ?? ""),
        "ruoli" => array_values(array_intersect(array_keys(PERSON_ROLES), (array) ($person["ruoli"] ?? []))),
        "attivoDal" => (string) ($person["attivoDal"] ?? ""),
        "attivoAl" => (string) ($person["attivoAl"] ?? ""),
        "bio" => (string) ($person["bio"] ?? ""),
    ];
}
