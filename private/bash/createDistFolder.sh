#!/bin/bash

if [[ $# -eq 0 ]] ; then
    echo 'You need to supply the dist folder'
    exit 1
fi

mkdir -p "$1"
cp ./LICENSE "$1"/LICENSE
cp ./composer.json "$1"/composer.json
cp ./woocommerce-myparcel.php "$1"/woocommerce-myparcel.php
cp ./wpm-config.json "$1"/wpm-config.json
cp -r ./assets "$1"
cp -r ./includes "$1"
cp -r ./languages "$1"
cp -r ./migration "$1"
cp -r ./templates "$1"
cp -r ./vendor "$1"
