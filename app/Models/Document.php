<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Document.php
class Document extends Model
{
    protected $fillable = [
        'id_number',
    'filename',
    'original_name',
    'mime_type',
    'size'];
}

