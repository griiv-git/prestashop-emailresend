# Griiv Email Resend

Module PrestaShop permettant de visualiser et renvoyer les emails depuis le back-office.

## Description

Ce module intercepte tous les emails envoyés par PrestaShop pour stocker leur contenu HTML complet. Il permet ensuite de :
- Visualiser le contenu exact d'un email envoyé
- Renvoyer l'email à un ou plusieurs destinataires
- Inclure les pièces jointes originales (si configuré)

**Use case principal :** QA et debug des communications client.

## Compatibilité

| PrestaShop | PHP | Statut |
|------------|-----|--------|
| 1.7.6+ | 7.1 - 7.3 | Supporté |
| 1.7.7+ | 7.2 - 7.4 | Supporté |
| 8.x | 7.4 - 8.1 | Supporté |
| 9.x | 8.1+ | Supporté |

## Installation

1. Copier le dossier `griivemailresend` dans `modules/`
2. Installer via le back-office : Modules > Module Manager
3. Configurer le module selon vos besoins

## Configuration

Accès : **Module Manager > Griiv Email Resend > Configure**

| Option | Description | Défaut |
|--------|-------------|--------|
| Stocker les pièces jointes | Active le stockage des PJ | Non |
| Mode de stockage | Base de données ou fichiers | Base de données |
| Taille max par pièce jointe | Limite en MB | 10 MB |

## Utilisation

1. Aller sur **Configure > Advanced Parameters > E-mail**
2. Dans la liste des emails, cliquer sur le bouton "Renvoyer" (icône oeil)
3. Une modale s'ouvre avec :
   - Preview de l'email
   - Dropdown des employés actifs
   - Champ pour saisir des emails personnalisés
   - Option pour inclure les pièces jointes
4. Cliquer sur "Envoyer"

## Architecture

Le module utilise une architecture full Symfony :

```
src/
├── Controller/Admin/     # Controller Symfony BO
├── Entity/               # Entités Doctrine
├── Repository/           # Repositories Doctrine
├── Service/              # Services métier
├── Hook/                 # Classes de hooks
├── Form/                 # Formulaires Symfony
└── Install/              # Installation
```

### Tables

| Table | Description |
|-------|-------------|
| `ps_griiv_email_content` | Contenu HTML des emails |
| `ps_griiv_email_attachment` | Pièces jointes |

### Hooks utilisés

| Hook | Rôle |
|------|------|
| `actionEmailSendBefore` | Capture les données de l'email avant envoi |
| `actionObjectMailAddAfter` | Stocke le contenu après création en BDD |
| `displayBackOfficeHeader` | Charge les assets sur la page AdminEmails |

## Limitations

- Les emails envoyés **avant l'installation** du module ne peuvent pas être visualisés
- L'envoi renvoyé n'est **pas loggé** dans `ps_mail` (pour éviter duplication)
- Pas de modification du contenu avant renvoi

## Maintenance

### Nettoyer les orphelins

Les entrées orphelines (emails supprimés de `ps_mail`) peuvent être nettoyées via la configuration du module.

### Vider le cache

Après une mise à jour du module :
```bash
rm -rf var/cache/*
```

---

## Documentation technique

Voir [TECH-SPEC.md](TECH-SPEC.md) pour les détails d'implémentation.

---

## Améliorations possibles

### Fonctionnalités

- [ ] **Historique des renvois** - Logger qui a renvoyé, quand, à qui
- [ ] **Modification avant renvoi** - Permettre d'éditer le contenu HTML avant renvoi
- [ ] **Purge automatique** - Cron pour supprimer les anciens contenus (> X jours)
- [ ] **Recherche avancée** - Filtrer par template, destinataire, date
- [ ] **Export** - Exporter les emails stockés (PDF, EML)
- [ ] **Statistiques** - Dashboard avec stats d'envoi/renvoi
- [ ] **Comparaison templates** - Comparer différentes versions d'un même template

### Interface

- [ ] **Extension de la grille** - Hook `actionEmailLogsGridDefinitionModifier` pour ajouter colonne "Contenu disponible"
- [ ] **Preview responsive** - Afficher la preview en différentes tailles (desktop/mobile)
- [ ] **Dark mode** - Support du thème sombre du BO
- [ ] **Bulk actions** - Renvoyer plusieurs emails en une fois

### Technique

- [ ] **Tests unitaires** - PHPUnit pour les services
- [ ] **Tests fonctionnels** - Behat pour les scénarios
- [ ] **API REST** - Endpoints pour accès programmatique
- [ ] **Webhooks** - Notification lors d'un renvoi
- [ ] **Multi-shop** - Support complet du multi-boutique
- [ ] **Compression** - Compresser le HTML stocké (gzip)
- [ ] **Chiffrement** - Chiffrer les contenus sensibles en BDD

### Performance

- [ ] **Index supplémentaires** - Optimiser les requêtes fréquentes
- [ ] **Cache preview** - Mettre en cache les previews générées
- [ ] **Lazy loading** - Charger les PJ uniquement si demandé
- [ ] **Queue** - Utiliser une queue pour les renvois en masse

### Sécurité

- [ ] **Audit log** - Logger toutes les actions (consultation, renvoi)
- [ ] **Permissions granulaires** - Droits par employé
- [ ] **Rate limiting** - Limiter le nombre de renvois par heure
- [ ] **Validation destinataires** - Liste blanche/noire de domaines

---

## Changelog

### 1.0.0 (2026-02-04)
- Initial release
- Capture et stockage du contenu HTML
- Stockage optionnel des pièces jointes
- Preview dans modale
- Renvoi vers destinataires multiples
- Architecture Symfony complète

---

## Licence

Academic Free License (AFL 3.0)

## Auteur

Griiv - https://griiv.fr
