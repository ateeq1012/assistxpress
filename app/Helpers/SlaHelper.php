<?php

namespace App\Helpers;

class SlaHelper
{
    public static function getSlaInfo($service_request_status, $service_request, $sla_rule)
    {

        $sla_rule_name = null;
        $response_time_sla = null;
        $response_time_sla_sec = null;
        $response_time_spent = null;
        $response_time_spent_sec = null;
        $response_sla_percentage = null;
        $response_sla_status = null;
        $resolution_time_sla = null;
        $resolution_time_sla_sec = null;
        $resolution_time_spent = null;
        $resolution_time_spent_sec = null;
        $resolution_sla_percentage = null;
        $resolution_sla_status = null;

        if( isset($service_request->sla_rule_id) )
        {
            $sla_rule_name = $sla_rule['name'];
            $sla_rule_settings = json_decode($sla_rule['settings'], true);

            $response_time = $sla_rule_settings['response_time'];
            $resolution_time = $sla_rule_settings['resolution_time'];

            $response_time_sla_sec = GeneralHelper::hh_mm_to_sec($response_time);
            $response_time_spent_sec = $service_request->tto ?? 0;
            $resolution_time_sla_sec = GeneralHelper::hh_mm_to_sec($resolution_time);
            $resolution_time_spent_sec = $service_request->ttr ?? 0;

            $response_time_sla = $response_time;
            $response_time_spent = isset($service_request->tto) ? GeneralHelper::sec_to_hh_mm($service_request->tto) : '';

            $response_sla_percentage = ($response_time_sla_sec > 0) ? round(($service_request->tto / $response_time_sla_sec) * 100 , 2) : 0;
            
            $response_sla_status = null;
            if($response_sla_percentage > 100) {
                $response_sla_status = 'SLA Breach';
            } else if(isset($service_request->response_time) ) {
                $response_sla_status = 'SLA Met';
            } else if(!isset($service_request->response_time) ) {
                $response_sla_status = 'Ongoing';
            }

            $resolution_time_sla = $resolution_time;
            $resolution_time_spent = isset($service_request->ttr) ? GeneralHelper::sec_to_hh_mm($service_request->ttr) : '';
            $resolution_sla_percentage = ($resolution_time_sla_sec > 0) ? round(($service_request->ttr / $resolution_time_sla_sec) * 100 , 2) : 0;
            
            $resolution_sla_status = null;
            if($resolution_sla_percentage > 100) {
                $resolution_sla_status = 'SLA Breach';
            } else if(in_array($service_request_status['type'], [3,4]) ) {
                $resolution_sla_status = 'SLA Met';
            } else if(!isset($service_request->response_time) ) {
                $resolution_sla_status = 'Ongoing';
            }
        }

        return [
            'sla_rule_name' => $sla_rule_name,
            'response_time_sla' => $response_time_sla,
            'response_time_sla_sec' => $response_time_sla_sec,
            'response_time_spent' => $response_time_spent,
            'response_time_spent_sec' => $response_time_spent_sec,
            'response_sla_percentage' => $response_sla_percentage,
            'response_sla_status' => $response_sla_status,
            'resolution_time_sla' => $resolution_time_sla,
            'resolution_time_sla_sec' => $resolution_time_sla_sec,
            'resolution_time_spent' => $resolution_time_spent,
            'resolution_time_spent_sec' => $resolution_time_spent_sec,
            'resolution_sla_percentage' => $resolution_sla_percentage,
            'resolution_sla_status' => $resolution_sla_status,
        ];
    }
}
