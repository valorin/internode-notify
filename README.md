Internode Notify
================

Simple script to send notifications when the rate of download usage for internode changes dramatically.

Copy config.default.php to config.php and edit to meet your requirmenets.

Set notify.php to run every hour or so via cron. It will check the current usage and calculate the daily
download target to not go over your download limit. If this target changes by 10% (customised in config.php)
fron the last notified value, an email will be sent with the remaining download limit and usage target.

Warning: The code isn't that pretty, I'll clean it up later.

Credits
-------

I used functions from the fantastic 'internode-php' project.

http://archive.cafuego.net/internode-usage.php 

Written by Peter Lieverdink <me@cafuego.net>
Copyright 2004 Intellectual Property Holdings Pty. Ltd.

License: GPL; See http://www.gnu.org/copyleft/gpl.html#SEC1 for a full version.

