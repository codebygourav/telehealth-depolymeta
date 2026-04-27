FROM node:20-alpine

WORKDIR /app

ARG NEXT_PUBLIC_API_BASE_URL
ENV NEXT_PUBLIC_API_BASE_URL=$NEXT_PUBLIC_API_BASE_URL
ENV PORT=3000
ENV HOSTNAME=0.0.0.0

COPY package*.json ./
RUN npm install

COPY . .

RUN printf "NEXT_PUBLIC_API_BASE_URL=%s\n" "$NEXT_PUBLIC_API_BASE_URL" > .env.production
RUN npm run build
RUN cp -r public .next/standalone/ && mkdir -p .next/standalone/.next && cp -r .next/static .next/standalone/.next/

WORKDIR /app/.next/standalone

EXPOSE 3000

CMD ["node", "server.js"]
