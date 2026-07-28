<?php

namespace App\Support;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Support\Collection;

/**
 * Author-defined attribute values for a whole novel, fetched in two queries.
 *
 * Asking each record for its own custom fields (the way a page does, for one
 * record) would be two queries per record — several hundred round trips against
 * a remote database that already stalls for tens of seconds. Everything is
 * loaded once here and looked up by morph alias.
 */
class CustomValues
{
    private Collection $fields;

    private Collection $values;

    public function __construct(Collection $worldIds)
    {
        $this->fields = CustomField::whereIn('world_id', $worldIds)
            ->orderBy('position')->orderBy('id')
            ->get()
            ->keyBy('id');

        $this->values = CustomFieldValue::whereIn('custom_field_id', $this->fields->keys())
            ->get()
            ->groupBy(fn (CustomFieldValue $value) => $value->valuable_type . ':' . $value->valuable_id);
    }

    /** "Atribut: Tingkat Mana: 4; Kasta: Bangsawan" for one record. */
    public function fieldsFor(object $record): string
    {
        $answers = $this->values->get($record->getMorphClass() . ':' . $record->getKey());

        if ($answers === null) {
            return '';
        }

        $rendered = $answers
            // The author's field order, not the order the answers happen to
            // have been saved in, so the text comes out identical next request.
            ->sortBy(fn (CustomFieldValue $v) => $this->fields->get($v->custom_field_id)?->position ?? 0)
            ->map(function (CustomFieldValue $value) {
                $field = $this->fields->get($value->custom_field_id);
                $display = $field?->display($value->value);

                return $field && filled($display) ? "{$field->name}: {$display}" : null;
            })
            ->filter()
            ->implode('; ');

        return filled($rendered) ? "Atribut: {$rendered}\n" : '';
    }
}
