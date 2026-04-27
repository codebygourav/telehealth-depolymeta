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
cd cmc-telehealth-pwa-doctor
```

### 2. Environment Variables
Ensure you have a `.env` file in the root directory. This is required by `docker-compose.yml` to pass configuration variables into the container. Follow the .env.sample file to create your .env file. 

### 3. Build and Start the Container
Use Docker Compose to build the image and run the container in detached mode:
```bash
docker-compose up --build -d
```
*This command uses the `Dockerfile` to install dependencies, build the optimized Next.js app, and start the application server.*

### 4. Access the Application
Once the container is up and running, open your web browser and navigate to:
**[http://localhost:8000](http://localhost:8000)**

*(Note: The Docker configuration maps the exposed Next.js server to port `8000` on your host machine).*

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
