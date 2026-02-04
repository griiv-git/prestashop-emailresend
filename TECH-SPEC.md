---
title: 'Module de renvoi d''emails PrestaShop'
slug: 'resend-email-module'
created: '2026-02-03'
updated: '2026-02-04'
status: 'in-progress'
tech_stack:
  - PrestaShop 1.7.6+
  - PrestaShop 9
  - PHP 7.1+ / 8.1+
  - Symfony Services
  - Doctrine ORM
  - Twig (BO)
  - SwiftMailer (PS 1.7.x) / Symfony Mailer (PS 9)
  - jQuery (BO)
architecture:
  - Architecture full Symfony avec services
  - Hooks délégués à des classes dédiées via ModuleAbstract
  - Repositories Doctrine pour l'accès aux données
  - Controller Symfony FrameworkBundleAdminController
  - Entités Doctrine avec DoctrineNamingStrategy pour préfixe tables
---

# Tech-Spec: Module de renvoi d'emails PrestaShop

**Module:** `griivemailresend`
**Version:** 1.0.0
**Créé:** 2026-02-03
**Mis à jour:** 2026-02-04

## Overview

### Problem Statement

**Use case principal : QA et debug des communications client.**

L'équipe interne a besoin de vérifier ce que reçoivent les clients pour s'assurer que les emails s'envoient correctement avec les bonnes données. Actuellement, impossible de visualiser ou renvoyer un email déjà envoyé depuis le back-office PrestaShop.

PrestaShop ne stocke pas le contenu HTML des emails envoyés - seulement les métadonnées (destinataire, template, sujet, langue, date). Il est donc impossible de voir ou renvoyer le contenu exact d'un email passé pour vérification qualité.

**Volumétrie estimée :** 100-200 emails/jour maximum.

### Solution

Module PrestaShop compatible 1.7.6+ et 9 qui :
1. Intercepte chaque envoi d'email via les hooks pour stocker le contenu HTML complet
2. Stocke optionnellement les pièces jointes (configurable : activé/désactivé + mode BDD ou fichiers)
3. Ajoute un bouton "Renvoyer" dans la grille des emails du back-office
4. Affiche une modale avec preview HTML sécurisée + sélection destinataires
5. Renvoie l'email exact (HTML + pièces jointes si configuré) au(x) nouveau(x) destinataire(s)

### Scope

**In Scope:**
- Tables Doctrine pour stocker le contenu HTML et pièces jointes
- Hook `actionEmailSendBefore` pour capturer templateVars et templateHtml
- Hook `actionObjectMailAddAfter` pour lier le contenu à l'id_mail
- Hook `displayBackOfficeHeader` pour charger JS/CSS sur AdminEmails
- Stockage optionnel des pièces jointes avec configuration
- Modale enrichie avec preview HTML, dropdown admins, champ libre destinataires
- Envoi de l'email exact avec le contenu HTML stocké (charset UTF-8)
- Architecture full Symfony avec services et Doctrine

**Out of Scope:**
- Modification du contenu de l'email avant renvoi
- Historique des renvois (qui a renvoyé, quand, à qui)
- Purge automatique des anciens contenus
- Hook actionEmailLogsGridDefinitionModifier (extension grille)

---

## Architecture Symfony

### Structure des fichiers

```
griivemailresend/
├── griivemailresend.php              # Classe principale (minimal)
├── config/
│   ├── services.yml                  # Définition des services Symfony
│   ├── routes.yml                    # Routes Symfony
│   └── admin/services.yml            # Services admin (controller)
├── src/
│   ├── Controller/Admin/
│   │   └── EmailResendController.php # Controller Symfony BO
│   ├── Entity/
│   │   ├── GriivEmailContent.php     # Entité contenu email
│   │   └── GriivEmailAttachment.php  # Entité pièces jointes
│   ├── Repository/
│   │   ├── EmailContentRepository.php
│   │   └── EmailAttachmentRepository.php
│   ├── Service/
│   │   ├── PendingEmailDataService.php   # Données pending inter-hooks
│   │   ├── EmailCaptureService.php       # Capture et stockage
│   │   ├── EmailResendService.php        # Envoi emails
│   │   └── OrphanCleanerService.php      # Nettoyage orphelins
│   ├── Hook/
│   │   ├── Action/
│   │   │   ├── ActionEmailSendBefore.php
│   │   │   └── ActionObjectMailAddAfter.php
│   │   └── Display/
│   │       └── DisplayBackOfficeHeader.php
│   ├── Form/
│   │   └── ConfigurationType.php
│   └── Install/
│       └── Installer.php
├── sql/
│   ├── install.sql
│   └── uninstall.sql
├── views/
│   ├── templates/admin/
│   │   ├── modal_resend.tpl
│   │   └── configuration.html.twig
│   ├── js/resend.js
│   └── css/resend.css
├── uploads/                          # Stockage fichiers PJ (si mode fichier)
└── vendor/
    └── griiv/prestashop-module-contracts/
```

### Tables Base de Données

| Entité | Table | Description |
|--------|-------|-------------|
| `GriivEmailContent` | `ps_griiv_email_content` | Contenu HTML des emails |
| `GriivEmailAttachment` | `ps_griiv_email_attachment` | Pièces jointes |

**Note :** Le préfixe `ps_` est ajouté automatiquement par la `DoctrineNamingStrategy` de PrestaShop.

### Services Symfony

```yaml
services:
  # Context pour hooks
  griiv.email_resend.context:
    class: Context
    factory: ['Context', 'getContext']

  # Services métier
  Griiv\EmailResend\Service\PendingEmailDataService:
    # Gère les données email en attente (static array)

  Griiv\EmailResend\Service\EmailCaptureService:
    # Capture et stockage des emails via Doctrine

  Griiv\EmailResend\Service\EmailResendService:
    # Envoi direct SwiftMailer/Symfony Mailer

  Griiv\EmailResend\Service\OrphanCleanerService:
    # Nettoyage des entrées orphelines

  # Hooks (héritent de Hook, reçoivent Context)
  Griiv\EmailResend\Hook\Action\ActionEmailSendBefore:
    arguments:
      $context: '@griiv.email_resend.context'
      $pendingDataService: '@...'

  Griiv\EmailResend\Hook\Action\ActionObjectMailAddAfter:
    arguments:
      $context: '@griiv.email_resend.context'
      $captureService: '@...'
      $pendingDataService: '@...'

  Griiv\EmailResend\Hook\Display\DisplayBackOfficeHeader:
    arguments:
      $context: '@griiv.email_resend.context'
      $router: '@router'
```

### Flux des Hooks

```
┌─────────────────────────────────────────────────────────────────┐
│                     ENVOI EMAIL                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. actionEmailSendBefore                                        │
│     - Capture templateVars, template, idLang, subject, to        │
│     - Génère clé unique (md5)                                    │
│     - Stocke dans PendingEmailDataService (static array)         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  [PrestaShop envoie l'email et crée entrée ps_mail]              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. actionObjectMailAddAfter                                     │
│     - Récupère id_mail depuis $params['object']->id              │
│     - Récupère données pending via recipient                     │
│     - Génère HTML final (str_replace templateVars)               │
│     - Stocke dans ps_griiv_email_content via Doctrine            │
│     - Stocke pièces jointes si configuré                         │
└─────────────────────────────────────────────────────────────────┘
```

### ModuleAbstract

Le module hérite de `Griiv\Prestashop\Module\Contracts\Module\ModuleAbstract` :

- Route automatiquement les hooks vers les classes dédiées via `__call()`
- Pattern : `Griiv\EmailResend\Hook\{Type}\{HookName}::{method}()`
- Tous les hooks étendent `Griiv\Prestashop\Module\Contracts\Hook\Hook`
- Interfaces implémentées :
  - Display hooks → `DisplayHookInterface::display($params): string`
  - Action hooks → `ActionHookInterface::action($params): bool`

---

## Implementation Status

### Complété ✅

#### Phase 1 : Structure de base
- [x] Classe principale `griivemailresend.php`
- [x] Configuration Symfony `services.yml` et `routes.yml`
- [x] Entités Doctrine `GriivEmailContent`, `GriivEmailAttachment`
- [x] Repositories Doctrine avec méthodes custom
- [x] Installer SQL

#### Phase 2 : Services
- [x] `PendingEmailDataService` - Gestion données pending
- [x] `EmailCaptureService` - Capture et stockage
- [x] `EmailResendService` - Envoi emails
- [x] `OrphanCleanerService` - Nettoyage

#### Phase 3 : Hooks (architecture Symfony)
- [x] `ActionEmailSendBefore` - Capture avant envoi
- [x] `ActionObjectMailAddAfter` - Stockage après création mail
- [x] `DisplayBackOfficeHeader` - Assets et modale

#### Phase 4 : Controller et Forms
- [x] `EmailResendController` - Actions AJAX et configuration
- [x] `ConfigurationType` - Formulaire Symfony

### En Cours 🔄

#### Phase 5 : Interface
- [ ] Template modale `modal_resend.tpl`
- [ ] JavaScript `resend.js`
- [ ] CSS `resend.css`
- [ ] Template configuration `configuration.html.twig`

### Restant 📋

#### Phase 6 : Tests et finalisation
- [ ] Tests manuels complets
- [ ] Vérification cache Symfony
- [ ] Traductions

---

## Configuration

### Clés de configuration

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `GRIIV_EMAILRESEND_STORE_ATTACHMENTS` | bool | 0 | Activer stockage pièces jointes |
| `GRIIV_EMAILRESEND_STORAGE_MODE` | string | database | Mode: `database` ou `file` |
| `GRIIV_EMAILRESEND_MAX_SIZE` | int | 10 | Taille max PJ en MB |

---

## Sécurité

- **Controller** : Protégé par `@AdminSecurity` annotations
- **Preview email** : Iframe sandboxée (`sandbox="allow-same-origin"`)
- **Validation emails** : `Validate::isEmail()` côté serveur
- **Uploads** : Dossier protégé par `.htaccess` (`Deny from all`)
- **Envoi direct** : Pas via `Mail::send()` pour éviter duplication

---

## Notes Techniques

### Compatibilité PrestaShop 1.7.6

- Pas de typed properties avec valeurs par défaut (PHP 7.1)
- Services Symfony déclarés explicitement
- Context injecté via factory service

### Gestion du cache

```bash
# Vider le cache Symfony après modifications
rm -rf var/cache/*
# Ou via BO : Advanced Parameters > Performance > Clear cache
```

### Couche d'abstraction Mailer

```php
// Détection automatique du mailer disponible
if (class_exists('Symfony\Component\Mime\Email')) {
    // PrestaShop 9 - Symfony Mailer
} else {
    // PrestaShop 1.7.x - SwiftMailer
}
```
