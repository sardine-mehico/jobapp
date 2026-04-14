FROM node:22-alpine

LABEL org.opencontainers.image.source="https://github.com/sardine-mehico/jobapp"
LABEL org.opencontainers.image.description="JobApp Nuxt frontend"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /app

COPY package*.json ./
RUN npm install --no-audit --no-fund

# Accept build arguments for runtime config
ARG NUXT_PUBLIC_GA_ID
ENV NUXT_PUBLIC_GA_ID=${NUXT_PUBLIC_GA_ID}

ARG CACHE_BUST=1
COPY . .

RUN npm run build

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
