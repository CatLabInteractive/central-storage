<?php

namespace App\Http\Controllers\Api;
use App\Models\ConsumerAsset;
use DB;
use Request;
use Response;

/**
 * Class HealthCheckController
 * @package App\Http\Controllers\Api
 */
class HealthCheckController
{
    /**
     * @return mixed
     */
    public function healthCheck()
    {
        $checks = [];

        $checks['database'] = $this->checkDatabase();
        $checks['getAsset'] = $this->checkGetAsset(ConsumerAsset::first()->ca_key);

        $aOkay = true;
        foreach ($checks as $check) {
            if (!$check) {
                $aOkay = false;
            }
        }

        return $this->toResponse([
            'status ' => $aOkay ? 'online' : 'error',
            'checks' => $checks
        ])->setStatusCode($aOkay ? 200 : 503);
    }

    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function toResponse($data)
    {
        if (Request::input('debug')) {

            if (!isset($data['debug'])) {
                $data['debug'] = [];
            }

            $data['debug'] = array_merge($data['debug'], [
                'queries' => \DB::getQueryLog(),
                'queryCount' => count(\DB::getQueryLog())
            ]);
        }

        return Response::json($data);
    }

    /**
     * @return bool
     */
    private function checkDatabase()
    {
        $db = DB::connection()->getDatabaseName();
        return $db ? true : false;
    }

    /**
     * @param $key
     * @return bool
     */
    private function checkGetAsset($key)
    {
        $consumerAsset = ConsumerAsset::assetKey($key)->first();
        if (!$consumerAsset) {
            return false;
        }

        return true;
    }
}