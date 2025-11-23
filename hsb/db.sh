##!/bin/bash
LOG_FILE="/var/log/hsb_db_failover.log"
echo "starting the db takeover process on the backup vm" >> "$LOG_FILE"
echo "stopping the db service " >> "$LOG_FILE"
sudo systemctl stop mysql