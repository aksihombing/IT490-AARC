crontab -l | grep 'updateRecentBooks.php'

# if the job exists, we dont need to update its cron
if [ $? -eq 1 ]; then echo 'Cron job updateRecenBooks.php already exists.'; exit 1; fi

# if it doesnt exist, make sure to re-make the cronjob
crontab -l; echo '0 10 * * * /usr/bin/php /home/aida/cron/updateRecentBooks.php >> /home/aida/cron/recentBooks.log.txt 2>&1' | crontab -

if [ $? -ne 0 ]; then echo 'Failed to install cronjob.'; exit 1; fi
