#!/bin/bash
# Copy .service file into /etc/systemd/system
sudo cp libraryapi.service /etc/systemd/system
sudo cp loglistener.service /etc/systemd/system
# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable libraryapi.service
sudo systemctl enable loglistener.service

# Start daemon
sudo systemctl start libraryapi.service
sudo systemctl start loglistener.service
# User Notification
echo Libraryapi daemon installation successful!
