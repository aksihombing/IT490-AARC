#!/bin/bash

# FOR [FRONTEND]
# moves files + restarts apache2

echo 'Installing bookFeatures'


# move folders if needed
sudo mv -r frontend/* /var/www/aarc/
if [ $? -ne 0 ]; then
    echo "Failed to move files."
    exit 1
fi


# processes to bounce
sudo service apache2 restart
if [ $? -ne 0 ]; then
    echo "Failed restart apache2."
    exit 1
fi


echo "-----Installation complete-----"
exit 0