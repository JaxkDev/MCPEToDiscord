#!/bin/bash

echo "+--------------------------------+"
echo "| MCPEToDiscord by Jackthehaxk21 |"
echo "+--------------------------------+"

  rm -f MCPEToDiscord.phar

# Check if phar.readonly is Off
if [ `php -r 'print ini_get("phar.readonly") ? "false" : "true";'` == false ]; then
    echo "PHAR creation is not enabled in your php.ini. Please set phar.readonly = Off and try again."
    exit 1
fi

phar pack -c gz -f MCPEToDiscord.phar -x "(.git|.idea|CONTRIBUTING.md|tests|phpunit.xml|.travis.yml)" .
