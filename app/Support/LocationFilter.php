<?php

namespace App\Support;

use App\Models\LocationNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Search across all five location tiers WITHOUT flattening the hierarchy.
 *
 * The index already loads the whole tree eagerly, so matching happens in
 * memory: a node is kept when it matches itself or when any descendant does,
 * which leaves every match sitting under its real ancestors. The tree view
 * survives — it is just pruned.
 */
class LocationFilter
{
    /** @var array<string, true> keys are "tier:id" of nodes that matched */
    private array $matched = [];

    private string $term;

    private ?string $tier;

    public function __construct(?string $term = null, ?string $tier = null)
    {
        $this->term = trim((string) $term);
        $this->tier = Hierarchy::isTier($tier) ? $tier : null;
    }

    public function active(): bool
    {
        return $this->term !== '' || $this->tier !== null;
    }

    public function term(): string
    {
        return $this->term;
    }

    public function tier(): ?string
    {
        return $this->tier;
    }

    public function matchCount(): int
    {
        return count($this->matched);
    }

    public function isMatch(LocationNode $node): bool
    {
        return isset($this->matched[$node->tierKey() . ':' . $node->id]);
    }

    /**
     * Prune a loaded tree down to matches and their ancestors.
     *
     * @param  Collection<int, LocationNode>  $nodes  top-tier nodes
     * @return Collection<int, LocationNode>
     */
    public function prune(Collection $nodes): Collection
    {
        if (! $this->active()) {
            return $nodes;
        }

        return $nodes->filter(fn (LocationNode $node) => $this->keep($node))->values();
    }

    /** Depth-first: prune children first, then decide whether this node stays. */
    private function keep(LocationNode $node): bool
    {
        $keptChildren = collect();

        if ($childTier = $node->childTierKey()) {
            $keptChildren = $node->nodeChildren()
                ->filter(fn (LocationNode $child) => $this->keep($child))
                ->values();

            $node->setRelation(Hierarchy::table($childTier), $keptChildren);
        }

        if ($this->matches($node)) {
            $this->matched[$node->tierKey() . ':' . $node->id] = true;

            return true;
        }

        return $keptChildren->isNotEmpty();
    }

    /** Name, in-world type and summary are all searchable; tier narrows it. */
    private function matches(LocationNode $node): bool
    {
        if ($this->tier !== null && $node->tierKey() !== $this->tier) {
            return false;
        }

        if ($this->term === '') {
            return true;
        }

        foreach ([$node->name, $node->type, $node->summary] as $field) {
            if ($field && Str::contains($field, $this->term, ignoreCase: true)) {
                return true;
            }
        }

        return false;
    }
}
