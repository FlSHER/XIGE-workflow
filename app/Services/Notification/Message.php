<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/20/020
 * Time: 11:38
 */

namespace App\Services\Notification;


use App\Services\OA\OaApiService;

trait Message
{
    /**
     * 发送工作通知OA消息
     * @param $stepRun
     * @param $formData
     */
    public function sendJobOaMessage($stepRun, $formData)
    {
        //前三表单data
        $topThreeFormData = $this->getTopThreeFormData($formData, $stepRun->form_id);
        $data = [
            'oa_client_id' => config('oa.client_id'),
            'userid_list' => $stepRun->approver_sn,
            'msg' => [
                'msgtype' => 'oa',
                'oa' => [
                    'message_url' => request()->get('host') . '/' . $stepRun->id,
                    'head' => [
                        'bgcolor' => 'FFF44336',
                        'text' => '工作流'
                    ],
                    'body' => [
                        'title' => $stepRun->flowRun->creator_name . '发起的' . $stepRun->flow_name . '需要你审批',
                        'form' => $topThreeFormData
                    ]
                ]
            ]
        ];
        $this->sendToOaApi($data);
    }

    /**
     * text 工作通知
     * @param $staffSn
     * @param string $content
     */
    public function sendJobTextMessage($staffSn, $content = '')
    {
        $data = [
            'oa_client_id' => config('oa.client_id'),
            'userid_list' => $staffSn,
            'msg' => [
                'msgtype' => 'text',
                'text' => [
                    'content'=>$content
                ]
            ]
        ];
        $this->sendToOaApi($data);
    }

    protected function sendToOaApi($data)
    {
        $oaApiService = new OaApiService();
        try {
            //result 1发送成功 0发送失败
            $result = $oaApiService->sendDingtalkJobNotificationMessage($data);
            return $result;
        } catch (\Exception $e) {

        }
    }
}