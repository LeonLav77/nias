# Nias Age Verification

Reusable Laravel package for verifying a buyer's adulthood through the Croatian
e-Građani / m-Građani (Nias) system. A consuming Laravel application installs the
package and uses its public API to decide whether an order requires verification,
start the flow, and retain proof that the check happened.

Supports Laravel 11/12/13 and PHP 8.2+. Namespace `Vendor\NiasAgeVerification`,
PSR-4 rooted at `src/`.

## Repository state (read this first)

The scaffold is empty. These files exist but contain **zero bytes**:

- `config/nias-age-verification.php`
- `routes/web.php`
- `src/NiasAgeVerificationServiceProvider.php`

`database/migrations/` and `resources/views/` are empty directories. There is no
`tests/` directory, no `phpunit.xml`, no `README.md`.

`composer.json` currently requires only `php: ^8.2` and
`illuminate/support: ^11.0|^12.0|^13.0`. That is not enough to build the flow —
see "Missing dependencies" below. Nothing in this repo has been run or tested yet,
so treat every section here as a specification to implement, not as a description
of existing behaviour.

## Dependencies

Only `illuminate/support` is installed, which isn't enough to build the flow. Two
gaps to fill as the code that needs them lands:

- **HTTP** for the token exchange and JWKS fetch. `illuminate/http` is the natural
  fit and its `Http::fake()` helps in tests, but any sane client works.
- **JWT/JWKS** for RS256 verification. `web-token/jwt-library` and
  `firebase/php-jwt` are both reasonable; the choice is open. What isn't open:
  don't hand-roll RS256, and don't base64-decode the token to read claims without
  verifying the signature.
- Dev: a test runner and, for package testing, `orchestra/testbench` against the
  Laravel majors you want covered.

Keep the runtime list lean and skip a generic abstraction layer over whichever
HTTP client you pick.

## Agreed architecture

Decided during design; the rest of this document is subordinate to it.

**Guest checkout, no authenticated users, stateless REST API.** No account to key
against and no session to carry state, so the frontend holds an opaque token
between the callback and checkout. Lookup storage is the audit table.

**The flow:**

1. `POST /age-verification/start` — frontend calls before checkout. Scans the cart;
   nothing eligible → "not required", checkout proceeds untouched. Something
   eligible → generate PKCE + `state` + `nonce`, store server-side, return the
   Nias authorize URL.
2. Buyer confirms in m-Građani; Nias redirects to our callback.
3. **Callback** — resolve the stored `state` (reject missing/expired/reused),
   exchange the code, validate the ID token fully, then write the
   `order_age_confirmations` row with a random opaque `verification_token`,
   `expires_at` (the token's `exp`), and `consumed_at` null. Redirect to the
   frontend with the token, distinguishing approved from denied.
4. Frontend sends the token with `/checkout` (header or body).
5. **Middleware** — repeats the *same* eligibility scan. Nothing eligible → pass
   through. Something eligible → look up the row by token, require unexpired and
   unconsumed and adult, then backfill `order_id` and set `consumed_at`. Missing,
   expired, consumed, or false → refuse.

**The raw ID token is never stored or returned.** Validate signature and claims at
the callback, read `age.alcohol`, discard. The `verification_token` is our own
opaque value, unrelated to the JWT.

**`verification_token` is a bearer credential and travels in a URL**, so: generate
with `random_bytes` (32 bytes, hex or base64url), single-use via `consumed_at`,
short-lived via `expires_at`. Look it up in constant time or treat it as a secret
in logs — never log it. Index the column and make it unique.

**Validity window: strictly the ID token's `exp`.** Tradeoff accepted: if Nias
issues a short `exp` (often 5–15 min, sometimes 60s), a buyer who lingers over the
cart is refused and must re-verify. Fails closed, so it is safe — but confirm the
real `exp` and revisit if it is very short.

**One eligibility check, two callers.** The start endpoint and the middleware call
the *same* service. The middleware is the security boundary; the endpoint is only a
UX affordance. Two copies of the scan will drift.

**Verification is bearer-token-bound and nothing more.** Anyone holding the token
during its window can use it once. There is no account to tie it to. Single-use
plus the short window carry the whole security burden — an accepted limitation:
the obligation is a credible check, not an unbypassable one.

## Structure

Only two names are part of the public contract and must not move: the
`IsAgeRestricted` interface and the `order_age_confirmations` table. Everything else
is internal — organise it however the code reads best, and let the layout follow
the implementation rather than the reverse.

The responsibilities that need a home somewhere:

- PKCE generation and the authorization URL
- server-side storage of `state` / `nonce` / `code_verifier`, single-use
- the token exchange and JWKS fetch (keep this behind an interface so tests can
  fake the transport)
- ID-token signature and claim validation
- the cart eligibility scan — one service, called by both the start endpoint and
  the middleware
- the orchestration, plus the audit write and `verification_token` generation
- three HTTP surfaces: the start endpoint, the callback, and the checkout
  middleware

Several of these are small enough to share a class; splitting one per file is
fine too. Start with whatever is simplest and extract when a piece grows or needs
its own test seam. Create files as features land — no empty placeholders.

Fixed by Laravel package convention: `config/nias-age-verification.php`,
`database/migrations/`, `routes/web.php`, `resources/views/`, and the existing
`src/NiasAgeVerificationServiceProvider.php`.

## Public API

Only `IsAgeRestricted` is a frozen contract — the consuming app implements it, so its
shape can't drift:

```php
namespace Vendor\NiasAgeVerification\Contracts;

interface IsAgeRestricted
{
    public function isAgeRestricted(): bool;
}
```

`isAgeRestricted()` means *this product is subject to the age-verification
requirement*. It does **not** mean the current buyer has passed verification.
Keep those two ideas distinct wherever they meet in the code.

Beyond that, the shape follows the flow in "Agreed architecture": something to
scan a cart for eligible items, something to begin a verification and hand back a
redirect URL, and something to complete one from the callback's `code` and
`state`. Roughly:

```php
public function isVerificationRequired(iterable $items): bool;   // shared by endpoint + middleware
public function start(iterable $items): string;                  // authorize URL
public function complete(string $code, string $state): /* result */;
```

Whether the result is a value object, a bool, or an enum is open — pick what the
calling code needs. It must distinguish approved from denied from errored, since
the callback redirects differently for each. Whatever it is, it carries no name,
OIB, or document data.

The cart is passed in as items implementing `IsAgeRestricted`, so the package never
needs to know how the application stores carts.

Avoid baking in a concrete product class, order class, primary-key type, or
relationship name — scalar order IDs or configurable model references keep the
package installable in different applications.

## Nias flow implementation notes

Authorization-code flow with PKCE (S256). This section is the exception to the
flexibility above — the steps and validations are dictated by the protocol and by
the ministry's technical instructions, not by taste. How you structure the code
around them is still open.

1. **PKCE**: random `code_verifier`, 43–128 chars from
   `[A-Za-z0-9-._~]`. `code_challenge = BASE64URL(SHA256(verifier))`, **unpadded**
   — strip `=` and translate `+/` to `-_`. Use `random_bytes`, never `rand`/`mt_rand`.
2. **State store**: `state`, `nonce`, `code_verifier`, order ID, and an expiry,
   held server-side (cache or a short-lived table) keyed by `state`. The verifier
   must never reach the browser. Consumption must be single-use — delete or mark
   used *before* the token exchange so a replayed callback cannot reuse it.
3. **Authorize redirect**: `response_type=code`, `client_id`, registered
   `redirect_uri`, `scope=age.alcohol`, `state`, `nonce`, `code_challenge`,
   `code_challenge_method=S256`.
4. **Callback**: reject missing, expired, reused, or mismatched `state` *before*
   touching the code. Take the order ID from the stored state, never from the
   query string.
5. **Token exchange**: backend-only POST, `application/x-www-form-urlencoded`,
   `grant_type=authorization_code`, `code`, exact `redirect_uri`, `client_id`,
   original `code_verifier`. Apply the configured timeout.
6. **ID token validation** — all of these, before reading any claim:
   signature via JWKS, `alg` is RS256 (reject `none`, HMAC, and any algorithm
   confusion), `iss` matches config, `aud` matches `client_id`, `nonce` matches
   the stored value, `exp` not passed, `iat` sane with a small leeway.
7. **Decision**: read only `age.alcohol`. `true` permits. `false`, a missing
   claim, an invalid token, a transport failure, a timeout, or a refusal all deny.
8. **Persist** the audit record, then resume the application's checkout flow.

Fail closed everywhere. There is no "allow on error" branch.

Endpoint URLs, issuer, JWKS URL, client ID, and redirect URI are deployment
configuration. Do not hard-code the example paths from the ministry's technical
instructions — the real values arrive with merchant registration
(`client_id` is issued by MPUDT after registering with MINGO).

## Audit record

A migration and model for `order_age_confirmations`. The table name and these
columns are the public part, since the consuming app queries them:

- `id`
- `order_id`, indexed, **nullable**, type compatible with the app's order key
  (configurable column type, or a big integer with a documented override).
  Nullable because guest checkout means no order exists at callback time — the row
  is written during the callback and `order_id` is backfilled by the middleware
  once the order is created. A consequence: "one confirmation per order" cannot be
  enforced as a database constraint.
- the Nias response as JSON, redacted
- `timestamps`

Worth adding when available, though the exact names are yours: the normalized
boolean result, a provider session/transaction reference, and the verification
time, plus `verification_token` (unique, indexed), `expires_at`, and
`consumed_at`. Cast the JSON column to array and index whatever gets queried.

The hard limits are about what must not land in the table: no `code_verifier`,
`state`, secrets, or private keys; no personal identity fields; and no raw ID
token absent a documented legal reason — prefer the minimal claims. `sub` is a
pairwise pseudonym and isn't needed for the age decision, so don't use it to
identify anyone.

Treat the table as append-only from the package's side. If an order can be checked
more than once, either keep every attempt or define an explicit
attempt/idempotency policy — evidence should never be silently overwritten.

## Service provider

Standard Laravel package wiring: merge the config in `register()` along with the
container bindings; in `boot()`, publish config and migrations and load routes
and (namespaced) views. Bind behind interfaces wherever a test will want to swap
the implementation — the transport especially.

The one firm rule: no network calls and no side effects during register or boot.

## Configuration

`config/nias-age-verification.php` needs to cover the Nias endpoints (authorize,
token, JWKS), the issuer, the `client_id`, the `redirect_uri`, the scope (default
`age.alcohol`), HTTP timeout, JWKS cache TTL, state TTL, the route
prefix/middleware, the frontend redirect targets for approved and denied
outcomes, and however you reference the order key or model. Naming and nesting
are yours to choose.

The validity window is the ID token's `exp` per the agreed design. Consider
exposing it as a config value defaulting to `exp` anyway, so a deployment can
shorten it — or work around an unusably short `exp` — without a code change.

Secrets and environment-dependent values come from `env()` in the config file
only, never read elsewhere. Missing required config should fail closed with a
clear exception rather than firing a request with an empty `client_id`.

## Routes

Two named routes — the start endpoint and the callback — behind a configurable
prefix, plus the checkout middleware registered for the app to apply.

Routes live in the `api` group; nothing here depends on sessions or cookies. The
callback must not trust return URLs or order IDs from the browser: resolve
everything from the server-side `state` record, and take redirect targets from
config.

The `state` record still needs server-side storage without a session — use the
cache or a short-lived table keyed by `state`.

## Security rules

- HTTPS in production; exact registered `redirect_uri`.
- `state` is the CSRF defence — always validate it.
- PKCE S256 always; verifier stays server-side.
- Authorization codes are one-time and short-lived.
- Validate signature and all claims before reading `age.alcohol`.
- Cache JWKS on a controlled TTL, support rotation, and never skip signature
  validation when a key is unavailable — deny instead.
- Never log codes, verifiers, ID tokens, secrets, or personal data. Redact
  sensitive protocol values from exceptions and request logs.
- Restrict access to audit records through the application's own authorization.
## Testing

No test suite exists yet — a runner, a config, and a `tests/` directory all need
creating. What's worth covering, in rough priority:

- ID-token validation failure paths: bad signature, wrong `iss`, wrong `aud`,
  wrong `nonce`, expired, `alg` confusion, missing `age.alcohol`
- state/nonce matching, expiry, and replay rejection
- fail-closed behaviour on malformed or unreachable Nias responses
- PKCE output (charset, length, unpadded challenge) and the authorize URL params
- the token-exchange request body, and that the verifier never reaches the browser
- callback failure paths and buyer refusal
- one audit record written, with no personal identity fields
- `IsAgeRestricted` for eligible and non-eligible products, and the shared cart scan
- middleware: passes through a cart with nothing eligible; refuses an eligible
  cart with a missing, expired, already-consumed, or false verification
- the verification is consumed after a successful checkout and cannot be reused
- an unknown, forged, or already-consumed `verification_token` is refused
- `order_id` backfill on the audit row once the order exists
- config/migration publishing and JSON casting

Fake the HTTP layer and use deterministic fixtures — a fixed key pair and fixed
nonce make the JWT assertions tractable. Never call real Nias endpoints from
tests, and keep production credentials and real customer data out of fixtures.

## Conventions

PSR-4 under `Vendor\NiasAgeVerification`, typed properties and return types, and
explicit exceptions for integration failures. Keep changes narrowly scoped and
preserve public APIs once introduced. ASCII in source and docs unless Croatian is
needed for user-facing text. Update docs and tests when a public contract or
config key changes.

## Verification commands

Run the narrowest relevant check first. With no test suite yet the baseline is:

```bash
composer validate
find src database routes config -type f -name '*.php' -print0 | xargs -0 -r -n1 php -l
```

Note that `php -l` passes on an empty file, so this proves very little right now.
Once a test runner exists, run the focused test for the change, then the suite.

Known pre-existing issues, unrelated to any new work: `composer.lock` is stale
(pins `illuminate/support` at `11.x-dev`, which doesn't satisfy the `^13.0`
constraint — needs `composer update`), and `composer.json` has no `license` field.
