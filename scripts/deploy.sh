sudo cp /var/www/growth-sessions/.env /home/github/temp/.env

timestamp=$(date +'%Y-%m-%d-%T')
echo "${timestamp}"

pathToDeploy="/var/www/growth-sessions/${timestamp}"

sudo cp -r /home/github/temp ${pathToDeploy}
sudo chown -R www-data:www-data ${pathToDeploy}

sudo find ${pathToDeploy} -type f -exec chmod 644 {} \;
sudo find ${pathToDeploy} -type d -exec chmod 755 {} \;

cd ${pathToDeploy}

sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

sudo rm /var/www/growth-sessions-current
sudo ln -s ${pathToDeploy} /var/www/growth-sessions-current
