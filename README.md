# Nias Age Verification

Laravel package for verifying a buyer's adulthood through the Croatian
e-Građani / m-Građani (Nias) system, as required by the amendments to the Trade
Act (NN 59/2026) for online sales of alcoholic and energy drinks.

Nias returns a plain adult/not-adult answer. No name, OIB, or identity document
is ever received or stored.

## Install

```bash
composer require leonlav77/nias-age-verification
php artisan vendor:publish --tag=nias-age-verification-config
php artisan vendor:publish --tag=nias-age-verification-migrations
php artisan migrate
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

And on your order model:

```php
use LeonLav77\NiasAgeVerification\Concerns\HasAgeVerifications;
use LeonLav77\NiasAgeVerification\Contracts\AgeVerifiableOrder;

class Order extends Model implements AgeVerifiableOrder
{
	use HasAgeVerifications;
}
```

Then fill in `.env`:

```dotenv
NIAS_ENABLED=true
NIAS_CLIENT_ID=...
NIAS_REDIRECT_URI=https://yourapp.hr/api/age-verification/callback
NIAS_CALLBACK_APPROVED_URL=https://yourapp.hr/checkout
NIAS_CALLBACK_DENIED_URL=https://yourapp.hr/checkout
```

`NIAS_ENABLED` defaults to `false` — nothing is checked until you set it.
`NIAS_REDIRECT_URI` must byte-match a URI registered with MINGO, or Nias rejects
the authorization request.

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
use LeonLav77\NiasAgeVerification\Jobs\AttachOrderToVerification;

public function store(Request $request, AgeVerificationManager $nias)
{
	$items = collect($request->items)->map(fn ($item) => OrderItem::getItemModel($item));

	$verification = $nias->assertVerified($items, $request->input('verification.id'), $request->input('country'));

	$order = Order::create([...]);

	AttachOrderToVerification::dispatch($order, $verification)
		->afterCommit();
}
```

`AttachOrderToVerification` records which verification authorised the order. You
dispatch it yourself, which keeps the queue, connection and delay under your
control — pick a queue for it, or dispatch it synchronously with
`dispatchSync()`. Dispatch it `afterCommit()` as above so the worker cannot run
ahead of the order's own transaction.

Hand it the verification `assertVerified()` returned rather than an id — you
already hold it, and the job then has nothing to look up. It is an Eloquent
model, so only its key rides on the queue and the worker refetches it. The
order, by contrast, is reduced to its identifier at construction, so it need not
be a model itself.

A null verification is fine: `assertVerified()` returns null whenever the order
needed no check at all, and the job returns without doing anything, so you can
dispatch it unconditionally on every order.

The link is eventually consistent: an order can exist for a short window with no
verification attached to it, and permanently if the job exhausts its retries, so
monitor `failed_jobs` if you rely on this link as an audit record.

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

## Documentation

| | |
| --- | --- |
| [Installation](docs/installation.md) | Requirements, publishing, migrations, contracts |
| [Configuration](docs/configuration.md) | Every config key and what changing it does |
| [Integration guide](docs/integration.md) | Trust model, checkout, testing, production checklist |
| [HTTP endpoints](docs/http.md) | Both routes and the frontend contract |
| [API reference](docs/api.md) | Classes, contracts, models, DTOs |
| [Errors](docs/errors.md) | Exception hierarchy, error codes, diagnosing |
