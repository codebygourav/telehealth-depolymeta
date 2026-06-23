## Run after every deploy on Hostinger

docker exec -it tele-backend-app php artisan storage:link
docker exec -it tele-backend-app php artisan config:clear
docker exec -it tele-backend-app php artisan cache:clear
docker exec -it tele-backend-app php artisan migrate

## Rebuild containers (after code push)

For Patient  - docker compose -f docker-compose.prod.yml up -d --build patient
For Doctor   - docker compose -f docker-compose.prod.yml up -d --build doctor
For Backend  - docker compose -f docker-compose.prod.yml up -d --build db app web

## Seeder

docker exec -it tele-backend-app php artisan migrate:fresh --seed

## Queue

docker exec tele-backend-app php artisan queue:work --daemon

## Other

docker exec tele-backend-app php artisan vaccinations:send-reminders


##  if face error while uploading: 
run these commands:

## docker exec -it tele-backend-app bash
ls -la /var/www/html/storage
ls -la /var/www/html/storage/logs
ls -la /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache