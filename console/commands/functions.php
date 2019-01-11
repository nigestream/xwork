<?php

function xwork_console_replaceVar($str, array $replace = null) {
    $line = $str;
    if (empty($replace)) {
        return $line;
    }

    foreach ($replace as $key => $value) {
        $key = str_replace('{{', '', $key);
        $key = str_replace('}}', '', $key);
        $line = str_replace(
            ['{{' . $key . '}}', '{{' . strtoupper($key) . '}}', '{{' . ucfirst($key) . '}}'],
            [$value, strtoupper($value), ucfirst($value)],
            $line
        );
    }
    return $line;
}