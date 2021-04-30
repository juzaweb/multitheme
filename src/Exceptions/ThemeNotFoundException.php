<?php

namespace Tadcms\MultiTheme\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Tadcms\MultiTheme\Exceptions\ThemeNotFoundException
 *
 * @package    Tadcms\MultiTheme
 * @author     The Anh Dang <dangtheanh16@gmail.com>
 * @link       https://github.com/tadcms/tadcms
 * @license    MIT
 */
class ThemeNotFoundException extends NotFoundHttpException
{
    public function __construct($themeName)
    {
        parent::__construct("Theme [ $themeName ] not found!");
    }
}
