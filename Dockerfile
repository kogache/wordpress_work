FROM wordpress:latest

RUN apt-get -y update && apt-get install -y ssmtp mailutils

# メール送信設定。一旦devのみ。
COPY ./sendmail.ini /usr/local/etc/php/conf.d/
COPY ./ssmtp.conf /etc/ssmtp/
