<?php

namespace App\Support;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Glue between a record's form and its per-world custom attributes. Inputs
 * arrive as `custom[<field_id>]`; rules and persistence are derived from each
 * field's declared type.
 */
class CustomFieldInput
{
    /**
     * Validation rules for the `custom` array of one record.
     *
     * @param  Collection<int, CustomField>  $fields
     * @return array<string, mixed>
     */
    public static function rules(Collection $fields): array
    {
        $rules = ['custom' => ['nullable', 'array']];

        foreach ($fields as $field) {
            $rules["custom.{$field->id}"] = match ($field->type) {
                'textarea' => ['nullable', 'string', 'max:5000'],
                'number' => ['nullable', 'numeric'],
                'select' => ['nullable', 'string', 'in:' . $field->optionList()->implode(',')],
                'boolean' => ['nullable', 'in:0,1'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Friendly attribute names so errors read "Tingkat Mana wajib angka".
     *
     * @param  Collection<int, CustomField>  $fields
     * @return array<string, string>
     */
    public static function attributes(Collection $fields): array
    {
        return $fields->mapWithKeys(fn (CustomField $field) => [
            "custom.{$field->id}" => $field->name,
        ])->all();
    }

    /**
     * Store the record's answers. A blank answer removes that one row — the
     * delete is always scoped to this record and this field.
     *
     * @param  array<int|string, mixed>  $input
     */
    public static function sync(Model $owner, array $input): void
    {
        foreach ($owner->applicableCustomFields() as $field) {
            $value = $input[$field->id] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                $owner->customFieldValues()->where('custom_field_id', $field->id)->delete();

                continue;
            }

            $owner->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                ['value' => (string) $value],
            );
        }
    }
}
