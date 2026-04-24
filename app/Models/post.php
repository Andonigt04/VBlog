<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['title', 'tags', 'content', 'user_id'])]
#[Hidden(['created_at', 'updated_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, Notifiable;

}
