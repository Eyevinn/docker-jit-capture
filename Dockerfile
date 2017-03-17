FROM eyevinntechnology/packager-base:0.1.0
MAINTAINER Eyevinn Technology <info@eyevinn.se>
RUN apt-get update && apt-get install -y --force-yes \
  apache2 \
  libapache2-mod-php5 \
  curl
RUN pip install hlsorigin
RUN mkdir -p /var/capture && \
  chown www-data.www-data /var/capture
COPY www/ /var/capture/
COPY capture/config/apache2/ /etc/apache2/
COPY capture/php/ /var/capture/
COPY entrypoint.sh /root/entrypoint.sh
EXPOSE 80
CMD /root/entrypoint.sh
