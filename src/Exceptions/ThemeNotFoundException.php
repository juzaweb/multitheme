<?php

namespace Theanh\MultiTheme\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Theanh\MultiTheme\Exceptions\ThemeNotFoundException
 *
 * @package    Theanh\MultiTheme
 * @author     The Anh Dang <dangtheanh16@gmail.com>
 * @link       https://github.com/theanhk/tadcms
 * @license    MIT
 */
class ThemeNotFoundException extends NotFoundHttpException
{
    public function __construct($themeName)
    {
        parent::__construct("Theme [ $themeName ] not found!");
    }
}
