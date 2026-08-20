<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];

    const ROLE_ADMINISTRATOR = 'Operational Administrator';

    const ROLE_DIRECTOR = 'Director';

    const ROLE_SECRETARY = 'Service Secretariat';

    const ROLE_PROFESSOR = 'Professor';

    const ROLE_STUDENT = 'Student';

    public static function supportedNames(): array
    {
        return [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_DIRECTOR,
            self::ROLE_SECRETARY,
            self::ROLE_PROFESSOR,
            self::ROLE_STUDENT,
        ];
    }
}
