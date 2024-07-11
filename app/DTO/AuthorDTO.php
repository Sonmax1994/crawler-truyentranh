<?php

namespace App\DTO;

class AuthorDTO
{
    public $id;
    public $name;
    public $slug;

    public function __construct($author)
    {
        $this->id = $author->id;
        $this->name = $author->name;
        $this->slug = $author->slug;
    }
}
