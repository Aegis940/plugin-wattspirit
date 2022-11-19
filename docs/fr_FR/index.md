# Plugin wattspirit

![plugin-wattspirit logo](https://aegis940.github.io/plugin-wattspirit/assets/images/logo.png)

Plugin permettant la récupération des mesures de puissance du compteur électrique remontées par l'équipement *Wattspirit* par l'interrogation du compte-client *My Wattspirit*. Les données sont remontées toutes les 10 minutes par *Wattspirit* avec un retard de 10 minutes. 

Les types de données de consommation suivants sont accessibles :
- La **puissance** mesurée au compteur *(en W)*.


# Configuration

## Configuration du plugin


## Configuration des équipements

Pour accéder aux différents équipements **Veolia Téléo**, dirigez-vous vers le menu **Plugins → Energie → Wattspirit**.

> **A savoir**    
> Le bouton **+ Ajouter** permet d'ajouter un nouveau compte **Wattspirit**.

Sur la page de l'équipement, renseignez :

- l'**identifiant** ainsi que le **mot de passe** de votre compte-client *My Wattspirit* 

Puis cliquez sur le bouton **Sauvegarder**.

Le plugin lance une première récupération de données, puis programme une tâche cron à pour qu'elle éxécute 10 minutes après la date/heure de la dernière mesure récupérée. Cette tâche sera recréé après chaque récupération en fonction de la date/heure de la dernière mesure.

Ce plugin gratuit est ouvert à contributions (améliorations et/ou corrections). N'hésitez pas à soumettre vos pull-requests sur <a href="https://github.com/Aegis940/plugin-wattspirit" target="_blank">Github</a>

# Avertissement
- Ce code ne prétend pas être exempt de bogues
- Ce plugin vous est fourni sans aucune garantie. Bien que peu probable, si il venait à corrompre votre installation **Jeedom**,l'auteur ne pourrait en être tenu pour responsable.

# ChangeLog
Disponible [ici](./changelog.md).
