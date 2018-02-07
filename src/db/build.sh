#!/usr/bin/env bash

mysql -h mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /data/www/vendor/jasongrimes/silex-simpleuser/sql/mysql.sql
mysql -h mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /data/www/src/db/migrations/1.create_initial_tables.sql
mysql -h mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /data/www/src/db/standingdata.sql