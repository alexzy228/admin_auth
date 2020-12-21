<?php

declare (strict_types=1);
namespace Ycbl\AdminAuth\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $pid 
 * @property string $path 
 * @property string $auth 
 * @property string $title 
 * @property string $icon 
 * @property string $remark 
 * @property int $ismenu 
 * @property int $weigh 
 * @property int $status 
 */
class AuthRule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auth_rule';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'ismenu' => 'integer', 'weigh' => 'integer', 'status' => 'integer'];
}