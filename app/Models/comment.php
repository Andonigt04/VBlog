<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

#[Fillable(['title', 'post_id', 'user_id', 'tags', 'content'])]
#[Hidden(['created_at', 'updated_at'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, Notifiable;

}
