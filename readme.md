README!!!
=============

PHP packages Instalation
------------------------
Php packages are installed via composer like this:

	composer require mpdf/mpdf

If composer.lock file has been changed DO NOT use composer update. Istead of it delete all vendor files and 
run command:

	composer install
 
Dependecies will be installed according composer.lock file. So packages versions will be the same as before. 

CSS/JS Installation
-------------------
All packages are installed via npm like this

	cd www
	npm install jquery

MongoDB Connection
------------------
MongoDB connection has to have rigth params in config.local.neon. 
Use _id params to create relations between json documents stored in database.

