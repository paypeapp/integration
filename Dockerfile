FROM ubuntu:latest
MAINTAINER robert@paype.com

# install php
RUN apt-get update && apt-get install -y php5-cli php5-curl rsyslog && apt-get clean
RUN echo "error_log = /var/log/paype.log" >> /etc/php5/cli/php.ini
RUN echo "memory_limit = 512M" >> /etc/php5/cli/php.ini

# copy files
WORKDIR /var/www
ADD *.php /var/www/
ADD wsInterfaces /var/www/wsInterfaces

RUN touch /var/log/paype.log

# cron
ADD crontab /etc/crontab

CMD rsyslogd && cron && tail -f /var/log/syslog /var/log/paype.log