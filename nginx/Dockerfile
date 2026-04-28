FROM nginx:alpine

COPY . /etc/nginx/

RUN chmod -R 755 /etc/nginx/

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]