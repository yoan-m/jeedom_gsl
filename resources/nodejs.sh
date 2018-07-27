#!/bin/bash
cd $1
touch /tmp/${2}_dep
echo "Début de l'installation"

echo 0 > /tmp/${2}_dep

sh dependencies.sh ${1} ${2}

rm /tmp/${2}_dep

echo "Fin de l'installation"