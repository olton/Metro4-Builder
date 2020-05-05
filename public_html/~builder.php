<?php

function ReturnJSON($result = true, $message = "OK", $data = array()){
    header('Content-Type: text/json; charset=UTF-8');
    echo json_encode(array("result"=>$result, "message"=>$message, "data"=>$data));
    exit(0);
}

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

$js_header = <<< JS_HEADER
(function( factory ) {
    if ( typeof define === 'function' && define.amd ) {
        define('metro4', factory );
    } else {
        factory( );
    }
}(function( ) { 
'use strict';

window.hideM4QVersion = true;
JS_HEADER;

$js_footer = <<< JS_FOOTER
if (METRO_INIT ===  true) {
	METRO_INIT_MODE === 'immediate' ? Metro.init() : $(function(){Metro.init()});
}

return Metro;

}));
JS_FOOTER;

$local = false;
$package_path = $local ? "source/" : "https://raw.githubusercontent.com/olton/Metro-UI-CSS/master/";
$source_path = $local  ? "source/" : "https://raw.githubusercontent.com/olton/Metro-UI-CSS/master/source/";
$package = json_decode(file_get_contents($package_path . "package.json"), true);
$config = json_decode(file_get_contents("config.json"), true);
$ver_number = $package['version'];

$common_css = isset($_POST["common-css"]) ? $_POST["common-css"] : [];
$common_js = isset($_POST["common-js"]) ? $_POST["common-js"] : [];
$animations = isset($_POST["animations"]) ? $_POST["animations"] : [];
$special = isset($_POST["special"]) ? $_POST["special"] : [];
$components = isset($_POST["components"]) ? $_POST["components"] : [];
$icons = isset($_POST["icons"]) ? $_POST["icons"] : [];

$minified = isset($_POST["minified"]);
$source_map = isset($_POST["source_map"]);
$build_from_dev = isset($_POST["build_from_dev"]);

if ($build_from_dev) {
    $source_path = str_replace("master", $config["setup"]["next"], $source_path);
    $ver_number = $config["setup"]["next"];
}

$hash = "metro4-$ver_number-".($build_from_dev ? "dev-":"").md5(""
        .implode($common_css)
        .implode($common_js)
        .implode($animations)
        .implode($special)
        .implode($components)
        .implode($icons)
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

$css_build_array = [
    "mixins" => [],
    "required" => [],
    "common" => [],
    "animation" => [],
    "components" => [],
    "special" => []
];
$js_build_array = [
    "required" => [],
    "common" => [],
    "components" => []
];

$css_array = [];
$js_array = [];

$less_replace_array = [
    '@import (once) "vars";',
    '@import (once) "mixins";',
    '@import (once) "default-icons";',
    '@import (once) "include/vars";',
    '@import (once) "include/mixins";',
    '@import (once) "include/default-icons";',
    '@import (once) "../include/vars";',
    '@import (once) "../include/mixins";',
    '@import (once) "../../include/mixins";',
    '@import (once) "../../include/vars";',
    '@import (once) "../../include/default-icons";'
];

$js_replace_array = [
    '@@version',
    '@@compile',
    '@@build'
];

$js_replace_array_to = [
    $package["version"],
    date("d/m/Y H:i:s"),
    $package["build"]
];

function addCss($path){
    global $css_array;
    $result = "";

    if (!in_array($path, $css_array)) {
        $css_array[] = $path;
        $result = $path;
    }

    return $result;
}

function addJs($path){
    global $js_array;
    $result = "";
    if (!in_array($path, $js_array)) {
        $js_array[] = $path;
        $result = $path;
    }
    return $result;
}

// Create less file

$mixins = ["vars", "mixins", "default-icons"];

foreach ($mixins as $css) {
    $css_build_array["mixins"][] = addCss($source_path . "include/$css.less");
}

$css_build_array["required"][] = addCss($source_path . "common/less/reset.less");

foreach ($common_css as $css) {
    $css_build_array["common"][] = addCss($source_path . "common/less/$css.less");
}
foreach ($animations as $css) {
    $css_build_array["animation"][] = addCss($source_path . "animations/$css/$css.less");
}

function addComponentCss($name, $component){
    global $config, $css_build_array, $source_path;
    $css_files = isset($component["content"]["less"]) ? $component["content"]["less"] : [];
    $deps = isset($component["dependencies"]) ? $component["dependencies"] : [];
    foreach ($css_files as $css) {
        $css_build_array["components"][] = addCss($source_path . "components/$name/$css");
    }
    if (isset($deps["common-css"])) foreach ($deps["common-css"] as $c) {
        $css_build_array["common"][] = addCss($source_path . "common/less/$c.less");
    }
    if (isset($deps["components"])) foreach ($deps["components"] as $c) {
        addComponentCss($c, $config["components"][$c]);
    }
}

foreach ($components as $component) {
    addComponentCss($component, $config["components"][$component]);
}

foreach ($special as $css) {
    $css_build_array["special"][] = addCss($source_path . "common/less/$css.less");
}

// Concat less files
$less_file_content = str_replace(["%TIME%", "%VER%", "%%YEAR"], [date("d/m/Y H:i:s"), $package["version"], date("Y")], $copyright) . "\n\n";
foreach ($css_build_array as $key=>$arr) {
    foreach ($css_build_array[$key] as $less_file) {
        if ($less_file !== "") {
            $file_content = @file_get_contents($less_file);
            if ($file_content !== false) {
                $less_file_content .= "/* ".$less_file . " */\n\n";
                $less_file_content .= str_replace($less_replace_array, "", $file_content) . "\n\n";
            }
        }
    }
}

$less_file_name = "temp/" . $hash . ".less";
$css_file_name = "temp/" . $hash . ".css";
$css_file_name_min = "temp/" . $hash . ".min.css";
$css_file_name_min_map = "temp/" . $hash . ".min.css.map";
$less_file = fopen($less_file_name, "w");
fwrite($less_file, $less_file_content);
fclose($less_file);

// Create js file

$js_file_content = str_replace(["%TIME%", "%VER%", "%%YEAR"], [date("d/m/Y H:i:s"), $package["version"], date("Y")], $copyright) . "\n\n";
$js_file_content .= "\n" . $js_header . "\n";

$js_build_array["required"][] = addJs($source_path . "m4q/m4q.js");
$js_build_array["required"][] = addJs($source_path . "metro.js");
$js_build_array["required"][] = addJs($source_path . "common/js/utilities.js");

foreach ($common_js as $js) {
    $js_build_array["common"][] = addJs($source_path . "common/js/$js.js");
}

function addComponentJs($name, $component){
    global $config, $js_build_array, $source_path;
    $js_files = isset($component["content"]["js"]) ? $component["content"]["js"] : [];
    $deps = isset($component["dependencies"]) ? $component["dependencies"] : [];
    foreach ($js_files as $js) {
        $js_build_array["components"][] = addJs($source_path . "components/$name/$js");
    }
    if (isset($deps["common-js"])) foreach ($deps["common-js"] as $c) {
        $js_build_array["common"][] = addJs($source_path . "common/js/$c.js");
    }
    if (isset($deps["components"])) foreach ($deps["components"] as $c) {
        addComponentJs($c, $config["components"][$c]);
    }
}

foreach ($components as $component) {
    addComponentJs($component, $config["components"][$component]);
}

foreach ($js_build_array as $key=>$arr) {
    foreach ($js_build_array[$key] as $js_file) {
        if ($js_file !== "") {
            $file_content = @file_get_contents($js_file);
            if ($file_content !== false) {
                $js_file_content .= "/* ".$js_file . " */\n\n";
                $js_file_content .= str_replace($js_replace_array, $js_replace_array_to, $file_content) . "\n\n";
            }
        }
    }
}

$js_file_content .= "\n" . $js_footer . "\n";

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
    $was_successful = ($exit_status == 0) ? TRUE : FALSE;

    if ($minified) {
        $clean_command = str_replace(['CSS_FILE', 'CSS_MIN'], [$css_file_name, $css_file_name_min], $clean_command);
        exec($clean_command, $output, $exit_status);
        $was_successful = ($exit_status == 0) ? TRUE : FALSE;

        $uglify_command = str_replace(['JS_FILE', 'JS_MIN'], [$js_file_name, $js_file_name_min], $uglify_command);
        exec($uglify_command, $output, $exit_status);
        $was_successful = ($exit_status == 0) ? TRUE : FALSE;
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