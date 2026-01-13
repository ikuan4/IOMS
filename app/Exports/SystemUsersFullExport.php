<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports all users (including soft-deleted) with their role + branch details.
 * Includes audit fields and resolved audit user names/emails.
 *
 * @implements WithMapping<User>
 */
class SystemUsersFullExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading, ShouldAutoSize
{
    /** @var array<int, string> */
    private array $userColumns;

    /** @var array<int, string> */
    private array $roleColumns;

    /** @var array<int, string> */
    private array $branchColumns;

    public function __construct()
    {
        $this->userColumns = $this->loadColumns((new User())->getTable(), [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);

        $this->roleColumns = $this->loadColumns((new Role())->getTable(), []);
        $this->branchColumns = $this->loadColumns((new Branch())->getTable(), []);
    }

    /**
     * @return Builder<User>
     */
    public function query()
    {
        return User::query()
            ->withTrashed()
            ->with([
                'roles',
                'createdBy:id,name,email',
                'updatedBy:id,name,email',
                'deletedBy:id,name,email',
                'restoredBy:id,name,email',
                'role' => function ($q) {
                    $q->withTrashed()->with([
                        'branch' => fn($b) => $b->withTrashed(),
                        'createdBy:id,name,email',
                        'updatedBy:id,name,email',
                        'deletedBy:id,name,email',
                        'restoredBy:id,name,email',
                    ]);
                },
                'branch' => function ($q) {
                    $q->withTrashed()->with([
                        'createdBy:id,name,email',
                        'updatedBy:id,name,email',
                        'deletedBy:id,name,email',
                        'restoredBy:id,name,email',
                    ]);
                },
            ])
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = [];

        foreach ($this->userColumns as $col) {
            $headings[] = 'user_' . $col;
        }

        $headings[] = 'user_effective_role_name';
        $headings[] = 'user_effective_role_slug';
        $headings[] = 'user_pivot_roles';

        $headings[] = 'user_created_by_name';
        $headings[] = 'user_created_by_email';
        $headings[] = 'user_updated_by_name';
        $headings[] = 'user_updated_by_email';
        $headings[] = 'user_deleted_by_name';
        $headings[] = 'user_deleted_by_email';
        $headings[] = 'user_restored_by_name';
        $headings[] = 'user_restored_by_email';

        foreach ($this->roleColumns as $col) {
            $headings[] = 'role_' . $col;
        }

        $headings[] = 'role_created_by_name';
        $headings[] = 'role_created_by_email';
        $headings[] = 'role_updated_by_name';
        $headings[] = 'role_updated_by_email';
        $headings[] = 'role_deleted_by_name';
        $headings[] = 'role_deleted_by_email';
        $headings[] = 'role_restored_by_name';
        $headings[] = 'role_restored_by_email';

        foreach ($this->branchColumns as $col) {
            $headings[] = 'branch_' . $col;
        }

        $headings[] = 'branch_created_by_name';
        $headings[] = 'branch_created_by_email';
        $headings[] = 'branch_updated_by_name';
        $headings[] = 'branch_updated_by_email';
        $headings[] = 'branch_deleted_by_name';
        $headings[] = 'branch_deleted_by_email';
        $headings[] = 'branch_restored_by_name';
        $headings[] = 'branch_restored_by_email';

        return $headings;
    }

    /**
     * @return array<int, string|int|float|null>
     */
    public function map($user): array
    {
        /** @var User $user */
        $row = [];

        foreach ($this->userColumns as $col) {
            $row[] = $this->stringify($user->getAttribute($col));
        }

        $effectiveRole = $user->effectiveRole();
        $row[] = $this->stringify($effectiveRole?->name);
        $row[] = $this->stringify($effectiveRole?->slug);
        $row[] = $this->stringify($user->roles->pluck('name')->join('|'));

        $row[] = $this->stringify($user->createdBy?->name);
        $row[] = $this->stringify($user->createdBy?->email);
        $row[] = $this->stringify($user->updatedBy?->name);
        $row[] = $this->stringify($user->updatedBy?->email);
        $row[] = $this->stringify($user->deletedBy?->name);
        $row[] = $this->stringify($user->deletedBy?->email);
        $row[] = $this->stringify($user->restoredBy?->name);
        $row[] = $this->stringify($user->restoredBy?->email);

        $role = $user->role;
        foreach ($this->roleColumns as $col) {
            $row[] = $this->stringify($role?->getAttribute($col));
        }

        $row[] = $this->stringify($role?->createdBy?->name);
        $row[] = $this->stringify($role?->createdBy?->email);
        $row[] = $this->stringify($role?->updatedBy?->name);
        $row[] = $this->stringify($role?->updatedBy?->email);
        $row[] = $this->stringify($role?->deletedBy?->name);
        $row[] = $this->stringify($role?->deletedBy?->email);
        $row[] = $this->stringify($role?->restoredBy?->name);
        $row[] = $this->stringify($role?->restoredBy?->email);

        $branch = $user->branch;
        foreach ($this->branchColumns as $col) {
            $row[] = $this->stringify($branch?->getAttribute($col));
        }

        $row[] = $this->stringify($branch?->createdBy?->name);
        $row[] = $this->stringify($branch?->createdBy?->email);
        $row[] = $this->stringify($branch?->updatedBy?->name);
        $row[] = $this->stringify($branch?->updatedBy?->email);
        $row[] = $this->stringify($branch?->deletedBy?->name);
        $row[] = $this->stringify($branch?->deletedBy?->email);
        $row[] = $this->stringify($branch?->restoredBy?->name);
        $row[] = $this->stringify($branch?->restoredBy?->email);

        return $row;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param string $table
     * @param array<int, string> $exclude
     * @return array<int, string>
     */
    private function loadColumns(string $table, array $exclude): array
    {
        try {
            $cols = Schema::getColumnListing($table);
        } catch (\Throwable) {
            $cols = [];
        }

        $exclude = array_map(fn($v) => strtolower((string) $v), $exclude);

        $result = [];
        foreach ($cols as $c) {
            $lc = strtolower((string) $c);
            if (in_array($lc, $exclude, true)) {
                continue;
            }
            $result[] = (string) $c;
        }

        return $result;
    }

    private function stringify(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            try {
                return $value->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
        }

        return (string) $value;
    }
}
