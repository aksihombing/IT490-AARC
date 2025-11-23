#!/bin/bash
LOG_FILE="/var/log/hsb_db_failover.log"

echo "starting db takeover process on the backup vm" >> "$LOG_FILE"
echo "stopping sql server service to break replication link" >> "$LOG_FILE"
sudo systemctl stop mssql-server

echo "forcing primary role on sql server" >> "$LOG_FILE"
/opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P 'pccc' -Q "ALTER DATABASE [userdb] SET PRIMARY" >> "$LOG_FILE" 2>&1

echo "restarting sql server service as new primary" >> "$LOG_FILE"
sudo systemctl restart mssql-server

sleep 5
exit 0