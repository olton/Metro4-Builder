<?php

/** @var array $config */
/** @var array $commonCss */
/** @var array $colorsCss */
/** @var array $i18n */
/** @var array $components */
/** @var bool $icons */

// Before less
foreach ($config["required"]["before_less"] as $value) {
    addFiles($value, "less");
}

// Add common css
if (count($commonCss)) {
    foreach ($commonCss as $key) {
        addComponent($key, "common-css");
    }
}

// Add additional colors css
if (count($colorsCss)) {
    foreach ($colorsCss as $key) {
        addComponent($key, "colors-css");
    }
}

// Before js
foreach ($config["required"]["before_js"] as $value) {
    addFiles($value, "js");
}

// Add internationalization
if (count($i18n) === 0) {
    addFiles($config["i18n"]["en-US"], "js");
} else {
    foreach ($i18n as $key) {
        addComponent($key, "i18n");
    }
}

// Add components
if (count($components)) {
    foreach ($components as $key) {
        addComponent($key, "components");
    }
}

if ($icons) {
    $value = $config["usefulness"]["icons"];
    addFiles($value, "less");
}