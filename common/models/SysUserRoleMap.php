<?php
/**
 * SysUserRoleMap Model.
 *
 * @category description
 *
 * @author guolei <guolei@lkmotion.com>
 */

namespace common\models;

/**
 * Undocumented class.
 */
class SysUserRoleMap extends BaseModel
{
    /**
     * Undocumented function.
     */
    public static function tableName()
    {
        return '{{%sys_user_role_map}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'role_id', 'is_deleted'], 'required']
        ];
    }
}
