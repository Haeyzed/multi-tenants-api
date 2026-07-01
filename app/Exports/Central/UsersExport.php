<?php

declare(strict_types=1);

namespace App\Exports\Central;

use App\Exports\Central\Concerns\BaseCentralExport;
use App\Models\Central\CentralUser;
use Illuminate\Support\Collection;

/**
 * @extends BaseCentralExport<CentralUser>
 */
class UsersExport extends BaseCentralExport
{
    /**
     * @param  Collection<int, CentralUser>  $users
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $users, ?array $columns = null)
    {
        parent::__construct($users, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return ['id', 'name', 'email', 'phone', 'is_active', 'created_at'];
    }

    /**
     * @return array<string, array{heading: string, map: callable(CentralUser): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (CentralUser $user) => (string) $user->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (CentralUser $user) => $user->name,
            ],
            'email' => [
                'heading' => 'Email',
                'map' => fn (CentralUser $user) => $user->email,
            ],
            'phone' => [
                'heading' => 'Phone',
                'map' => fn (CentralUser $user) => $user->phone,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (CentralUser $user) => $user->is_active ? 'Yes' : 'No',
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (CentralUser $user) => $user->created_at?->toDateTimeString(),
            ],
        ];
    }
}
