# Recipe: an audit-logged model

1. Add the trait:
   ```php
   use App\Modules\Logs\Concerns\Auditable;

   class Invoice extends Model
   {
       use Auditable;

       protected array $auditExclude = ['pdf_blob'];      // optional: never log these columns

       public function auditLabel(): string { return 'Invoice '.$this->number; } // shown in the log
   }
   ```
   `created`, `updated` (old/new values of changed columns only), `deleted`/`trashed` and `restored`
   are recorded with user, IP and user agent. Passwords, remember tokens, 2FA secrets and the model's
   `$hidden` attributes are never logged; long strings are truncated.
2. Add a short alias to the morph map in `LarawebkitServiceProvider::boot()` (`'invoice' => Invoice::class`)
   so log entries stay readable and stable if you move the class.
3. Show a "History" tab: add `AuditLogsRelationManager::class` to the resource's `getRelations()`.
4. Custom events: `AuditLogger::record('approved', $invoice, [], ['by' => …], 'reason')`;
   attach a reason to everything inside a callback with `AuditLogger::withReason('…', fn () => …)`.
   Translate custom event names in `lang/*/larawebkit-logs.php` → `audit.events`.

## Tests

```php
it('audits invoice changes', function () {
    $this->actingAs(superAdmin());
    $invoice = Invoice::factory()->create();
    $invoice->update(['total' => 10]);
    expect(AuditLog::where('auditable_type', 'invoice')->pluck('event')->all())->toBe(['created', 'updated']);
});
```
