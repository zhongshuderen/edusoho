<?php

namespace Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;
use Biz\Activity\Service\ActivityService;
use Biz\Testpaper\Service\TestpaperService;
use Biz\Activity\Service\ActivityLearnLogService;
use Biz\Activity\Service\TestpaperActivityService;

class Testpaper extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getTestpaperActivityService()->getActivity($targetId);
    }

    public function create($fields)
    {
        $fields = $this->filterFields($fields);

        return $this->getTestpaperActivityService()->createActivity($fields);
    }

    public function copy($activity, $config = array())
    {
        if ($activity['mediaType'] != 'testpaper') {
            return null;
        }

        $ext = $this->get($activity['mediaId']);

        $newExt = array(
            'mediaId' => $config['testId'],
            'doTimes' => $ext['doTimes'],
            'redoInterval' => $ext['redoInterval'],
            'limitedTime' => $ext['limitedTime'],
            'checkType' => $ext['checkType'],
            'finishCondition' => $ext['finishCondition'],
            'requireCredit' => $ext['requireCredit'],
            'testMode' => $ext['testMode'],
        );

        return $this->create($newExt);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceExt = $this->getTestpaperActivityService()->getActivity($sourceActivity['mediaId']);
        $ext = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        $testPaper = $this->getTestpaperService()->getTestpaperByCopyIdAndCourseSetId($sourceExt['mediaId'], $activity['fromCourseSetId']);

        $ext['mediaId'] = $testPaper['id'];
        $ext['redoInterval'] = $sourceExt['redoInterval'];
        $ext['limitedTime'] = $sourceExt['limitedTime'];
        $ext['checkType'] = $sourceExt['checkType'];
        $ext['finishCondition'] = $sourceExt['finishCondition'];
        $ext['requireCredit'] = $sourceExt['requireCredit'];
        $ext['testMode'] = $sourceExt['testMode'];

        return $this->getTestpaperActivityService()->updateActivity($ext['id'], $ext);
    }

    public function update($targetId, &$fields, $activity)
    {
        $activity = $this->get($targetId);

        if (!$activity) {
            throw $this->createNotFoundException('教学活动不存在');
        }

        //引用传递，当考试时间设置改变时，时间值也改变
        if ($fields['testMode'] == 'normal') {
            $fields['startTime'] = 0;
        }

        $filterFields = $this->filterFields($fields);

        return $this->getTestpaperActivityService()->updateActivity($activity['id'], $filterFields);
    }

    public function delete($targetId)
    {
        return $this->getTestpaperActivityService()->deleteActivity($targetId);
    }

    public function isFinished($activityId)
    {
        $biz = $this->getBiz();
        $user = $biz['user'];

        $activity = $this->getActivityService()->getActivity($activityId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        $result = $this->getTestpaperService()->getUserLatelyResultByTestId($user['id'], $testpaperActivity['mediaId'], $activity['fromCourseSetId'], $activity['id'], 'testpaper');

        if (!$result || empty($testpaperActivity['finishCondition'])) {
            return false;
        }

        if (in_array($result['status'], array('reviewing', 'finished')) && $testpaperActivity['finishCondition']['type'] == 'submit') {
            return true;
        }

        if (in_array($result['status'], array('reviewing', 'finished')) && $testpaperActivity['finishCondition']['type'] == 'score' && $result['score'] >= $testpaperActivity['finishCondition']['finishScore']) {
            return true;
        }

        return false;
    }

    protected function getListeners()
    {
        return array();
    }

    protected function filterFields($fields)
    {
        $filterFields = ArrayToolkit::parts($fields, array(
            'mediaId',
            'doTimes',
            'redoInterval',
            'length',
            'limitedTime',
            'checkType',
            'finishCondition',
            'finishScore',
            'requireCredit',
            'testMode',
        ));

        $finishCondition = array();

        if (!empty($filterFields['finishCondition'])) {
            $finishCondition['type'] = $filterFields['finishCondition'];
        }

        if (isset($filterFields['finishScore'])) {
            $finishCondition['finishScore'] = $filterFields['finishScore'];
            unset($filterFields['finishScore']);
        }

        if (isset($filterFields['length'])) {
            $filterFields['limitedTime'] = $filterFields['length'];
            unset($filterFields['length']);
        }

        if (isset($filterFields['doTimes']) && $filterFields['doTimes'] == 0) {
            $filterFields['testMode'] = 'normal';
        }

        $filterFields['finishCondition'] = $finishCondition;

        return $filterFields;
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->getBiz()->service('Activity:TestpaperActivityService');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->getBiz()->service('Testpaper:TestpaperService');
    }
}
