<?php

namespace App\Services\Pim\PropertyGroup\Form;

use App\Models\Pim\Property\PimPropertyGroup;

abstract class Form
{
    protected PimPropertyGroup $group;

    protected bool $inlineEdit;

    public function __construct(PimPropertyGroup $group, bool $inlineEdit = false)
    {
        $this->group = $group;
        $this->inlineEdit = $inlineEdit;
    }
}
