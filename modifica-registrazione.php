<?php

$query = [];
if (isset($_GET["id"]) && is_string($_GET["id"]) && $_GET["id"] !== "") {
    $query["id"] = $_GET["id"];
} else {
    $query["mode"] = "modifica";
}
if (isset($_GET["inviata"])) {
    $query = ["inviata" => "1"];
}
$target = "registrazione" . ($query ? ("?" . http_build_query($query)) : "");
header("Location: " . $target, true, 301);
exit;
