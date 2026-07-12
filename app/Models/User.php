<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'student_id',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function assignedClassrooms(): BelongsToMany
    { return $this->belongsToMany(Classroom::class, 'classroom_professor', 'professor_id', 'classroom_id')->withTimestamps(); }
    public function isOperationalAdministrator(): bool { return $this->hasRole(Role::ROLE_ADMINISTRATOR); }
    public function isDirector(): bool { return $this->hasRole(Role::ROLE_DIRECTOR); }
    public function isSecretary(): bool { return $this->hasRole(Role::ROLE_SECRETARY); }
    public function isProfessor(): bool { return $this->hasRole(Role::ROLE_PROFESSOR); }
    public function isStudentUser(): bool { return $this->hasRole(Role::ROLE_STUDENT); }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

   
}
