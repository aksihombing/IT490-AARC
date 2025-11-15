#!/bin/bash

#might need to instal sshpass because it prompts for passwords
#ssh aida@172.28.219.213
#scp chizorom@172.28.108.126:/home/chizorom/test.txt aida@172.28.219.213:/home/aida/



# apt-get install sshpass
# TEMPLTATE : sshpass -p 'my_password' ssh -t test-admin@my_ip "sudo su c command_must_be_run_root --arguments"

sudo sshpass -p "passw0rd" ssh -t aida@172.28.219.213 "

