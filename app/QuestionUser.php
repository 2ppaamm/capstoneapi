<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Question;

class QuestionUser extends Model
{
    use HasFactory;

    protected $table = 'question_user'; // ensure this matches your actual table name

    public $incrementing = false; // no auto-increment ID
    public $timestamps = false;   // if your table doesn't have created_at / updated_at

    protected $primaryKey = null; // no single primary key

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}