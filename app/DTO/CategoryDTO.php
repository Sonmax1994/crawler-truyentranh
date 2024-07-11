<?php

namespace App\DTO;

class CategoryDTO
{
    public $id;
    public $name;
    public $slug;

    public function __construct($category)
    {
        $this->id = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
    }
}
