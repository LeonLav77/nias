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
use LeonLav77\NiasAgeVerification\Contracts\IsAgeRestricted;

class Product extends Model implements IsAgeRestricted
{
	public function isAgeRestricted(): bool
	{
		return $this->is_alcoholic;
	}
}
```

Then fill in `.env`:

```
NIAS_CLIENT_ID=...
NIAS_REDIRECT_URI=https://yourapp.hr/api/age-verification/callback
```

`NIAS_REDIRECT_URI` must byte-match a URI registered with MINGO, or Nias
rejects the authorization request.

## Flow

The buyer is never trusted to report their own age, and the frontend is never
trusted to report what is in the cart.

1. **Before checkout** the frontend decides its cart needs a verification and
   posts to `/api/age-verification`, which returns a Nias authorize URL to
   redirect to.
2. The buyer confirms in the m-Građani app.
3. Nias redirects to `/callback`, which exchanges the code for an ID token,
   validates it, and records the outcome.
4. **At checkout** your controller calls `assertVerified()` with the order's own
   resolved items and requires a valid verification.

Step 4 is the security boundary. Step 1 is a UX affordance, and the frontend
decides when to run it — nothing there is trusted, so a buyer who skips it, or
who verifies with a clean cart and then adds alcohol, is still caught at
checkout.

## At checkout

There is no middleware. The check is one call, and the application passes in
everything the decision needs:

```php
use LeonLav77\NiasAgeVerification\AgeVerificationManager;

public function store(Request $request, AgeVerificationManager $nias)
{
	$items = collect($request->items)->map(fn ($item) => OrderItem::getItemModel($item));

	$nias->assertVerified($items, $request->input('verification.id'), $request->input('country'));
}
```

Passing resolved models rather than ids is what lets a cart hold a mix of types
— `Product`, `Subscription`, anything implementing `IsAgeRestricted`. The
package never resolves a model or compares an id, so two different classes
sharing an id cannot be confused for each other.

It throws `VerificationRequiredException` when the cart needs a verification and
does not have a usable one, which renders as a 403 for JSON requests. Catch it
if you would rather send the buyer into the flow than refuse:

```php
try {
	$nias->assertVerified($items, $verificationId, $country);
} catch (VerificationRequiredException $e) {
	return redirect()->to($service->getRedirectUrl());
}
```

The check returns early, allowing the order through, when the package is
disabled, when the country is not the configured domestic one (Nias can only
verify Croatian buyers, so a foreign order cannot be held to a check it has no
way of passing), or when nothing in the cart is age-restricted.

Once the order exists, record the proof:

```php
$nias->attachOrder($order, $verificationId);
```

## Files

### `src/Contracts/IsAgeRestricted.php`
The package's one frozen public contract, implemented by the consuming app.
Deliberately tiny and Eloquent-free. `isAgeRestricted()` describes the
*product*, never the buyer — it means "this item requires an adult", not "this
buyer has been verified".

### `src/AgeVerificationManager.php`
The application-facing entry point, injected where it is needed.

- `assertVerified()` — the whole checkout check in one call: country, then
  eligibility, then the verification's existence, adulthood and expiry.
- `attachOrder()` — links a verification to an order once the order exists.

### `src/AgeVerificationService.php`
The Nias protocol flow.

- `getRedirectUrl()` — generates PKCE, `state` and `nonce`, and builds the
  authorize URL.
- `complete()` — exchanges the code and validates the ID token.

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

- `initialize()` — returns an authorize URL. Takes no cart: the frontend decides
  when a verification is worth starting, and starting a needless one costs
  nothing since checkout re-decides against the order's own items.
- `callback()` — where Nias sends the buyer back. Exchanges the code, validates
  the ID token, records the outcome, and redirects to the configured frontend
  target.

### `src/NiasAgeVerificationServiceProvider.php`
Merges config, registers the publish tag, and loads the routes under a fixed
`api/age-verification` prefix. Auto-discovered via `composer.json`'s
`extra.laravel`, so installing the package is the whole setup.

### `routes/api.php`
`POST /` and `GET /callback`, both under the `api/age-verification` prefix. The
callback is a `GET` because it receives
a browser redirect from Nias, not a request from your frontend.

### `config/nias-age-verification.php`
- `country` — the domestic country code (`HR`). Orders from anywhere else skip
  the check.
- `base_url` — the Nias environment, defaulting to test. All endpoints hang off
  it (`/authorize`, `/token`, `/jwks`) and it doubles as the expected `iss`.
- `client_id` — issued by MPUDT after registering with MINGO.
- `redirect_uri` — the full public callback URL, sent both in the authorize
  redirect and again in the token exchange. Both must match the registered
  value exactly.