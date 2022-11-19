# plugin-wattspirit
Wattspirit est un outil de suivi de consommation électrique. Toutes les dix minutes, un envoi de données est fait. Chaque envoi contient 10 valeurs de puissance, à raison d’une valeur par minute. On a donc accès à sa consommation minute par minute.

Mais Wattspirit n’est pas qu’un simple capteur ni qu’une simple interface graphique pour présenter les données de consommation. Wattspirit offre des fonctionnalités intelligentes pour étudier en détails les consommations électriques et tenter de comprendre quels appareils consomment à quels moments. Le but ultime est, bien entendu, l’amélioration de la performance énergétique et la baisse de coût qui y est associée. C’est ainsi que Wattspirit a mis en place des algorithmes de détection des appareils et des outils d’inspection et d’analyse des consommations.

Wattspirit met à disposition les données brutes remontées et le plugin récupère ces données via l'API fournit par Wattspirit

# Avertissement
- Ce code ne prétend pas être exempt de bogues.
- Bien qu'il ne devrait pas nuire à votre système **Jeedom**, il est fourni sans aucune garantie ni responsabilité.

# Limitations
- Les données sont remontées toutes les 10 minutes par l'équipement. Les mesures remontées concerne le créneau \[-20 min, -10 min\].

# Contributions
Ce plugin est ouvert aux contributions et même encouragé ! Veuillez soumettre vos demandes d'amélioration ou de correction.
