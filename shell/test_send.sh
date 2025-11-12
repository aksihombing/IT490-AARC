#!/bin/bash

# information to remote host/user
USERNAME=""
HOST=""
SCRIPT=""
SENDFILE=""



ssh -l ${USERNAME} ${HOST} "${SCRIPT}"

scp -P aarc490 -p ${SENDFILE} ${USERNAME}@${HOST}



