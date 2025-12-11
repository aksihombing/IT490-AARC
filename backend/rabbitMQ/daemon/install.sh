#!/bin/bash
# Copy .service file into /etc/systemd/system
sudo cp rabbitmqserver.service /etc/systemd/system
sudo cp loglistener.service /etc/systemd/system
# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable rabbitmqserver.service
sudo systemctl enable loglistener.service

# Start daemon
sudo systemctl start rabbitmqserver.service
sudo systemctl start loglistener.service
# User Notification
echo Rabbitmqserver daemon installation successful!
