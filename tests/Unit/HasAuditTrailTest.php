<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use LedgerTrail\Traits\HasAuditTrail;

class FakeOrder extends Model
{
    use HasAuditTrail;

    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = ['id', 'status'];
    public $timestamps    = false;
}

beforeEach(function () {
    config(['ledgertrail.async' => false]);
    config(['ledgertrail.tenant_resolver' => fn () => 'tenant-uuid-001']);
    Http::fake(['*' => Http::response(['id' => 'evt-1'], 201)]);

    // Ensure the trait boot hooks are registered for FakeOrder.
    FakeOrder::clearBootedModels();
    (new FakeOrder())->getEventDispatcher(); // triggers bootIfNotBooted
});

it('logs model.created on create', function () {
    $model = new FakeOrder(['id' => 'order-1']);

    // Dispatch the Eloquent lifecycle event directly — this is what Model::save() fires.
    $model->getEventDispatcher()->dispatch('eloquent.created: ' . FakeOrder::class, $model);

    Http::assertSent(fn ($req) => $req->data()['event_type'] === 'fake_order.created'
        && ($req->data()['target_id'] ?? null) === 'order-1');
});

it('logs model.updated on update', function () {
    $model = new FakeOrder(['id' => 'order-1']);
    $model->syncOriginal();
    $model->fill(['status' => 'paid']);

    $model->getEventDispatcher()->dispatch('eloquent.updated: ' . FakeOrder::class, $model);

    Http::assertSent(fn ($req) => $req->data()['event_type'] === 'fake_order.updated');
});

it('logs model.deleted on delete', function () {
    $model = new FakeOrder(['id' => 'order-1']);

    $model->getEventDispatcher()->dispatch('eloquent.deleted: ' . FakeOrder::class, $model);

    Http::assertSent(fn ($req) => $req->data()['event_type'] === 'fake_order.deleted');
});
