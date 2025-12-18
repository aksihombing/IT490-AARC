#!/bin/bash
# backend rabbitmq setup in the event it wipes itself again after cloning
USER=saas_user
PASS='p@ssw0rd'
VHOST=/saas

# creates the vhost, user, and sets permissions
sudo rabbitmqctl add_vhost $VHOST
sudo rabbitmqctl add_user $USER "$PASS"
sudo rabbitmqctl set_permissions -p $VHOST $USER ".*" ".*" ".*"


# auth exchanges/queues/bindings
rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare exchange name=auth.direct type=direct durable=true

for q in auth.register auth.login auth.validate auth.logout;
do
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare queue name=$q durable=true
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare binding source=auth.direct destination_type=queue destination=$q routing_key=$q
done


# library exchanges/queues/bindings
rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare exchange name=library.direct type=direct durable=true

for q in library.search library.details library.collect library.personal library.review;
do
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare queue name=$q durable=true
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare binding source=library.direct destination_type=queue destination=$q routing_key=$q
done

# club exchanges/queues/bindings
rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare exchange name=club.direct type=direct durable=true

for q in club.create;
do
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare queue name=$q durable=true
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare binding source=club.direct destination_type=queue destination=$q routing_key=$q
done

# log exchange/queues NO ROUTING KEY
rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare exchange name=logs.fanout type=fanout durable=true

for q in logs.frontend logs.backend logs.dmz;
do
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare queue name=$q durable=true
  rabbitmqadmin -V $VHOST -u $USER -p "$PASS" declare binding source=logs.fanout destination_type=queue destination=$q
done