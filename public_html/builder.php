<?php

include "builder-inc.php";

define("TEMP_DIR", __DIR__."/temp/");
define("OUT_DIR", __DIR__."/output/");

$copyright = <<<COPYRIGHT
/*
 * Metro 4 Components Library %VER%  (https://metroui.org.ua)
 * Copyright 2012-%YEAR% by Serhii Pimenov (https://pimenov.com.ua). All rights reserved.
 * Built at %TIME%
 * Licensed under MIT
 */
COPYRIGHT;

$local = false;
$package_path = "https://raw.githubusercontent.com/olton/Metro-UI-CSS/master/";
$source_path = "https://raw.githubusercontent.com/olton/Metro-UI-CSS/master/source/";
$config = json_decode(file_get_contents("config.json"), true);

$minified = isset($_POST["minified"]);
$source_map = isset($_POST["source_map"]);
$build_from_dev = isset($_POST["build_from_dev"]);

$ver_number = $build_from_dev ? $config['setup']['next'] : $config['setup']['release'];

if ($build_from_dev) {
    $source_path = str_replace("master", $config["setup"]["next"], $source_path);
    $ver_number = $config["setup"]["next"];
}

$parts = [];
$parts_hash = "";
$build = [];

foreach ($config['parts'] as $key => $val) {
    $parts[$key] = isset($_POST[$key]) ? $_POST[$key] : [];
    $parts_hash .= implode(",",$parts[$key]);
}

$hash = "metro4-$ver_number-".($build_from_dev ? "dev-":"").md5(""
    .$parts_hash
    .($minified ? "minified" : "")
    .($source_map ? "source-map" : "")
    .($build_from_dev ? "build-from-dev" : "")
);

$archive = $hash . ".zip";
$archive_path = OUT_DIR . $archive;
$archive_link = "getmetro.php?file=".$archive;

if (file_exists($archive_path)) {
    ReturnJSON(true, "CACHE", ['href'=>$archive_link]);
    exit(0);
}

$less = "npx lessc";
$uglify = "npx uglifyjs";
$clean = "npx cleancss";

$less_command = $less . " LESS_FILE CSS_FILE 2>&1 ";
$uglify_command = $uglify ." JS_FILE ".($source_map ? " --source-map " : " ") ." -o JS_MIN ". ($minified ? " --compress ":" "). " 2>&1";
$clean_command = $clean . " -o CSS_MIN CSS_FILE ".($source_map ? " --source-map " : " ")." 2>&1 ";

$copyright = str_replace(['%VER%', '%TIME%', '%YEAR%'], [$ver_number, date("d/m/Y H:i:s"), date("Y")], $copyright) . "\n";

$less_file_content = $copyright;
$js_file_content = $copyright . "\n";

// Add required css
foreach (['vars', 'mixins', 'default-icons'] as $file) {
    $build['include'][$file] = $source_path . "include/$file.less";
}


foreach ($parts as $key => $val) {
    foreach ($val as $comp) {
        addComponent($key, $comp, 'less');
        addComponent($key, $comp, 'js');

        if (isset($config[$key][$comp]['dependencies'])) {
            $deps = $config[$key][$comp]['dependencies'];
            foreach ($deps as $deps_key => $deps_val) {
                foreach ($deps_val as $deps_comp_name) {
                    addComponent($deps_key, $deps_comp_name, 'less');
                    addComponent($deps_key, $deps_comp_name, 'js');
                }
            }
        }
    }
}

// Create less file

$build['include'][$file] = $source_path . "common/less/reset.less";

foreach ($build["include"] as $file) {
    if (does_url_exists($file))
        $less_file_content .= clear_less(file_get_contents($file));
}

foreach (['common-css', 'colors-css', 'animation-css', 'components'] as $val) {
    if (isset($build[$val]['less'])) foreach ($build[$val]['less'] as $file) {
        if (does_url_exists($file))
            $less_file_content .= "\n\n/* $val/".basename($file)." */\n\n".clear_less(file_get_contents($file));
    }
}

// Create js file

// Add REQUIRED files
$js_file_content .= clear_js(file_get_contents($source_path . "/m4q/m4q.js"));
$js_file_content .= clear_js(file_get_contents($source_path . "/core/global.js"));
$js_file_content .= clear_js(file_get_contents($source_path . "/core/metro.js"));

// Add default locale if specified components selected
$us = false;
if (count($parts['components']) && count(array_intersect(['calendar', 'calendarpicker', 'countdown', 'datepicker', 'dialog', 'table', 'timepicker', 'validator'], $parts['components']))) {
    if (does_url_exists($source_path . "/i18n/en-US.js")) {
        $us = true;
        $js_file_content .= clear_js(file_get_contents($source_path . "/i18n/en-US.js"));
    }
}

// Add others locales
foreach (['i18n'] as $val) {
    if (isset($build[$val]['js'])) foreach ($build[$val]['js'] as $file) {
        if ($us && basename($file) === 'en-US.js') continue;
        if (does_url_exists($file))
            $js_file_content .= "\n\n/* $val/".basename($file)." */\n\n".clear_js(file_get_contents($file));
    }
}

// Add extensions, REQUIRED
foreach (['array', 'date', 'number', 'object', 'string'] as $file) {
    if (does_url_exists($source_path . "extensions/$file.js"))
        $js_file_content .= "\n\n/* extensions/$file.js */\n\n".clear_js(file_get_contents($source_path . "extensions/$file.js"));
}

// Add common js, utilities REQUIRED
$js_file_content .= clear_js(file_get_contents($source_path . "/common/js/utilities.js"));
foreach (['common-js'] as $val) {
    if (isset($build[$val]['js'])) foreach ($build[$val]['js'] as $file) {
        if (does_url_exists($file))
            $js_file_content .= "\n\n/* $val/".basename($file)." */\n\n".clear_js(file_get_contents($file));
    }
}

// Add components
foreach (['components'] as $val) {
    if (isset($build[$val]['js'])) foreach ($build[$val]['js'] as $file) {
        if (does_url_exists($file))
            $js_file_content .= "\n\n/* $val/".basename($file)." */\n\n".clear_js(file_get_contents($file));
    }
}

$js_file_content .= "\n";

$less_file_name = "temp/" . $hash . ".less";
$css_file_name = "temp/" . $hash . ".css";
$css_file_name_min = "temp/" . $hash . ".min.css";
$css_file_name_min_map = "temp/" . $hash . ".min.css.map";
$less_file = fopen($less_file_name, "w");
fwrite($less_file, $less_file_content);
fclose($less_file);

$js_file_name = "temp/" . $hash . ".js";
$js_file_name_min = "temp/" . $hash . ".min.js";
$js_file_name_min_map = "temp/" . $hash . ".min.js.map";
$js_file = fopen($js_file_name, "w");
fwrite($js_file, $js_file_content);
fclose($js_file);

// Compile
if (substr(php_uname(), 0, 7) == "Windows"){
    $less_command = str_replace(['LESS_FILE', 'CSS_FILE'], [$less_file_name, $css_file_name], $less_command);
    $handle = popen($less_command, 'r');
    $read = fread($handle, 2096);
    pclose($handle);


    if ($minified) {
        $clean_command = str_replace(['CSS_FILE', 'CSS_MIN'], [$css_file_name, $css_file_name_min], $clean_command);
        $handle = popen($clean_command, 'r');
        $read = fread($handle, 2096);
        pclose($handle);

        $uglify_command = str_replace(['JS_FILE', 'JS_MIN'], [$js_file_name, $js_file_name_min], $uglify_command);
        $handle = popen($uglify_command, 'r');
        $read = fread($handle, 2096);
        pclose($handle);
    }
} else {
    $less_command = str_replace(['LESS_FILE', 'CSS_FILE'], [$less_file_name, $css_file_name], $less_command);
    exec($less_command, $output, $exit_status);
    $was_successful = $exit_status == 0;

    if ($minified) {
        $clean_command = str_replace(['CSS_FILE', 'CSS_MIN'], [$css_file_name, $css_file_name_min], $clean_command);
        exec($clean_command, $output, $exit_status);
        $was_successful = $exit_status == 0;

        $uglify_command = str_replace(['JS_FILE', 'JS_MIN'], [$js_file_name, $js_file_name_min], $uglify_command);
        exec($uglify_command, $output, $exit_status);
        $was_successful = $exit_status == 0;
    }
}

// Create array
$zip = new ZipArchive();

if ($zip->open($archive_path, ZipArchive::CREATE) !== true) {
    ReturnJSON(false, "Unable to create archive file!");
    exit(0);
}

if (file_exists($css_file_name)) {
    $zip->addFile($css_file_name, pathinfo($css_file_name, PATHINFO_BASENAME));
    if ($minified && file_exists($css_file_name_min)) {
        $zip->addFile($css_file_name_min, pathinfo($css_file_name_min, PATHINFO_BASENAME));
    }
    if ($source_map && file_exists($css_file_name_min_map)) {
        $zip->addFile($css_file_name_min_map, pathinfo($css_file_name_min_map, PATHINFO_BASENAME));
    }
}

if (file_exists($js_file_name)) {
    $zip->addFile($js_file_name, pathinfo($js_file_name, PATHINFO_BASENAME));
    if ($minified && file_exists($js_file_name_min)) {
        $zip->addFile($js_file_name_min, pathinfo($js_file_name_min, PATHINFO_BASENAME));
    }
    if ($source_map && file_exists($js_file_name_min_map)) {
        $zip->addFile($js_file_name_min_map, pathinfo($js_file_name_min_map, PATHINFO_BASENAME));
    }
}

$zip->close();

@unlink($less_file_name);
@unlink($css_file_name);
@unlink($css_file_name_min);
@unlink($css_file_name_min_map);
@unlink($js_file_name);
@unlink($js_file_name_min);
@unlink($js_file_name_min_map);

ReturnJSON(true, "OK", ["href"=>$archive_link]);