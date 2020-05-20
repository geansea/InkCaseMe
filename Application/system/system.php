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

    private static function createGrayscaleImage($width, $height) {
        $im = imagecreate($width, $height);
        for ($i = 0; i < 256; ++$i) {
            imagecolorallocate($im, $i, $i, $i);
        }
        return $im;
    }

    private static function ditherGrayscaleImageOrderly($im) {
        if (imageistruecolor($im)) {
            return;
        }
        // 8 x 8 Bayer Matrix
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
                $gray8 = imagecolorat($im, $x, $y);
                $gray4 = 0;
                if ($gray8 < 8) {
                    $gray4 = 0;
                } else if ($gray8 >= 0xF8) {
                    $gray4 = 0xF;
                } else {
                    $gray4 = ($gray8 - 8) >> 4; // 0x0 ~ 0xE
                    $delta = $gray8 - 8 - $gray4 << 4; // 0x0 ~ 0xF
                    if ($delta > $thresholdMap[$x][$y]) {
                        ++$gray4;
                    }
                }
                imagesetpixel($im, $x, $y, $gray4 * 0x11);
            }
        }
    }

    private static function ditherGrayscaleImageRandomly($im) {
        if (imageistruecolor($im)) {
            return;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0 ; $x < $w; ++$x) {
                $gray8 = imagecolorat($im, $x, $y);
                $gray4 = 0;
                if ($gray8 < 8) {
                    $gray4 = 0;
                } else if ($gray8 >= 0xF8) {
                    $gray4 = 0xF;
                } else {
                    $gray4 = ($gray8 + mt_rand(-8, 7)) >> 4;
                }
                imagesetpixel($im, $x, $y, $gray4 * 0x11);
            }
        }
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