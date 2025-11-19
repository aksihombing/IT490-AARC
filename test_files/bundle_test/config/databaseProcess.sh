#!/bin/bash

# FOR [BACKEND]
# moves files (?) + installs(?)/restarts daemon

echo 'Installing databaseProcess (Backend RabbitMQ Processor)'

# move folders if needed
sudo mv -r backend/ /home/
if [ $? -ne 0 ]; then
    echo 'Failed to move files.'
    exit 1
fi

# processes to bounce
# NEED TO UPDATE SERVICE FILE NAME
sudo systemctl restart rabbitmqserver.service
if [ $? -ne 0 ]; then
    echo 'Failed to restart rabbitmqserver.service.'
    exit 1
fi


echo '-----Installation complete-----'
exit 0