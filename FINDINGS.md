# FINDINGS.md â€” Suivi des vulnÃ©rabilitÃ©s et anomalies dÃ©tectÃ©es


---

### F-025 — Scan ZAP authentifié sur la démo — en-têtes de sécurité HTTP manquants

| Champ | Valeur |
|---|---|
| **Outil** | OWASP ZAP 2.17.0 (mode daemon + API REST, contexte authentifié Bearer/Sanctum) |
| **Cible** | `http://host.docker.internal:4173` (build de production, `vite preview`, démo `Afrikraga-projet`) |
| **Statut** | Terminé — **12 alertes** (0 High, 5 Medium, 6 Low, 1 Informational) |
| **Description** | Suite à F-023 (0 finding, couverture 5 URLs, non authentifié), ce scan reprend le DAST avec authentification Bearer (comptes de test `zap-client@demo.local` / `zap-admin@demo.local` créés via `tinker`) et une couverture élargie (page d'accueil, `/catalog`, assets, endpoints `/api/*`). Contrairement au dev server (`5173`, bloqué par le HMR WebSocket de Vite — voir obstacles ci-dessous), le scan cible le build de production (`4173`) pour éviter cette limitation. |
| **Findings détaillés** | 1. **Missing Anti-clickjacking Header** (Medium) — absence de `X-Frame-Options` sur `/` et `/catalog` ; le site peut être intégré dans une iframe tierce (clickjacking). 2. **CSP Header Not Set** (Medium) — aucune Content-Security-Policy sur `/` et `/catalog` ; surface XSS élargie en cas d'autre faille. 3. **X-Content-Type-Options Header Missing** (Low) — absent sur `/`, `/catalog`, assets JS/CSS, favicon ; risque mineur de MIME-sniffing. 4. **Timestamp Disclosure - Unix** (Low) — timestamp Unix détecté dans un asset JS compilé. 5. **Modern Web Application** (Informational) — détection automatique du framework SPA, sans impact sécurité. |
| **Endpoints API testés** | `/api/products`, `/api/categories`, `/api/cart`, `/api/banners`, `/api/auth/login` — capturés et scannés authentifié (token Bearer injecté via ZAP Replacer), **0 alerte** sur le périmètre API (headers de sécurité déjà corrects côté réponses JSON Laravel/Sanctum). |
| **Obstacles méthodologiques rencontrés et résolus** | (1) Vite `server.allowedHosts` ne couvrait pas `host.docker.internal` (uniquement `preview.allowedHosts`) → ajouté dans `vite.config.js`. (2) Serveur de dev Vite (`5173`) : le spider AJAX (navigateur Firefox headless piloté par Selenium/Crawljax) timeout systématiquement (`504 Gateway Timeout` après 20s) alors qu'un `curl` simple répondait instantanément — cause probable : connexion WebSocket HMR de Vite bloquant le rendu complet côté navigateur réel. Contourné en scannant le **build de production** (`npm run build` + `npm run preview`, port `4173`, sans WebSocket HMR) plutôt que le dev server — port ajouté à `docker-compose.yml`. (3) CORS backend : `host.docker.internal:4173` ajouté à `allowed_origins` dans `cors.php`, mais requêtes toujours bloquées (`403 Forbidden`) malgré la config correcte → cache de configuration Laravel obsolète (`php artisan config:clear` + `cache:clear` nécessaires après modification de `cors.php`, un simple restart de conteneur ne suffit pas). (4) Spider AJAX (Crawljax) limité sur les composants React modernes : navigation au-delà de la page d'accueil et `/catalog` non atteinte automatiquement (0 clic sur les fiches produits malgré `MaxDuration=5min`, `EventWait=2000ms`) ; un crash ponctuel du navigateur headless observé (`Error occurred while closing the browser`). **Décision** : couverture partielle acceptée (page d'accueil, catalogue, endpoints API authentifiés) plutôt que de basculer vers une navigation manuelle via proxy ZAP — amélioration réelle par rapport à F-023 sans sur-ingénierie disproportionnée pour le temps restant avant soutenance. |
| **Remédiation proposée** | Ajouter un middleware Laravel (ou configuration Nginx en prod) injectant les headers de sécurité manquants : `X-Frame-Options: DENY`, `Content-Security-Policy` (à définir selon les sources légitimes du site — CDN Tailwind, Cloudinary, etc.), `X-Content-Type-Options: nosniff`. Package `secure-headers` ou middleware natif Laravel envisageable. |
| **Limite de couverture non résolue** | Panier, checkout, et back-office admin restent non couverts par le scan actif (Crawljax n'a pas navigué jusque-là). Si le temps le permet après la soutenance : navigation manuelle via proxy ZAP (`127.0.0.1:8090`) pour capturer passivement ces zones avant un nouveau scan actif. |
# FINDINGS.md â€” Suivi des vulnÃ©rabilitÃ©s et anomalies dÃ©tectÃ©es
## Projet PFA DevSecOps â€” Afrikraga.com

DerniÃ¨re mise Ã  jour : 11/07/2026
Repo auditÃ© : https://github.com/Small-Danger/Afrikraga

---

## LÃ©gende

**SÃ©vÃ©ritÃ©** : CRITICAL / HIGH / MEDIUM / LOW / INFO
**Statut** : Ã€ valider Â· ConfirmÃ© Â· Faux positif Â· CorrigÃ© Â· AcceptÃ© (risque rÃ©siduel)

---

## RÃ©sumÃ© exÃ©cutif

| Outil | Scope | Findings | Statut global |
|---|---|---|---|
| Gitleaks | Historique Git (5 commits) | 0 | âœ… Aucun secret exposÃ© |
| TruffleHog | Historique Git (623 chunks) | 0 | âœ… Aucun secret exposÃ© |
| Semgrep (config auto) | 217 fichiers (frontend + backend) | 10 pertinents (+ 6 hors pÃ©rimÃ¨tre) | âœ… TriÃ© |
| Enlightn (env. Docker Compose complet) | Backend Laravel (67 checks) | 17 Ã©checs (dont 1 majeur) | âœ… TriÃ© |
| ESLint Security | Frontend JS/React | 55 (rÃ¨gle unique) | âœ… Tous faux positifs confirmÃ©s |
| composer audit | DÃ©pendances Composer | 24 advisories | âœ… TriÃ© prod/dev |
| npm audit | DÃ©pendances npm | 13 vulnÃ©rabilitÃ©s | âœ… TriÃ© prod/dev |
| OWASP Dependency-Check | 443 dÃ©pendances (209 uniques) | 59 vulnÃ©rabilitÃ©s | âœ… TriÃ©, 1 faux positif critique Ã©cartÃ© |
| OWASP ZAP | Site rÃ©el (autorisation requise) | â€” | â³ BloquÃ© â€” autorisation BS International en attente |
| Burp Suite | API Sanctum + formulaires | â€” | â³ BloquÃ© â€” idem |
| Trivy / Hadolint | Image Docker / Dockerfile | â€” | â³ Phase 2 |

---

## Finding prioritaire du projet

### F-000 â€” Absence de protection contre les attaques par force brute

| Champ | Valeur |
|---|---|
| **Outil** | Enlightn (Check 60/67) |
| **SÃ©vÃ©ritÃ©** | MEDIUM-HIGH |
| **CVSS estimÃ©** | 5.3â€“6.5 |
| **Statut** | ConfirmÃ© |
| **Description** | Aucune limitation (rate limiting / throttling) dÃ©tectÃ©e sur les routes d'authentification. Un attaquant peut tenter un nombre illimitÃ© de combinaisons identifiant/mot de passe. |
| **Routes concernÃ©es** | `api/auth/register`, `api/auth/login`, `api/auth/logout`, `api/auth/forgot-password` |
| **RemÃ©diation proposÃ©e** | Ajouter le middleware `throttle` de Laravel (ou `RateLimiter`) sur ces routes, avec une limite raisonnable (ex. 5 tentatives/minute par IP). VÃ©rifier Ã©galement si un throttling existe dÃ©jÃ  au niveau serveur web (Nginx/Apache), auquel cas ce finding serait Ã  requalifier en risque rÃ©siduel acceptÃ©. |

---

## Findings SAST â€” Semgrep

### F-001 Ã  F-010 â€” Log forging (format string injection)

| Champ | Valeur |
|---|---|
| **Outil** | Semgrep |
| **RÃ¨gle** | `javascript.lang.security.audit.unsafe-formatstring.unsafe-formatstring` |
| **SÃ©vÃ©ritÃ©** | INFO |
| **CVSS estimÃ©** | 2.0â€“3.5 (Low) |
| **Statut** | Ã€ valider (probable faux positif faible, non exploitÃ© pour l'instant) |
| **Description** | ConcatÃ©nation de variables non-littÃ©rales dans des appels `console.log`, permettant potentiellement l'injection de faux logs. |
| **Fichiers concernÃ©s** | `bs_shop_frontend/src/components/admin/ImageManager.jsx:79`, `bs_shop_frontend/src/components/ui/Form.jsx:26,28,44,46,61,79,92,116`, `bs_shop_frontend/src/pages/admin/Dashboard.jsx:282` |
| **RemÃ©diation proposÃ©e** | Utiliser des chaÃ®nes de format constantes, ou passer les variables comme arguments sÃ©parÃ©s. |

**Note mÃ©thodologique** : un second scan (11/07) a rÃ©vÃ©lÃ© 6 findings supplÃ©mentaires de rÃ¨gles `yaml.docker-compose.security.*` â€” **hors pÃ©rimÃ¨tre**, car ils concernent `audit-env/docker-compose.yml`, l'infrastructure de test crÃ©Ã©e pour l'audit lui-mÃªme, et non le code source d'Afrikraga. Non comptabilisÃ©s dans les findings du projet.

---

## Findings SAST â€” Enlightn (backend Laravel)

Scan rÃ©alisÃ© dans un environnement Docker Compose complet (MySQL 8 + Redis 7 + `.env` de test), Ã©liminant les faux Ã©checs liÃ©s Ã  l'absence d'environnement applicatif observÃ©s lors d'un premier scan partiel. RÃ©sultat : 46 passÃ©s / 17 Ã©chouÃ©s / 4 non applicables sur 67 checks.

### F-011 â€” Absence de Content-Security-Policy

| Champ | Valeur |
|---|---|
| **SÃ©vÃ©ritÃ©** | MEDIUM |
| **CVSS estimÃ©** | 4.0â€“5.0 |
| **Statut** | ConfirmÃ© |
| **Description** | Aucun header CSP adÃ©quat (`script-src`/`default-src` sans `unsafe-eval`/`unsafe-inline`), protection XSS incomplÃ¨te au niveau HTTP. |
| **RemÃ©diation proposÃ©e** | Configurer un middleware CSP dans Laravel (ex. package `spatie/laravel-csp`). |

### F-012 â€” Configuration PHP non sÃ©curisÃ©e

| Champ | Valeur |
|---|---|
| **SÃ©vÃ©ritÃ©** | LOW |
| **CVSS estimÃ©** | 2.0â€“3.0 |
| **Statut** | ConfirmÃ© |
| **Description** | `expose_php`, `display_errors`, `display_startup_errors` non dÃ©sactivÃ©s â€” facilite le fingerprinting de la stack par un attaquant. |
| **RemÃ©diation proposÃ©e** | Ajuster `php.ini` en production : `expose_php=Off`, `display_errors=Off`, `display_startup_errors=Off`, `log_errors=On`. |

### F-013 â€” Appel `env()` hors fichier de configuration

| Champ | Valeur |
|---|---|
| **SÃ©vÃ©ritÃ©** | LOW (fiabilitÃ© plus que sÃ©curitÃ© pure) |
| **Statut** | ConfirmÃ© |
| **Fichier** | `app/Services/CloudinaryService.php:17` |
| **Description** | Une fois la config mise en cache (`config:cache`), les appels `env()` hors fichiers de config renvoient `null` â€” casse potentielle en production si le cache de config est activÃ©. |
| **RemÃ©diation proposÃ©e** | DÃ©placer la lecture de la variable Cloudinary dans `config/services.php`, puis utiliser `config('services.cloudinary.xxx')`. |

### F-014 â€” DÃ©pendances backend avec vulnÃ©rabilitÃ©s connues (dÃ©tail par package)

| Champ | Valeur |
|---|---|
| **Outil** | Enlightn (Check 66/67), corrobore composer audit |
| **SÃ©vÃ©ritÃ©** | Variable selon package (voir composer audit ci-dessous) |
| **Statut** | ConfirmÃ© |
| **Description** | 10 packages vulnÃ©rables activement utilisÃ©s : `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `laravel/framework`, `symfony/http-foundation`, `symfony/mailer`, `symfony/mime`, `symfony/polyfill-intl-idn`, `symfony/routing`, `phpunit/phpunit`, `symfony/yaml`. |
| **RemÃ©diation** | Voir section SCA ci-dessous pour le dÃ©tail CVE par package. |

### Findings environnementaux (non comptabilisÃ©s comme vulnÃ©rabilitÃ©s)

Checks Ã©chouÃ©s liÃ©s Ã  des choix d'architecture ou de configuration non-sÃ©curitÃ©, Ã  noter pour un rapport complet mais hors du pÃ©rimÃ¨tre "vulnÃ©rabilitÃ©" : absence de cache/route/view/config caching en environnement non-local (performance), Horizon non utilisÃ© avec Redis (performance), pas de pages d'erreur personnalisÃ©es (fingerprinting lÃ©ger), permissions de fichiers larges (Ã  vÃ©rifier en conditions rÃ©elles de dÃ©ploiement Railway), dÃ©pendances dev installÃ©es hors environnement local (propre Ã  l'environnement de test, Ã  vÃ©rifier sur la vraie CI/CD d'Afrikraga).

---

## Findings SAST â€” ESLint Security (frontend)

### F-015 â€” 55 occurrences `detect-object-injection` â€” FAUX POSITIFS CONFIRMÃ‰S

| Champ | Valeur |
|---|---|
| **Outil** | ESLint Security |
| **RÃ¨gle** | `security/detect-object-injection` |
| **Statut** | **Faux positif confirmÃ©** (Ã©chantillon vÃ©rifiÃ© manuellement) |
| **Description** | RÃ¨gle connue pour un taux Ã©levÃ© de faux positifs sur du code React utilisant des accÃ¨s dynamiques standards. VÃ©rification manuelle sur `services/api.js:248` (clÃ©s issues d'un FormData construit cÃ´tÃ© client) et `hooks/useCacheManager.js` (clÃ©s issues d'un objet de config interne codÃ© en dur) : dans les deux cas, aucune entrÃ©e utilisateur non filtrÃ©e n'atteint l'accÃ¨s dynamique. |
| **Action** | Aucune remÃ©diation nÃ©cessaire. MentionnÃ© dans le rapport pour dÃ©montrer la dÃ©marche de validation manuelle des rÃ©sultats automatisÃ©s. |

---

## Findings SCA â€” DÃ©pendances (composer audit, npm audit, Dependency-Check)

### F-016 â€” React Router : vulnÃ©rabilitÃ©s critiques actives en production

| Champ | Valeur |
|---|---|
| **Outil** | npm audit, corroborÃ© par OWASP Dependency-Check |
| **Package** | `react-router` / `react-router-dom` 7.0.0â€“7.14.2 |
| **SÃ©vÃ©ritÃ©** | HIGH |
| **CVSS estimÃ©** | 7.0â€“8.5 |
| **Statut** | ConfirmÃ© â€” actif en production (dÃ©pendance directe du frontend, pas dev-only) |
| **Description** | 11 CVE incluant XSS via redirections ouvertes, XSS stockÃ© via header Location, CSRF sur Server Actions, et un risque de RCE via dÃ©sÃ©rialisation non authentifiÃ©e de `turbo-stream`. **Finding le plus sÃ©rieux identifiÃ© sur l'ensemble du frontend.** |
| **RemÃ©diation proposÃ©e** | `npm audit fix`, Ã  tester en environnement de dÃ©veloppement avant dÃ©ploiement (montÃ©e de version potentiellement majeure). |

### F-017 â€” DÃ©pendances backend Laravel/Symfony/Guzzle vulnÃ©rables (production)

| Champ | Valeur |
|---|---|
| **Outil** | composer audit, corroborÃ© par Enlightn Check 66 |
| **SÃ©vÃ©ritÃ©** | HIGH Ã  LOW selon package |
| **Statut** | ConfirmÃ© â€” actif en production (dÃ©pendances transitives obligatoires de Laravel + Cloudinary) |
| **DÃ©tail par package** | |
| `laravel/framework` 12.24.0 | CVE-2026-48019 â€” CRLF injection rÃ¨gle email par dÃ©faut (HIGH) |
| `symfony/http-foundation` 7.3.2 | CVE-2025-64500 â€” bypass autorisation via PATH_INFO (HIGH) ; CVE-2026-48736 â€” bypass SSRF IPv6 (MEDIUM) |
| `symfony/mime` 7.3.2 | CVE-2026-45067 â€” injection en-tÃªtes email/SMTP via CRLF (HIGH) ; CVE-2026-45070 â€” injection en-tÃªte via paramÃ¨tre MIME (MEDIUM) |
| `symfony/mailer` 7.3.2 | CVE-2026-45068 â€” injection d'arguments SendmailTransport (MEDIUM) |
| `guzzlehttp/guzzle` 7.9.3 + `psr7` 2.7.1 | 4 CVE â€” downgrade HTTPSâ†’HTTP silencieux, injection CRLF, confusion d'hÃ´te (MEDIUM) â€” actifs via Cloudinary |
| `symfony/routing` 7.3.2 | 2 CVE â€” contournement de contraintes de route, injection d'URL externe (MEDIUM) |
| `symfony/yaml` 7.3.2 | 3 CVE â€” dÃ©ni de service par rÃ©cursion/ReDoS (LOW) |
| `symfony/polyfill-intl-idn` | CVE-2026-46644 â€” Ã©quivalence Punycode non sÃ©curisÃ©e (LOW) |
| **RemÃ©diation proposÃ©e** | `composer update --with-all-dependencies`, Ã  tester avant dÃ©ploiement en production. |

### F-018 â€” Faux positif critique Ã©cartÃ© : React Server Components

| Champ | Valeur |
|---|---|
| **Outil** | OWASP Dependency-Check |
| **Statut** | **Faux positif confirmÃ© et Ã©cartÃ©** |
| **Description** | 4 CVE (dont 1 CRITICAL â€” RCE prÃ©-authentification CVE-2025-55182) associÃ©es par erreur de matching CPE au package `react:19.1.1`, concernant en rÃ©alitÃ© les packages `react-server-dom-parcel/turbopack/webpack`. VÃ©rification via `npm ls react-server-dom-*` : **packages absents de l'arbre de dÃ©pendances installÃ©es**. Afrikraga est un SPA classique via Vite, n'utilisant pas React Server Components. |
| **Action** | Aucune remÃ©diation nÃ©cessaire. DocumentÃ© pour dÃ©montrer la dÃ©marche de validation croisÃ©e entre outils SCA. |

### F-019 â€” DÃ©pendances de build (Vite, Rollup, Babel, etc.) â€” dev-only

| Champ | Valeur |
|---|---|
| **Outil** | npm audit, OWASP Dependency-Check |
| **Statut** | ConfirmÃ© mais **non exploitable en production** |
| **Description** | La majoritÃ© des CVE restantes (vite, rollup, @babel/core, postcss, tar, minimatch, picomatch, flatted, brace-expansion, js-yaml, ajv) concernent des outils de build/dÃ©veloppement, jamais exÃ©cutÃ©s dans le navigateur des utilisateurs finaux. |
| **RemÃ©diation proposÃ©e** | Mise Ã  jour recommandÃ©e par hygiÃ¨ne gÃ©nÃ©rale (`npm audit fix`), mais non prioritaire du point de vue du risque de production. |

---

## Risques structurels identifiÃ©s (hors scan automatisÃ©)

### R-001 â€” Script de dÃ©ploiement non sÃ©curisÃ©

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle â€” `deploy-railway.sh` |
| **SÃ©vÃ©ritÃ©** | MEDIUM (risque latent, non matÃ©rialisÃ© Ã  ce jour) |
| **Statut** | ConfirmÃ© |
| **Description** | Le script exÃ©cute `git add .` sans vÃ©rification prÃ©alable des secrets avant push sur `main`, sans contrÃ´le de sÃ©curitÃ© automatisÃ© dans la chaÃ®ne de dÃ©ploiement. |
| **Preuve associÃ©e** | CorroborÃ© par l'absence totale d'outils de sÃ©curitÃ© dans le pipeline actuel (aucun CI/CD constatÃ©). Aucun secret trouvÃ© Ã  ce jour (Gitleaks/TruffleHog), mais le risque reste structurel. |
| **RemÃ©diation proposÃ©e** | IntÃ©grer Gitleaks en pre-commit hook + dans le futur pipeline GitHub Actions (bloquant). Ajouter un `.gitignore` strict pour les fichiers `.env`. |

### R-002 â€” Absence totale de pipeline de sÃ©curitÃ©

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle du repo |
| **SÃ©vÃ©ritÃ©** | HIGH (absence de garde-fou systÃ©mique) |
| **Statut** | ConfirmÃ© |
| **Description** | Aucun SAST, SCA, secret scanning ou DAST dans le workflow de dÃ©veloppement actuel â€” le code va en production sans vÃ©rification automatique. |
| **RemÃ©diation proposÃ©e** | Objet de la Phase 2 du projet (pipeline GitHub Actions). |

### R-003 â€” DÃ©pendance Cloudinary sans audit â€” confirmÃ© via SCA

| Champ | Valeur |
|---|---|
| **Source** | Revue manuelle initiale, confirmÃ©e par composer audit (F-017) |
| **SÃ©vÃ©ritÃ©** | MEDIUM |
| **Statut** | ConfirmÃ© |
| **Description** | Cloudinary gÃ¨re les uploads d'images produits ; tire `guzzlehttp/guzzle` et `guzzlehttp/psr7` en dÃ©pendances, tous deux avec des CVE actives (voir F-017). Les credentials eux-mÃªmes ne sont pas exposÃ©s (confirmÃ© par Gitleaks/TruffleHog). |
| **RemÃ©diation proposÃ©e** | Mettre Ã  jour Guzzle/PSR7 (`composer update`), vÃ©rifier la rotation des clÃ©s Cloudinary et les permissions du compte. |

---

## Prochaines actions

- [x] Trier F-001 Ã  F-010 (Semgrep)
- [x] Lancer Enlightn sur le backend Laravel (environnement complet)
- [x] Lancer ESLint Security sur le frontend
- [x] Lancer npm audit + composer audit
- [x] Lancer OWASP Dependency-Check
- [ ] Obtenir autorisation Ã©crite de BS International pour ZAP/Burp (bloquant)
- [ ] Lancer OWASP ZAP et Burp Suite sur le site rÃ©el une fois autorisation obtenue
- [ ] Phase 2 : Trivy + Hadolint sur l'image Docker de l'application de dÃ©monstration

