<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Carries a soft delete down to a record's children, and undoes it exactly.
 *
 * Why this has to exist: a soft delete is an UPDATE, so the database's FK
 * cascade never fires. Without this, soft-deleting a world would leave its
 * characters, locations, organisations and lore behind — alive, listed, and
 * pointing at a parent that is in the bin.
 *
 * How restore stays honest: everything that goes down together is tagged with
 * one `deleted_batch`, and restore brings back exactly that batch. A character
 * the author had already thrown away stays in the bin when the world around it
 * is restored — it was never part of this deletion.
 *
 * The batch id exists because matching on `deleted_at` was not safe: stamps are
 * stored to the second, so a record binned moments earlier looked identical and
 * got resurrected. Record the fact, do not infer it.
 *
 * A model using this declares its soft-deletable children:
 *
 *     protected array $cascadeSoftDeletes = ['characters', 'loreEntries'];
 */
trait CascadesSoftDeletes
{
    /** The batch the record carried just before it was restored. */
    protected ?string $batchBeforeRestore = null;

    public static function bootCascadesSoftDeletes(): void
    {
        static::deleted(function (Model $model) {
            // Only a soft delete needs help. A permanent delete is a real
            // DELETE, so the database cascade does the work for us.
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                return;
            }

            // Already carrying a batch means this model is itself a child in a
            // cascade that is already running; the parent owns the tagging.
            $batch = $model->deleted_batch ?: (string) Str::uuid();

            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_batch' => $batch]);
            $model->deleted_batch = $batch;

            $model->cascadeSoftDeleteTo($model->{$model->getDeletedAtColumn()}, $batch);
        });

        // `restored` fires after the columns have been cleared, so the batch
        // has to be caught on the way past.
        static::restoring(function (Model $model) {
            $model->batchBeforeRestore = $model->getOriginal('deleted_batch') ?: $model->deleted_batch;
        });

        static::restored(function (Model $model) {
            $model->cascadeRestoreFrom($model->batchBeforeRestore);

            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_batch' => null]);
        });
    }

    /** Soft-delete every declared child, tagging it with the parent's batch. */
    public function cascadeSoftDeleteTo(mixed $deletedAt, string $batch): void
    {
        foreach ($this->cascadeRelations() as $name) {
            // The relation already hides trashed children, so anything already
            // in the bin keeps its own batch — and stays behind on restore.
            foreach ($this->{$name}()->get() as $child) {
                // Recurse first, while the child can still see its own
                // children, then stamp the child itself.
                if (method_exists($child, 'cascadeSoftDeleteTo')) {
                    $child->cascadeSoftDeleteTo($deletedAt, $batch);
                }

                $this->stamp($child, $deletedAt, $batch);
            }
        }
    }

    /** Restore only the children tagged with this exact deletion. */
    public function cascadeRestoreFrom(?string $batch): void
    {
        if (blank($batch)) {
            return;
        }

        foreach ($this->cascadeRelations() as $name) {
            $children = $this->{$name}()
                ->onlyTrashed()
                ->where('deleted_batch', $batch)
                ->get();

            foreach ($children as $child) {
                $this->stamp($child, null, null);

                if (method_exists($child, 'cascadeRestoreFrom')) {
                    $child->cascadeRestoreFrom($batch);
                }
            }
        }
    }

    /**
     * Permanently remove this record and everything under it.
     *
     * Deliberately walks the tree in PHP instead of letting the database's FK
     * cascade do it: a cascade fires no model events, so uploaded files,
     * gallery rows and comments would be stranded. Going child-first means
     * every `forceDeleted` hook gets its chance to clean up after itself.
     */
    public function purge(): void
    {
        foreach ($this->cascadeRelations() as $name) {
            foreach ($this->{$name}()->withTrashed()->get() as $child) {
                method_exists($child, 'purge') ? $child->purge() : $child->forceDelete();
            }
        }

        $this->forceDelete();
    }

    /**
     * Write deleted_at straight to the row. Deliberately not $child->delete():
     * that would stamp its own `now()` and break the matching that restore
     * depends on, and would fire another round of events.
     */
    private function stamp(Model $child, mixed $value, ?string $batch): void
    {
        $child->newQueryWithoutScopes()
            ->whereKey($child->getKey())
            ->update([
                $child->getDeletedAtColumn() => $value,
                'deleted_batch' => $batch,
            ]);
    }

    /** @return list<string> */
    protected function cascadeRelations(): array
    {
        return property_exists($this, 'cascadeSoftDeletes') ? $this->cascadeSoftDeletes : [];
    }
}
