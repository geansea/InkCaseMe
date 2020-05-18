#!/bin/sh

# Copy files to /tmp in case unmount
cp -R /mnt/udisk/system /tmp
cp -R /mnt/udisk/resources /tmp

# Run
/opt/bin/php /tmp/system/main.php &

# Listen button in background
chmod +x /tmp/system/button
/tmp/system/button &
