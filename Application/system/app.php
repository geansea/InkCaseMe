<?php

include_once('/tmp/system/system.php');

define('IMAGES_BASE', '/mnt/udisk/images/');
define('INDEX_FILE', '/tmp/images_index.txt');

file_put_contents(KEY_STAMP, time());
$key = isset($argv[1]) ? $argv[1] : 'click';

$images = getImages();
$size = sizeof($images);
if ($size == 0) {
    exit(0);
}

if (!file_exists(INDEX_FILE)) {
    file_put_contents(INDEX_FILE, '-1');
}
$imageIndex = intval(file_get_contents(INDEX_FILE));

if ($key == 'long_press') {
    // Refresh current image
    $imageIndex = ($imageIndex + $size) % $size;
    System::refreshScreen();
    System::showJpg($images[$imageIndex], false);
}

if ($key == 'click') {
    // Next
    $imageIndex = ($imageIndex + 1) % $size;
    System::showJpg($images[$imageIndex]);
}

if ($key == 'double_click') {
    // Previous
    $imageIndex = ($imageIndex - 1 + $size) % $size;
    System::showJpg($images[$imageIndex]);
}

file_put_contents(INDEX_FILE, $imageIndex);

function getImages() {
    $images = array();
    $imagesDir = opendir(IMAGES_BASE);
    while ($item = readdir($imagesDir)) {
        if ($item{0} == '.') {
            continue;
        }
        $info = pathinfo($item);
        if (strtolower($info['extension']) != 'jpg') {
            continue;
        }
        $images[] = IMAGES_BASE . $item;
    }
    sort($images);
    return $images;
}

function getCurrentIndex() {
    if (!file_exists(INDEX_FILE)) {
        file_put_contents(INDEX_FILE, '-1');
    }
    return intval(file_get_contents(INDEX_FILE));
}

?>