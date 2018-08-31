Description 
===

Plugin qui récupère les geolocalisations partagées avec Google.
L'affichage est paramétrable au sein du plugin.
Les coordonnées peuvent être envoyées à d'autres plugins Geoloc, Geotrav, Jeeloc, etc...

Configuration
===

La configuration du plugin nécessite une adresse gmail et son mot de passe. 
Je vous conseille de créer un compte gmail dédié. 

> **IMPORTANT**
>
> La double authentification ne doit pas être activée.
>
> Avant de pouvoir utiliser le plugin il faut s'être authentifié au moins une fois depuis votre adresse IP au compte Google configuré.
>
>Vérifiez que Google ne vous demande pas de confirmation (mail ou sms) de connection
>
>Activer l'option suivante dans votre compte Google : Connexion et sécurité / Autoriser les applications moins sécurisées
>
>Il n'est pas recommandé de descendre la fréquence de rafraichissement (10 minutes par défaut) car cela augmente le risque de détection d'une utilisation via automate de votre compte par Google.
>Il s'est avéré qu'à la suite d'une détection, ce dernier met en place un captcha impossible a résoudre via Jeedom.
Le plugin devient donc inutilisable pendant plusieurs heures. 


Partager votre position depuis l'application Gmaps de votre smartphone avec le compte que vous venez de paramétrer.

Choisissez la fréquence de rafraichissement des données (toutes les 5 minutes par défaut). 

Equipement
===
Chaque contact créé automatiquement un équipement.
Sur chaque contact, il est possible de mettre à jour une commande afin d'alimenter d'autres plugins.
Il est possible de choissir d'afficher ou non ce contact sur le widget global ainsi que sur le panel.

Un contact "Global" est créé. Il s'agit de l'équipement qui affichera tous les contacts au sein d'une même carte
Il est possible de choissir d'afficher ce widget sur le panel.

Des équipements "fixes" peuvent être créés (ex: domicile, travail, etc...)
Il suffit d'indiquer les coordonnées gps dans le champs correspondant.
Il est possible d'attribuer une couleur à un équipement fixe et de choissir de l'afficher ou non sur le widget global.

Affichage
===

Deux modes d'affichage sont disponibles.
- Contact par contact

- Global avec tous les contacts sur une même carte.
En cliquant sur l'avatar d'un contact, la carte se centre sur celui ci.
En double cliquant, la carte revient à un affichage global.
La carte se zoom et se positionne de façon à ce que tous les contacts soient visibles.

