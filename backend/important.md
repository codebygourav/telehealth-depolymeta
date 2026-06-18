docker exec -it tele-backend-app php artisan migrate:fresh --seed

docker exec tele-backend-app php artisan vaccinations:send-reminders


php artisan queue:work --daemon

For Patient - docker compose -f docker-compose.prod.yml up -d --build patient
For Doctor - docker compose -f docker-compose.prod.yml up -d --build doctor
For Backend - docker compose -f docker-compose.prod.yml up -d --build db app web
