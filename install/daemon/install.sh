#!/bin/bash
# Copy .service file into /etc/systemd/system
sudo cp installbundle.service /etc/systemd/system
# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable installbundle.service

# Start daemon
sudo systemctl start installbundle.service
# User Notification
echo Install_bundle daemon installation successful!
