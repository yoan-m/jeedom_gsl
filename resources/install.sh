PROGRESS_FILE=/tmp/dependancy_gsl_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}

echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
BASEDIR=$(dirname "$0")
cd $BASEDIR
echo 10 > ${PROGRESS_FILE}
if [ -x /usr/bin/nodejs ]; then
  actual=`nodejs -v | awk -F v '{ print $2 }' | awk -F . '{ print $1 }'`;
  echo "Version actuelle : ${actual}"
else
  actual=0;
  echo "Nodejs non installé"
fi
sudo apt-get update
sudo apt-get -y install lsb-release
release=$(lsb_release -c -s)
if [ $actual -ge 8 ]; then
  echo "Ok, version suffisante";
else
  echo "KO, version obsolète à upgrader";
  echo "Suppression du Nodejs existant et installation du paquet recommandé"
  sudo apt-get -y --purge autoremove nodejs npm
  arch=$(arch);
  echo 30 > ${PROGRESS_FILE}
  if [ $arch == "armv6l" ];  then
    echo "Raspberry 1 détecté, utilisation du paquet pour armv6"
    sudo rm /etc/apt/sources.list.d/nodesource.list
    wget http://node-arm.herokuapp.com/node_latest_armhf.deb
    sudo dpkg -i node_latest_armhf.deb
    sudo ln -s /usr/local/bin/node /usr/local/bin/nodejs
    rm node_latest_armhf.deb
  else
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
    sudo apt-get install -y nodejs
  fi
  new=$(nodejs -v);
  echo "Version actuelle : ${new}"
fi
echo 70 > ${PROGRESS_FILE}
if [ -d node_modules ]; then
  sudo rm -rf node_modules
fi
echo 80 > ${PROGRESS_FILE}
npm install
if [ -d node_modules ]; then
  sudo chown -R www-data node_modules
fi
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
















sh dependencies.sh ${1} ${2}

rm ${PROGRESS_FILE}

echo "Fin de l'installation"