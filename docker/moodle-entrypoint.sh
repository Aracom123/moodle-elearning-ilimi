#!/bin/sh
set -eu

cd /var/www/moodle

if [ ! -f vendor/autoload.php ]; then
    echo "Installing PHP dependencies..."
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

until php -r '
    if (getenv("MOODLE_DB_TYPE") === "mysqli") {
        $connection = @new mysqli(
            getenv("MOODLE_DB_HOST"),
            getenv("MOODLE_DB_USER"),
            getenv("MOODLE_DB_PASSWORD"),
            getenv("MOODLE_DB_NAME")
        );
        exit($connection->connect_errno ? 1 : 0);
    }
    $connection = @pg_connect("host=" . getenv("MOODLE_DB_HOST")
        . " dbname=" . getenv("MOODLE_DB_NAME")
        . " user=" . getenv("MOODLE_DB_USER")
        . " password=" . getenv("MOODLE_DB_PASSWORD"));
    exit($connection === false ? 1 : 0);
'; do
    echo "Waiting for the database..."
    sleep 2
done

cp /usr/local/share/moodle-config.php config.php
chown -R www-data:www-data /var/www/moodledata

if ! php -r '
    $connection = new mysqli(
        getenv("MOODLE_DB_HOST"),
        getenv("MOODLE_DB_USER"),
        getenv("MOODLE_DB_PASSWORD"),
        getenv("MOODLE_DB_NAME")
    );
    $table = $connection->real_escape_string(getenv("MOODLE_DB_PREFIX") . "config");
    $result = $connection->query("SHOW TABLES LIKE \"{$table}\"");
    exit($result && $result->num_rows > 0 ? 0 : 1);
'; then
    echo "Installing Moodle..."
    runuser -u www-data -- php admin/cli/install_database.php \
        --agree-license \
        --fullname="${MOODLE_SITE_NAME}" \
        --shortname="${MOODLE_SITE_SHORTNAME}" \
        --adminuser="${MOODLE_ADMIN_USER}" \
        --adminpass="${MOODLE_ADMIN_PASSWORD}" \
        --adminemail="${MOODLE_ADMIN_EMAIL}"
fi

exec docker-php-entrypoint "$@"
