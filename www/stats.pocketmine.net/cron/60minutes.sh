#!/usr/local/bin/bash

# executed every 5 minutes

cd /data/www/stats.pocketmine.net/cron/

php -q rank-plugins.php
