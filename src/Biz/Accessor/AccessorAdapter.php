<?php

namespace Biz\Accessor;

use Biz\User\CurrentUser;
use Codeages\Biz\Framework\Context\Biz;

abstract class AccessorAdapter implements AccessorInterface
{
    /**
     * @var Biz
     */
    protected $biz;

    private $messages;

    public function __construct($biz)
    {
        $this->biz = $biz;
        $this->registerDefaultMessages();
        $this->registerMessages();
    }

    protected function registerMessages()
    {
    }

    abstract public function access($bean);

    protected function buildResult($code, $params = array())
    {
        // api暂时不需要支持国际化
        return array(
            'code' => $code,
            'msg' => $this->getMessage($code, $params),
        );
    }

    private function getMessage($key, $params)
    {
        if (empty($this->messages[$key])) {
            return 'Denied';
        }
        if (!empty($params)) {
            return sprintf($this->messages[$key], extract($params, EXTR_OVERWRITE));
        }

        return $this->messages[$key];
    }

    private function registerDefaultMessages()
    {
        $this->messages['course.not_found'] = '教学计划未找到';
        $this->messages['course.unpublished'] = '教学计划(#%s)尚未发布';
        $this->messages['course.closed'] = '教学计划(#%s)已关闭';
        $this->messages['course.expired'] = '教学计划(#%s)已到期';
        $this->messages['course.buy_expiried'] = '教学计划(#%s)已过购买期限';

        $this->messages['user.not_found'] = '用户未找到';
        $this->messages['user.not_login'] = '用户未登录';
        $this->messages['user.locked'] = '用户(#%s)已被禁用';

        $this->messages['member.not_found'] = '会员未找到';
        $this->messages['member.member_exist'] = '用户(#%s)已经是会员';
        $this->messages['member.expired'] = '会员(#%s)已过期';
        $this->messages['member.auditor'] = '旁听生无权限学习';

        $this->messages['classroom.not_found'] = '班级未找到';
        $this->messages['classroom.unpublished'] = '班级(#%s)未发布';
        $this->messages['classroom.closed'] = '班级(#%s)已关闭';
        $this->messages['classroom.expired'] = '班级(#%s)已到期';
    }

    protected function registerMessage($key, $msg)
    {
        if (empty($this->messages[$key])) {
            $this->messages[$key] = $msg;
        }
    }

    /**
     * @return CurrentUser
     */
    protected function getCurrentUser()
    {
        return $this->biz['user'];
    }
}
