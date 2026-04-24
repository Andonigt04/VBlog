FROM php:8.3-fpm

FROM nginx:alpine

COPY nginx/nginx.conf /etc/nginx/conf.d/nginx.conf

COPY src/public /var/www/html/public

EXPOSE 80
