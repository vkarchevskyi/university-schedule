FROM node:24-alpine AS build

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@10.19.0 --activate

COPY frontend/package.json frontend/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY frontend/ .

ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}

RUN pnpm build-only

FROM caddy:2-alpine AS prod

COPY --from=build /app/dist /srv/app
COPY docker/frontend/Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443
