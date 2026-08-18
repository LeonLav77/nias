# Nias Age Verification

Laravel package for verifying a buyer's adulthood through the Croatian
e-Građani / m-Građani (Nias) system, as required by the amendments to the Trade
Act (NN 59/2026) for online sales of alcoholic and energy drinks.

Nias returns a plain adult/not-adult answer. No name, OIB, or identity document
is ever received or stored.

## Install

```bash
composer require vendor/nias-age-verification
php artisan vendor:publish --tag=nias-age-verification-config
```

The service provider is auto-discovered, so routes exist immediately at
`/api/age-verification/*`.

Implement the contract on your product model:

```php
use Vendor\NiasAgeVerification\Contracts\IsAgeRestricted;

class Product extends Model implements IsAgeRestricted
{
	public function isAgeRestricted(): bool
	{
		return $this->is_alcoholic;
	}
}
```

Then point the config at it and fill in `.env`:

```php
'product_model' => App\Models\Product::class,
```

```
NIAS_CLIENT_ID=...
NIAS_REDIRECT_URI=https://yourapp.hr/api/age-verification/callback
```

`NIAS_REDIRECT_URI` must byte-match a URI registered with MINGO, or Nias
rejects the authorization request.

## Flow

The buyer is never trusted to report their own age, and the frontend is never
trusted to report what is in the cart.

1. **Before checkout** the frontend posts the cart's product ids to
   `/start`. The package loads those products and scans them.
2. Nothing age-restricted → `{"required": false}`, checkout proceeds untouched.
3. Something age-restricted → the package generates PKCE, `state` and `nonce`,
   and returns a Nias authorize URL for the frontend to redirect to.
4. The buyer confirms in the m-Građani app.
5. Nias redirects to `/callback`, which exchanges the code for an ID token,
   validates it, and records the outcome.
6. **At checkout** middleware repeats the same scan against the order's own
   items and requires a valid verification. This is the security boundary —
   step 1 is only a UX affordance so the buyer is not surprised at the end.

Because the scan runs twice, a buyer who verifies with a clean cart and then
adds alcohol is still caught.

## Files

### `src/Contracts/IsAgeRestricted.php`
The package's one frozen public contract, implemented by the consuming app.
Deliberately tiny and Eloquent-free. `isAgeRestricted()` describes the
*product*, never the buyer — it means "this item requires an adult", not "this
buyer has been verified".

### `src/AgeVerificationService.php`
The orchestrator, and the only class the application talks to.

- `requiresVerification()` — scans items for anything age-restricted. Called by
  both the start endpoint and the checkout middleware, so the two cannot drift.
- `getRedirectUrl()` — generates PKCE, `state` and `nonce`, and builds the
  authorize URL.
- `complete()` — exchanges the code and validates the ID token. *Not yet built.*

### `src/Pkce.php`
Random value generation for the OAuth flow. Separate from the service because it
is pure, static, and the easiest part to unit test.

Each piece exists for a specific attack:

- `verifier()` / `challenge()` — PKCE. Proves the party redeeming the code is
  the one that started the flow. Nias is a public client with no secret
  (`token_endpoint_auth_methods_supported: ["none"]`), so PKCE is the only thing
  binding the exchange.
- `state()` — CSRF. Returned unchanged by Nias; a callback bearing an unissued
  `state` is forged.
- `nonce()` — replay. Embedded in the ID token, so a captured token cannot be
  replayed into a later flow.

All use `random_bytes`. The challenge is unpadded base64url of the raw SHA-256
digest — hex or padded output produces a challenge Nias rejects.

### `src/Enums/`
The protocol constants: `ResponseType` (`code`), `Scope` (`age.alcohol`),
`CodeChallengeMethod` (`S256`), `GrantType` (`authorization_code`). Each has a
single case and a `getDefault()`, since Nias accepts exactly one legal value for
each.

### `src/Http/Controllers/AgeVerificationController.php`
Both HTTP endpoints.

- `start()` — loads products by the posted ids, scans them, and either reports
  that nothing is required or returns the authorize URL. Loading from the
  database rather than trusting a posted flag is what stops a caller claiming
  their cart is alcohol-free.
- `callback()` — where Nias sends the buyer back. *Not yet built.*

### `src/NiasAgeVerificationServiceProvider.php`
Merges config, registers the publish tag, and loads the routes under a fixed
`api/age-verification` prefix. Auto-discovered via `composer.json`'s
`extra.laravel`, so installing the package is the whole setup.

### `routes/api.php`
`POST /start` and `GET /callback`. The callback is a `GET` because it receives
a browser redirect from Nias, not a request from your frontend.

### `config/nias-age-verification.php`
- `product_model` — the class implementing `IsAgeRestricted`.
- `base_url` — the Nias environment, defaulting to test. All endpoints hang off
  it (`/authorize`, `/token`, `/jwks`) and it doubles as the expected `iss`.
- `client_id` — issued by MPUDT after registering with MINGO.
- `redirect_uri` — the full public callback URL, sent both in the authorize
  redirect and again in the token exchange. Both must match the registered
  value exactly.