<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterRelation;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ties between two characters of the same world. One row per tie — the
 * opposite side is derived from CharacterRelation's inverse map, so a tie
 * added here immediately shows up on the other character's page too.
 */
class CharacterRelationController extends Controller
{
    public function store(Request $request, World $world, Character $character)
    {
        $this->authorize('update', $world);

        $data = $request->validate([
            'related_character_id' => [
                'required',
                'integer',
                Rule::exists('characters', 'id')->where('world_id', $world->id),
                Rule::notIn([$character->id]),
            ],
            'type' => ['required', Rule::in(CharacterRelation::typeKeys())],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'related_character_id.required' => 'Pilih karakter yang ingin ditautkan.',
            'related_character_id.exists' => 'Karakter itu tidak ada di dunia ini.',
            'related_character_id.not_in' => 'Karakter tidak bisa ditautkan dengan dirinya sendiri.',
            'type.required' => 'Pilih jenis relasinya.',
        ]);

        $other = (int) $data['related_character_id'];

        // The same tie may already exist from either side — reject both.
        $exists = CharacterRelation::query()
            ->matchingPair($character->id, $other, $data['type'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'related_character_id' => 'Relasi itu sudah tercatat.',
            ]);
        }

        CharacterRelation::create([
            'world_id' => $world->id,
            'character_id' => $character->id,
            'related_character_id' => $other,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
        ]);

        $otherName = Character::where('world_id', $world->id)->find($other)?->name;

        return back()->with('status', "Relasi dengan \"{$otherName}\" ditambahkan.");
    }

    public function destroy(World $world, Character $character, string $relation)
    {
        $this->authorize('update', $world);

        // Deletable from either side, but only within this world and only when
        // this character is actually part of the tie.
        $row = CharacterRelation::where('world_id', $world->id)
            ->where(function ($query) use ($character) {
                $query->where('character_id', $character->id)
                    ->orWhere('related_character_id', $character->id);
            })
            ->findOrFail($relation);

        $row->delete();

        return back()->with('status', 'Relasi dilepas.');
    }
}
