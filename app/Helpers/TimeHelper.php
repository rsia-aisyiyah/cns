<?php

namespace App\Helpers;

use Carbon\Carbon;


class TimeHelper
{
    public static function getState(string $time): string
    {
        $hour = Carbon::parse($time)->translatedFormat('H');

        if ($hour >= 0 && $hour < 11) {
            return 'pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            return 'siang';
        } elseif ($hour >= 15 && $hour < 19) {
            return 'sore';
        } else {
            return 'malam';
        }
    }
}