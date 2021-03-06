<?php

include_once('/tmp/system/system.php');

define('VBUS_STATUS', '/sys/bus/platform/drivers/dwc_otg/vbus_status');
define('USB_ONLINE', '/sys/devices/platform/rockchip_battery/power_supply/usb/online');

// Get status early
file_put_contents(KEY_STAMP, time());
$lastVbus = getVbusStatus();
$lastOnline = getUsbOnline();

gotoWorkMode();

// Control lifecycle
while (true) {
    sleep(1);

    $vbus = getVbusStatus();
    $online = getUsbOnline();

    $delta = time() - file_get_contents(KEY_STAMP);
    if ($delta > 30 && !$vbus && !$online) {
        file_put_contents(KEY_STAMP, time());
        System::standBy();
        continue;
    }
    
    // Nothing changed
    if ($vbus == $lastVbus && $online == $lastOnline) {
        continue;
    }

    if (($vbus && !$lastVbus) && !$online) {
        // Just connected the USB
        gotoUsbMode();
    } else if ((!$vbus && $lastVbus) || (!$online && $lastOnline)) {
        // USB unconnected
        gotoWorkMode();
    }

    $lastVbus = $vbus;
    $lastOnline = $online;
}

// Charging or not
function getVbusStatus() {
    return intval(file_get_contents(VBUS_STATUS)) == 1;
}

// USB connected or not
function getUsbOnline() {
    return intval(file_get_contents(USB_ONLINE)) == 1;
}

function gotoUsbMode() {
    System::showUsb();
    system('umount /mnt/udisk');
    system('insmod /lib/g_file_storage.ko file=/dev/mtdblock5 stall=0 removable=1');
    file_put_contents(KEY_STAMP, time());
}

function gotoWorkMode() {
    system('rmmod g_file_storage.ko');
    system('umount /mnt/udisk');
    system('mount -t vfat -o iocharset=utf8 /dev/mtdblock5 /mnt/udisk');
    System::showLogo();
    file_put_contents(KEY_STAMP, time());
}

?>