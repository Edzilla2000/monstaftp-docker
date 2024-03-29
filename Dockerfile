FROM ubuntu:20.04

LABEL maintainer="Jean Cardona"
LABEL description="Project to allow you to run MonstaFTP (https://www.monstaftp.com/) in Docker"

RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
   apache2 php7.4  libapache2-mod-php7.4 \
   && apt-get clean \
   && rm -rf /var/lib/apt/lists/*

RUN a2enmod php7.4
RUN a2enmod rewrite

# Update the PHP.ini file, enable <? ?> tags and quieten logging.
RUN sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php/7.4/apache2/php.ini
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php/7.4/apache2/php.ini
RUN sed -i "s/memory_limit = .*$/memory_limit = 1024M/" /etc/php/7.4/apache2/php.ini
RUN sed -i "s/post_max_size = .*$/post_max_size = 2048M/" /etc/php/7.4/apache2/php.ini
RUN sed -i "s/upload_max_filesize = .*$/upload_max_filesize = 2048M/" /etc/php/7.4/apache2/php.ini
RUN sed -i "s/max_execution_time = .*$/max_execution_time = 1800/" /etc/php/7.4/apache2/php.ini


ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80



COPY mftp /var/www/mftp

ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

CMD /usr/sbin/apache2ctl -D FOREGROUND

