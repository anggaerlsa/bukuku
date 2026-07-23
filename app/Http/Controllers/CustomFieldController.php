<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Author-defined attributes, managed per world. Each world keeps its own set,
 * so a sci-fi world and a fantasy world never share a schema.
 */
class CustomFieldController extends Controller
{
    public function index(World $world)
    {
        $this->authorize('view', $world);

        $fields = $world->customFields()->orderBy('applies_to')->orderBy('position')->orderBy('id')->get();

        return view('manage.custom-fields.index', compact('world', 'fields'));
    }

    public function create(World $world)
    {
        $this->authorize('update', $world);

        return view('manage.custom-fields.create', [
            'world' => $world,
            'field' => new CustomField(['type' => 'text', 'applies_to' => 'character', 'position' => 0]),
        ]);
    }

    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $field = $world->customFields()->create($this->validateField($request, $world));

        return redirect()->route('custom-fields.index', $world)
            ->with('status', "Atribut \"{$field->name}\" ditambahkan.");
    }

    public function edit(World $world, CustomField $customField)
    {
        $this->authorize('update', $world);
        abort_unless($customField->world_id === $world->id, 404);

        return view('manage.custom-fields.edit', ['world' => $world, 'field' => $customField]);
    }

    public function update(Request $request, World $world, CustomField $customField)
    {
        $this->authorize('update', $world);
        abort_unless($customField->world_id === $world->id, 404);

        $customField->update($this->validateField($request, $world, $customField));

        return redirect()->route('custom-fields.index', $world)
            ->with('status', "Atribut \"{$customField->name}\" diperbarui.");
    }

    public function destroy(World $world, CustomField $customField)
    {
        $this->authorize('update', $world);
        abort_unless($customField->world_id === $world->id, 404);

        // Only this field; its stored answers go with it via the FK cascade.
        $name = $customField->name;
        $customField->delete();

        return redirect()->route('custom-fields.index', $world)
            ->with('status', "Atribut \"{$name}\" dihapus beserta isinya.");
    }

    private function validateField(Request $request, World $world, ?CustomField $field = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('custom_fields', 'name')
                    ->where('world_id', $world->id)
                    ->ignore($field?->id),
            ],
            'applies_to' => ['required', Rule::in(array_keys(CustomField::targets()))],
            'type' => ['required', Rule::in(array_keys(CustomField::TYPES))],
            'options' => ['nullable', 'string', 'max:2000', 'required_if:type,select'],
            'hint' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.unique' => 'Dunia ini sudah punya atribut dengan nama itu.',
            'options.required_if' => 'Isi daftar pilihannya, satu per baris.',
        ]);
    }
}
