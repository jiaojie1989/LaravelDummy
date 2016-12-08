<?php

namespace App\Models\Foxebook;

use Jenssegers\Mongodb\Eloquent\Model;

class Book extends Model
{

    protected $connection = "mongo";
    protected $collection = "books";

}
