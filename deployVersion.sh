#!/bin/bash

if test -f "/usr/bin/php7.4"; then
  PHP_BIN="/usr/bin/php7.4"
else
  PHP_BIN="/usr/bin/php"
fi

deployDate=$(date "+%Y-%m-%d %X %z")
git fetch --all
git fetch --tags
git checkout $1
gitHashCommit=$(git rev-parse --short $1)
echo '<?php' > 'version.php';
echo "\$gitHash='$gitHashCommit';">>  'version.php';
echo "\$gitBranch='$1';">>  'version.php';
echo "\$deployDate='$deployDate';">>  'version.php';
$PHP_BIN composer.phar install -o --no-dev
yarn encore production

