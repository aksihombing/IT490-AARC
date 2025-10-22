#!/bin/bash
USER=saas_user
PASS='p@ssw0rd'

# creates the vhost, user, and sets certain permissions. unsure if working 100%
sudo rabbitmqctl add_vhost /saas
sudo rabbitmqctl add_user $USER $PASS
sudo rabbitmqctl set_permissions -p /saas $USER "^auth\.direct$" "^auth\.(register|login|validate|logout)$" "^auth\.(register|login|validate|logout)$"


# creates the exchange
rabbitmqadmin -V /saas -u $USER -p $PASS declare exchange name=auth.direct type=direct durable=true


# creates the queues and bindings
for q in auth.register auth.login auth.validate auth.logout; do
  rabbitmqadmin -V /saas -u $USER -p $PASS declare queue name=$q durable=true
  rabbitmqadmin -V /saas -u $USER -p $PASS declare binding source=auth.direct destination_type=queue destination=$q routing_key=$q
done

