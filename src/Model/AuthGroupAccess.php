<?php

declare (strict_types=1);
namespace Ycbl\AdminAuth\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $uid 
 * @property int $group_id 
 */
class AuthGroupAccess extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auth_group_access';
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
    protected $casts = ['uid' => 'integer', 'group_id' => 'integer'];
}