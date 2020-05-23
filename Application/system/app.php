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

// Here it maybe -1
$imageIndex = getCurrentIndex();

if ($key == 'click') {
    // Next
    $imageIndex = ($imageIndex + 1) % $size;
    System::showJpg($images[$imageIndex]);
}

if ($imageIndex < 0) {
    $imageIndex = 0;
}

putCurrentIndex($imageIndex);

if ($key == 'double_click') {
    // Refresh current image
    System::refreshScreen();
    System::showJpg($images[$imageIndex], true);
}

if ($key == 'long_press') {
    // Shut down
    System::shutDown();
}

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

function putCurrentIndex($index) {
    file_put_contents(INDEX_FILE, $index);
}

?>