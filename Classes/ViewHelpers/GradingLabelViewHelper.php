<?php

namespace Beheist\NW\ViewHelpers;

use Neos\FluidAdaptor\Core\ViewHelper;

class GradingLabelViewHelper extends ViewHelper\AbstractViewHelper
{
    /**
     * Renders the grade class.
     * @param int $grade the grade
     * @return string grade label
     */
    public function render($grade)
    {
        if ($grade >= 90) {
            return 'Sehr empfehlenswert';
        } else if ($grade >= 70) {
            return 'Empfehlenswert';
        } else if ($grade >= 50) {
            return 'Befriedigend';
        } else {
            return 'Ausreichend';
        }
    }
}
