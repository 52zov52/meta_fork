# Meta Frontend

- Node.js (версия в [.nvmrc](.nvmrc))

## Локальная разработка

**Через Docker**

```bash
cp .env.example .env

docker compose watch
```

**Linux/WSL**

```bash
cp .env.example .env

curl -o- https://fnm.vercel.app/install | bash
fnm use
corepack enable pnpm
pnpm i

pnpm dev
```

Сайт: [http://localhost:3000](http://localhost:3000)

