<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
   protected $fillable = ['name'];
   const ROLE_ADMINISTRATOR = 'Operational Administrator';
   const ROLE_DIRECTOR = 'Director';
   const ROLE_SECRETARY = 'Service Secrétaire';
   const ROLE_PROFESSOR = 'Professor';
   const ROLE_STUDENT = 'Student';
}
