<?php

define("TEMP_DIR", __DIR__."/temp/");
define("OUT_DIR", __DIR__."/output/");

include_once "build-inc.php";

$copyright = <<<COPYRIGHT
/*
 * Metro 4 Components Library %VER%  (https://metroui.org.ua)
 * Copyright 2012-%YEAR% by Serhii Pimenov (https://pimenov.com.ua). All rights reserved.
 * Built at %TIME%
 * Licensed under %LICENSE%
 */
COPYRIGHT;

$ip = getIp();
$source_root = "https://raw.githubusercontent.com/";
$config = json_decode(file_get_contents("https://raw.githubusercontent.com/olton/metro4-builder-config/master/config.json"), true);
$package = json_decode(file_get_contents("https://raw.githubusercontent.com/olton/Metro-UI-CSS/master/package.json"), true);
$minified = isset($_POST["minified"]);
$source_map = isset($_POST["source_map"]);
$commonCss = isset($_POST['common-css']) ? $_POST['common-css'] : [];
$colorsCss = isset($_POST['colors-css']) ? $_POST['colors-css'] : [];
$i18n = isset($_POST['i18n']) ? $_POST['i18n'] : [];
$components = isset($_POST['components']) ? $_POST['components'] : [];
$icons = isset($_POST['usefulness']);

$ver_number = "4.4.2";//$package["version"];
$license = "MIT";

$parts = [];
$parts_hash = "";
$build = [];

foreach ($config['parts'] as $key => $val) {
    $parts[$key] = isset($_POST[$key]) ? $_POST[$key] : [];
    $parts_hash .= implode(",",$parts[$key]);
}

$hash = "metro4-$ver_number-".md5(""
        .$ip
        .$parts_hash
        .($minified ? "minified" : "")
        .($source_map ? "source-map" : "")
    );

$archive = $hash . ".zip";
$archive_path = OUT_DIR . $archive;
$archive_link = "getmetro.php?file=".$archive;

if (file_exists($archive_path)) {
    ReturnJSON(true, "CACHE", ['href'=>$archive_link]);
    exit(0);
}

$copyright = str_replace(['%VER%', '%TIME%', '%YEAR%', '%LICENSE%'], [$ver_number, date("d/m/Y H:i:s"), date("Y"), $license], $copyright) . "\n";

$less_file_content = $copyright . "\n";
$js_file_content = $copyright . "\n";

// ================================================ Build ===================================================

include_once "assembly.php";

// ============================================= End of Build================================================

$js_file_content .= "\n";

$temp_folder = "temp/$hash/";
if (!file_exists($temp_folder))
    mkdir($temp_folder);

$less_file_name = $temp_folder . $hash . ".less";
$css_file_name = $temp_folder . $hash . ".css";
$css_file_name_min = $temp_folder . $hash . ".min.css";
$css_file_name_min_map = $temp_folder . $hash . ".min.css.map";
$less_file = fopen($less_file_name, "w");
fwrite($less_file, $less_file_content);
fclose($less_file);

$js_file_name = $temp_folder . $hash . ".js";
$js_file_name_min = $temp_folder . $hash . ".min.js";
$js_file_name_min_map = $temp_folder . $hash . ".min.js.map";
$js_file = fopen($js_file_name, "w");
fwrite($js_file, $js_file_content);
fclose($js_file);

if ($icons) {
    $value = $config["usefulness"]["icons"];
    foreach ($value["font"] as $name) {
        $font_file_url = $source_root . $value["repo"] . "/" . $value["branch"] . "/" . $value["font_path"] . "/" . $name;
        $font_file_local = $temp_folder . $name;
        $font_file = fopen($font_file_local, "w");
        fwrite($font_file, file_get_contents($font_file_url));
        fclose($font_file);
    }
}

// Compile
include_once "compile.php";

// Create array
include_once "zip.php";

@unlink($less_file_name);
@unlink($css_file_name);
@unlink($css_file_name_min);
@unlink($css_file_name_min_map);
@unlink($js_file_name);
@unlink($js_file_name_min);
@unlink($js_file_name_min_map);

if ($icons) {
    @unlink($temp_folder . "metro.svg");
    @unlink($temp_folder . "metro.ttf");
    @unlink($temp_folder . "metro.woff");
}

rmdir($temp_folder);

ReturnJSON(true, "OK", ["href"=>$archive_link]);