docker exec -it cmc_telehealth_app php artisan migrate:fresh --seed
docker compose up -d --build
php artisan queue:work --daemon
docker exec -it cmc_telehealth_app php artisan make:migration add_sub_bio_to_doctors_table --table=doctors
