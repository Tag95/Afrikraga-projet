# Afrikraga Demo — App de démonstration Phase 2 (PFA DevSecOps)

Environnement de dev conteneurisé, distinct de `audit-env/` (qui reste dédié à l'audit du vrai
repo Afrikraga en Phase 1). Sert de terrain d'expérimentation pour construire et tester le
pipeline DevSecOps complet, sans risque pour le site réel.

## Stack (décision assumée, voir note ci-dessous)

- Backend : Laravel 12 (PHP 8.3), Sanctum
- Frontend : React 19 + Vite
- DB : **MySQL 8** (et non PostgreSQL comme prévu dans la note de cadrage initiale)
- Cache : Redis 7

**Note sur l'écart MySQL vs PostgreSQL** : la note de cadrage initiale prévoyait PostgreSQL.
Le choix a été révisé vers MySQL une fois la stack réelle d'Afrikraga confirmée en Phase 1
(confirmée MySQL via `.env.audit`), pour que le pipeline construit sur l'app de démo soit
directement transposable au vrai repo sans adaptation de la couche DB. Écart assumé et documenté,
à mentionner si la question est posée en soutenance.

## Étapes de setup (à faire toi-même en local, hors de cet environnement)

Ces commandes nécessitent un accès réseau à Packagist/npm que je n'ai pas ici — à exécuter sur
ton PC Windows, dans `Afrikraga-demo/` :

```powershell
# 1. Scaffolder Laravel dans backend/
composer create-project laravel/laravel backend "12.*"
cd backend
composer require laravel/sanctum
cd ..

# 2. Scaffolder React + Vite dans frontend/
npm create vite@latest frontend -- --template react
cd frontend
npm install
cd ..

# 3. Copier le fichier d'environnement
Copy-Item .env.example .env
# Puis générer APP_KEY une fois le backend scaffoldé :
# php artisan key:generate (à lancer dans le conteneur backend, voir étape 4)

# 4. Lancer l'environnement
docker compose up --build
```

Backend disponible sur http://localhost:8000, frontend sur http://localhost:5173.

## Endpoints prévus (minimalistes, alignés sur le finding F-000)

Objectif : reproduire uniquement les routes d'authentification d'Afrikraga pour pouvoir tester
le fix du rate limiting (F-000, absence de throttling confirmée par Enlightn) directement sur
l'app de démo avant de le reporter sur le vrai repo.

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`

## Prochaines étapes (pipeline Phase 2)

1. Scaffolder backend/frontend (ci-dessus)
2. Implémenter les 4 endpoints d'auth (Sanctum)
3. Durcir le Dockerfile backend, l'analyser avec Hadolint + Trivy
4. Construire le pipeline GitHub Actions (CI, SAST continu, SCA, scan conteneur, GHCR, déploiement k3d)
5. Démo Mozilla SOPS sur ce `.env`
6. Reporter les parties non-intrusives du pipeline sur le vrai repo Afrikraga
