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
            
            $response_sla_status = 'Ongoing';
            if($response_sla_percentage > 100) {
                $response_sla_status = 'SLA Breach';
            } else if(isset($service_request->response_time) ) {
                $response_sla_status = 'SLA Met';
            }

            $resolution_time_sla = $resolution_time;
            $resolution_time_spent = isset($service_request->ttr) ? GeneralHelper::sec_to_hh_mm($service_request->ttr) : '';
            $resolution_sla_percentage = ($resolution_time_sla_sec > 0) ? round(($service_request->ttr / $resolution_time_sla_sec) * 100 , 2) : 0;
            
            $resolution_sla_status = 'Ongoing';
            if($resolution_sla_percentage > 100) {
                $resolution_sla_status = 'SLA Breach';
            } else if(in_array($service_request_status['type'], [3,4]) ) {
                $resolution_sla_status = 'SLA Met';
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

    public static function getSlaNotificationDetails($sla_rule, $ttopercentage_old, $ttrpercentage_old, $ttopercentage_new, $ttrpercentage_new, $tto_old, $ttr_old, $tto_new, $ttr_new)
    {
        $sla_settings = json_decode($sla_rule->settings, true);
        $notification_settings = [
            'reminders' => [],
            'escalations' => [],
        ];

        // Handle reminders
        if ($ttrpercentage_new < 100) {
            self::processReminders($sla_settings, $ttopercentage_old, $ttrpercentage_new, $notification_settings);
        }

        // Handle escalations
        if ($ttrpercentage_new >= 40) {
            $response_time = self::timeToSeconds($sla_settings['response_time']);
            $resolution_time = self::timeToSeconds($sla_settings['resolution_time']);

            $tto_old_min_since_breach = ($tto_old - $response_time) / 60;
            $tto_new_min_since_breach = ($tto_new - $response_time) / 60;
            $ttr_old_min_since_breach = ($ttr_old - $resolution_time) / 60;
            $ttr_new_min_since_breach = ($ttr_new - $resolution_time) / 60;

            $unique_ids = [];
            $unique_emails = [];

            self::processEscalations(
                $sla_settings,
                $tto_old_min_since_breach,
                $tto_new_min_since_breach,
                $ttr_old_min_since_breach,
                $ttr_new_min_since_breach,
                $notification_settings,
                $unique_ids,
                $unique_emails
            );

            // Ensure unique values and assign to escalations
            $unique_ids = array_values(array_unique($unique_ids));
            $unique_emails = array_values(array_unique($unique_emails));

            if (!empty($unique_ids) || !empty($unique_emails)) {
                $escalation_users = ['ids' => $unique_ids, 'emails' => $unique_emails];
                foreach (['response', 'resolution'] as $type) {
                    if (isset($notification_settings['escalations'][$type])) {
                        // Get the highest threshold
                        $max_threshold = max(array_keys($notification_settings['escalations'][$type]));
                        $notification_settings['escalations'][$type] = [
                            $max_threshold => array_merge(
                                $notification_settings['escalations'][$type][$max_threshold],
                                ['escalation_users' => $escalation_users]
                            )
                        ];
                    }
                }
            }
        }

        return $notification_settings;
    }

    private static function processReminders($sla_settings, $ttopercentage_old, $ttrpercentage_new, &$notification_settings)
    {
        $types = ['response', 'resolution'];
        $roles = ['issuer', 'executor', 'issuer_group', 'executor_group'];

        foreach ($types as $type) {
            if (!isset($sla_settings['reminders'][$type])) {
                continue;
            }
            $threshold_roles = [];
            foreach ($roles as $role) {
                if (isset($sla_settings['reminders'][$type][$role])) {
                    foreach ($sla_settings['reminders'][$type][$role] as $value) {
                        if ($ttopercentage_old <= $value && $ttrpercentage_new > $value) {
                            $threshold_roles[$value][] = $role;
                        }
                    }
                }
            }
            if (!empty($threshold_roles)) {
                // Keep only the highest threshold
                $max_threshold = max(array_keys($threshold_roles));
                $notification_settings['reminders'][$type][$max_threshold] = $threshold_roles[$max_threshold];
            }
        }
    }

    private static function processEscalations($sla_settings, $tto_old_min, $tto_new_min, $ttr_old_min, $ttr_new_min, &$notification_settings, &$unique_ids, &$unique_emails)
    {
        $types = ['response' => [$tto_old_min, $tto_new_min], 'resolution' => [$ttr_old_min, $ttr_new_min]];
        $roles = ['issuer', 'executor', 'issuer_group', 'executor_group'];
        $escalation_levels = ['issuer_escalation_1', 'issuer_escalation_2', 'issuer_escalation_3', 'executor_escalation_1', 'executor_escalation_2', 'executor_escalation_3'];

        foreach ($types as $type => [$old_min, $new_min]) {
            if (!isset($sla_settings['escalations'][$type])) {
                continue;
            }
            $threshold_data = [];
            // Handle standard escalations
            foreach ($roles as $role) {
                if (isset($sla_settings['escalations'][$type][$role])) {
                    foreach ($sla_settings['escalations'][$type][$role] as $value) {
                        if ($old_min <= $value && $new_min > $value) {
                            $threshold = round($new_min, 0);
                            $threshold_data[$threshold][$role] = 1; // Use 1 instead of true
                        }
                    }
                }
            }
            // Handle escalation users
            foreach ($escalation_levels as $level) {
                if (isset($sla_settings['escalations'][$type][$level])) {
                    foreach ($sla_settings['escalations'][$type][$level] as $value) {
                        if ($old_min <= $value && $new_min > $value) {
                            $threshold = round($new_min, 0);
                            $level_num = substr($level, -1); // Extract level number (1, 2, 3)
                            $level_key = 'l' . $level_num;
                            if (isset($sla_settings['escalation_users'][$level_key])) {
                                $escalation_users = self::get_escalation_user($sla_settings['escalation_users'][$level_key], $level);
                                if (!empty($escalation_users)) {
                                    if (isset($escalation_users['ids'])) {
                                        $unique_ids = array_merge($unique_ids, $escalation_users['ids']);
                                    }
                                    if (isset($escalation_users['emails'])) {
                                        $unique_emails = array_merge($unique_emails, $escalation_users['emails']);
                                    }
                                    // Ensure the threshold entry exists
                                    $threshold_data[$threshold] = $threshold_data[$threshold] ?? [];
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($threshold_data)) {
                // Keep only the highest threshold
                $max_threshold = max(array_keys($threshold_data));
                $notification_settings['escalations'][$type] = [
                    $max_threshold => $threshold_data[$max_threshold]
                ];
            }
        }
    }

    public static function get_escalation_user($escalation_users, $level)
    {
        $resp = [];
        if(isset($escalation_users[$level])) {
            $resp['ids'] = $escalation_users[$level];
        }
        if(isset($escalation_users[$level.'_emails'])) {
            $emails_str = $escalation_users[$level.'_emails'];
            if(trim($emails_str) != '') {
                
                $lines = explode("\n", $emails_str);

                $emails = [];
                foreach ($lines as $line) {
                    $emails = array_merge($emails, explode(',', $line));
                }

                $resp['emails'] = array_filter(array_map('trim', $emails));
                // $resp['emails'] = array_unique($resp['emails']);
            }
        }
        return $resp;
    }

    public static function timeToSeconds($time)
    {
        list($hours, $minutes) = explode(':', $time);
        return ($hours * 3600) + ($minutes * 60);
    }
}
