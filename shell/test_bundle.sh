#!/bin/bash


# generic bundling shell script draft

#./test_bundle.sh bundleTest /home/Chizzy/test.txt
# script is the first argument ($0)

# needs 2 arguments ($1, $2)
# + name of tar/bundle (database in deploy vm will keep track of the number)
# + file path of what to bundle into tarball thing

if [ $# -lt 2 ]; then
    echo "Please enter the arguments: $0 <bundle_name> <source_file_path>"
    exit 1
fi


BUNDLE_NAME=$1
FILE_PATH=$2
TAR_NAME="${BUNDLE_NAME}.tar.gz"

# c-create || z-zip f-output file || -v verbose || -C change directory || . selects all contents in directory
# -v is good to use for debugging
# NOTE: IT TARS FOLDERS not individual files
tar -czf "$TAR_NAME" -C "$FILE_PATH" .
if [ $? -ne 0 ]; then
    echo "Failed to create tarball."
    exit 1
fi

# $? = the exit status of the last command ran, exit status 0 usually means success; anything else means fail/error

# tarball -> php script -> sends into RMQ -> received by Deploy VM
php test_bundle.php "$TAR_NAME"
if [ $? -ne 0 ]; then
    echo "Failed to send tarball to messaging queue."
    exit 1
fi


echo "$BUNDLE_NAME : Successfully sent $SOURCE via RMQ"

# need to SCP bundle into destination i think ? i dont know if this needs to be in the bundle script itself
#sshpass -p "passw0rd" ssh aida@172.28.219.213 "scp (source) aida@172.28.219.213:/home/aida/"
# scp (source) (destination)
scp "$TAR_NAME" aida@172.28.219.213:/home/aida/bundles/
if [ $? -ne 0 ]; then
    echo "Failed to SCP file to destination."
    exit 1
fi
echo "Sent $BUNDLE_NAME via SCP"