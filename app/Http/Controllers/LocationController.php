<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\World;
use App\Support\CustomFieldInput;
use App\Support\Hierarchy;
use App\Support\LocationFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /** Columns shared by every tier table. */
    private const FIELDS = [
        'name', 'type', 'summary', 'description', 'geography',
        'climate', 'population', 'government', 'points_of_interest',
    ];

    public function index(Request $request, World $world)
    {
        $this->authorize('view', $world);

        // The whole tree comes from the top tier down.
        $benuas = $world->benuas()->with('negaras.provinsis.kotas.desas')->orderBy('name')->get();
        $hasLocations = $benuas->isNotEmpty();

        // Search/filter prunes that same tree in memory, so matches keep their
        // ancestors and the hierarchy view stays intact.
        $filter = new LocationFilter($request->query('q'), $request->query('tier'));
        $benuas = $filter->prune($benuas);

        return view('manage.locations.index', compact('world', 'benuas', 'filter', 'hasLocations'));
    }

    public function create(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $tier = $request->query('tier', 'benua');
        if (! Hierarchy::isTier($tier)) {
            $tier = 'benua';
        }

        return view('manage.locations.create', [
            'world' => $world,
            'tier' => $tier,
            'node' => null,
            'parentId' => (string) $request->query('parent', ''),
            'parentsByTier' => $this->parentsByTier($world),
            'customFields' => $this->customFields($world),
            'customValues' => [],
        ]);
    }

    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $tier = (string) $request->input('tier');
        abort_unless(Hierarchy::isTier($tier), 404);

        $data = $this->validateNode($request, $world, $tier);
        $model = Hierarchy::model($tier);

        $payload = collect($data)->only(self::FIELDS)->all();
        $payload['world_id'] = $world->id;
        if ($fk = Hierarchy::parentForeignKey($tier)) {
            $payload[$fk] = $data['parent_id'];
        }
        $payload['map_image'] = $this->resolveImage($request, null);

        $node = $model::create($payload);
        CustomFieldInput::sync($node, $request->input('custom', []));

        return redirect()->route('locations.show', [$world, $tier, $node->id])
            ->with('status', "Lokasi \"{$node->name}\" (" . Hierarchy::label($tier) . ") ditambahkan.");
    }

    public function show(World $world, string $tier, string $id)
    {
        $this->authorize('view', $world);

        $node = $this->resolveNode($world, $tier, $id);

        if ($childTier = Hierarchy::child($tier)) {
            $node->load(Hierarchy::table($childTier));
        }

        $ancestors = collect();
        $cursor = $node->parentNode();
        while ($cursor) {
            $ancestors->prepend($cursor);
            $cursor = $cursor->parentNode();
        }

        // Characters tied to this place, from both directions of the link.
        $natives = $node->nativeCharacters()->orderBy('name')->get();
        $residents = $node->residentCharacters()->orderBy('name')->get();

        // Organisasi yang bermarkas di sini.
        $based = $node->basedOrganizations()->withCount('memberships')->with('parent')->orderBy('name')->get();

        return view('manage.locations.show', compact('world', 'node', 'tier', 'ancestors', 'natives', 'residents', 'based'));
    }

    public function edit(World $world, string $tier, string $id)
    {
        $this->authorize('update', $world);

        $node = $this->resolveNode($world, $tier, $id);
        $fk = Hierarchy::parentForeignKey($tier);

        return view('manage.locations.edit', [
            'world' => $world,
            'tier' => $tier,
            'node' => $node,
            'parentId' => (string) ($fk ? ($node->{$fk} ?? '') : ''),
            'parentsByTier' => $this->parentsByTier($world),
            'customFields' => $this->customFields($world),
            'customValues' => $node->customFieldValueMap(),
        ]);
    }

    public function update(Request $request, World $world, string $tier, string $id)
    {
        $this->authorize('update', $world);

        $node = $this->resolveNode($world, $tier, $id);
        $data = $this->validateNode($request, $world, $tier, $node);

        $payload = collect($data)->only(self::FIELDS)->all();
        if ($fk = Hierarchy::parentForeignKey($tier)) {
            $payload[$fk] = $data['parent_id'];
        }
        $payload['map_image'] = $this->resolveImage($request, $node->map_image);

        $node->update($payload);
        CustomFieldInput::sync($node, $request->input('custom', []));

        return redirect()->route('locations.show', [$world, $tier, $node->id])
            ->with('status', "Lokasi \"{$node->name}\" diperbarui.");
    }

    public function destroy(World $world, string $tier, string $id)
    {
        $this->authorize('update', $world);

        $node = $this->resolveNode($world, $tier, $id);

        if (($childTier = Hierarchy::child($tier)) && $node->{Hierarchy::table($childTier)}()->exists()) {
            return back()->with('error', 'Lokasi ini memiliki sub-lokasi. Hapus atau pindahkan sub-lokasinya terlebih dahulu.');
        }

        if ($node->map_image && ! Str::startsWith($node->map_image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($node->map_image);
        }

        $name = $node->name;
        $node->delete();

        return redirect()->route('locations.index', $world)->with('status', "Lokasi \"{$name}\" dihapus.");
    }

    private function resolveNode(World $world, string $tier, string $id)
    {
        abort_unless(Hierarchy::isTier($tier), 404);

        $model = Hierarchy::model($tier);

        return $model::where('world_id', $world->id)->findOrFail($id);
    }

    /**
     * Candidate parents for each tier that can be a parent, keyed by tier.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function parentsByTier(World $world): array
    {
        $out = [];
        foreach (Hierarchy::keys() as $tier) {
            if (Hierarchy::child($tier) === null) {
                continue; // bottom tier is never a parent
            }
            $out[$tier] = $world->{Hierarchy::table($tier)}()->orderBy('name')->get(['id', 'name']);
        }

        return $out;
    }

    /**
     * The world's own attributes for locations: those defined for every tier
     * plus every tier-specific one, since the form can switch tier before save.
     * Which ones actually stick is decided by the node in CustomFieldInput.
     */
    private function customFields(World $world)
    {
        return CustomField::where('world_id', $world->id)
            ->forTarget(array_merge(['location'], Hierarchy::keys()))
            ->get();
    }

    private function validateNode(Request $request, World $world, string $tier, $node = null): array
    {
        $parentTier = Hierarchy::parent($tier);
        $parentTable = $parentTier ? Hierarchy::table($parentTier) : null;

        $parentRule = $parentTier === null
            ? ['nullable']
            : ['required', Rule::exists($parentTable, 'id')->where('world_id', $world->id)];

        $customFields = $this->customFields($world);

        return $request->validate([
            'tier' => ['required', Rule::in(Hierarchy::keys())],
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'parent_id' => $parentRule,
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'geography' => 'nullable|string',
            'climate' => 'nullable|string|max:255',
            'population' => 'nullable|string|max:255',
            'government' => 'nullable|string|max:255',
            'points_of_interest' => 'nullable|string',
            'map_image' => 'nullable|image|max:2048',
            'map_url' => 'nullable|url|max:2048',
        ] + CustomFieldInput::rules($customFields), [
            'parent_id.required' => 'Pilih lokasi induk (' . ($parentTier ? Hierarchy::label($parentTier) : '') . ').',
            'parent_id.exists' => 'Induk harus berupa ' . ($parentTier ? Hierarchy::label($parentTier) : 'lokasi') . ' di dunia ini.',
        ], CustomFieldInput::attributes($customFields));
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('map_image')) {
            if ($current && ! Str::startsWith($current, ['http://', 'https://'])) {
                Storage::disk('public')->delete($current);
            }

            return $request->file('map_image')->store('maps', 'public');
        }

        if ($url = trim((string) $request->input('map_url', ''))) {
            return $url;
        }

        return $current;
    }
}
