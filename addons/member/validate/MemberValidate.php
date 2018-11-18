<?php
namespace addons\member\validate;
use think\Validate;

class MemberValidate extends Validate
{
    protected $rule = [
        'username'      =>  'require|max:15|min:5|alphaDash',
        'national'      =>  'require|max:15',
        'pay_password'      =>  'require|max:15',
        'password'      =>  'require|max:15|alphaDash',
        'phone'      =>  'require|max:15',
        'code'      =>  'require|max:15',
        'country'      =>  'require|max:15',
        'user_id'  => 'require|'
    ];

    protected $message  =   [
        'username.require'      =>  '用户名不能为空',
        'username.max'          =>  '用户名称最多不能超过15个字符',
        'desc.max'          =>  '备注最多不能超过100个字符',
        'user_id.require'          =>  '请求异常',
        'country.require'          =>  '请选择国家',
        'password.require'          =>  '请输入密码',
    ];
    protected $scene = [
        'register'   =>  ['username','pay_password','password','phone','code'],
        'login'  =>  ['username','password'],
    ];

}
