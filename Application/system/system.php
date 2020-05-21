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
        imagecolordeallocate($im, $white);
        // Text
        $black = imagecolorallocate($im, 0, 0, 0);
        imagettftext($im, 16, 0, 10, 20, $black, DEFAULT_FONT, $text);
        imagecolordeallocate($im, $black);
        // Show
        self::showScreen($im);
        imagedestroy($im);
    }

    public static function showJpg($file, $fast = true) {
        $jpg = imagecreatefromjpeg($file);
        if (!$jpg) {
            self::showText('Failed to open jpg');
            return;
        }
        self::showImage($jpg, $fast);
        imagedestroy($jpg);
    }

    private static function showImage($im, $fast = true) {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w == SCREEN_W && $h == SCREEN_H) {
            // Show directly
            self::showScreen($im, !$fast);
            return;
        }
        // Scale
        $dstIm = imagecreatetruecolor(SCREEN_W, SCREEN_H);
        $scaleRate = min(SCREEN_W * 1.0 / $w, SCREEN_H * 1.0 / $h);
        $dstW = round($w * $scaleRate);
        $dstH = round($h * $scaleRate);
        $dstX = (SCREEN_W - $dstW) / 2;
        $dstY = (SCREEN_H - $dstH) / 2;
        if ($fast) {
            imagecopyresized($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        } else {
            imagecopyresampled($dstIm, $im, $dstX, $dstY, 0, 0, $dstW, $dstH, $w, $h);
        }
        // Show
        self::showScreen($dstIm, !$fast);
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
        $thresholdMap = array(
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
                $gray8 = ($r * 306 + $g * 601 + $b * 117) >> 10;
                $gray4 = 0;
                if ($gray8 < 8) {
                    $gray4 = 0;
                } else if ($gray8 >= 0xF8) {
                    $gray4 = 0xF;
                } else {
                    $gray4 = ($gray8 - 8) >> 4; // 0x0 ~ 0xE
                    $delta = $gray8 - 8 - ($gray4 << 4); // 0x0 ~ 0xF
                    if ($delta > $thresholdMap[$x % 4][$y % 4]) {
                        ++$gray4;
                    }
                }
                $gray8 = $gray4 * 0x11;
                $color = imagecolorallocate($im, $gray8, $gray8, $gray8);
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
                $gray8 = ($r * 306 + $g * 601 + $b * 117) >> 10;
                $gray4 = 0;
                if ($gray8 < 8) {
                    $gray4 = 0;
                } else if ($gray8 >= 0xF8) {
                    $gray4 = 0xF;
                } else {
                    $gray4 = ($gray8 + mt_rand(-8, 7)) >> 4;
                }
                $gray8 = $gray4 * 0x11;
                $color = imagecolorallocate($im, $gray8, $gray8, $gray8);
                imagesetpixel($im, $x, $y, $color);
            }
        }
    }
}

?>