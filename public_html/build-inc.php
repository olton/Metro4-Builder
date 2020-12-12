<?php

function getIp() {
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $a = explode(',', $_SERVER[$key]);
            $ip = trim(end($a));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
}

function ReturnJSON($result = true, $message = "OK", $data = array()){
    header('Content-Type: text/json; charset=UTF-8');
    echo json_encode(array("result"=>$result, "message"=>$message, "data"=>$data));
    exit(0);
}

function does_url_exists($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code == 200;
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


function addFiles($value, $type = "less"){
    global $source_root, $less_file_content, $js_file_content;

    foreach ($value[$type] as $name) {
        $file = $source_root . $value["repo"] . "/" . $value["branch"] . "/" . $value["path"] . "/" . $name . "." . $type;
        $file_content = file_get_contents( $file );
        if (!$file_content) {
            continue;
        }
        if ($type === "less") {
            $less_file_content .= "\n\n". clear_less($file_content) . "\n";
        } else {
            $js_file_content .= "\n\n". clear_js($file_content) . "\n";
        }
    }
}

function addComponent($name, $where = "components"){
    global $config;

    $value = $config[$where][$name];

    if (isset($value["less"])) addFiles($value, "less");
    if (isset($value["js"])) addFiles($value, "js");

//    if (isset($value["dependencies"])) foreach ($value["dependencies"] as $key => $val) {
//        foreach ($val as $name) {
//            addComponent($name, $key);
//        }
//    }
}