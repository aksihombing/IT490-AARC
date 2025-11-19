#!/bin/bash

# FOR [DMZ]
# moves files (?) + installs(?)/restarts daemon
# similar to the databaseProcess

echo 'Installing apiProcess (DMZ RabbitMQ Processor)'
# move folders if needed
sudo mv -r api/ /home/
if [ $? -ne 0 ]; then
    echo "Failed to move files."
    exit 1
fi
# processes to bounce
sudo systemctl restart libraryapi.service
if [ $? -ne 0 ]; then
    echo "Failed to restart libraryapi.service."
    exit 1
fi

echo "-----Installation complete-----"
exit 0