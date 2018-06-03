<?php

namespace Beheist\NW\ViewHelpers;

use Neos\FluidAdaptor\Core\ViewHelper;

class GradingClassViewHelper extends ViewHelper\AbstractViewHelper
{
    /**
     * Renders the grade class.
     * @param int $grade the grade
     * @return string grade class
     */
    public function render($grade)
    {
        if ($grade >= 90) {
            return 'verygood';
        } else if ($grade >= 70) {
            return 'good';
        } else if ($grade >= 50) {
            return 'average';
        } else {
            return 'bad';
        }
    }
}
