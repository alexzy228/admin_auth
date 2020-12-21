<?php


namespace Ycbl\AdminAuth\Annotation;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 * Class Auth
 */
class Auth extends AbstractAnnotation
{
    /**
     * @var bool
     */
    public $noNeedLogin = false;

    /**
     * @var bool
     */
    public $noNeedRight = false;

    public function __construct($value = null)
    {
        parent::__construct($value);
    }

}