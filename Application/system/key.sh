#!/bin/sh

if [ ! -e /mnt/udisk/system/app.php ]; then
	exit
fi

if [ code_$1 == code_61 ]; then
	# Wake-up
	/opt/bin/php /mnt/udisk/system/app.php click
	exit
fi

if [ code_$1 == code_28 ]; then
	# Click
	/opt/bin/php /mnt/udisk/system/app.php click
	exit
fi

if [ code_$1 == code_66 ]; then
	# Double-click
	/opt/bin/php /mnt/udisk/system/app.php double_click
	exit
fi

if [ code_$1 == code_63 ]; then
	# Long-press
	/opt/bin/php /mnt/udisk/system/app.php long_press
	exit
fi

