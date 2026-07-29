<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Penyewa = 'penyewa';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Owner => 'Owner',
            self::Admin => 'Admin Cabang',
            self::Staff => 'Staff',
            self::Penyewa => 'Penyewa',
        };
    }

    /**
     * Roles that operate inside the admin CMS.
     *
     * @return array<int, self>
     */
    public static function staffRoles(): array
    {
        return [self::SuperAdmin, self::Owner, self::Admin, self::Staff];
    }

    /**
     * Whether this role uses the tenant/staff-facing CMS (vs. the penyewa portal).
     */
    public function isStaff(): bool
    {
        return in_array($this, self::staffRoles(), true);
    }
}
