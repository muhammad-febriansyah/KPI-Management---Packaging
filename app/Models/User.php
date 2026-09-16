<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'avatar_path', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)
            ->withPivot(['role_id', 'is_default', 'status'])
            ->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * The role code this user holds for the given client, or null if unassigned.
     * Super admins always resolve to 'super-admin' regardless of client membership.
     */
    public function roleCodeFor(?Client $client): ?string
    {
        if ($this->is_super_admin) {
            return 'super-admin';
        }

        if (! $client) {
            return null;
        }

        $roleId = $this->clients()->whereKey($client->getKey())->first()?->pivot?->role_id;

        return $roleId ? Role::query()->whereKey($roleId)->value('code') : null;
    }

    /**
     * Sidebar menu keys this user may see for the given client.
     * Null means unrestricted (super admins always see every menu).
     * An empty array means the user has no role for this client, or their
     * role has not been granted any menu yet.
     *
     * @return array<int, string>|null
     */
    public function allowedMenuKeys(?Client $client): ?array
    {
        if ($this->is_super_admin) {
            return null;
        }

        $roleId = $client ? $this->clients()->whereKey($client->getKey())->first()?->pivot?->role_id : null;

        if (! $roleId) {
            return [];
        }

        return Role::query()->whereKey($roleId)->first()
            ?->permissions()
            ->where('code', 'like', 'menu.%')
            ->pluck('code')
            ->map(fn (string $code): string => substr($code, 5))
            ->all() ?? [];
    }

    /**
     * Whether this user's role for the given client has been granted the given menu.
     * Super admins always have access. Use this for controller-level gating so it
     * always follows whatever Super Admin configured in Settings > Hak Akses.
     */
    public function canAccessMenu(string $menuKey, ?Client $client): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return in_array($menuKey, $this->allowedMenuKeys($client) ?? [], true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
