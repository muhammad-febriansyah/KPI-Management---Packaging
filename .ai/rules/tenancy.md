---
paths:
  - 'app/Policies/**,app/Http/Controllers/**,app/Models/**'
---

# Tenancy

## Tenant models carry the BelongsToClient trait
`App\Models\Concerns\BelongsToClient` registers `ClientScope` and a `creating` hook on every model owned by a client. Add it to any new model whose table has a `client_id`; `ClientScopeTest` fails when one is missing.

While a client is active, reads are filtered to it and a write either inherits its `client_id` or must match it — a mismatch throws. With no active client (console, seeders, queued jobs) the scope and the check both stand down, so such code names its client itself, as `DemoDataSeeder` does.

`AuditLog` is deliberately outside the trait: its `client_id` is nullable because login rows belong to no client, and `AuditLogController` asks for `client_id = active OR client_id IS NULL`.

Raw `DB::table()` queries bypass the scope entirely. `PayrollReportBuilder` and the `/reports/work` route filter by client themselves — keep those filters.

## Scope every tenant record to the active client, not to membership
Every tenant table carries a `client_id`, and `EnsureClientAccess` resolves exactly one active client per request into `CurrentClientService`.

A policy must compare the record against that active client:

```php
return $this->viewAny($user)
    && $this->currentClient->isResolved()
    && $model->client_id === $this->currentClient->id();
```

Never authorize with "is the user a member of the record's client" (`$user->clients()->whereKey($model->client_id)->exists()`). A user assigned to two clients then reaches the other client's records without switching, and a super admin reaches every client at once. Super admins are bound to the active client too; crossing clients requires the switcher at `PUT /current-client`.

## Guard route-model-bound records with 404, not 403
Route model binding resolves by global id, so a bound model may belong to another client. Every controller action that receives a bound tenant model opens with:

```php
abort_unless($model->client_id === $client->id(), 404);
```

before any `Gate::authorize` or menu check. 403 would confirm that another client's record exists. This guard runs after Form Request validation, so a cross-client request with an invalid payload still returns 422 — put the tenant assertion in the controller body regardless, and write endpoint tests against `destroy` (no Form Request) when asserting the 404.

## Composite foreign keys already enforce cross-tenant integrity
Tenant tables declare `unique(['client_id', 'id'])` and reference each other through composite foreign keys such as `products_client_unit_foreign` on `(client_id, unit_id)`. A model factory must therefore leave tenant foreign keys null by default; a caller that sets one has to pass a record belonging to the same client.
