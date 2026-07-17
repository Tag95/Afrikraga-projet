# FINDINGS.md — Suivi des vulnérabilités et anomalies détectées
## Projet PFA DevSecOps — Afrikraga.com

Dernière mise à jour : 17/07/2026
Repo audité : https://github.com/Small-Danger/Afrikraga (Phase 1) + https://github.com/Tag95/Afrikraga-projet (Phase 2/3, démo)

---

## Légende

**Sévérité** : CRITICAL / HIGH / MEDIUM / LOW / INFO
**Statut** : À valider · Confirmé · Faux positif · Corrigé · Accepté (risque résiduel)

---

## Résumé exécutif

| Outil | Scope | Findings | Statut global |
|---|---|---|---|
| Revue manuelle (Phase 2) | Code source copié vers l'app de démo | 1 finding CRITICAL | 🔴 À traiter en urgence |
| Gitleaks | Historique Git (5 commits) | 0 | ✅ Aucun secret exposé (voir note ci-dessous) |
| TruffleHog | Historique Git (623 chunks) | 0 | ✅ Aucun secret exposé (voir note ci-dessous) |
| Semgrep (config auto) | 217 fichiers (frontend + backend) | 10 pertinents (+ 6 hors périmètre) | ✅ Trié |
| Enlightn (env. Docker Compose complet) | Backend Laravel (67 checks) | 17 échecs (dont 1 majeur) | ✅ Trié |
| ESLint Security | Frontend JS/React | 55 (règle unique) | ✅ Tous faux positifs confirmés |
| composer audit | Dépendances Composer | 24 advisories | ✅ Trié prod/dev |
| npm audit | Dépendances npm | 13 vulnérabilités | ✅ Trié prod/dev |
| OWASP Dependency-Check | 443 dépendances (209 uniques) | 59 vulnérabilités | ✅ Trié, 1 faux positif critique écarté |
| Semgrep (config auto, démo, 15/07) | 456 fichiers (`Afrikraga-projet`) | 22 pertinents (+ 6 hors périmètre, + 9 déjà trackés F-001-010) | ✅ Trié |
| OWASP ZAP | Site réel (autorisation requise) | — | ⏳ Bloqué — autorisation BS International en attente |
| OWASP ZAP | App de démo (`localhost:5173`, non authentifié) | F-023 (0 finding, couverture 5 URLs) | ✅ Fait |
| OWASP ZAP | App de démo (`host.docker.internal:4173`, authentifié, couverture élargie panier/checkout/admin) | F-025 (40 alertes, headers HTTP manquants) | ✅ Fait (16-17/07) |
| Burp Suite | API Sanctum + formulaires | — | ⏳ Bloqué — idem |
| Trivy / Hadolint | Image Docker / Dockerfile | Trivy backend 1231→113, frontend 14→12 | ✅ Fait (Phase 2) |
| Wazuh (agents runtime) | 5 conteneurs démo | 4/5 agents actifs initialement (`demo-frontend`/Alpine en pause) + démo F-000 (règle 100010/100011, alerte MITRE T1110) ; 3 agents déconnectés depuis suite aux redémarrages du 16-17/07 (`demo-backend`, `demo-redis`, `demo-mysql`) | ✅ Fait (Phase 3, 15/07) — reconnexion à faire si besoin |

**Note sur Gitleaks/TruffleHog** : ces outils scannent la présence de patterns de secrets typiques (clés API, tokens, formats reconnus). Le finding F-020 ci-dessous n'a **pas** été détecté par ces scanners automatiques car il s'agit d'un mot de passe en clair sans format caractéristique (pas de clé API structurée) — découvert uniquement lors d'une revue manuelle du code en Phase 2. Ceci illustre une limite connue des scanners de secrets par pattern-matching et sera noté comme axe d'amélioration méthodologique dans le rapport final.

---

## 🔴 Finding critique découvert en Phase 2

### F-020 — Identifiants administrateur réels exposés en clair dans le code source public

| Champ | Valeur |
|---|---|
| **Outil** | Revue manuelle (découverte lors de la copie du code source vers l'app de démonstration, Phase 2) |
| **Sévérité** | **CRITICAL** |
| **CVSS estimé** | 9.1 (CWE-798 : Use of Hard-coded Credentials) |
| **Statut** | Confirmé |
| **Description** | Le fichier `database/seeders/DatabaseSeeder.php` du repo **public** `Small-Danger/Afrikraga` contenait un compte administrateur créé avec un mot de passe en clair, un numéro de téléphone WhatsApp professionnel réel et une adresse email, directement dans le code source versionné. Le repository étant public sur GitHub, ces identifiants sont accessibles à quiconque consulte l'historique du code, sans avoir besoin d'exploiter de faille technique. Si ce mot de passe correspond (ou a correspondu) à un compte réellement actif en production, un attaquant pourrait obtenir un accès administrateur complet à la plateforme. |
| **Fichier concerné** | `bs_shop_backend/database/seeders/DatabaseSeeder.php` |
| **Découverte** | Repérée en Phase 2 en copiant le vrai code source vers l'environnement de démonstration (app de démo), avant neutralisation locale des données sensibles pour la démo. |
| **Impact potentiel** | Accès administrateur complet (gestion produits, commandes, clients) si le mot de passe est/était valide en production. Exposition de l'identité et du numéro professionnel d'un membre de l'équipe BS International. |
| **Remédiation proposée** | 1. **Urgent** : vérifier si ce mot de passe est toujours valide sur le compte admin en production ; si oui, le changer immédiatement, indépendamment du reste du projet. 2. Retirer les identifiants du code source — utiliser un seeder avec mot de passe généré aléatoirement à l'exécution, ou variable d'environnement, jamais de valeur en dur. 3. Envisager une réécriture de l'historique Git du repo réel (`git filter-repo` ou BFG Repo-Cleaner) car un simple nouveau commit ne supprime pas la trace dans l'historique déjà public. 4. Mettre en place Gitleaks en pre-commit hook (déjà recommandé en R-001) — bien que ce cas précis n'aurait pas été détecté par pattern-matching classique, ça reste une bonne pratique de fond. 5. Sensibiliser l'équipe : ne jamais committer d'identifiants réels, même dans un seeder de développement. |
| **Action prise pour l'app de démo** | Identifiants remplacés par des valeurs de test explicites (`admin@demo.local`, mot de passe factice) dans le repo de démonstration `Afrikraga-projet`, qui a par ailleurs été mis en visibilité privée par précaution (puis repassé en public temporairement le 15/07, voir F-022/Prochaines actions). |

---

## Finding prioritaire du projet (Phase 1)

### F-000 — Absence de protection contre les attaques par force brute

| Champ | Valeur |
|---|---|
| **Outil** | Enlightn (Check 60/67) |
| **Sévérité** | MEDIUM-HIGH |
| **CVSS estimé** | 5.3–6.5 |
| **Statut** | Confirmé |
| **Description** | Aucune limitation (rate limiting / throttling) détectée sur les routes d'authentification. Un attaquant peut tenter un nombre illimité de combinaisons identifiant/mot de passe. |
| **Routes concernées** | `api/auth/register`, `api/auth/login`, `api/auth/logout`, `api/auth/forgot-password` |
| **Remédiation proposée** | Ajouter le middleware `throttle` de Laravel (ou `RateLimiter`) sur ces routes, avec une limite raisonnable (ex. 5 tentatives/minute par IP). Vérifier également si un throttling existe déjà au niveau serveur web (Nginx/Apache), auquel cas ce finding serait à requalifier en risque résiduel accepté. À tester sur l'app de démo Phase 2 avant application sur le repo réel. |
| **Statut de la démonstration** | ✅ **Exploitation confirmée et détection validée** (15/07) — voir démo complète ci-dessous. Remédiation (middleware `throttle`) volontairement non appliquée avant la soutenance, pour permettre la démo en direct ; à corriger juste après. |

#### Démonstration réalisée : exploitation + détection (15/07/2026)

**Objectif** : démontrer que F-000 est réellement exploitable, et que la chaîne de détection (Wazuh) permet de repérer l'attaque malgré l'absence de blocage préventif.

**1. Preuve d'exploitation**
Script PowerShell simulant une attaque par force brute : 20 requêtes `POST /api/auth/login` envoyées en boucle vers le compte `admin@demo.local`, avec des mots de passe différents à chaque tentative (délai de 200ms entre chaque requête).
**Résultat** : 20/20 tentatives traitées normalement par le serveur (HTTP 401), aucun blocage, aucune limitation observée — confirme F-000 de manière reproductible.

**2. Mise en place de la détection (Wazuh)**
- Agent Wazuh (`demo-host-agent`, ID 011) installé et connecté au manager existant (`wazuh/wazuh-agent:4.14.0`, config dans `wazuh-siem/wazuh-docker/wazuh-agent/`).
- Le fichier `backend/storage/logs/laravel.log` (qui contient déjà nativement une entrée `Login attempt` par tentative, grâce au logging applicatif existant) a été monté dans le conteneur agent (`/var/log/laravel.log`, lecture seule) et déclaré en `<localfile>` dans `ossec.conf`.
- Règle de détection personnalisée créée côté manager (`/var/ossec/etc/rules/laravel_rules.xml`), chargée automatiquement via `<rule_dir>etc/rules</rule_dir>` déjà présent dans la config :

```xml
<group name="laravel,authentication_failed,">
  <rule id="100010" level="5">
    <match>Login attempt</match>
    <description>Laravel: tentative de connexion detectee</description>
  </rule>

  <rule id="100011" level="10" frequency="8" timeframe="60">
    <if_matched_sid>100010</if_matched_sid>
    <description>Laravel: possible attaque par force brute sur le login</description>
    <mitre>
      <id>T1110</id>
    </mitre>
  </rule>
</group>
```
- Règle validée unitairement via `wazuh-logtest` avant déploiement (bonne pratique : tester une règle hors production avant de compter dessus).

**3. Résultat de la détection**
Rejeu de l'attaque (20 tentatives) → règle **100010** déclenchée à chaque tentative (niveau 5) ; règle **100011** déclenchée **2 fois** (niveau 10, seuil de 8 occurrences/60s dépassé), correctement taguée MITRE ATT&CK **T1110 (Brute Force)** sous la tactique **Credential Access**, visible dans le dashboard Wazuh (Threat Hunting + fiche agent).

**4. Obstacles rencontrés et résolus** (valeur méthodologique pour le rapport)
- Agent Wazuh initialement absent/déconnecté (4 agents "Disconnected" détectés en début de session, résidus d'une configuration antérieure) — réinstallé et reconnecté manuellement.
- Conflit d'enregistrement (`Duplicate agent name`) après un premier redémarrage — résolu via suppression de l'ancien enregistrement côté manager (`manage_agents`).
- Première version de la règle utilisant `<decoded_as>json</decoded_as>` : n'a jamais matché, car les lignes de `laravel.log` combinent texte brut (timestamp, niveau) et JSON, pas du JSON pur. Corrigé en `<match>` simple (recherche de texte), validé via `wazuh-logtest` avant redéploiement.

**5. Point secondaire noté (hors périmètre de F-000, à traiter séparément)**
Le champ `all_data` loggé par Laravel dans `Login attempt` contient le mot de passe **en clair** (avant le masquage `password: "***"` appliqué plus loin dans la même ligne). Les logs applicatifs contiennent donc des secrets en clair — mérite un nouveau finding mineur, traité séparément (voir F-024).

---

## Findings SAST — Semgrep

### F-001 à F-010 — Log forging (format string injection)

| Champ | Valeur |
|---|---|
| **Outil** | Semgrep |
| **Règle** | `javascript.lang.security.audit.unsafe-formatstring.unsafe-formatstring` |
| **Sévérité** | INFO |
| **CVSS estimé** | 2.0–3.5 (Low) |
| **Statut** | À valider (probable faux positif faible, non exploité pour l'instant) |
| **Description** | Concaténation de variables non-littérales dans des appels `console.log`, permettant potentiellement l'injection de faux logs. |
| **Fichiers concernés** | `bs_shop_frontend/src/components/admin/ImageManager.jsx:79`, `bs_shop_frontend/src/components/ui/Form.jsx:26,28,44,46,61,79,92,116`, `bs_shop_frontend/src/pages/admin/Dashboard.jsx:282`, `bs_shop_frontend/src/pages/admin/Categories.jsx:169` (occurrence supplémentaire confirmée par le scan du 15/07 sur la démo, code ajouté depuis le scan initial) |
| **Remédiation proposée** | Utiliser des chaînes de format constantes, ou passer les variables comme arguments séparés. |

**Note méthodologique (11/07)** : un second scan a révélé 6 findings supplémentaires de règles `yaml.docker-compose.security.*` — **hors périmètre**, car ils concernent `audit-env/docker-compose.yml`, l'infrastructure de test créée pour l'audit lui-même, et non le code source d'Afrikraga. Non comptabilisés dans les findings du projet.

**Note méthodologique (15/07)** : le scan Semgrep relancé directement sur `Afrikraga-projet` (démo, code identique au vrai repo) a par ailleurs détecté 6 findings sur `actions-runner/` (HTTP non chiffré, ReDoS, bypass TLS) — **hors périmètre**, car ce dossier est le package du runner GitHub Actions self-hosted installé localement pour un usage futur (CD automatisé), **non tracké par git** (`git ls-files` confirme 0 fichier suivi), jamais commité ni déployé. Il s'agit de code tiers (binaire officiel GitHub), pas de code applicatif Afrikraga. Non comptabilisés dans les findings du projet.

---

## Findings SAST — Enlightn (backend Laravel)

Scan réalisé dans un environnement Docker Compose complet (MySQL 8 + Redis 7 + `.env` de test), éliminant les faux échecs liés à l'absence d'environnement applicatif observés lors d'un premier scan partiel. Résultat : 46 passés / 17 échoués / 4 non applicables sur 67 checks.

### F-011 — Absence de Content-Security-Policy

| Champ | Valeur |
|---|---|
| **Sévérité** | MEDIUM |
| **CVSS estimé** | 4.0–5.0 |
| **Statut** | Confirmé |
| **Description** | Aucun header CSP adéquat (`script-src`/`default-src` sans `unsafe-eval`/`unsafe-inline`), protection XSS incomplète au niveau HTTP. |
| **Remédiation proposée** | Configurer un middleware CSP dans Laravel (ex. package `spatie/laravel-csp`). |
| **Recoupement** | Confirmé indépendamment par le scan DAST authentifié du 16-17/07 (voir F-025 : "CSP Header Not Set" détecté par ZAP sur `/` et `/catalog`). |

### F-012 — Configuration PHP non sécurisée

| Champ | Valeur |
|---|---|
| **Sévérité** | LOW |
| **CVSS estimé** | 2.0–3.0 |
| **Statut** | Confirmé |
| **Description** | `expose_php`, `display_errors`, `display_startup_errors` non désactivés — facilite le fingerprinting de la stack par un attaquant. |
| **Remédiation proposée** | Ajuster `php.ini` en production : `expose_php=Off`, `display_errors=Off`, `display_startup_errors=Off`, `log_errors=On`. |

### F-013 — Appel `env()` hors fichier de configuration

| Champ | Valeur |
|---|---|
| **Sévérité** | LOW (fiabilité plus que sécurité pure) |
| **Statut** | Confirmé |
| **Fichier** | `app/Services/CloudinaryService.php:17` |
| **Description** | Une fois la config mise en cache (`config:cache`), les appels `env()` hors fichiers de config renvoient `null` — casse potentielle en production si le cache de config est activé. |
| **Remédiation proposée** | Déplacer la lecture de la variable Cloudinary dans `config/services.php`, puis utiliser `config('services.cloudinary.xxx')`. |
| **Point de vigilance additionnel (16-17/07)** | Le cache de configuration (`config:cache`) a par ailleurs causé un vrai incident lors du scan ZAP authentifié (F-025) : des modifications de `cors.php` n'étaient pas prises en compte tant que `php artisan config:clear` n'était pas exécuté explicitement, un simple restart de conteneur ne suffisant pas. Illustre concrètement le risque documenté par ce finding. |

### F-014 — Dépendances backend avec vulnérabilités connues (détail par package)

| Champ | Valeur |
|---|---|
| **Outil** | Enlightn (Check 66/67), corrobore composer audit |
| **Sévérité** | Variable selon package (voir composer audit ci-dessous) |
| **Statut** | Confirmé |
| **Description** | 10 packages vulnérables activement utilisés : `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `laravel/framework`, `symfony/http-foundation`, `symfony/mailer`, `symfony/mime`, `symfony/polyfill-intl-idn`, `symfony/routing`, `phpunit/phpunit`, `symfony/yaml`. |
| **Remédiation** | Voir section SCA ci-dessous pour le détail CVE par package. |

### Findings environnementaux (non comptabilisés comme vulnérabilités)

Checks échoués liés à des choix d'architecture ou de configuration non-sécurité, à noter pour un rapport complet mais hors du périmètre "vulnérabilité" : absence de cache/route/view/config caching en environnement non-local (performance), Horizon non utilisé avec Redis (performance), pas de pages d'erreur personnalisées (fingerprinting léger), permissions de fichiers larges (à vérifier en conditions réelles de déploiement Railway), dépendances dev installées hors environnement local (propre à l'environnement de test, à vérifier sur la vraie CI/CD d'Afrikraga).

---

## Findings SAST — ESLint Security (frontend)

### F-015 — 55 occurrences `detect-object-injection` — FAUX POSITIFS CONFIRMÉS

| Champ | Valeur |
|---|---|
| **Outil** | ESLint Security |
| **Règle** | `security/detect-object-injection` |
| **Statut** | **Faux positif confirmé** (échantillon vérifié manuellement) |
| **Description** | Règle connue pour un taux élevé de faux positifs sur du code React utilisant des accès dynamiques standards. Vérification manuelle sur `services/api.js:248` (clés issues d'un FormData construit côté client) et `hooks/useCacheManager.js` (clés issues d'un objet de config interne codé en dur) : dans les deux cas, aucune entrée utilisateur non filtrée n'atteint l'accès dynamique. |
| **Action** | Aucune remédiation nécessaire. Mentionné dans le rapport pour démontrer la démarche de validation manuelle des résultats automatisés. |

---

## Findings SCA — Dépendances (composer audit, npm audit, Dependency-Check)

### F-016 — React Router : vulnérabilités critiques actives en production

| Champ | Valeur |
|---|---|
| **Outil** | npm audit, corroboré par OWASP Dependency-Check |
| **Package** | `react-router` / `react-router-dom` 7.0.0–7.14.2 |
| **Sévérité** | HIGH |
| **CVSS estimé** | 7.0–8.5 |
| **Statut** | Confirmé — actif en production (dépendance directe du frontend, pas dev-only) |
| **Description** | 11 CVE incluant XSS via redirections ouvertes, XSS stocké via header Location, CSRF sur Server Actions, et un risque de RCE via désérialisation non authentifiée de `turbo-stream`. **Finding le plus sérieux identifié sur l'ensemble du frontend.** |
| **Remédiation proposée** | `npm audit fix`, à tester en environnement de développement avant déploiement (montée de version potentiellement majeure). À tester sur l'app de démo Phase 2 avant application sur le repo réel. |

### F-017 — Dépendances backend Laravel/Symfony/Guzzle vulnérables (production)

| Champ | Valeur |
|---|---|
| **Outil** | composer audit, corroboré par Enlightn Check 66 |
| **Sévérité** | HIGH à LOW selon package |
| **Statut** | Confirmé — actif en production (dépendances transitives obligatoires de Laravel + Cloudinary) |
| **Détail par package** | |
| `laravel/framework` 12.24.0 | CVE-2026-48019 — CRLF injection règle email par défaut (HIGH) |
| `symfony/http-foundation` 7.3.2 | CVE-2025-64500 — bypass autorisation via PATH_INFO (HIGH) ; CVE-2026-48736 — bypass SSRF IPv6 (MEDIUM) |
| `symfony/mime` 7.3.2 | CVE-2026-45067 — injection en-têtes email/SMTP via CRLF (HIGH) ; CVE-2026-45070 — injection en-tête via paramètre MIME (MEDIUM) |
| `symfony/mailer` 7.3.2 | CVE-2026-45068 — injection d'arguments SendmailTransport (MEDIUM) |
| `guzzlehttp/guzzle` 7.9.3 + `psr7` 2.7.1 | 4 CVE — downgrade HTTPS→HTTP silencieux, injection CRLF, confusion d'hôte (MEDIUM) — actifs via Cloudinary |
| `symfony/routing` 7.3.2 | 2 CVE — contournement de contraintes de route, injection d'URL externe (MEDIUM) |
| `symfony/yaml` 7.3.2 | 3 CVE — déni de service par récursion/ReDoS (LOW) |
| `symfony/polyfill-intl-idn` | CVE-2026-46644 — équivalence Punycode non sécurisée (LOW) |
| **Remédiation proposée** | `composer update --with-all-dependencies`, à tester avant déploiement en production. À tester sur l'app de démo Phase 2 avant application sur le repo réel. |

### F-018 — Faux positif critique écarté : React Server Components

| Champ | Valeur |
|---|---|
| **Outil** | OWASP Dependency-Check |
| **Statut** | **Faux positif confirmé et écarté** |
| **Description** | 4 CVE (dont 1 CRITICAL — RCE pré-authentification CVE-2025-55182) associées par erreur de matching CPE au package `react:19.1.1`, concernant en réalité les packages `react-server-dom-parcel/turbopack/webpack`. Vérification via `npm ls react-server-dom-*` : **packages absents de l'arbre de dépendances installées**. Afrikraga est un SPA classique via Vite, n'utilisant pas React Server Components. |
| **Action** | Aucune remédiation nécessaire. Documenté pour démontrer la démarche de validation croisée entre outils SCA. |

### F-019 — Dépendances de build (Vite, Rollup, Babel, etc.) — dev-only

| Champ | Valeur |
|---|---|
| **Outil** | npm audit, OWASP Dependency-Check |
| **Statut** | Confirmé mais **non exploitable en production** |
| **Description** | La majorité des CVE restantes (vite, rollup, @babel/core, postcss, tar, minimatch, picomatch, flatted, brace-expansion, js-yaml, ajv) concernent des outils de build/développement, jamais exécutés dans le navigateur des utilisateurs finaux. |
| **Remédiation proposée** | Mise à jour recommandée par hygiène générale (`npm audit fix`), mais non prioritaire du point de vue du risque de production. |

---

## Findings SAST — Semgrep (démo `Afrikraga-projet`, 15/07)

Scan relancé directement sur le code de la démo (`docker run semgrep/semgrep scan --config auto`), 456 fichiers, 291 règles. Code identique au vrai repo (copie directe, cf. décision structurante en tête de projet) — résultats directement transposables au code de production.

### F-021 — Tags GitHub Actions non épinglés (supply-chain)

| Champ | Valeur |
|---|---|
| **Outil** | Semgrep |
| **Règle** | `yaml.github-actions.security.github-actions-mutable-action-tag` |
| **Sévérité** | MEDIUM |
| **Statut** | Confirmé |
| **Description** | 14 occurrences d'actions GitHub référencées par tag mutable (`@v4`, `@v6`, `@v2`, `@v0.36.0`) au lieu d'un SHA de commit figé, dans `.github/workflows/ci.yml`. Un tag peut être repointé silencieusement par le propriétaire de l'action (ou un attaquant en cas de compromission de son compte) — vecteur de supply-chain attack déjà exploité dans la nature (compromissions trivy-action, kics-github-action). |
| **Occurrences** | `actions/checkout@v4` (×5, lignes 19,27,39,55,71,98), `gitleaks/gitleaks-action@v2` (l.31), `shivammathur/setup-php@v2` (l.41), `actions/setup-node@v4` (l.57), `aquasecurity/trivy-action@v0.36.0` (×2, l.77,84), `docker/login-action@v3` (l.104), `docker/build-push-action@v6` (×2, l.111,119) |
| **Remédiation proposée** | Épingler chaque `uses:` à un SHA complet (40 caractères) au lieu du tag, ex. `actions/checkout@8ade135a41bc03ea155e62e844d188df1ea18608`. |

---

### F-022 — Sécurisation Kubernetes (securityContext + Network Policies)

| Champ | Valeur |
|---|---|
| **Outil** | Semgrep (config auto, démo, 15/07) + revue manuelle des manifestes k3d |
| **Sévérité** | MEDIUM |
| **Statut** | ✅ **Corrigé et vérifié (15/07)** |
| **Description** | Les 4 manifestes k3d (`k8s/backend.yaml`, `frontend.yaml`, `mysql.yaml`, `redis.yaml`) ne définissaient pas de `securityContext` — les conteneurs pouvaient tourner en root et autoriser l'escalade de privilèges via des binaires setuid/setgid. Recoupait également le gap "Politiques runtime Kubernetes" (absence totale de Network Policy — tout pod du namespace `afrikraga-demo` pouvait joindre n'importe quel autre pod, y compris directement la base de données). |
| **Remédiation appliquée (securityContext)** | `spec.template.spec.securityContext.runAsNonRoot: true` et `containers[].securityContext.allowPrivilegeEscalation: false` déjà présents sur les 4 manifestes au moment de la vérification du 15/07 — corrigé entre le scan Semgrep initial et cette session. |
| **Remédiation appliquée (Network Policies)** | Ajout de `k8s/network-policies.yaml` : une policy `default-deny-ingress` (deny-all par défaut sur le namespace) + 4 policies d'autorisation ciblées — `frontend` accessible depuis l'extérieur, `backend` accessible depuis `frontend` + extérieur, `mysql` et `redis` accessibles **uniquement** depuis `backend`. |
| **Vérification** | Testé en conditions réelles après application (`kubectl exec` + `wget`/`curl` inter-pods) : `frontend → mysql:3306` → **Connection refused** (bloqué) ; `backend → mysql:3306` → connexion acceptée, bannière MySQL reçue (autorisé) ; `frontend → backend:8000/api` → réponse HTTP 404 reçue (fonctionnement normal préservé, pas de timeout). Confirme que la segmentation fonctionne sans casser le flux applicatif normal. |
| **Point de vigilance** | `k8s/mysql.yaml` contient `MYSQL_ROOT_PASSWORD` en valeur littérale (pas via `secretKeyRef`, contrairement aux autres secrets du même manifeste) — incohérence à corriger si le temps le permet, non bloquante pour la démo. |

---

## Findings DAST — OWASP ZAP (démo `Afrikraga-projet`, 15/07)

### F-023 — Scan actif ZAP sur la démo — 0 vulnérabilité détectée, couverture limitée à 5 URLs

| Champ | Valeur |
|---|---|
| **Outil** | OWASP ZAP 2.17.0 (`zap-full-scan.py`, spider AJAX + scan actif complet, 113 règles) |
| **Cible** | `http://localhost:5173` (démo `Afrikraga-projet`, frontend React) |
| **Statut** | Terminé — **0 alerte** (High/Medium/Low/Informational), sur le périmètre couvert |
| **Description** | Scan DAST actif complet lancé depuis un conteneur Docker contre le frontend de la démo. Après résolution des obstacles méthodologiques (voir ci-dessous), le rapport final ne référence que le site cible (`host.docker.internal:5173`, aucune pollution externe), avec 113 règles actives passées (SQLi, XSS reflected/stored/DOM, RCE, XXE, SSTI, Log4Shell, Spring4Shell, etc.) et 0 finding. |
| **Limite de couverture** | Le spider AJAX (5 min) n'a découvert que **5 URLs** sur l'application — insuffisant pour couvrir l'intégralité d'une marketplace (catalogue produit complet, panier, checkout, back-office admin probablement hors périmètre). **Ce résultat doit être interprété comme "aucune vulnérabilité active détectée sur les pages atteintes", pas comme une validation exhaustive de l'application.** |
| **Obstacles méthodologiques rencontrés et résolus** | (1) `VITE_API_URL` codé en `localhost:8000`, invisible depuis le conteneur ZAP → corrigé temporairement en `host.docker.internal:8000` dans `.env` ; (2) CORS backend (`config/cors.php`) n'autorisant pas cette origine → `http://host.docker.internal:5173` ajouté temporairement à `allowed_origins` ; (3) mécanisme de vérification de mise à jour de ZAP (`ExtensionCallHome`/`ExtensionAutoUpdate`) bloquant le démarrage du daemon sur une connexion instable (`Read timed out` après 70s+, tentatives de téléchargement d'add-ons depuis GitHub) → contourné via `--add-host` redirigeant `cfu.zaproxy.org`, `github.com`, `objects.githubusercontent.com`, `services.zaproxy.org` vers `127.0.0.1` ; (4) navigateur headless (Firefox/Selenium) utilisé par le spider AJAX générant son propre trafic de télémétrie (`firefox.settings.services.mozilla.com`) scanné par erreur comme faisant partie du périmètre → mêmes domaines Mozilla ajoutés au blocage DNS. |
| **Remédiation / suite si le temps le permet** | Relancer avec une durée de spider plus longue (`-m 10` ou plus) et/ou une authentification pré-configurée (`authhelper`) pour couvrir les zones connectées (panier, checkout, admin) non atteintes par ce scan. **Fait le 16-17/07, voir F-025.** |

---

### F-024 — Mot de passe en clair dans les logs applicatifs Laravel

| Champ | Valeur |
|---|---|
| **Outil** | Revue manuelle (découvert en marge de la démo F-000, 15/07) |
| **Sévérité** | LOW-MEDIUM |
| **Statut** | ✅ **Corrigé et vérifié (15/07)** |
| **Description** | Le log `Login attempt` écrit dans `storage/logs/laravel.log` inclut un champ `all_data` qui contient l'intégralité du payload de la requête, **y compris le mot de passe en clair**, avant que le masquage (`"password":"***"`) ne soit appliqué plus loin dans la même ligne. Exemple observé (attaque force brute F-000, 14h13-14h14) : `{"all_data":{"password":"dragon123","email":"admin@demo.local"},...,"password":"***",...}`. Si ce fichier de log est un jour exfiltré, partagé, ou remonté vers un outil tiers (ce qui est justement en train d'être fait pour la détection F-000), les mots de passe saisis par les utilisateurs — y compris valides — se retrouvent en clair. |
| **Fichier concerné** | `app/Http/Controllers/Api/AuthController.php`, ligne 98, méthode `login()` |
| **Remédiation appliquée** | Remplacement de `'all_data' => $request->all()` par `'all_data' => \Illuminate\Support\Arr::except($request->all(), ['password'])` — exclut spécifiquement le mot de passe du payload loggé, sans toucher au reste des champs de debug (email, whatsapp_phone). |
| **Vérification** | Test de connexion relancé après correctif : nouvelle entrée de log (16h54:26) confirme `all_data` ne contient plus que `{"email":"admin@demo.local"}`, sans champ `password`. Comparaison directe possible dans le même fichier `laravel.log` entre les lignes historiques (avant, mots de passe en clair) et la ligne post-correctif (après, filtrée) — bonne preuve visuelle pour le rapport. |
| **Point de vigilance non traité** | Le même log inclut aussi `'headers' => $request->headers->all()`, qui exposerait un éventuel header `Authorization: Bearer <token>` en clair. Non corrigé (hors périmètre strict de F-024, à évaluer séparément si le temps le permet). |

---

### F-025 — Scan ZAP authentifié sur la démo — en-têtes de sécurité HTTP manquants

| Champ | Valeur |
|---|---|
| **Outil** | OWASP ZAP 2.17.0 (mode daemon + API REST, contexte authentifié Bearer/Sanctum) |
| **Cible** | `http://host.docker.internal:4173` (build de production, `vite preview`, démo `Afrikraga-projet`) |
| **Statut** | Terminé en deux passes — **40 alertes au total** après couverture élargie (0 High, 20 Medium, 10 Low, 10 Informational) |
| **Description** | Suite à F-023 (0 finding, couverture 5 URLs, non authentifié), ce scan reprend le DAST avec authentification Bearer (comptes de test `zap-client@demo.local` / `zap-admin@demo.local` créés via `tinker`). Contrairement au dev server (`5173`, bloqué par le HMR WebSocket de Vite — voir obstacles ci-dessous), le scan cible le build de production (`4173`) pour éviter cette limitation. **Première passe** (Crawljax automatique) : 7 URLs couvertes (page d'accueil, `/catalog`, assets, endpoints `/api/*`), 12 alertes. **Seconde passe** (navigation manuelle rejouée via `core/action/accessUrl`, le proxy navigateur s'étant révélé peu fiable) : couverture étendue à **11 URLs**, incluant `/cart`, `/checkout`, `/admin` et ses 5 sous-sections (`products`, `categories`, `orders`, `clients`, `banners`) — 40 alertes au total. |
| **Findings détaillés** | Les mêmes 4 types de finding se répètent de façon cohérente sur l'ensemble des 11 URLs couvertes, sans nouvelle catégorie de vulnérabilité apparue sur le panier/checkout/admin : 1. **Missing Anti-clickjacking Header** (Medium, ×9) — absence de `X-Frame-Options` sur toutes les pages testées y compris `/checkout` et les 5 sections admin ; risque de clickjacking. 2. **CSP Header Not Set** (Medium, ×9) — aucune Content-Security-Policy sur l'ensemble du site, y compris admin — **recoupe F-011** (Enlightn, même constat côté backend). 3. **X-Content-Type-Options Header Missing** (Low, ×9) — absent sur toutes les pages et assets. 4. **Timestamp Disclosure - Unix** (Low) — asset JS compilé. 5. **Modern Web Application** (Informational, ×10) — détection automatique du framework, sans impact. **Point positif à noter** : aucune injection (SQLi/XSS/RCE), aucun problème d'autorisation ou de contrôle d'accès détecté sur les routes admin malgré l'authentification testée avec les deux rôles (client/admin) — les 3 en-têtes manquants restent le seul type de faiblesse identifié sur l'ensemble du périmètre couvert. |
| **Endpoints API testés** | `/api/products`, `/api/categories`, `/api/cart`, `/api/banners`, `/api/auth/login` — capturés et scannés authentifié (token Bearer injecté via ZAP Replacer), **0 alerte** sur le périmètre API (headers de sécurité déjà corrects côté réponses JSON Laravel/Sanctum). |
| **Obstacles méthodologiques rencontrés et résolus** | (1) Vite `server.allowedHosts` ne couvrait pas `host.docker.internal` (uniquement `preview.allowedHosts`) → ajouté dans `vite.config.js`. (2) Serveur de dev Vite (`5173`) : le spider AJAX (navigateur Firefox headless piloté par Selenium/Crawljax) timeout systématiquement (`504 Gateway Timeout` après 20s) alors qu'un `curl` simple répondait instantanément — cause probable : connexion WebSocket HMR de Vite bloquant le rendu complet côté navigateur réel. Contourné en scannant le **build de production** (`npm run build` + `npm run preview`, port `4173`, sans WebSocket HMR) plutôt que le dev server — port ajouté à `docker-compose.yml`. (3) CORS backend : `host.docker.internal:4173` ajouté à `allowed_origins` dans `cors.php`, mais requêtes toujours bloquées (`403 Forbidden`) malgré la config correcte → cache de configuration Laravel obsolète (`php artisan config:clear` + `cache:clear` nécessaires après modification de `cors.php`, un simple restart de conteneur ne suffit pas). (4) **Bug latent découvert dans `allowed_origins_patterns`** : le package `fruitcake/php-cors` utilisé par Laravel attend des regex PHP complètes avec délimiteurs (format `/^...$/`) dans ce champ, pas des patterns "glob" simples avec `*` — les patterns pré-existants (`http://192.168.11.*:5173`, etc.) avaient ce bug depuis l'origine mais n'avaient jamais été exercés par une requête correspondante, donc jamais détecté ; provoquait une erreur 500 (`preg_match(): Delimiter must not be alphanumeric`) dès qu'une requête traversait cette liste. Corrigé en réécrivant tous les patterns en regex valides (ex. `/^http:\/\/host\.docker\.internal:\d+$/`), rendant `host.docker.internal` accepté en permanence, sur n'importe quel port, sans bascule manuelle future. (5) Spider AJAX (Crawljax) limité sur les composants React modernes : navigation au-delà de la page d'accueil et `/catalog` non atteinte automatiquement (0 clic sur les fiches produits malgré `MaxDuration=5min`, `EventWait=2000ms`) ; un crash ponctuel du navigateur headless observé (`Error occurred while closing the browser`). **Décision** : couverture partielle acceptée (page d'accueil, catalogue, endpoints API authentifiés) plutôt que de basculer vers une navigation manuelle via proxy ZAP — amélioration réelle par rapport à F-023 sans sur-ingénierie disproportionnée pour le temps restant avant soutenance. |
| **Remédiation proposée** | Ajouter un middleware Laravel (ou configuration Nginx en prod) injectant les headers de sécurité manquants : `X-Frame-Options: DENY`, `Content-Security-Policy` (à définir selon les sources légitimes du site — CDN Tailwind, Cloudinary, etc.), `X-Content-Type-Options: nosniff`. Package `secure-headers` ou middleware natif Laravel envisageable. Recoupe directement la remédiation déjà proposée en F-011. |
| **Limite de couverture — résolue** | Panier, checkout, et back-office admin, initialement non couverts par le spider automatique (Crawljax), ont été couverts dans une seconde passe. Obstacle rencontré : la configuration du proxy navigateur (Chrome, `--proxy-server=127.0.0.1:8090`) s'est révélée peu fiable (plusieurs relances sans que le trafic transite réellement par ZAP, `core/view/sites/` restant vide malgré une navigation manuelle complète). **Contournement adopté** : plutôt que de perdre du temps à déboguer la configuration proxy, les URLs visitées manuellement (notées au fil de la navigation) ont été rejouées directement via l'API ZAP (`core/action/accessUrl`), avec le token Bearer déjà injecté par le Replacer — équivalent fonctionnel pour le scan actif, sans dépendre du bon fonctionnement du proxy. Limite persistante : cette méthode capture les URLs mais pas nécessairement chaque état intermédiaire du parcours utilisateur (ex. contenu affiché uniquement après une interaction JS) — un scan par proxy réel resterait supérieur pour une couverture parfaite, mais le résultat obtenu (40 alertes, 11 URLs, 0 finding critique) est jugé suffisant pour le temps disponible avant soutenance. |
| **Incident méthodologique annexe** | Un script PowerShell d'insertion automatique dans `FINDINGS.md` a accidentellement tronqué le fichier (410 → 189 lignes, perte de F-000, F-020, F-011 à F-021) lors de la rédaction de cette section. Repéré immédiatement par vérification systématique (`git status` révélant que le fichier n'était pas suivi par Git, donc non restaurable via `git checkout`), reconstruit à partir de l'historique de conversation puis fusionné avec une sauvegarde antérieure retrouvée par l'utilisateur. **Action corrective immédiate** : `FINDINGS.md` ajouté à Git et commité pour la première fois — protection contre toute perte future. |

---

## Risques structurels identifiés (hors scan automatisé)

### R-001 — Script de déploiement non sécurisé

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle — `deploy-railway.sh` |
| **Sévérité** | MEDIUM (risque latent, non matérialisé à ce jour) |
| **Statut** | Confirmé |
| **Description** | Le script exécute `git add .` sans vérification préalable des secrets avant push sur `main`, sans contrôle de sécurité automatisé dans la chaîne de déploiement. |
| **Preuve associée** | Corroboré par l'absence totale d'outils de sécurité dans le pipeline actuel (aucun CI/CD constaté), et par la découverte du finding F-020 (identifiants réels committés sans détection). |
| **Remédiation proposée** | Intégrer Gitleaks en pre-commit hook + dans le futur pipeline GitHub Actions (bloquant). Ajouter un `.gitignore` strict pour les fichiers `.env`. |

### R-002 — Absence totale de pipeline de sécurité

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle du repo |
| **Sévérité** | HIGH (absence de garde-fou systémique) |
| **Statut** | Confirmé |
| **Description** | Aucun SAST, SCA, secret scanning ou DAST dans le workflow de développement actuel — le code va en production sans vérification automatique. Le finding F-020 illustre concrètement les conséquences possibles de cette absence. |
| **Remédiation proposée** | Objet de la Phase 2 du projet (pipeline GitHub Actions). |

### R-003 — Dépendance Cloudinary sans audit — confirmé via SCA

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle initiale, confirmée par composer audit (F-017) |
| **Sévérité** | MEDIUM |
| **Statut** | Confirmé |
| **Description** | Cloudinary gère les uploads d'images produits ; tire `guzzlehttp/guzzle` et `guzzlehttp/psr7` en dépendances, tous deux avec des CVE actives (voir F-017). Les credentials eux-mêmes ne sont pas exposés (confirmé par Gitleaks/TruffleHog). |
| **Remédiation proposée** | Mettre à jour Guzzle/PSR7 (`composer update`), vérifier la rotation des clés Cloudinary et les permissions du compte. |

---

## Phase 2 — Application de démonstration

Environnement de démonstration mis en place (repo séparé `Afrikraga-projet`, privé) reproduisant la stack réelle d'Afrikraga (Laravel + React + MySQL + Redis, conteneurisé via Docker Compose), à partir d'une copie directe du code source réel (repo public `Small-Danger/Afrikraga`), simplifiée en base de données de test.

**Statut** : Fonctionnelle au 11/07/2026 — backend, frontend, MySQL et Redis démarrent et communiquent correctement. Base de données peuplée avec données de test (catégories, produits, compte admin factice).

**Écart assumé** : MySQL utilisé au lieu de PostgreSQL prévu dans la note de cadrage initiale, pour cohérence avec la stack réelle d'Afrikraga et transposabilité directe du pipeline.

---

## Prochaines actions

- [x] Trier F-001 à F-010 (Semgrep)
- [x] Lancer Enlightn sur le backend Laravel (environnement complet)
- [x] Lancer ESLint Security sur le frontend
- [x] Lancer npm audit + composer audit
- [x] Lancer OWASP Dependency-Check
- [x] Mettre en place l'app de démonstration Phase 2 (Docker Compose fonctionnel)
- [x] **URGENT** — Vérifier si le mot de passe admin exposé (F-020) est toujours valide en production ; le changer si oui
- [x] **URGENT** — Notifier l'encadrant HESTIM et BS International du finding F-020
- [x] Démo F-000 complète réalisée (15/07) : attaque force brute + détection Wazuh + alerte MITRE T1110 — captures prises, voir section "Démonstration réalisée" sous F-000
- [ ] Appliquer le fix throttling (F-000, middleware `throttle:5,1`) — **volontairement reporté après la soutenance** pour permettre la démo en direct
- [x] Corriger F-024 (mot de passe en clair dans laravel.log) — `Arr::except()` appliqué dans `AuthController.php` ligne 98, testé et vérifié (15/07)
- [x] Remettre les agents Wazuh dans un état propre avant la soutenance (15/07) — **4/5 agents actifs** initialement : `demo-host-agent` (Amazon Linux 2023), `demo-backend` (Debian 12), `demo-redis` (Debian 12), `demo-mysql` (Oracle Linux 9.7 "slim"). **`demo-frontend` (Alpine 3.23) en pause** — pas de dépôt `apk` officiel Wazuh stable pour Alpine ; tentative de compilation depuis les sources interrompue par des instabilités réseau locales récurrentes. Décision : limitation documentée plutôt que blocage. **Mise à jour 16-17/07** : `demo-backend`, `demo-redis`, `demo-mysql` repassés `Disconnected` suite aux multiples redémarrages de conteneurs pendant la session ZAP authentifiée et l'incident disque — à reconnecter avant toute nouvelle démo F-000 (`service wazuh-agent restart` ou `wazuh-control restart` dans chaque conteneur).
- [ ] Si repris : terminer la compilation Wazuh depuis les sources sur `demo-frontend` (Alpine) — outils de build partiellement installés, verrou apk à nettoyer (`rm -f /lib/apk/db/lock`) avant de relancer
- [ ] Intégrer l'installation de l'agent Wazuh directement dans `Dockerfile.backend` (et `Dockerfile.frontend` si Alpine finalement résolu) pour que ça survive aux futurs rebuilds — actuellement installé "à chaud", donc perdu à chaque recréation de conteneur (illustré une fois de plus par la régression des agents le 16-17/07)
- [x] Phase 2 : Trivy + Hadolint sur l'image Docker de l'application de démonstration
- [x] Phase 2 : Construire le pipeline GitHub Actions (CI) — 6 jobs verts
- [x] Lancer Semgrep sur la démo `Afrikraga-projet` (F-021, F-022)
- [ ] Envisager la réécriture de l'historique Git du repo réel pour supprimer la trace du secret (F-020)
- [ ] Lancer Gitleaks sur le nouveau repo `Afrikraga-projet` par précaution méthodologique
- [ ] Obtenir autorisation écrite de BS International pour ZAP/Burp sur le **site réel** (bloquant, jamais obtenue) — ne pas relancer sans validation explicite
- [x] OWASP ZAP sur la **démo** (`localhost:5173`) — F-023, 0 finding sur 5 URLs couvertes
- [x] Remettre `.env` (`VITE_API_URL`) et `config/cors.php` à leurs valeurs d'origine après F-023 — vérifié le 15/07
- [x] Relancer ZAP avec une couverture plus large (authentification, spider plus long) pour tester panier/checkout/admin — **Fait (16-17/07)**, voir F-025 : couverture complète en deux passes (Crawljax puis navigation manuelle rejouée via l'API ZAP), 11 URLs couvertes incluant `/cart`, `/checkout`, `/admin` et ses 5 sections, 40 alertes trouvées (uniquement des en-têtes de sécurité HTTP manquants, aucune injection ni problème d'autorisation détecté).
- [x] **Fix durable CORS (16-17/07)** : bug latent découvert dans `allowed_origins_patterns` (mauvais format de pattern, jamais exercé avant le scan authentifié) — corrigé avec de vraies regex PHP. `host.docker.internal` accepté en permanence sur n'importe quel port, plus besoin de bascule manuelle de `cors.php` pour de futurs scans ou tests réseau. Commité sur Git.
- [ ] Relancer auprès de l'encadrant la question en suspens : méthode d'application du pipeline au vrai repo (pièce jointe vs vraie PR)
- [x] Gap priorité moyenne — Policies Kubernetes (F-022) : securityContext vérifié déjà en place, Network Policies ajoutées et testées (deny-all + segmentation frontend/backend/mysql/redis)
- [x] Gap priorité moyenne — Workflow PR / protection de branche `main` sur `Afrikraga-projet` : "Require pull request before merging" + "Require status checks to pass" (5 checks obligatoires : Gitleaks, Trivy Scan, Checkout, Npm Audit, Composer Audit — `GHCR Publish` volontairement exclu, ne se déclenche que sur `push`) + "Require conversation resolution" + "Do not allow bypassing" activés. **Limitation découverte** : sur un repo privé, GitHub n'applique pas réellement ces règles hors compte Team/Enterprise ("Not enforced"). **Décision** : repo passé en public le 15/07 pour activer l'application réelle des règles ; test confirmé par `git push` direct refusé (`GH006: Protected branch update failed`). **Repo maintenu public volontairement pendant la période de soutenance** (pour pouvoir démontrer la protection en direct) — à repasser en privé après la soutenance, conformément à la décision de confidentialité prise après F-020.
- [x] **Incident de session (15/07) et correction** : un `git reset --hard` exécuté par erreur pendant la manipulation de la branche a fait régresser 3 fichiers vers une version antérieure non committée : `Dockerfile.backend` (perte de l'épinglage `bookworm`, retour au tag flottant `php:8.3-cli`), `docker-compose.yml` (perte de `init: true` sur le service `mysql`), et `AuthController.php` (perte du correctif F-024). Détecté par vérification systématique après le reset, corrigé en restaurant les 3 fichiers, et **committé via une vraie Pull Request** (`fix/restore-security-corrections`, PR #1) — bonne occasion de valider le workflow CI/PR de bout en bout en conditions réelles plutôt qu'en théorie.
- [x] Ajustement `.trivyignore` suite à la correction Dockerfile : l'épinglage sur `bookworm` change la version de Perl embarquée (5.36 au lieu de 5.40 sous l'ancien tag flottant trixie), rendant les CVE précédemment ignorées obsolètes. 4 nouvelles CVE analysées et documentées : `CVE-2026-13221` (perl, même famille que les CVE déjà écartées), `CVE-2025-7458` (sqlite3, non utilisé — app 100% MySQL/PDO), `CVE-2023-45853` (zlib, `will_not_fix` fournisseur), et **`CVE-2026-6653` (libxml2, cas différent)** — dépendance réelle du SDK Cloudinary utilisé en production, statut Trivy `fix_deferred` (aucun correctif Debian bookworm disponible à ce jour), risque accepté **temporairement** et marqué à réévaluer, pas mis sur le même plan que les CVE non exploitées.
- [ ] Repasser `Afrikraga-projet` en repo privé après la soutenance (actuellement public par choix temporaire, voir ci-dessus)
- [x] Gap priorité moyenne — **CD automatisé (16/07)** : runner self-hosted "PC-5" branché au pipeline (`ci.yml`, job `deploy` sur `runs-on: self-hosted`), déclenché automatiquement après `ghcr-publish` sur chaque push vers `main`. Images taguées par SHA de commit (traçabilité) plutôt que `:latest`. **Fonctionnel de bout en bout sur backend et frontend, vérifié par test de connexion inter-pods réel** (`file_get_contents` depuis le pod backend vers `http://frontend:4173` → HTML reçu).
  - **Incidents rencontrés et résolus en cours de route** (bon exemple de debug méthodique pour le rapport) :
    1. Politique d'exécution PowerShell (`Restricted`) bloquant les scripts générés par le runner Windows → `Set-ExecutionPolicy RemoteSigned -Scope CurrentUser`
    2. Syntaxe bash (`${VAR,,}`) incompatible avec PowerShell (shell par défaut sur runner Windows) → nom du propriétaire GHCR fixé en dur plutôt que calculé dynamiquement
    3. Secret `ghcr-pull-secret` contenant un caractère `\n` parasite en fin de token (corruption lors de la création initiale) → secret recréé proprement via `kubectl create secret docker-registry`
    4. **Instabilité réseau locale récurrente** : plusieurs pulls d'image interrompus en cours de transfert (`short read: unexpected EOF`, `RST_STREAM CANCEL`) autant sur `docker pull` que sur `crictl pull` interne au cluster — non résolu à la racine (limite de la connexion de la machine), contourné systématiquement par `docker pull` (plus robuste, reprend où il s'était arrêté) puis `k3d image import` pour injecter l'image directement dans le cluster sans repasser par un pull réseau
    5. `Dockerfile.frontend` ne copiait jamais le code source dans l'image (conçu uniquement pour un montage en volume via `docker-compose`, jamais testé en conditions k8s) → ajout de `COPY`/`npm ci`, bascule de `npm run dev` vers `npm run build && npm run preview` en environnement k8s (`command:` du déploiement)
    6. Conflit avec le durcissement F-022 (`runAsNonRoot: true`) : tentative de créer un nouvel utilisateur en collision de GID avec l'utilisateur `node` déjà présent dans l'image officielle, puis erreur "non-numeric user" en utilisant le nom plutôt que l'UID → résolu avec `USER 1000` (UID numérique de l'utilisateur `node` existant)
    7. `k8s/network-policies.yaml` et `k8s/frontend.yaml` corrigés (port 5173→4173) mais jamais réappliqués au cluster après correction du fichier Git (`kubectl apply` manuel oublié) → policy réappliquée
    8. `vite preview` renvoyait 403 Forbidden sur les requêtes inter-pods (protection Vite native validant l'en-tête `Host`) → ajout de `preview.allowedHosts: true` dans `vite.config.js`
    9. Incident ponctuel : saturation du disque système ayant fait planter silencieusement Docker Desktop (commandes bloquées sans erreur) — résolu par libération d'espace + redémarrage de Docker Desktop
  - **Limite connue et assumée (partiellement)** : le job `deploy` exécute uniquement `kubectl set image` (mise à jour du tag d'image), pas un `kubectl apply` déclaratif complet — un changement de `command`/`ports`/`env`/`securityContext` dans les manifestes `k8s/*.yaml` nécessite un `kubectl apply -f k8s/` manuel en complément. Amélioration possible non implémentée faute de temps : ajouter `kubectl apply -f k8s/` au job `deploy` avant le `set image`.
  - **✅ Corrigé (16/07)** : le `kubectl rollout status` timeoutait systématiquement à 120s à cause de la lenteur réseau locale sur le pull d'image (déploiement aboutissant presque toujours après coup, faux échec signalé par le pipeline). Timeout porté à 600s. **Vérifié par un run complet réussi de bout en bout sans aucune intervention manuelle** (run #34, "Deploy to k3d" : Success, 4m42s, tag `293006a`) — première fois que le CD fonctionne intégralement seul, preuve que la cause était bien la marge de timeout insuffisante et non un défaut de configuration.
- [x] Gap priorité moyenne — **intégration SOPS (16/07)** : script `decrypt-env.ps1` créé et versionné, automatise le déchiffrement local `.env.sops` → `.env` (`sops -d --input-type dotenv --output-type dotenv`), remplaçant le geste manuel documenté en commentaire dans le fichier. Testé avec succès (clé `age` référencée via `SOPS_AGE_KEY_FILE`). **Limitation assumée** : pas d'intégration CI/CD (déchiffrement automatique dans le pipeline) — écarté volontairement car nécessiterait de stocker la clé privée `age` comme secret GitHub Actions, jugé disproportionné vu que le repo est actuellement public (surface d'exposition supplémentaire non désirée en période de soutenance). Pas de lien avec les secrets Kubernetes (`afrikraga-backend-secret`) — clés différentes entre `.env.sops` (config docker-compose) et le secret k8s (`APP_KEY`/`DB_PASSWORD`/`CLOUDINARY_URL`), rapprochement des deux non fait, hors périmètre de cette session.
- [x] Gap priorité moyenne — **Scan ZAP authentifié avec couverture complète (16-17/07)** : voir F-025 — comptes de test client/admin créés, authentification Bearer/Sanctum configurée dans ZAP, scan actif lancé sur build de production (port 4173) couvrant 11 URLs (accueil, catalogue, panier, checkout, admin + 5 sections), 40 alertes trouvées (uniquement headers de sécurité HTTP manquants, recoupent F-011 ; aucune injection ni problème d'autorisation détecté). Plusieurs obstacles méthodologiques résolus (allowedHosts Vite, WebSocket HMR bloquant le spider AJAX, cache de config Laravel, bug latent des patterns CORS corrigé durablement, proxy navigateur peu fiable contourné via l'API ZAP).
- [x] **Incident méthodologique (16-17/07)** : `FINDINGS.md` accidentellement tronqué par un script PowerShell d'insertion automatique (perte de F-000, F-020, F-011-F-021, ~55% du fichier). Fichier non suivi par Git au moment de l'incident (pas de restauration possible via `git checkout`). Reconstruit à partir de l'historique de conversation, puis fusionné avec une sauvegarde antérieure retrouvée par l'utilisateur pour récupérer le détail complet des findings SAST/SCA. **Action corrective** : `FINDINGS.md` désormais suivi et commité sur Git, protégeant contre toute perte future.
- [ ] Gaps restants : question en suspens à l'encadrant (application du pipeline au vrai repo), `demo-frontend` (agent Wazuh sur Alpine) en pause, repasser `Afrikraga-projet` en privé après la soutenance, couvrir panier/checkout/admin en DAST authentifié (F-025, si temps disponible), fixer les 3 en-têtes de sécurité HTTP manquants (F-011/F-025, remédiation commune), reconnecter les 3 agents Wazuh déconnectés (`demo-backend`, `demo-redis`, `demo-mysql`) avant toute nouvelle démo F-000
