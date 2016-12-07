<?php 

namespace Topxia\Api\Resource\User;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\SimpleValidator;
use Topxia\Api\Resource\BaseResource;
use Topxia\Api\Util\SmsUtil;

class Password extends BaseResource
{
    public function post(Application $app, Request $request)
    {
        $password = $request->request->get('password');
        $smsCode  = $request->request->get('sms_code');
        $smsToken = $request->request->get('verified_token');
        $type     = $request->request->get('type');

        if (!in_array($type, array('sms', 'email'))) {
            return $this->error('500', '不存在此type对应的业务场景');
        }
        if (empty($smsCode)) {
            return $this->error('500', '短信验证码为空，请输入');
        }
        if (empty($smsToken)) {
            return $this->error('500', 'smsToken字段为空');
        }

        if (empty($password)) {
            return $this->error('500', '未输入新密码');
        }
        if ( !SimpleValidator::password($password)) {
            return $this->error('500', '密码不符合要求');
        }

        if ($type == 'sms') {
            $smsUtil = new SmsUtil();
            $result = $smsUtil->verifySmsCode('sms_change_password', $smsCode, $smsToken);
            if( $result !== true ){
                if( $result == 'sms_code_expired' ){
                     return $this->error('sms_code_expired', '验证码已过期');
                }else{
                    return $this->error('500', '验证码错误');
                }
            }

        }

        $token = $this->getTokenService()->verifyToken('sms_change_password', $smsToken);
        $user  = $this->getUserService()->getUserByVerifiedMobile($token['data']['mobile']);
        $this->getUserService()->changePassword($user['id'], $password);
        $this->getTokenService()->destoryToken($currentToken);

        return array('userId' => $user['id']);
    }

    public function filter($res)
    {
        return $res;
    }

    protected function getTokenService()
    {
        return $this->getServiceKernel()->createService('User.TokenService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }
}