<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Per-world custom attributes on a lore record (characters, all location
 * tiers). Which fields apply is decided by the record's target keys.
 */
trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'valuable');
    }

    /**
     * Target keys this record answers to. A Kota picks up fields defined for
     * "location" as well as those defined for "kota" only.
     *
     * @return list<string>
     */
    abstract public function customFieldTargets(): array;

    /** Fields defined in this record's world that apply to it. */
    public function applicableCustomFields(): Collection
    {
        return CustomField::where('world_id', $this->world_id)
            ->forTarget($this->customFieldTargets())
            ->get();
    }

    /** field_id => stored value, for prefilling a form. */
    public function customFieldValueMap(): Collection
    {
        return $this->customFieldValues()->pluck('value', 'custom_field_id');
    }

    /**
     * Applicable fields paired with this record's answers, ready to render.
     *
     * @return Collection<int, array{field: CustomField, value: ?string, display: ?string}>
     */
    public function customFieldEntries(): Collection
    {
        $values = $this->customFieldValueMap();

        return $this->applicableCustomFields()->map(fn (CustomField $field) => [
            'field' => $field,
            'value' => $values[$field->id] ?? null,
            'display' => $field->display($values[$field->id] ?? null),
        ]);
    }

    /** Answers die with the record — the morph side has no FK to cascade. */
    public static function bootHasCustomFields(): void
    {
        static::deleting(function ($model) {
            $model->customFieldValues()->delete();
        });
    }
}
