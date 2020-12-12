<?php

/** @var bool $minified */
/** @var bool $source_map */
/** @var bool $icons */
/** @var string $archive_path */
/** @var string $css_file_name */
/** @var string $css_file_name_min */
/** @var string $css_file_name_min_map */
/** @var string $js_file_name */
/** @var string $js_file_name_min */
/** @var string $js_file_name_min_map */
/** @var string $ver_number */
/** @var string $source_root */
/** @var array $config */
/** @var string $temp_folder */

$zip = new ZipArchive();

if ($zip->open($archive_path, ZipArchive::CREATE) !== true) {
    ReturnJSON(false, "Unable to create archive file!");
    exit(0);
}

if (file_exists($css_file_name)) {
    $dir = "css/";
    $zip->addEmptyDir($dir);
    $zip->addFile($css_file_name, $dir."metro-{$ver_number}.css");

    if ($minified && file_exists($css_file_name_min)) {
        $zip->addFile($css_file_name_min, $dir."metro-{$ver_number}.min.css");
    }
    if ($source_map && file_exists($css_file_name_min_map)) {
        $zip->addFile($css_file_name_min_map, $dir.pathinfo($css_file_name_min_map, PATHINFO_BASENAME));
    }
}

if (file_exists($js_file_name)) {
    $dir = "js/";
    $zip->addEmptyDir($dir);
    $zip->addFile($js_file_name, $dir."metro-{$ver_number}.js");
    if ($minified && file_exists($js_file_name_min)) {
        $zip->addFile($js_file_name_min, $dir."metro-{$ver_number}.min.js");
    }
    if ($source_map && file_exists($js_file_name_min_map)) {
        $zip->addFile($js_file_name_min_map, $dir."metro-{$ver_number}.min.js.map");
    }
}

if ($icons) {
    $dir = "mif/";
    $zip->addEmptyDir($dir);
    $value = $config["usefulness"]["icons"];
    foreach ($value["font"] as $name) {
        $zip->addFile($temp_folder . $name, $dir.$name);
    }
}

$zip->close();