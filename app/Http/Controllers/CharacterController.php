<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CustomField;
use App\Models\World;
use App\Support\CustomFieldInput;
use App\Support\LocationLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CharacterController extends Controller
{
    public function index(Request $request, World $world)
    {
        $this->authorize('view', $world);

        $query = $world->characters()->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('aliases', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $characters = $query->paginate(12)->withQueryString();

        return view('manage.characters.index', compact('world', 'characters', 'search', 'role'));
    }

    public function create(World $world)
    {
        $this->authorize('update', $world);

        return view('manage.characters.create', [
            'world' => $world,
            'character' => new Character(),
            'locationOptions' => LocationLookup::options($world),
            'customFields' => $this->customFields($world),
            'customValues' => [],
        ]);
    }

    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $data = $this->validateCharacter($request, $world);
        $data['portrait_image'] = $this->resolveImage($request, null);

        $character = $world->characters()->create($data);
        CustomFieldInput::sync($character, $request->input('custom', []));

        return redirect()->route('characters.show', [$world, $character])
            ->with('status', "Karakter \"{$character->name}\" ditambahkan ke dunia.");
    }

    public function show(World $world, Character $character)
    {
        $this->authorize('view', $world);

        $character->load(['origin', 'residence', 'relationsOut.relatedCharacter', 'relationsIn.character']);

        $memberships = $character->memberships()
            ->with('organization')
            ->get()
            ->filter(fn ($m) => $m->organization !== null)
            ->sortBy([
                fn ($a, $b) => $a->status <=> $b->status,
                fn ($a, $b) => $a->organization->name <=> $b->organization->name,
            ])
            ->values();

        // Organisasi yang belum diikuti karakter ini.
        $joinableOrganizations = $world->organizations()
            ->whereNotIn('id', $character->memberships()->select('organization_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $relationEntries = $character->relationEntries();
        $otherCharacters = $world->characters()
            ->whereKeyNot($character->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('manage.characters.show', compact(
            'world', 'character', 'relationEntries', 'otherCharacters', 'memberships', 'joinableOrganizations'
        ));
    }

    public function edit(World $world, Character $character)
    {
        $this->authorize('update', $world);

        return view('manage.characters.edit', [
            'world' => $world,
            'character' => $character,
            'locationOptions' => LocationLookup::options($world),
            'customFields' => $this->customFields($world),
            'customValues' => $character->customFieldValueMap(),
        ]);
    }

    public function update(Request $request, World $world, Character $character)
    {
        $this->authorize('update', $world);

        $data = $this->validateCharacter($request, $world);
        $data['portrait_image'] = $this->resolveImage($request, $character->portrait_image);

        $character->update($data);
        CustomFieldInput::sync($character, $request->input('custom', []));

        return redirect()->route('characters.show', [$world, $character])
            ->with('status', "Karakter \"{$character->name}\" diperbarui.");
    }

    public function destroy(World $world, Character $character)
    {
        $this->authorize('update', $world);

        if ($character->portrait_image && ! Str::startsWith($character->portrait_image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($character->portrait_image);
        }

        $name = $character->name;
        $character->delete();

        return redirect()->route('characters.index', $world)->with('status', "Karakter \"{$name}\" dihapus.");
    }

    private function validateCharacter(Request $request, World $world): array
    {
        // "asal"/"domisili" arrive as a `tier:id` token because locations are
        // spread over five tables; both must resolve inside THIS world.
        $inThisWorld = function (string $attribute, $value, $fail) use ($world) {
            if (filled($value) && ! LocationLookup::resolve($world, $value)) {
                $fail('Lokasi yang dipilih tidak ditemukan di dunia ini.');
            }
        };

        $customFields = $this->customFields($world);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'aliases' => 'nullable|string|max:255',
            'role' => 'nullable|in:' . implode(',', array_keys(Character::roles())),
            'species' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:100',
            'age' => 'nullable|string|max:100',
            'status' => 'nullable|in:' . implode(',', array_keys(Character::statuses())),
            'occupation' => 'nullable|string|max:255',
            'affiliation' => 'nullable|string|max:255',
            'appearance' => 'nullable|string',
            'personality' => 'nullable|string',
            'backstory' => 'nullable|string',
            'goals' => 'nullable|string',
            'portrait_image' => 'nullable|image|max:2048',
            'portrait_url' => 'nullable|url|max:2048',
            'origin' => ['nullable', 'string', 'max:50', $inThisWorld],
            'residence' => ['nullable', 'string', 'max:50', $inThisWorld],
        ] + CustomFieldInput::rules($customFields), [], [
            'origin' => 'asal',
            'residence' => 'domisili',
        ] + CustomFieldInput::attributes($customFields));

        unset($data['custom']);

        [$data['origin_type'], $data['origin_id']] = LocationLookup::parse($data['origin'] ?? null);
        [$data['residence_type'], $data['residence_id']] = LocationLookup::parse($data['residence'] ?? null);
        unset($data['origin'], $data['residence']);

        return $data;
    }

    /** The world's own attributes that apply to characters. */
    private function customFields(World $world)
    {
        return CustomField::where('world_id', $world->id)->forTarget('character')->get();
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('portrait_image')) {
            if ($current && ! Str::startsWith($current, ['http://', 'https://'])) {
                Storage::disk('public')->delete($current);
            }

            return $request->file('portrait_image')->store('portraits', 'public');
        }

        if ($url = trim((string) $request->input('portrait_url', ''))) {
            return $url;
        }

        return $current;
    }
}
