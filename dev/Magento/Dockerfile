FROM alexcheng/magento2:2.2.3

COPY *.json /var/www/html/
COPY magento-install /opt/
COPY wait-for-it.sh /opt/wait-for-it.sh
COPY docker-healthcheck /usr/local/bin/

WORKDIR /var/www/html

HEALTHCHECK --retries=20 CMD ["docker-healthcheck"]



