#!/bin/bash
# SCRAPPED because im silly
# ./bundle.sh {BUNDLENAME} serves as a utility script

# generic bundle shell script
# script is the first argument ($0)

# needs 2 arguments ($1, $2)
# + name of tar/bundle (database in deploy vm will keep track of the number)
# + file path of what to bundle into tarball thing

if [ $# -lt 1 ]; then
    echo "Please enter the arguments: $0 <bundle_name>"
    exit 1
fi

BUNDLE_NAME=$1

php bundle.php "$BUNDLE_NAME"
if [ $? -ne 0 ]; then
    echo "Failed to send tarball to messaging queue."
    exit 1
fi


echo "$BUNDLE_NAME : Successfully sent $SOURCE via RMQ"
# this is a utility script, it just needs to know a valid bundle_name and bundle.php will handle the rest !