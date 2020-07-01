<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobHistory extends Model
{
    use UsesUuid;

    protected $table = 'jobs_history';

    protected $fillable = [
        "server_id",
        "extension_id",
        "user_id",
        "status",
        "job",
        "job_id",
    ];
}
