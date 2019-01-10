<?php

function replaceVar($str, array $replace = null)
{
    $line = $str;
    if (empty($replace)) {
        return $line;
    }

    foreach ($replace as $key => $value) {
        $line = str_replace(
            [$key, strtoupper($key), ucfirst($key)],
            [$value, strtoupper($value), ucfirst($value)],
            $line
        );
    }
    return $line;
}