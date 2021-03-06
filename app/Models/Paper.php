<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paper extends Model
{
    use HasFactory;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function suites()
    {
        return $this->belongsToMany(Suite::class, 'suite_paper', 'paper_id', 'suite_id');
    }
}
