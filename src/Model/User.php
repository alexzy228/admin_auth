<?php

declare (strict_types=1);
namespace Ycbl\AdminAuth\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id
 * @property string $real_name
 * @property string $card_no
 * @property string $telephone
 * @property string $password
 * @property string $last_ip
 * @property string $status
 * @property string $secret
 * @property int $create_user
 * @property int $update_user
 * @property int $psw_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';
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
    protected $casts = ['id' => 'integer', 'create_user' => 'integer', 'update_user' => 'integer', 'psw_time' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $hidden = ['password'];
}