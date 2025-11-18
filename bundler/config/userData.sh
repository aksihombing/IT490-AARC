#!/bin/bash

# FOR [BACKEND]
# moves files (?) + installs(?)/restarts daemon + update database (?)
# https://stackoverflow.com/questions/31820750/run-sql-file-in-database-from-terminal

echo 'Installing userData'


# move folders if needed
sudo mv -r backend/ /home/
if [ $? -ne 0 ]; then
    echo "Failed to move files."
    exit 1
fi


# processes to bounce
sudo mysql -u 'userAdmin' -p 'aarc490' 'userdb' < 'userdb.sql'

echo "-----Installation complete-----"
exit 0