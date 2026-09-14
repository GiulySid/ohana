<?php

const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "data";
const MEDIA_DIR = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "media";
const DATA_BACKUP_DIR = DATA_DIR . DIRECTORY_SEPARATOR . "backups";

const PERSON_ROLES = [
    "attore" => "Attori",
    "regista" => "Registi",
    "tecnico" => "Tecnici",
    "collaboratore" => "Collaboratori",
];

const SHOW_STATES = [
    "prossimo" => "Prossimo",
    "archivio" => "Archivio",
];

const MEDIA_TYPES = [
    "youtube" => "Video YouTube",
    "gallery" => "Gallery foto",
];

function data_h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function data_path($name) {
    return DATA_DIR . DIRECTORY_SEPARATOR . $name . ".json";
}

function data_default($name) {
    switch ($name) {
        case "compagnia":
            return ["people" => []];
        case "spettacoli":
            return ["shows" => []];
        case "media":
            return ["items" => []];
        case "sito":
            return [
                "nome" => "Ohana Musical Company",
                "tagline" => "Le luci si accenderanno, le musiche partiranno.",
                "chiSiamo" => "",
                "email" => "",
                "instagram" => "",
                "facebook" => "",
                "telefono" => "",
                "indirizzo" => "",
                "orariProve" => "",
            ];
        default:
            return [];
    }
}

function data_read($name) {
    $path = data_path($name);
    $defaults = data_default($name);
    if (!is_file($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }
    return array_replace_recursive($defaults, $decoded);
}

function data_write($name, $payload) {
    if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0755, true)) {
        return false;
    }
    if (!is_dir(DATA_BACKUP_DIR)) {
        @mkdir(DATA_BACKUP_DIR, 0755, true);
    }
    $path = data_path($name);
    if (is_file($path) && is_dir(DATA_BACKUP_DIR)) {
        $stamp = date("Ymd-His");
        @copy($path, DATA_BACKUP_DIR . DIRECTORY_SEPARATOR . $name . "-" . $stamp . ".json");
        $existing = glob(DATA_BACKUP_DIR . DIRECTORY_SEPARATOR . $name . "-*.json") ?: [];
        rsort($existing);
        foreach (array_slice($existing, 20) as $old) {
            @unlink($old);
        }
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    $tmp = $path . ".tmp";
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        return false;
    }
    return @rename($tmp, $path);
}

function data_site() {
    return data_read("sito");
}

function data_people($includeHidden = false) {
    $people = data_read("compagnia")["people"] ?? [];
    if ($includeHidden) {
        return array_values($people);
    }
    return array_values(array_filter($people, static function ($person) {
        return empty($person["hidden"]);
    }));
}

function data_person($id, $includeHidden = false) {
    foreach (data_people($includeHidden) as $person) {
        if (($person["id"] ?? "") === $id) {
            return $person;
        }
    }
    return null;
}

function data_person_map($includeHidden = false) {
    $map = [];
    foreach (data_people($includeHidden) as $person) {
        $map[$person["id"]] = $person;
    }
    return $map;
}

function data_person_is_active($person) {
    $to = trim((string) ($person["attivoAl"] ?? ""));
    return $to === "";
}

function data_person_period($person) {
    $from = trim((string) ($person["attivoDal"] ?? ""));
    $to = trim((string) ($person["attivoAl"] ?? ""));
    if ($from === "" && $to === "") {
        return "";
    }
    if ($to === "") {
        return ($from !== "" ? $from : "…") . " — oggi";
    }
    if ($from === "") {
        return "fino al " . $to;
    }
    return $from . " — " . $to;
}

function data_shows($includeHidden = false) {
    $shows = data_read("spettacoli")["shows"] ?? [];
    if (!$includeHidden) {
        $shows = array_values(array_filter($shows, static function ($show) {
            return empty($show["hidden"]);
        }));
    }
    usort($shows, static function ($a, $b) {
        $yearA = (int) ($a["anno"] ?? 0);
        $yearB = (int) ($b["anno"] ?? 0);
        if ($yearA !== $yearB) {
            return $yearB <=> $yearA;
        }
        return strcasecmp((string) ($a["titolo"] ?? ""), (string) ($b["titolo"] ?? ""));
    });
    return $shows;
}

function data_show($id, $includeHidden = false) {
    foreach (data_shows($includeHidden) as $show) {
        if (($show["id"] ?? "") === $id) {
            return $show;
        }
    }
    return null;
}

function data_upcoming_show() {
    foreach (data_shows() as $show) {
        if (($show["stato"] ?? "") === "prossimo") {
            return $show;
        }
    }
    return null;
}

function data_archive_by_year() {
    $groups = [];
    foreach (data_shows() as $show) {
        $label = trim((string) ($show["stagione"] ?? ""));
        if ($label === "") {
            $label = (string) ($show["anno"] ?? "");
        }
        if ($label === "") {
            $label = "Altri";
        }
        $groups[$label][] = $show;
    }
    return $groups;
}

function data_person_curriculum($personId) {
    $items = [];
    foreach (data_shows() as $show) {
        foreach (["cast", "staff"] as $group) {
            foreach ($show[$group] ?? [] as $row) {
                if (($row["personId"] ?? "") !== $personId) {
                    continue;
                }
                $credit = trim((string) ($row["ruoloInScena"] ?? $row["credito"] ?? ""));
                $items[] = [
                    "show" => $show,
                    "credit" => $credit,
                    "group" => $group,
                ];
            }
        }
    }
    return $items;
}

function data_media_items($includeHidden = false) {
    $items = data_read("media")["items"] ?? [];
    if ($includeHidden) {
        return array_values($items);
    }
    return array_values(array_filter($items, static function ($item) {
        return empty($item["hidden"]);
    }));
}

function data_media_item($id, $includeHidden = false) {
    foreach (data_media_items($includeHidden) as $item) {
        if (($item["id"] ?? "") === $id) {
            return $item;
        }
    }
    return null;
}

function data_media_photos() {
    $photos = [];
    foreach (data_media_items() as $item) {
        if (($item["tipo"] ?? "") !== "gallery") {
            continue;
        }
        foreach ($item["files"] ?? [] as $file) {
            $src = data_asset($file);
            if ($src === "" || in_array($src, $photos, true)) {
                continue;
            }
            $photos[] = $src;
        }
    }
    return $photos;
}

function data_slugify($text) {
    $ascii = trim((string) $text);
    if ($ascii === "") {
        return "";
    }
    if (function_exists("iconv")) {
        $converted = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $ascii);
        if (is_string($converted) && $converted !== "") {
            $ascii = $converted;
        }
    }
    $ascii = strtolower($ascii);
    $ascii = str_replace(["'", "`", "\""], "", $ascii);
    $ascii = preg_replace("/[^a-z0-9]+/", "-", $ascii) ?? "";
    $ascii = preg_replace("/-+/", "-", $ascii) ?? "";
    return trim($ascii, "-");
}

function data_is_safe_id($id) {
    return is_string($id) && (bool) preg_match("/^[a-z0-9][a-z0-9\-]{0,80}$/", $id);
}

function data_unique_id($used, $base) {
    $base = data_slugify($base);
    if ($base === "" || !data_is_safe_id($base)) {
        $base = "item";
    }
    $candidate = $base;
    $n = 2;
    $set = array_fill_keys($used, true);
    while (isset($set[$candidate])) {
        $candidate = $base . "-" . $n;
        $n += 1;
        if ($n > 80) {
            return null;
        }
    }
    return $candidate;
}

function data_youtube_id($url) {
    $url = trim((string) $url);
    if ($url === "") {
        return "";
    }
    if (preg_match("/^[A-Za-z0-9_-]{11}$/", $url)) {
        return $url;
    }
    if (preg_match("~(?:youtu\\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~", $url, $match)) {
        return $match[1];
    }
    return "";
}

function data_youtube_embed($url) {
    $id = data_youtube_id($url);
    return $id === "" ? "" : "https://www.youtube-nocookie.com/embed/" . rawurlencode($id);
}

function data_youtube_thumb($url) {
    $id = data_youtube_id($url);
    return $id === "" ? "" : "https://i.ytimg.com/vi/" . rawurlencode($id) . "/hqdefault.jpg";
}

function data_asset($relative) {
    $relative = str_replace("\\", "/", (string) $relative);
    $relative = ltrim($relative, "/");
    if ($relative === "" || strpos($relative, "..") !== false) {
        return "";
    }
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relative);
    if (!is_file($abs)) {
        return "";
    }
    return $relative;
}

function data_first_letter($text) {
    $text = trim((string) $text);
    if ($text === "") {
        return "•";
    }
    $letter = function_exists("mb_substr") ? mb_substr($text, 0, 1) : substr($text, 0, 1);
    if (function_exists("mb_strtoupper")) {
        return mb_strtoupper($letter);
    }
    return strtoupper($letter);
}

function data_initials($name) {
    $parts = preg_split("/\s+/", trim((string) $name)) ?: [];
    $letters = "";
    foreach ($parts as $part) {
        if ($part === "") {
            continue;
        }
        $letters .= data_first_letter($part);
        if (strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== "" ? $letters : "•";
}

function data_format_date($iso) {
    $iso = trim((string) $iso);
    if ($iso === "") {
        return "";
    }
    $dt = date_create($iso);
    if (!$dt) {
        return $iso;
    }
    $months = [
        1 => "gennaio", 2 => "febbraio", 3 => "marzo", 4 => "aprile",
        5 => "maggio", 6 => "giugno", 7 => "luglio", 8 => "agosto",
        9 => "settembre", 10 => "ottobre", 11 => "novembre", 12 => "dicembre",
    ];
    $days = ["domenica", "lunedì", "martedì", "mercoledì", "giovedì", "venerdì", "sabato"];
    $month = $months[(int) $dt->format("n")] ?? $dt->format("m");
    $weekday = $days[(int) $dt->format("w")] ?? "";
    return $weekday . " " . $dt->format("j") . " " . $month . " " . $dt->format("Y");
}

function data_next_replica($show) {
    $today = date("Y-m-d");
    $next = null;
    foreach ($show["repliche"] ?? [] as $replica) {
        $day = (string) ($replica["data"] ?? "");
        if ($day === "" || $day < $today) {
            continue;
        }
        if ($next === null || $day < $next["data"]) {
            $next = $replica;
        }
    }
    return $next;
}

function data_ensure_dir($relative) {
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relative);
    if (!is_dir($abs) && !@mkdir($abs, 0755, true)) {
        return null;
    }
    return $abs;
}

function data_image_from_upload($tmpPath) {
    if (!function_exists("imagecreatetruecolor")) {
        return [null, "GD is not available on this PHP."];
    }
    $info = @getimagesize($tmpPath);
    if (!$info) {
        return [null, "That file is not a valid image."];
    }
    switch ($info["mime"]) {
        case "image/jpeg":
            $src = @imagecreatefromjpeg($tmpPath);
            break;
        case "image/png":
            $src = @imagecreatefrompng($tmpPath);
            break;
        case "image/webp":
            $src = function_exists("imagecreatefromwebp") ? @imagecreatefromwebp($tmpPath) : null;
            break;
        case "image/gif":
            $src = @imagecreatefromgif($tmpPath);
            break;
        default:
            return [null, "Use JPG, PNG, WEBP or GIF."];
    }
    if (!$src) {
        return [null, "Could not read the image."];
    }
    return [$src, null];
}

function data_fit_image($src, $max) {
    $width = imagesx($src);
    $height = imagesy($src);
    if ($width < 1 || $height < 1) {
        return $src;
    }
    $scale = min(1, $max / max($width, $height));
    if ($scale >= 1) {
        return $src;
    }
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));
    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imagedestroy($src);
    return $dst;
}

function data_save_gd($image, $absPath) {
    $dir = dirname($absPath);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $ok = false;
    if (function_exists("imagewebp")) {
        $ok = imagewebp($image, $absPath, 82);
    } else {
        $absPath = preg_replace("/\\.webp$/i", ".jpg", $absPath);
        $ok = imagejpeg($image, $absPath, 85);
    }
    return $ok ? $absPath : false;
}

function data_store_image($tmpPath, $relativeDir, $basename, $max = 1920, $thumbMax = 480) {
    [$src, $error] = data_image_from_upload($tmpPath);
    if (!$src) {
        return [null, $error];
    }
    $full = data_fit_image($src, $max);
    $thumbSrc = data_fit_image(data_clone_image($full), $thumbMax);
    $fileName = $basename . ".webp";
    $relFull = trim($relativeDir, "/") . "/" . $fileName;
    $relThumb = trim($relativeDir, "/") . "/thumbs/" . $fileName;
    $absFull = data_save_gd($full, dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relFull));
    $absThumb = data_save_gd($thumbSrc, dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relThumb));
    imagedestroy($full);
    imagedestroy($thumbSrc);
    if (!$absFull) {
        return [null, "Could not save the image. Check folder permissions."];
    }
    $relFull = str_replace("\\", "/", substr($absFull, strlen(dirname(__DIR__) . DIRECTORY_SEPARATOR)));
    $relThumb = $absThumb
        ? str_replace("\\", "/", substr($absThumb, strlen(dirname(__DIR__) . DIRECTORY_SEPARATOR)))
        : $relFull;
    return [[
        "src" => $relFull,
        "thumb" => $relThumb,
    ], null];
}

function data_clone_image($src) {
    $width = imagesx($src);
    $height = imagesy($src);
    $dst = imagecreatetruecolor($width, $height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
    return $dst;
}

function data_delete_rel($relative) {
    $relative = str_replace("\\", "/", (string) $relative);
    if ($relative === "" || strpos($relative, "..") !== false || strpos($relative, "media/") !== 0) {
        return;
    }
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relative);
    if (is_file($abs)) {
        @unlink($abs);
    }
}

function data_role_labels($roles) {
    $labels = [];
    foreach ((array) $roles as $role) {
        if (isset(PERSON_ROLES[$role])) {
            $labels[] = PERSON_ROLES[$role];
        }
    }
    return $labels;
}
