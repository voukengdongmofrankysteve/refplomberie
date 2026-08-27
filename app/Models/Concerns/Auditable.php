<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Consigne la création, la modification et la suppression d'un modèle dans le
 * journal d'audit.
 *
 * Posé sur les modèles que gère le back-office — jamais sur ceux qu'un
 * client modifie lui-même (son propre profil, ses favoris) : `AuditLog`
 * filtre de toute façon aux seules actions d'un administrateur, mais autant
 * ne pas prétendre auditer ce qui n'a pas vocation à l'être.
 *
 * La ligne se pose avant l'écriture en base (`updating`, pas `updated`) pour
 * lire l'ancienne valeur pendant qu'elle est encore là — et parce que la
 * plupart des actions du back-office s'exécutent dans une transaction : si
 * elle échoue et se défait, la ligne d'audit se défait avec elle.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLog::record($model, 'created', snapshot: static::auditSnapshot($model));
        });

        static::updating(function (Model $model): void {
            $changes = static::auditChanges($model);

            if ($changes !== []) {
                AuditLog::record($model, 'updated', changes: $changes);
            }
        });

        static::deleted(function (Model $model): void {
            AuditLog::record($model, 'deleted', snapshot: static::auditSnapshot($model));
        });
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private static function auditChanges(Model $model): array
    {
        $dirty = $model->getDirty();
        unset($dirty['updated_at']);

        $changes = [];

        foreach ($dirty as $key => $new) {
            if (in_array($key, $model->getHidden(), true)) {
                continue;
            }

            $changes[$key] = ['old' => $model->getOriginal($key), 'new' => $new];
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    private static function auditSnapshot(Model $model): array
    {
        return collect($model->getAttributes())
            ->except($model->getHidden())
            ->except(['created_at', 'updated_at'])
            ->all();
    }
}
