<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
   protected $fillable = ['name'];
   const ROLE_ADMINISTRATOR = 'Administrator';
   const ROLE_OWNER = 'Service Secrétaire';
   const ROLE_USER = 'Simple User';
   
  


}
