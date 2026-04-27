# CMC Telehealth PWA (Doctor Dashboard)

A Progressive Web App for doctors working with CMC Telehealth, built using [Next.js](https://nextjs.org).

## Prerequisites
- [Docker](https://www.docker.com/products/docker-desktop) installed on your system.
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop).

## Getting Started with Docker

You can easily run the production build of this project locally without installing Node.js by using Docker.

### 1. Project Setup
Ensure you have the project files on your local machine and navigate to the project directory:
```bash
cd cmc-telehealth-pwa-patient
```

### 2. Environment Variables
You do not need to commit a `.env` file to GitHub for production.

For this project, `NEXT_PUBLIC_API_BASE_URL` is a client-exposed Next.js variable, which means it is baked into the app during `npm run build`. The Dockerfile creates `.env.production` inside the image during the build using the `NEXT_PUBLIC_API_BASE_URL` build argument.

That means your VPS or CI/CD pipeline must provide `NEXT_PUBLIC_API_BASE_URL` before `docker compose up --build` or `docker build` runs.

### 3. Build and Start the Container
Use Docker Compose to build the image and run the container in detached mode:
```bash
export NEXT_PUBLIC_API_BASE_URL=https://telehealthwebapplive.cmcludhiana.in/api/v2
docker-compose up --build -d
```
*This command uses the `Dockerfile` to install dependencies, build the optimized Next.js app, and start the application server.*

If you want to build manually without Docker Compose:
```bash
docker build \
  --build-arg NEXT_PUBLIC_API_BASE_URL=https://telehealthwebapplive.cmcludhiana.in/api/v2 \
  -t cmc-telehealth-pwa-patient .
```

### 4. Access the Application
Once the container is up and running, open your web browser and navigate to:
**[http://localhost:8001](http://localhost:8001)**

*(Note: The Docker configuration maps the exposed Next.js server to port `8001` on your host machine).*

### 5. Managing the Container
To view logs:
```bash
docker-compose logs -f
```

To stop and remove the running container:
```bash
docker-compose down
```

---

## Local Development (Without Docker)

If you prefer to run the application for active development without Docker:
```bash
# Install dependencies
npm install

# Start the development server
npm run dev
```
Then, open [http://localhost:3000](http://localhost:3000) with your browser.
