#!/bin/bash

echo 'Installing apiProcess (DMZ RabbitMQ Processor)'

sudo mv api/ /home/

if [ $? -ne 0 ]; then echo 'Failed to move files.'; exit 1; fi

sudo systemctl restart libraryapi.service

if [ $? -ne 0 ]; then echo 'Failed to restart libraryapi.service.'; exit 1; fi

echo '-----Installation complete-----'

exit 0