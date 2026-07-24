<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Organization;
use App\Models\World;
use App\Support\CustomFieldInput;
use App\Support\LocationLookup;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Organisations (Organisasi) — factions, houses, armies, orders. Nested lore
 * under a world, same shape as characters.
 */
class OrganizationController extends Controller
{
    public function index(Request $request, World $world)
    {
        $this->authorize('view', $world);

        $query = $world->organizations()->with('parent')->withCount('memberships')->orderBy('name');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('aliases', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $organizations = $query->paginate(12)->withQueryString();

        return view('manage.organizations.index', compact('world', 'organizations', 'search', 'status'));
    }

    public function create(Request $request, World $world)
    {
        $this->authorize('update', $world);

        return view('manage.organizations.create', [
            'world' => $world,
            'organization' => new Organization(['status' => 'aktif', 'parent_id' => $request->query('parent')]),
        ] + $this->formData($world));
    }

    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $data = $this->validateOrganization($request, $world);
        $data['emblem_image'] = $this->resolveImage($request, null);

        $organization = $world->organizations()->create($data);
        CustomFieldInput::sync($organization, $request->input('custom', []));

        return redirect()->route('organizations.show', [$world, $organization])
            ->with('status', "Organisasi \"{$organization->name}\" ditambahkan.");
    }

    public function show(World $world, Organization $organization)
    {
        $this->authorize('view', $world);

        $organization->load(['parent', 'headquarters', 'children' => fn ($q) => $q->orderBy('name')]);

        $members = $organization->memberships()
            ->with('character')
            ->get()
            ->filter(fn ($m) => $m->character !== null)
            ->sortBy([
                fn ($a, $b) => $a->status <=> $b->status,   // aktif dulu, mantan belakangan
                fn ($a, $b) => $a->character->name <=> $b->character->name,
            ])
            ->values();

        // Karakter yang belum jadi anggota, untuk form tambah.
        $candidates = $world->characters()
            ->whereNotIn('id', $organization->memberships()->select('character_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('manage.organizations.show', compact('world', 'organization', 'members', 'candidates'));
    }

    public function edit(World $world, Organization $organization)
    {
        $this->authorize('update', $world);

        return view('manage.organizations.edit', [
            'world' => $world,
            'organization' => $organization,
        ] + $this->formData($world, $organization));
    }

    public function update(Request $request, World $world, Organization $organization)
    {
        $this->authorize('update', $world);

        $data = $this->validateOrganization($request, $world, $organization);
        $data['emblem_image'] = $this->resolveImage($request, $organization->emblem_image);

        $organization->update($data);
        CustomFieldInput::sync($organization, $request->input('custom', []));

        return redirect()->route('organizations.show', [$world, $organization])
            ->with('status', "Organisasi \"{$organization->name}\" diperbarui.");
    }

    public function destroy(World $world, Organization $organization)
    {
        $this->authorize('update', $world);

        // Same guard as locations: refuse while something still hangs off it,
        // so a sub-division is never swept away as a side effect.
        if (($count = $organization->children()->count()) > 0) {
            return back()->with('error', "Organisasi ini masih menaungi {$count} sub-organisasi. Pindahkan atau hapus dulu.");
        }

        Uploads::delete($organization->emblem_image);

        $name = $organization->name;
        $organization->delete();

        return redirect()->route('organizations.index', $world)
            ->with('status', "Organisasi \"{$name}\" dihapus.");
    }

    /** Shared bits both the create and edit forms need. */
    private function formData(World $world, ?Organization $organization = null): array
    {
        return [
            'locationOptions' => LocationLookup::options($world),
            'parents' => $world->organizations()
                ->when($organization, fn ($q) => $q->whereKeyNot($organization->id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'customFields' => $this->customFields($world),
            'customValues' => $organization?->customFieldValueMap() ?? [],
        ];
    }

    private function customFields(World $world)
    {
        return CustomField::where('world_id', $world->id)->forTarget('organization')->get();
    }

    private function validateOrganization(Request $request, World $world, ?Organization $organization = null): array
    {
        $customFields = $this->customFields($world);

        $inThisWorld = function (string $attribute, $value, $fail) use ($world) {
            if (filled($value) && ! LocationLookup::resolve($world, $value)) {
                $fail('Lokasi yang dipilih tidak ditemukan di dunia ini.');
            }
        };

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'aliases' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'status' => ['required', Rule::in(array_keys(Organization::statuses()))],
            'motto' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'nullable|string',
            'history' => 'nullable|string',
            'emblem_image' => 'nullable|image|max:2048',
            'emblem_url' => 'nullable|url|max:2048',
            'headquarters' => ['nullable', 'string', 'max:50', $inThisWorld],
            'parent_id' => [
                'nullable',
                Rule::exists('organizations', 'id')->where('world_id', $world->id),
                // An organisation cannot be filed under itself.
                Rule::notIn(array_filter([$organization?->id])),
            ],
        ] + CustomFieldInput::rules($customFields), [
            'parent_id.not_in' => 'Organisasi tidak bisa menaungi dirinya sendiri.',
            'parent_id.exists' => 'Induk harus organisasi di dunia ini.',
        ], [
            'headquarters' => 'markas',
        ] + CustomFieldInput::attributes($customFields));

        [$data['headquarters_type'], $data['headquarters_id']] = LocationLookup::parse($data['headquarters'] ?? null);
        unset($data['headquarters'], $data['custom']);

        return $data;
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('emblem_image')) {
            Uploads::delete($current);

            return Uploads::store($request->file('emblem_image'), 'lambang');
        }

        if ($url = trim((string) $request->input('emblem_url', ''))) {
            return $url;
        }

        return $current;
    }
}
