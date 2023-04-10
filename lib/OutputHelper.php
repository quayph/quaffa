<?php
namespace quayph\quaffa;
use Codedungeon\PHPCliColors\Color;

class OutputHelper {

    public static function secondsToHuman($seconds) {
        $secs = $seconds%60;
        $mins = floor($seconds/60)%60;
        $hours = floor($seconds/3600);
        return ($hours ? $hours.':':'').str_pad($mins, 2, '0', STR_PAD_LEFT).':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    public static function bytesToHuman($bytes) {
        if ($bytes >= 1024*1024*1024) {
            $fileSize = round($bytes / 1024 / 1024 / 1024, 1) . ' Gb';
          } elseif ($bytes >= 1048576) {
              $fileSize = round($bytes / 1024 / 1024, 1) . ' Mb';
          } elseif($bytes >= 1024) {
              $fileSize = round($bytes / 1024, 1) . ' Kb';
          } else {
              $fileSize = $bytes . ' b';
          }
          return $fileSize;
    }

    public static function errorText($txt) {
        return Color::BG_RED.Color::WHITE.$txt.Color::RESET;  
    }
}