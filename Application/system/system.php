<?php

mb_internal_encoding('UTF-8');

define('KEY_STAMP', '/tmp/keystamp');
define('LOGO_IMG', '/tmp/resources/logo.jpg');
define('USB_IMG', '/tmp/resources/usb.jpg');
define('BATTERY_CAPACITY', '/sys/class/power_supply/battery/capacity');
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
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        imagefilledrectangle($im, 0, 0, SCREEN_W, SCREEN_H, $white);
        self::showScreen($im);
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
        // Text
        $black = imagecolorallocate($im, 0, 0, 0);
        imagettftext($im, 16, 0, 10, 20, $black, DEFAULT_FONT, $text);
        // Show
        self::showScreen($im);
        imagedestroy($im);
    }

    public static function showJpg($file, $better = false) {
        $jpg = imagecreatefromjpeg($file);
        if (!$jpg) {
            self::showText('Failed to open jpg');
            return;
        }
        self::showImage($jpg, $better);
        imagedestroy($jpg);
    }

    public static function showLogo() {
        $im = imagecreatefromjpeg(LOGO_IMG);
        if (!$im) {
            self::showText('Failed to show logo');
            return;
        }
        // Text
        $battery = file_get_contents(BATTERY_CAPACITY);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagettftext($im, 16, 0, 10, 20, $black, DEFAULT_FONT, $battery);
        self::showImage($im);
        imagedestroy($im);
    }

    public static function showUsb() {
        self::showJpg(USB_IMG);
    }

    private static function showImage($im, $better = false) {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w == SCREEN_W && $h == SCREEN_H) {
            // Show directly
            self::showScreen($im, $better);
            return;
        }
        // Scale
        $dstIm = imagecreatetruecolor(SCREEN_W, SCREEN_H);
        $scaleRate = min(SCREEN_W * 1.0 / $w, SCREEN_H * 1.0 / $h);
        $dstW = round($w * $scaleRate);
        $dstH = round($h * $scaleRate);
        $dstX = (SCREEN_W - $dstW) / 2;
        $dstY = (SCREEN_H - $dstH) / 2;
        if ($better) {
            imagecopyresampled($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        } else {
            imagecopyresized($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        }
        // Show
        self::showScreen($dstIm, $better);
        imagedestroy($dstIm);
    }

    private static function showScreen($im, $dither = false) {
        if ($dither) {
            self::ditherImageOrderly($im);
            //self::ditherImageRandomly($im);
        }
        imagefile($im, '/dev/fb', 1);
        sleep(1);
    }

    private static function ditherImageOrderly($im) {
        // 4 x 4 Bayer Matrix
        $bayerMatrix = array(
            array(0x0, 0x8, 0x2, 0xA),
            array(0xC, 0x4, 0xE, 0x6),
            array(0x3, 0xB, 0x1, 0x9),
            array(0xF, 0x7, 0xD, 0x5)
        );
        $w = imagesx($im);
        $h = imagesy($im);
        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0 ; $x < $w; ++$x) {
                $color = imagecolorat($im, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $gray = ($r * 306 + $g * 601 + $b * 117 + 512) >> 10;
                $gray = intdiv($gray + $bayerMatrix[$x % 4][$y % 4], 0x11) * 0x11;
                $color = imagecolorallocate($im, $gray, $gray, $gray);
                imagesetpixel($im, $x, $y, $color);
            }
        }
    }

    private static function ditherImageRandomly($im) {
        $w = imagesx($im);
        $h = imagesy($im);
        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0 ; $x < $w; ++$x) {
                $color = imagecolorat($im, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $gray = ($r * 306 + $g * 601 + $b * 117 + 512) >> 10;
                $gray = intdiv($gray + mt_rand(0, 0x10), 0x11) * 0x11;
                $color = imagecolorallocate($im, $gray, $gray, $gray);
                imagesetpixel($im, $x, $y, $color);
            }
        }
    }
}

?>