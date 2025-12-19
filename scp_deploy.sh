#!/bin/bash

scp -r deployment/ aarc-deploy@172.28.121.220:/home/aarc-deploy/
if [ $? -ne 0 ]; then
    echo "Failed to SCP."
    exit 1
fi
echo "--------- SUCCESS ---------"
echo "Sent deployment folder."
exit 0