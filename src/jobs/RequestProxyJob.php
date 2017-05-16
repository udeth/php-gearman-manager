<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-05-11
 * Time: 16:38
 */

namespace inhere\gearman\jobs;

use inhere\gearman\tools\CurlHelper;

/**
 * Class RequestProxyJob
 *
 * - 通用的请求转发工作处理器.
 * - 你只需要关注数据验证，设置好正确的 `$baseUrl`(api host) 和 `$path`(api path) (可选的 `$method`)
 * - 正确的数据将会原样的发送给接口地址(`$baseUrl + $path`)
 *
 * @package inhere\gearman\jobs
 */
abstract class RequestProxyJob extends UseLogJob
{
    /**
     * request method
     * @var string
     */
    protected $method = 'GET';

    /**
     * eg: http://api.domain.com
     * @var string
     */
    protected $baseUrl;

    /**
     * eg: /user/after-login
     * @var string
     */
    protected $path;

    /**
     * @param array $payload
     * @return bool
     */
    abstract protected function dataValidate(array &$payload);
    // {
    //      if (!isset($payload['userId']) || $payload['userId'] <= 0) {
    //          return false;
    //      }
    //
    //      return true;
    // }

    /**
     * doRun
     * @param $workload
     * @param \GearmanJob $job
     * @return mixed
     */
    protected function doRun($workload, \GearmanJob $job)
    {
        $this->info("received workload=$workload");

        $payload = json_decode($workload, true);

        if (!$this->dataValidate($payload)) {
            $this->err("data validate failed, workload=$workload");
            return false;
        }

        $method = strtolower($this->method);
        $baseUrl = $this->baseUrl;
        $path = $this->path;

        $this->info("request method=$method host=$baseUrl path=$path data=$workload");

        $ret = CurlHelper::make()->setBaseUrl($baseUrl)->$method($path, $payload);
        $ary = json_decode($ret, true);

        if ($ary && (int)$ary['status'] === 0) {
            $this->info("Successful for the job, remote return=$ret");

            return true;
        }

        $this->err("Failed for the job, remote return=$ret workload=$workload send=", [
            'method' => $method,
            'api' => $baseUrl . $path,
            'data' => $payload,
        ]);

        return false;
    }
}