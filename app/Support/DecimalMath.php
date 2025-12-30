<?php

namespace App\Support;

class DecimalMath
{
    public static function add(string|int|float $left, string|int|float $right, int $scale = 2): string
    {
        if (function_exists('bcadd')) {
            return bcadd((string) $left, (string) $right, $scale);
        }

        $result = (float) $left + (float) $right;
        return number_format($result, $scale, '.', '');
    }

    public static function sub(string|int|float $left, string|int|float $right, int $scale = 2): string
    {
        if (function_exists('bcsub')) {
            return bcsub((string) $left, (string) $right, $scale);
        }

        $result = (float) $left - (float) $right;
        return number_format($result, $scale, '.', '');
    }

    public static function mul(string|int|float $left, string|int|float $right, int $scale = 2): string
    {
        if (function_exists('bcmul')) {
            return bcmul((string) $left, (string) $right, $scale);
        }

        $result = (float) $left * (float) $right;
        return number_format($result, $scale, '.', '');
    }

    public static function div(string|int|float $left, string|int|float $right, int $scale = 2): string
    {
        if ((float) $right === 0.0) {
            return number_format(0, $scale, '.', '');
        }

        if (function_exists('bcdiv')) {
            return bcdiv((string) $left, (string) $right, $scale);
        }

        $result = (float) $left / (float) $right;
        return number_format($result, $scale, '.', '');
    }

    public static function comp(string|int|float $left, string|int|float $right, int $scale = 2): int
    {
        if (function_exists('bccomp')) {
            return bccomp((string) $left, (string) $right, $scale);
        }

        $diff = round((float) $left - (float) $right, $scale);
        if ($diff < 0) {
            return -1;
        }
        if ($diff > 0) {
            return 1;
        }
        return 0;
    }
}
