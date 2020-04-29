Google Shared Locations
=====

# Description

Plugin qui récupère les geolocalisations partagées avec Google.
L'affichage est paramétrable au sein du plugin.
Les coordonnées peuvent être envoyées à d'autres plugins Geoloc, Geotrav, Jeeloc, etc...

# Configuration

Il faut créer un compte gmail dédié et partager votre géolocalisation avec celui-ci. 

## Récupération du cookie

* Depuis Chrome, créez une nouvelle session dédiée.
* Installez l'extension [cookies.txt](https://chrome.google.com/webstore/detail/cookiestxt/njabckikapfpffapmjgojcnbfjonfjfg). Cette extension permet d'extraire un cookie à partir d'un site.
* Connectez vous au compte Google dédié que vous souhaitez configurer
* Rendez-vous sur le site [https://www.google.com/maps](https://www.google.com/maps) **(j'insiste sur le .com)**
* Extrayez le cookie du site en vous aidant de l'extension installée précedemment
* Fermez la fenêtre sans vous déconnecter
* Dans la configuration du plugin, parcourez votre disque dur afin de récupérer le cookie téléchargé
* Cliquez sur "Envoyer" pour enregistrer le cookie
> **NE PAS VOUS DECONNECTEZ DU COMPTE GOOGLE** sinon le cookie sera invalidé.
* Vous pouvez rebasculer sur la session principale de Chrome

# Affichage
Il est possible de choisir le fond de carte en fonction des deux thèmes officiels (light/dark).
[Aperçu des fonds](https://leaflet-extras.github.io/leaflet-providers/preview/)

# Utilisation
Partager votre position depuis l'application Gmaps de votre smartphone avec le compte que vous venez de paramétrer.

Choisissez la fréquence de rafraichissement des données (toutes les 5 minutes par défaut). 
## Equipement

Chaque contact créé automatiquement un équipement.
Sur chaque contact, il est possible de mettre à jour une commande afin d'alimenter d'autres plugins.
Il est possible de choissir d'afficher ou non ce contact sur le widget global ainsi que sur le panel.

Un contact *Global* est créé. Il s'agit de l'équipement qui affichera tous les contacts au sein d'une même carte
Il est possible de choissir d'afficher ce widget sur le panel.

Des équipements *fixes* peuvent être créés (ex: domicile, travail, etc...)
Il suffit d'indiquer les coordonnées gps dans le champs correspondant.
Il est possible d'attribuer une couleur à un équipement fixe et de choissir de l'afficher ou non sur le widget global.

## Affichage

Deux modes d'affichage sont disponibles.
- Contact par contact
- Global avec tous les contacts sur une même carte.
En cliquant sur l'avatar d'un contact, la carte se centre sur celui ci.
En double cliquant, la carte revient à un affichage global.
La carte se zoom et se positionne de façon à ce que tous les contacts soient visibles.

