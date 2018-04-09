#!/bin/bash
mysql -hlocalhost -utask -ptask task < /www/migrations/init.sql
