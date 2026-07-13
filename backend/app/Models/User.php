<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;


// dsjklfdsjklfdsjkldfjslkfdjslkfjdslkfjds
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens, HasPushSubscriptions;

    use \App\Traits\InteractsWithModuleDocuments;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['avatar'];
    protected $moduleDocumentKeys = ['avatar'];

    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'phone',
        'email_verified_at',
        'role',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function getDefaultGuardName(): string
    {
        return 'web';
    }
    public function routeNotificationForExpo($notification = null)
    {
        // return [
        //     \NotificationChannels\Expo\ExpoPushToken::make('ExponentPushToken[pV6qPoPl44zR3JXbHZ549X]')
        // ];

        // Original logic:
        $tokens = $this->devices()
            ->where('is_active', true)
            ->pluck('push_token')
            ->toArray();

        return collect($tokens)->map(fn($token) => \NotificationChannels\Expo\ExpoPushToken::make($token))->toArray();
    }

    public function hasExpoToken(): bool
    {
        $tokens = $this->routeNotificationForExpo();
        return !empty($tokens);
    }
    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
    ];
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }

            if (!empty($model->name)) {
                $baseSlug = Str::slug($model->name);
                $slug = $baseSlug;
                $counter = 1;

                // Ensure slug is unique
                while (self::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $model->slug = $slug;
            }

            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($user) {
            // Update slug if name is dirty
            if ($user->isDirty('name')) {
                $baseSlug = Str::slug($user->name);
                $slug = $baseSlug;
                $counter = 1;

                // Ensure slug is unique
                while (self::where('slug', $slug)
                    ->where('id', '!=', $user->id)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $user->slug = $slug;
            }

            // Always update the updated_by field
            if (auth()->check()) {
                $user->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    // In App\Models\User.php

    public function setRoleAttribute($roleName)
    {
        $this->attributes['role'] = $roleName;

        if (!empty($roleName)) {
            // Sync Spatie roles
            $this->syncRoles([$roleName]);
        }
    }


    public function setRolesAttribute($roles)
    {
        $this->syncRoles($roles);
        $this->attributes['role'] = is_array($roles) ? $roles[0] : $roles;
    }

    public static function getAllowedPanelRoles(): array
    {
        return ['super_admin', 'admin', 'doctor_manager', 'doctor', 'patient'];
    }

    public function canAccessResource(string $permission): bool
    {
        return $this->hasRole('super_admin') || $this->can($permission);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        if ($this->hasAnyRole(self::getAllowedPanelRoles())) {
            return true;
        }

        // Check if user has any permission that starts with a module name
        // This is a broad but safe way to allow access if they have ANY permission
        // Alternatively, check for a specific permission like 'dashboard.view'
        if ($this->hasAnyPermission(\App\Models\Permission::pluck('name')->toArray())) {
            return true;
        }

        return $this->can('dashboard.view');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the value of the model's route key.
     * Fallback to ID if slug is missing to prevent route generation errors.
     */
    public function getRouteKey()
    {
        return $this->slug ?: $this->id;
    }

    /**
     * Accessor for name to provide a fallback.
     */
    public function getNameAttribute($value)
    {
        return $value ?: 'Unknown';
    }

    /**
     * Accessor for slug to provide a fallback to ID.
     * This prevents route generation errors when slug is null.
     */
    public function getSlugAttribute($value)
    {
        return $value ?: $this->id;
    }

    /**
     * Retrieve the model for a bound value.
     * Supports looking up by slug OR ID.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where(function ($q) use ($value, $field) {
            $searchField = $field ?: 'slug';
            $q->where($searchField, $value);

            // If we are searching by slug and the value is a UUID, also search by ID
            if ($searchField === 'slug' && Str::isUuid($value)) {
                $q->orWhere('id', $value);
            }
        });
    }
    // In User.php model

    public static function canUserAccess(): bool
    {
        $user = auth()->user();
        return $user != null;
    }

    public function scopeVisibleTo($query, $user = null)
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }
        return $query->whereHas('roles', function ($q) use ($user) {
            $q->where('name', '!=', 'super_admin');
        });
    }

    /**
     * Get the primary role name from Spatie roles
     * Returns the first role name or null if no roles assigned
     */
    public function getPrimaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    /**
     * Accessor for role attribute - gets from Spatie roles
     */
    public function getRoleAttribute(): ?string
    {
        return $this->getPrimaryRole();
    }

    /**
     * Override savePendingModuleDocuments to save files in user_avatar folder
     */
    protected function savePendingModuleDocuments()
    {
        foreach ($this->pendingModuleDocuments as $key => $file) {
            $this->moduleDocuments()->where('name', $key)->delete();

            if ($file) {
                // Ensure file path includes user_avatar folder
                $filePath = $file;
                if (is_string($file) && !str_contains($file, 'user_avatar/')) {
                    // If file doesn't have the folder, prepend it
                    $fileName = basename($file);
                    $filePath = 'user_avatar/' . $fileName;
                }

                $this->moduleDocuments()->create([
                    'name' => $key,
                    'files' => [$filePath],
                ]);
            }
        }

        $this->pendingModuleDocuments = [];
    }
}
