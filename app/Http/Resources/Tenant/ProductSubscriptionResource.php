<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductSubscription
 */
class ProductSubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'interval' => $this->interval,
            'interval_count' => $this->interval_count,
            'trial_days' => $this->trial_days,
            'trial_price' => $this->trial_price,
            'billing_cycles' => $this->billing_cycles,
            'prorate' => $this->prorate,
            'allow_pause' => $this->allow_pause,
            'allow_cancel_anytime' => $this->allow_cancel_anytime,
            'has_trial' => $this->has_trial,
            'is_indefinite' => $this->is_indefinite,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
