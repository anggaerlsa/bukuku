<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMember;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Membership: a character's place inside an organisation, with the rank they
 * hold there. Added from either side — the organisation's page or the
 * character's — so both forms post here and we simply go back.
 */
class OrganizationMemberController extends Controller
{
    public function store(Request $request, World $world)
    {
        $this->authorize('update', $world);

        $data = $request->validate([
            'organization_id' => [
                'required', 'integer',
                Rule::exists('organizations', 'id')->where('world_id', $world->id),
            ],
            'character_id' => [
                'required', 'integer',
                Rule::exists('characters', 'id')->where('world_id', $world->id),
            ],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(OrganizationMember::statuses()))],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'organization_id.exists' => 'Organisasi itu tidak ada di dunia ini.',
            'character_id.required' => 'Pilih karakter yang ingin ditambahkan.',
            'character_id.exists' => 'Karakter itu tidak ada di dunia ini.',
        ]);

        $exists = OrganizationMember::where('organization_id', $data['organization_id'])
            ->where('character_id', $data['character_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'character_id' => 'Karakter itu sudah terdaftar di organisasi ini.',
            ]);
        }

        OrganizationMember::create($data);

        return back()->with('status', 'Anggota ditambahkan.');
    }

    public function destroy(World $world, string $member)
    {
        $this->authorize('update', $world);

        // Scoped through the world so a membership from another world can never
        // be removed by id-guessing.
        $row = OrganizationMember::whereHas('organization', fn ($q) => $q->where('world_id', $world->id))
            ->findOrFail($member);

        $row->delete();

        return back()->with('status', 'Anggota dilepas dari organisasi.');
    }
}
