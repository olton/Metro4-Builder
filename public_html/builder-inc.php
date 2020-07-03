<?php

function ReturnJSON($result = true, $message = "OK", $data = array()){
    header('Content-Type: text/json; charset=UTF-8');
    echo json_encode(array("result"=>$result, "message"=>$message, "data"=>$data));
    exit(0);
}

function clear_less($file){
    return preg_replace('/^@import.*;$/m', "", $file);
}

function clear_js($file){
    global $ver_number;

    $js_replace_array = ['@@version', '@@compile', '@@build'];
    $js_replace_array_to = [$ver_number, date("d/m/Y H:i:s"), ""];

    return str_replace($js_replace_array, $js_replace_array_to, $file);
}

function addComponent($part, $name, $type){
    global $config, $source_path, $build;
    $path = $config['parts'][$part][1];

    if (isset($config[$part][$name][$type])) foreach ($config[$part][$name][$type] as $file) {
        if (!isset($build[$part][$type][$file])) {
            if ($name === "resizable" || $name === "resizeable") {

                $name = "resizeable";

                if ($type === "less") {
                    $file = "resizeable";
                }
                if ($type === "js") {
                    $file = "resizable";
                }
            }
            $build[$part][$type][$file] = $source_path . $path . ($part === 'components' ? "/$name/" : "/") . "$file.$type";
        }
    }
}

function does_url_exists($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code == 200) {
        $status = true;
    } else {
        $status = false;
    }
    curl_close($ch);
    return $status;
}