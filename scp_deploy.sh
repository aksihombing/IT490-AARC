#!/bin/bash

scp -r deployment/ chizorom@172.28.121.220:/home/chizorom/
if [ $? -ne 0 ]; then
    echo "Failed to SCP."
    exit 1
fi
echo "--------- SUCCESS ---------"
echo "Sent deployment folder."
exit 0