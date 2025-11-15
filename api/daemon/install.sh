#!/bin/bash
# Copy .service file into /etc/systemd/system
sudo cp libraryapi.service /etc/systemd/system

# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable libraryapi.service

# Reboot system
echo libraryapi daemon installation successful! Rebooting in 10 seconds...
sleep 10
reboot
