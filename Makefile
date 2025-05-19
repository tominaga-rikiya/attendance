up:
	docker-compose up -d
	
cache:
	docker-compose exec php php artisan cache:clear 
	docker-compose exec php php artisan config:cache 
stop:
	docker-compose stop
php:
	docker compose exec php bash
	