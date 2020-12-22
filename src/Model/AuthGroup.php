<?php

declare (strict_types=1);
namespace Ycbl\AdminAuth\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $pid 
 * @property string $name 
 * @property string $rules 
 * @property int $status 
 */
class AuthGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auth_group';

    public $timestamps = false;
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
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'status' => 'integer'];
}