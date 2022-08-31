timestamp=$(date +'%Y-%m-%d-%T')
echo "${timestamp}"

pathToDeploy="/var/www/growth-sessions/${timestamp}"

cp -r test ${pathToDeploy}
chown -R www-data:www-data ${pathToDeploy}

find ${pathToDeploy} -type f -exec chmod 644 {} \;
find ${pathToDeploy} -type d -exec chmod 755 {} \;

cd ${pathToDeploy}

chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
