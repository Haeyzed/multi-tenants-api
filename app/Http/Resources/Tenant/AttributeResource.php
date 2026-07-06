<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attribute
 */
class AttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'type' => $this->type,
            'display_type' => $this->display_type,
            'description' => $this->description,
            'is_filterable' => $this->is_filterable,
            'is_visible_on_product' => $this->is_visible_on_product,
            'is_visible_on_listing' => $this->is_visible_on_listing,
            'is_required' => $this->is_required,
            'is_variant' => $this->is_variant,
            'is_user_defined' => $this->is_user_defined,
            'sort_order' => $this->sort_order,
            'validation_rules' => $this->validation_rules,
            'default_value' => $this->default_value,
            'values_count' => $this->whenCounted('values'),
            'values' => AttributeValueResource::collection($this->whenLoaded('values')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
