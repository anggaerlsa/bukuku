<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\LoreEntry;
use App\Models\World;
use App\Support\CustomFieldInput;
use App\Support\LoreCategories;
use App\Support\Uploads;
use Illuminate\Http\Request;

/**
 * Lore articles — the catch-all for what is neither person, place nor faction.
 * Grouped by a free-text `category` so each world keeps its own vocabulary.
 */
class LoreEntryController extends Controller
{
    public function index(Request $request, World $world)
    {
        $this->authorize('view', $world);

        $query = $world->loreEntries()->orderBy('category')->orderBy('position')->orderBy('title');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $category = $request->query('kategori');
        if (filled($category)) {
            $query->where('category', $category);
        } else {
            $category = null;
        }

        // Grouped rather than paginated: an author browses lore by subject,
        // and the category headings are the point of the page.
        $entries = $query->get()->groupBy(fn (LoreEntry $e) => $e->categoryLabel());

        return view('manage.lore.index', [
            'world' => $world,
            'entries' => $entries,
            'search' => $search,
            'category' => $category,
            'categories' => LoreCategories::usedIn($world),
            'suggestions' => LoreCategories::suggestionsFor($world),
            'total' => $world->loreEntries()->count(),
        ]);
    }

    public function create(Request $request, World $world)
    {
        $this->authorize('update', $world);

        return view('manage.lore.create', [
            'world' => $world,
            'entry' => new LoreEntry(['category' => $request->query('kategori')]),
        ] + $this->formData($world));
    }

    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $data = $this->validateEntry($request, $world);
        $data['cover_image'] = $this->resolveImage($request, null);

        $entry = $world->loreEntries()->create($data);
        CustomFieldInput::sync($entry, $request->input('custom', []));

        return redirect()->route('lore.show', [$world, $entry])
            ->with('status', "Artikel \"{$entry->title}\" ditambahkan.");
    }

    public function show(World $world, LoreEntry $loreEntry)
    {
        $this->authorize('view', $world);

        // Tetangga dalam kategori yang sama, untuk berpindah cepat.
        $siblings = $world->loreEntries()
            ->where('category', $loreEntry->category)
            ->whereKeyNot($loreEntry->id)
            ->orderBy('position')->orderBy('title')
            ->get(['id', 'title']);

        return view('manage.lore.show', ['world' => $world, 'lore' => $loreEntry, 'siblings' => $siblings]);
    }

    public function edit(World $world, LoreEntry $loreEntry)
    {
        $this->authorize('update', $world);

        return view('manage.lore.edit', [
            'world' => $world,
            'entry' => $loreEntry,
        ] + $this->formData($world, $loreEntry));
    }

    public function update(Request $request, World $world, LoreEntry $loreEntry)
    {
        $this->authorize('update', $world);

        $data = $this->validateEntry($request, $world);
        $data['cover_image'] = $this->resolveImage($request, $loreEntry->cover_image);

        $loreEntry->update($data);
        CustomFieldInput::sync($loreEntry, $request->input('custom', []));

        return redirect()->route('lore.show', [$world, $loreEntry])
            ->with('status', "Artikel \"{$loreEntry->title}\" diperbarui.");
    }

    public function destroy(World $world, LoreEntry $loreEntry)
    {
        $this->authorize('update', $world);

        Uploads::delete($loreEntry->cover_image);

        $title = $loreEntry->title;
        $loreEntry->delete();

        return redirect()->route('lore.index', $world)->with('status', "Artikel \"{$title}\" dihapus.");
    }

    private function formData(World $world, ?LoreEntry $entry = null): array
    {
        return [
            'categoryOptions' => LoreCategories::optionsFor($world),
            'customFields' => $this->customFields($world),
            'customValues' => $entry?->customFieldValueMap() ?? [],
        ];
    }

    private function customFields(World $world)
    {
        return CustomField::where('world_id', $world->id)->forTarget('lore')->get();
    }

    private function validateEntry(Request $request, World $world): array
    {
        $customFields = $this->customFields($world);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            // Free text on purpose — the vocabulary belongs to the author.
            'category' => 'nullable|string|max:100',
            'summary' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'position' => 'nullable|integer|min:0|max:9999',
            'cover_image' => 'nullable|image|max:2048',
            'cover_url' => 'nullable|url|max:2048',
        ] + CustomFieldInput::rules($customFields), [], CustomFieldInput::attributes($customFields));

        $data['category'] = trim((string) ($data['category'] ?? '')) ?: null;
        unset($data['custom']);

        return $data;
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('cover_image')) {
            Uploads::delete($current);

            return Uploads::store($request->file('cover_image'), 'lore');
        }

        if ($url = trim((string) $request->input('cover_url', ''))) {
            return $url;
        }

        return $current;
    }
}
