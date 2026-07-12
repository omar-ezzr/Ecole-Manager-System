<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'semester_id'];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }
}
