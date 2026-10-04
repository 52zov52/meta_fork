FROM node:22-alpine

ENV CI=true
ENV NODE_ENV=development

WORKDIR /app

RUN corepack enable pnpm
COPY package.json pnpm-lock.yaml ./
RUN pnpm i

COPY . .

EXPOSE 3000
CMD ["pnpm", "run", "dev"]
