<?php

mb_internal_encoding('UTF-8');

define('KEY_STAMP', '/tmp/keystamp');
define('POWER_STATE', '/sys/android_power/state');
define('DEFAULT_FONT', '/opt/qte/fonts/msyh.ttf');

define('SCREEN_W', 360);
define('SCREEN_H', 600);

class System {
    public static function standBy() {
        file_put_contents(POWER_STATE, 'standby');
    }

    public static function shutDown() {
        system('/sbin/poweroff');
        exit(0);
    }

    public static function refreshScreen() {
        // Show black
        $im = imagecreatetruecolor(SCREEN_W, SCREEN_H);
        self::showScreen($im);
        // Show white
        if (0) {
            $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
            imagefilledrectangle($im, 0, 0, SCREEN_W, SCREEN_H, $white);
            imagecolordeallocate($im, $white);
            self::showScreen($im);
        }
        imagedestroy($im);
    }

    public static function showText($text) {
        if ($text == '') {
            return;
        }
        $im = imagecreatetruecolor(SCREEN_W, SCREEN_H);
        // Background
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        imagefilledrectangle($im, 0, 0, SCREEN_W, SCREEN_H, $white);
        imagecolordeallocate($im, $white);
        // Text
        $black = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        imagettftext($im, 16, 0, 10, 20, $black, DEFAULT_FONT, $text);
        imagecolordeallocate($im, $black);
        // Show
        self::showScreen($im);
        imagedestroy($im);
    }

    public static function showJpg($file) {
        $jpg = imagecreatefromjpeg($file);
        if ($jpg == false) {
            self::showText('Failed to open jpg');
            return;
        }
        self::showImage($jpg);
        imagedestroy($jpg);
    }

    public static function showPng($file) {
        $png = imagecreatefrompng($file);
        if ($png == false) {
            self::showText('Failed to open png');
            return;
        }
        self::showImage($png);
        imagedestroy($png);
    }

    private static function showImage($im) {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w == SCREEN_W && $h == SCREEN_H) {
            // Show directly
            self::showScreen($im);
            return;
        }
        // Scale
        $dstIm = imagecreatetruecolor(SCREEN_W, SCREEN_H);
        $scaleRate = min(SCREEN_W * 1.0 / $w, SCREEN_H * 1.0 / $h);
        $dstW = round($w * $scaleRate);
        $dstH = round($h * $scaleRate);
        $dstX = (SCREEN_W - $dstW) / 2;
        $dstY = (SCREEN_H - $dstH) / 2;
        if (0) {
            imagecopyresampled($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        } else {
            imagecopyresized($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        }
        // Show
        self::showScreen($dstIm);
        imagedestroy($dstIm);
    }

    private static function showScreen($im) {
        // Internal function
        imagefile($im, '/dev/fb', 1);
        sleep(1);
    }
}

?>