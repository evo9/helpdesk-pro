# docker/nginx/Dockerfile
FROM nginx:1.25-alpine
ARG ENV=dev
COPY ${ENV}.conf /etc/nginx/conf.d/default.conf