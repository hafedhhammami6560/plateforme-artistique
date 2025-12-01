# 📍 Routes du Module Gestion Contrats et Discussions

## 🌐 FrontOffice (Interface Utilisateur)

### Page d'Accueil
```
GET  /                              app_home
```

### Discussions
```
GET     /discussions                app_discussion_index
GET     /discussions/new            app_discussion_new
POST    /discussions/new            app_discussion_new
GET     /discussions/{id}           app_discussion_show
POST    /discussions/{id}           app_discussion_show (envoyer message)
GET     /discussions/{id}/edit      app_discussion_edit
POST    /discussions/{id}/edit      app_discussion_edit
POST    /discussions/{id}/delete    app_discussion_delete
POST    /discussions/{id}/close     app_discussion_close
POST    /discussions/{id}/reopen    app_discussion_reopen
```

### Contrats
```
GET     /contracts                          app_contract_index
GET     /contracts/new                      app_contract_new
POST    /contracts/new                      app_contract_new
GET     /contracts/new/discussion/{id}      app_contract_new_from_discussion
POST    /contracts/new/discussion/{id}      app_contract_new_from_discussion
GET     /contracts/{id}                     app_contract_show
GET     /contracts/{id}/edit                app_contract_edit
POST    /contracts/{id}/edit                app_contract_edit
POST    /contracts/{id}/propose             app_contract_propose
GET     /contracts/{id}/sign                app_contract_sign
POST    /contracts/{id}/sign                app_contract_sign
POST    /contracts/{id}/activate            app_contract_activate
POST    /contracts/{id}/terminate           app_contract_terminate
POST    /contracts/{id}/delete              app_contract_delete
```

---

## 🖥️ BackOffice (Gestion Avancée Personnelle)

### Dashboard BackOffice
```
GET  /backoffice                    app_backoffice_dashboard
```

### Gestion Discussions
```
GET  /backoffice/discussions                    app_backoffice_discussion_index
GET  /backoffice/discussions/{id}/analytics     app_backoffice_discussion_analytics
```

### Gestion Contrats
```
GET  /backoffice/contracts                          app_backoffice_contract_index
GET  /backoffice/contracts/{id}/financial-report   app_backoffice_contract_financial_report
GET  /backoffice/contracts/reports                 app_backoffice_contract_reports
```

---

## 🛡️ Administration (ROLE_ADMIN)

### Dashboard Admin
```
GET  /admin                          app_admin_dashboard
```

### Discussions Admin
```
GET  /admin/discussions               app_admin_discussion_index
GET  /admin/discussions/{id}          app_admin_discussion_show
GET  /admin/discussions/{id}/edit     app_admin_discussion_edit
POST /admin/discussions/{id}/edit     app_admin_discussion_edit
POST /admin/discussions/{id}/delete   app_admin_discussion_delete
```

### Contrats Admin
```
GET  /admin/contracts                 app_admin_contract_index
GET  /admin/contracts/reports         app_admin_contract_reports
GET  /admin/contracts/{id}            app_admin_contract_show
GET  /admin/contracts/{id}/edit       app_admin_contract_edit
POST /admin/contracts/{id}/edit       app_admin_contract_edit
POST /admin/contracts/{id}/delete     app_admin_contract_delete
```

---

## 🔐 Permissions Requises

### FrontOffice
- **Discussions**: `ROLE_USER` + `DISCUSSION_VIEW`, `DISCUSSION_EDIT`, `DISCUSSION_SEND_MESSAGE`
- **Contrats**: `ROLE_USER` + `CONTRACT_VIEW`, `CONTRACT_EDIT`, `CONTRACT_SIGN`

### BackOffice
- **Toutes les routes**: `ROLE_USER` (accès personnel uniquement)

### Admin
- **Toutes les routes**: `ROLE_ADMIN`

---

## 📊 Paramètres de Requête

### Discussions
```
?status=pending|active|closed|archived
?search=texte
```

### Contrats
```
?status=draft|proposed|signed|active|terminated
?search=texte
```

---

## 🎯 Exemples d'Utilisation

### Créer une discussion et envoyer un message
```
1. GET  /discussions/new
2. POST /discussions/new (product_id, subject, initial_message)
3. Redirection vers /discussions/{id}
```

### Créer un contrat depuis une discussion
```
1. GET  /contracts/new/discussion/5
2. POST /contracts/new/discussion/5 (terms, commissionRate, dates)
3. Redirection vers /contracts/{id}
```

### Signer un contrat (Artiste)
```
1. GET  /contracts/10/sign
2. POST /contracts/10/sign (confirmation)
3. Redirection vers /contracts/10
```

### Voir les analytics d'une discussion (BackOffice)
```
1. GET /backoffice/discussions/5/analytics
   → Affiche temps réponse, engagement, activité
```

### Voir le rapport financier d'un contrat (BackOffice)
```
1. GET /backoffice/contracts/10/financial-report
   → Affiche commission, durée, dates, termes
```

---

**Note**: Toutes les routes POST nécessitent un token CSRF pour la sécurité.
