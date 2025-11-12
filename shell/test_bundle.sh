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

# need to check -d directory actually exists + verify success
if [! -d "$FILE_PATH"]; then
    echo "File path to '$FILE_PATH' not found."
fi

# c-create z-zip f-output file -C change directory
tar -czf "$TAR_NAME" -C "$FILE_PATH" .
if [$? -ne 0]; then
    echo "Failed to create tarball."
    exit 1
fi

# $? = the exit status of the last command ran, exit status 0 usually means success; anything else means fail/error

# tarball -> php script -> sends into RMQ -> received by Deploy VM
php test_bundle.php "$TAR_NAME"
if [$? -ne 0]; then
    echo "Failed to send tarball to messaging queue."
    exit 1
fi

echo "$BUNDLE_NAME : Successfully sent $SOURCE via RMQ"

# need to SCP bundle into destination i think
