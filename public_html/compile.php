<?php

/** @var bool $minified */
/** @var bool $source_map */
/** @var string $less_file_name */
/** @var string $css_file_name */
/** @var string $less_command */
/** @var string $css_file_name_min */
/** @var string $clean_command */
/** @var string $js_file_name */
/** @var string $js_file_name_min */
/** @var string $uglify_command */

$less = "npx lessc";
$uglify = "npx uglifyjs";
$clean = "npx cleancss";

$less_command = $less . " LESS_FILE CSS_FILE 2>&1 ";
$uglify_command = $uglify ." JS_FILE ".($source_map ? " --source-map " : " ") ." -o JS_MIN ". ($minified ? " --compress ":" "). " 2>&1";
$clean_command = $clean . " -o CSS_MIN CSS_FILE ".($source_map ? " --source-map " : " ")." 2>&1 ";

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
