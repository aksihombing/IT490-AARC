##!/bin/bash
LOG_FILE="/var/log/hsb_db_failover.log"
echo "starting the db takeover process on the backup vm" >> "$LOG_FILE"
echo "stopping the db service " >> "$LOG_FILE"
sudo systemctl stop mysql

echo "forcing primary role on my sql" >> "$LOG_FILE"
/opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P 'pccc' -Q "ALTER DATABASE [userdb] SET PRIMARY" >> "$LOG_FILE" 2>&1
