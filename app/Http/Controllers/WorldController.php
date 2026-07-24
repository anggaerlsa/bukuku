<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Image;
use App\Models\LoreEntry;
use App\Models\Novel;
use App\Models\Organization;
use App\Models\World;
use App\Support\Hierarchy;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorldController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', World::class);

        $user = $request->user();
        $query = World::withCount(['characters', 'benuas', 'negaras', 'provinsis', 'kotas', 'desas'])
            ->with('novel')
            ->latest();

        if (! $user->can('manage worlds')) {
            $query->where('user_id', $user->id);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Narrow to one novel, chosen from the ones this user may see.
        $novels = $this->availableNovels($request);
        $novelId = $request->query('novel');
        if ($novelId && $novels->contains('id', (int) $novelId)) {
            $query->where('novel_id', (int) $novelId);
        } else {
            $novelId = null;
        }

        $worlds = $query->paginate(12)->withQueryString();

        return view('manage.worlds.index', compact('worlds', 'search', 'novels', 'novelId'));
    }

    public function create()
    {
        $this->authorize('create', World::class);

        return view('manage.worlds.create', [
            'world' => new World(['status' => 'active', 'novel_id' => request()->query('novel')]),
            'novels' => $this->availableNovels(request()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', World::class);

        $data = $this->validateWorld($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['user_id'] = $request->user()->id;
        $data['cover_image'] = $this->resolveImage($request, 'cover_image', 'cover_url', 'covers', null);

        $world = World::create($data);

        return redirect()->route('worlds.show', $world)
            ->with('status', "Dunia \"{$world->name}\" telah ditempa. Mulailah membangun lorenya!");
    }

    public function show(World $world)
    {
        $this->authorize('view', $world);

        $world->load('user', 'novel.genres')->loadCount(['characters', 'organizations', 'loreEntries']);
        $loreByCategory = $world->loreEntries()->orderBy('category')->orderBy('title')
            ->get()->groupBy(fn ($e) => $e->categoryLabel());
        $characters = $world->characters()->latest()->take(6)->get();
        $organizations = $world->organizations()->withCount('memberships')->with('parent')->orderBy('name')->take(6)->get();
        $benuas = $world->benuas()->with('negaras.provinsis.kotas.desas')->orderBy('name')->get();
        $locationsCount = $world->locationsCount();

        return view('manage.worlds.show', compact('world', 'characters', 'organizations', 'loreByCategory', 'benuas', 'locationsCount'));
    }

    public function edit(World $world)
    {
        $this->authorize('update', $world);

        return view('manage.worlds.edit', [
            'world' => $world,
            'novels' => $this->availableNovels(request()),
        ]);
    }

    public function update(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $data = $this->validateWorld($request);
        $data['slug'] = $this->uniqueSlug($data['name'], $world->id);
        $data['cover_image'] = $this->resolveImage($request, 'cover_image', 'cover_url', 'covers', $world->cover_image);

        $world->update($data);

        return redirect()->route('worlds.show', $world)->with('status', "Dunia \"{$world->name}\" diperbarui.");
    }

    public function destroy(World $world)
    {
        $this->authorize('delete', $world);

        Uploads::delete($world->cover_image);

        // Gallery rows would vanish through the FK cascade without ever firing
        // their deleting hook, stranding the uploaded files. Delete them first,
        // scoped to this world only.
        Image::where('world_id', $world->id)->get()->each->delete();

        // Same story for the cover pictures held on the rows themselves —
        // character portraits and every location tier's map. The cascade takes
        // those rows silently too, so collect their files up front, scoped to
        // this world. Uploads::delete() drops the linked URLs among them,
        // which are not ours to remove.
        $paths = Character::where('world_id', $world->id)->pluck('portrait_image')
            ->concat(Organization::where('world_id', $world->id)->pluck('emblem_image'))
            ->concat(LoreEntry::where('world_id', $world->id)->pluck('cover_image'));

        foreach (Hierarchy::MODELS as $model) {
            $paths = $paths->concat($model::where('world_id', $world->id)->pluck('map_image'));
        }

        Uploads::delete($paths->all());

        $name = $world->name;
        $world->delete();

        return redirect()->route('worlds.index')->with('status', "Dunia \"{$name}\" telah dilenyapkan beserta seluruh lorenya.");
    }

    /**
     * Novels this user may file a world under: their own, or every one when
     * they can manage novels.
     */
    private function availableNovels(Request $request)
    {
        return Novel::query()
            ->when(! $request->user()->can('manage novels'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    private function validateWorld(Request $request): array
    {
        // A world must belong to a novel the user is actually allowed to use.
        $allowedNovels = $this->availableNovels($request)->pluck('id')->all();

        return $request->validate([
            'novel_id' => ['required', Rule::in($allowedNovels)],
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'premise' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(World::statuses())),
            'cover_image' => 'nullable|image|max:2048',
            'cover_url' => 'nullable|url|max:2048',
        ], [
            'novel_id.required' => 'Pilih novel tempat dunia ini bernaung.',
            'novel_id.in' => 'Novel itu tidak tersedia untukmu.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'dunia';
        $slug = $base;
        $i = 1;

        while (
            World::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function resolveImage(Request $request, string $fileField, string $urlField, string $dir, ?string $current): ?string
    {
        if ($request->hasFile($fileField)) {
            Uploads::delete($current);

            return Uploads::store($request->file($fileField), $dir);
        }

        if ($url = trim((string) $request->input($urlField, ''))) {
            return $url;
        }

        return $current;
    }
}
