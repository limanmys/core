<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LimanRequest extends Model
{
  use UsesUuid;

  protected $fillable = [
    "user_id", "status", "speed", "type", "note", "email"
  ];
}
