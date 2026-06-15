docker exec -it cmc_telehealth_app php artisan migrate:fresh --seed

php artisan queue:work --daemon

For Patient - docker compose -f docker-compose.prod.yml up -d --build patient
For Doctor - docker compose -f docker-compose.prod.yml up -d --build doctor
For Backend - docker compose -f docker-compose.prod.yml up -d --build db app web
