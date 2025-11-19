#!/bin/bash

# FOR [BACKEND]
# moves files (?) + installs(?)/restarts daemon + update database (?)
# https://stackoverflow.com/questions/31820750/run-sql-file-in-database-from-terminal
# https://unix.stackexchange.com/questions/187005/add-cron-job-via-single-command

echo 'Installing bookData'

# move folders if needed
sudo mv -r backend/ /home/
if [ $? -ne 0 ]; then
    echo 'Failed to move files.'
    exit 1
fi

# processes to bounce
sudo mysql -u 'apiAdmin' -p 'aarc490' 'apidb' < 'backend/api_db/library_cache.sql'
if [ $? -ne 0 ]; then
    echo 'Failed to updated library_cache table.'
    exit 1
fi
sudo mysql -u 'apiAdmin' -p 'aarc490' 'apidb' < 'backend/api_db/cron/recentBooks.sql'
if [ $? -ne 0 ]; then
    echo 'Failed to update cron table for recentBooks'
    exit 1
fi


# update and run crontab
chmod +rx cron/updateRecentBooks.php
{ crontab -l; echo '0 10 * * * /usr/bin/php /home/cron/updateRecentBooks.php >> /home/cron/recentBooks.log.txt 2>&1'; } | crontab -
if [ $? -ne 0 ]; then
    echo 'Failed to update crontab job for recentBooks'
    exit 1
fi

# forcibly run the script to update table again
/usr/bin/php /home/cron/updateRecentBooks.php >> /home/cron/recentBooks.log.txt 2>&1
if [ $? -ne 0 ]; then
    echo 'Failed to force-run updateRecentBooks.php'
    exit 1
fi

echo '-----Installation complete-----'
exit 0