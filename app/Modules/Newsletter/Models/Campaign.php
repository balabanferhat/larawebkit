<?php

namespace App\Modules\Newsletter\Models;

use App\Modules\Logs\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** draft → (scheduled) → sending → sent, or cancelled. Recipients are fixed when sending starts. */
class Campaign extends Model
{
    use Auditable;

    public const STATUSES = ['draft', 'scheduled', 'sending', 'sent', 'cancelled'];

    protected $table = 'newsletter_campaigns';

    protected $guarded = ['id', 'recipients_count', 'sent_count', 'failed_count', 'started_at', 'finished_at'];

    protected $attributes = ['status' => 'draft'];

    protected array $auditExclude = ['body', 'sent_count', 'failed_count', 'recipients_count'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }

    public function unsubscribedCount(): int
    {
        return Subscriber::where('unsubscribed_campaign_id', $this->id)->count();
    }

    public function auditLabel(): string
    {
        return $this->subject;
    }
}
