# wra602d-microservice — Mailer

Micro-service Symfony 8.0 / PHP 8.4 dédié à l'envoi d'emails transactionnels pour le jeu WRA602 City Builder. Appelé en HTTP par le backoffice (`wra602d-backend`) via un token partagé.

## Stack

- Symfony 8.0 (skeleton API-only, sans `--webapp`)
- `symfony/mailer` + Twig pour les templates HTML/texte
- `symfony/security-bundle` avec un Authenticator custom (token partagé, `hash_equals`)
- `symfony/uid` pour les `messageId` ULID
- Mailpit en dev (Docker), `null://null` en test
- PHPUnit 13

## Démarrage

```bash
composer install
docker compose up -d                       # Mailpit sur 1026 (SMTP) et 8026 (UI)
cp .env .env.local                         # ou éditer .env.local existant
openssl rand -hex 32                       # générer un token et le coller dans .env.local
symfony serve --port=8001
```

Vérifier que tout tourne :

```bash
curl http://localhost:8001/health        # → {"status":"ok"}

curl -X POST http://localhost:8001/api/send-email \
     -H "X-Microservice-Token: $TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"to":"alice@example.com","subject":"Bienvenue","template":"welcome","context":{"username":"alice"}}'
# → 202 + messageId, mail visible sur http://localhost:8026
```

## Variables d'environnement

| Var | Rôle | Exemple |
|---|---|---|
| `MAILER_DSN` | DSN Symfony Mailer | `smtp://localhost:1026` (dev) / `null://null` (test) |
| `MAILER_FROM` | Adresse "From" des mails | `WRA602 <noreply@wra602.local>` |
| `MICROSERVICE_SHARED_SECRET` | Secret partagé avec le backoffice | `openssl rand -hex 32` |
| `FRONTEND_URL` | URL injectée dans certains templates | `http://localhost:5173` |
| `CORS_ALLOW_ORIGIN` | Regex des origines CORS autorisées | `^https?://(localhost\|127\.0\.0\.1)(:[0-9]+)?$` |

`.env.local` n'est jamais committé (cf. `.gitignore`).

## Endpoints

| Méthode | URL | Auth | Description |
|---|---|---|---|
| GET | `/health` | publique | health check |
| POST | `/api/send-email` | header `X-Microservice-Token` | envoi d'email templaté |

### POST /api/send-email — payload

```json
{
  "to": "alice@example.com",
  "toName": "Alice Martin",
  "subject": "Nouveau record !",
  "template": "new_high_score",
  "context": { "username": "alice", "score": 12450, "rank": 3, "cityName": "Aurelius" }
}
```

Réponses :
- `202 Accepted` — `{"status":"accepted","messageId":"<ulid>"}`
- `401 Unauthorized` — token manquant ou invalide
- `404 Not Found` — template inconnu
- `422 Unprocessable Entity` — payload invalide (Validator)

## Templates disponibles

| Identifiant | Variables `context` requises |
|---|---|
| `welcome` | `username` (string), `frontendUrl` (string, optionnel) |
| `new_high_score` | `username`, `score`, `rank`, `cityName` |

Pour ajouter un nouveau template : créer le binôme Twig dans `templates/emails/<nom>.{html,txt}.twig` et ajouter une entrée dans `App\Mailer\TwigTemplateResolver::MAP`. Aucun autre fichier à modifier (Open/Closed).

## Sécurité

- **CORS** : `nelmio/cors-bundle` restreint les origines via `CORS_ALLOW_ORIGIN` (regex). Par défaut, seules les requêtes provenant de `localhost`/`127.0.0.1` sont acceptées. Le header `X-Microservice-Token` est autorisé en plus de `Content-Type` et `Authorization`.
- **Header partagé** : `X-Microservice-Token` comparé en `hash_equals` au secret stocké dans `MICROSERVICE_SHARED_SECRET`.
- Le secret est injecté avec `#[\SensitiveParameter]` pour qu'il ne fuite pas dans les stack traces.
- Endpoint `/health` public (pour les health-checks d'orchestration), tout le reste sous `^/api` exige `ROLE_MICROSERVICE_CLIENT`.
- Logs métier dans le channel monolog dédié `mailer` (`var/log/mailer.log` en dev).

**Évolution possible** : remplacer le token plat par une signature HMAC-SHA256 (`X-Microservice-Timestamp` + `X-Microservice-Signature`) pour rejouer-protéger les requêtes. Cible déjà encapsulée dans `SharedTokenAuthenticator`, l'évolution se limite à cette classe.

## SOLID

- **S** (SRP) — `SendEmailController` ne fait que désérialiser et déléguer ; `SymfonyMailSender` ne fait qu'envoyer ; `TwigTemplateResolver` ne fait que mapper un identifiant logique.
- **O** (OCP) — Ajouter un template = ajouter une ligne dans la `MAP`. Aucune classe existante n'est modifiée.
- **L** (LSP) — Toute implémentation de `MailSenderInterface` est substituable (en test, on injecte un fake si besoin).
- **I** (ISP) — `MailSenderInterface` et `TemplateResolverInterface` exposent strictement ce dont leurs consommateurs ont besoin.
- **D** (DIP) — Le contrôleur dépend de `MailSenderInterface`, pas de la classe concrète. Idem pour le mailer (interface `MailerInterface` Symfony).

## Tests

```bash
vendor/bin/phpunit
```

Couvre :
- contrôleur (401 / 422 / 404 / 202 + `assertEmailCount(1)`)
- résolveur de templates (cas connu / inconnu)
- authenticator (token vide / faux / correct)

## Appel depuis le backoffice

Côté `wra602d-backend/`, configurer un scoped HttpClient (`mailer.client`) avec `base_uri = http://localhost:8001` et le header `X-Microservice-Token` injecté automatiquement (cf. `config/packages/framework.yaml`). Les notifications sont envoyées par `App\Notifier\HttpMailerNotifier` (interface `MailerNotifierInterface`).
