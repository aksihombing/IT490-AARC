#!/bin/bash
# Copy .service file into /etc/systemd/system
sudo cp libraryapi.service /etc/systemd/system

# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable libraryapi.service

# Start daemon
sudo systemctl start libraryapi.service

# User Notification
echo Libraryapi daemon installation successful!
