docker exec -it cmc_telehealth_app php artisan migrate:fresh --seed

php artisan queue:work --daemon