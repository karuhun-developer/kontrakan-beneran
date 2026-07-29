<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Branches this user is assigned to (relevant for admin/staff).
     *
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * @return HasOne<Penyewa, $this>
     */
    public function penyewa(): HasOne
    {
        return $this->hasOne(Penyewa::class);
    }

    /**
     * The user's primary role as an enum, if it maps to a known role.
     */
    public function primaryRole(): ?UserRole
    {
        $name = $this->getRoleNames()->first();

        return $name ? UserRole::tryFrom($name) : null;
    }

    public function hasRoleEnum(UserRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * Whether the user belongs to the staff-facing CMS (vs. the penyewa portal).
     */
    public function isStaff(): bool
    {
        return (bool) $this->primaryRole()?->isStaff();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRoleEnum(UserRole::SuperAdmin);
    }

    /**
     * Branches the user is allowed to operate in.
     * Super-admins see every branch; owners see every branch in their tenant;
     * admin/staff are limited to their explicitly assigned branches.
     *
     * @return Collection<int, Branch>
     */
    public function accessibleBranches(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Branch::query()->orderBy('name')->get();
        }

        if ($this->hasRoleEnum(UserRole::Owner) && $this->tenant_id) {
            return Branch::query()->where('tenant_id', $this->tenant_id)->orderBy('name')->get();
        }

        return $this->branches()->orderBy('name')->get();
    }

    public function canAccessBranch(Branch $branch): bool
    {
        return $this->accessibleBranches()->contains('id', $branch->id);
    }
}
