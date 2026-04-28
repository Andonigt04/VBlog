FROM nginx:alpine

COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d/ /etc/nginx/conf.d/

RUN chmod -R 755 /etc/nginx/conf.d && \
    chmod 644 /etc/nginx/nginx.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]