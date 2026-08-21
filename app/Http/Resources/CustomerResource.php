<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'tax_number' => $this->tax_number,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'gst_registration_type' => $this->gst_registration_type,
            'tax_registration_status' => $this->tax_registration_status,
            'billing_state_code' => $this->billing_state_code,
            'place_of_supply' => $this->place_of_supply,
            'tax_exemption_status' => $this->tax_exemption_status,
            'tax_exemption_reason' => $this->tax_exemption_reason,
            'default_tax_preference' => $this->default_tax_preference,
            'shipping_same_as_billing' => (bool) $this->shipping_same_as_billing,
            'shipping_address_line_1' => $this->shipping_address_line_1,
            'shipping_address_line_2' => $this->shipping_address_line_2,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_postal_code' => $this->shipping_postal_code,
            'shipping_country' => $this->shipping_country,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'lifecycle_stage' => $this->lifecycle_stage,
            'lifecycle_stage_label' => $this->lifecycle_stage_label,
            'segment' => $this->segment,
            'segment_label' => $this->segment_label,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),
            'primary_contact' => $this->whenLoaded('primaryContact', fn () => $this->primaryContact ? [
                'id' => $this->primaryContact->id,
                'name' => $this->primaryContact->name,
                'email' => $this->primaryContact->email,
            ] : null),
            'contacts_count' => $this->whenCounted('contacts'),
            'custom_fields' => app(MetadataApiPresenter::class)->customFieldsFor(
                (int) $this->organization_id,
                'customer',
                $this->custom_fields,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
