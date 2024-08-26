<?php

namespace App\Helpers;

class MainHelper
{
    public static function formatter(string $key, string $value): string
    {
        $key_len = mb_strlen($key);
        $value_len = mb_strlen($value);
        if (($key_len + $value_len) < 29) {
            $need = 29 - ($key_len + $value_len);
            $dot = '.';
            for ($i = 2; $i <= $need; $i++) {
                $dot .= '.';
            }
            return "<code>$key</code> <code>$dot</code> <code>$value</code>";
        } else {
            return "<code>$key</code> - <code>$value</code>";
        }
    }
}
