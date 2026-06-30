FROM node:24-alpine

WORKDIR /app

RUN chown node:node /app

COPY --chown=node:node apps/web/package*.json ./

USER node

RUN npm ci

COPY --chown=node:node apps/web ./

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
