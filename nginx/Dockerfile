FROM nginx:alpine

# Copia tu configuración personalizada de nginx
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/conf.d/ /etc/nginx/conf.d/
COPY public/ /var/www/html/

# Ajusta permisos para nginx (usuario: nginx)
RUN chown -R nginx:nginx /etc/nginx /var/www/html \
    && chmod -R 755 /etc/nginx /var/www/html

# Opcional: asegúrate de que los archivos de configuración sean legibles
RUN chmod -R 644 /etc/nginx/conf.d/*

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]