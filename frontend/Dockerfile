# frontend/Dockerfile
# context: ./frontend

FROM node:20-alpine AS base
WORKDIR /app
COPY package*.json ./
RUN npm ci

# ---------- dev ----------
FROM base AS dev
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev"]

# ---------- prod ----------
FROM base AS prod
COPY . .
RUN npm run build