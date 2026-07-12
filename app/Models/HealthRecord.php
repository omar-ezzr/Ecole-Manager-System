<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    protected $fillable = ['student_id', 'date', 'type', 'medical_prescription'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
