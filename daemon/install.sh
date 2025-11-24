#!/bin/bash
# Copy file into systemd
sudo cp rabbitmqserver.service /etc/systemd/system
sudo cp loglistener.service /etc/systemd/system

# Reload daemon
systemctl daemon-reload

# Enable daemon
sudo systemctl enable rabbitmqserver.service
sudo systemctl enable loglistener.service
# Start daemon (I don't think we need to restart the whole computer. I may change that on the dmz machine later.)
sudo systemctl start rabbitmqserver.service
sudo systemctl start loglistener.service
