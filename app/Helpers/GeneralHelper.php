<?php

namespace App\Helpers;

class GeneralHelper
{
    /**
     * Clean text by removing special characters and whitespace.
     *
     * @param string $text
     * @return string
     */
    public static function cleanText($text, $opts = null)
    {
        // Trim leading and trailing whitespace
        if($opts == null || (is_array($opts) && in_array('trim', $opts))) {
            $cleaned = trim($text);
        }
        
        // Remove non-printable characters
        if($opts == null || (is_array($opts) && in_array('non-printable', $opts))) {
            $cleaned = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $cleaned);
        }
        
        // Remove control characters
        if($opts == null || (is_array($opts) && in_array('control-chars', $opts))) {
            $cleaned = preg_replace('/[\p{C}]/u', '', $cleaned);
        }
        
        // Remove diacritical marks
        if($opts == null || (is_array($opts) && in_array('diacritical-marks', $opts))) {
            $cleaned = preg_replace('/\p{M}/u', '', $cleaned);
        }
        
        // Decode HTML entities
        if($opts == null || (is_array($opts) && in_array('decode-html-entities', $opts))) {
            $cleaned = htmlspecialchars_decode($cleaned, ENT_QUOTES);
        }
        // Remove HTML
        if($opts == null || (is_array($opts) && in_array('html', $opts))) {
            $cleaned = strip_tags($cleaned);
        }
        
        return $cleaned;
    }

    public static function statusTypeName($status_type_id = null)
    {
        $status_types = config('statusTypes');
        if($status_type_id != null) {
            if(isset($status_types[$status_type_id])) {
                return $status_types[$status_type_id];
            } else {
                return null;
            }
        } else {
            return $status_types;
        }
    }
    
    public static function invert_color($bg_color) {

        // Remove any non-hexadecimal characters
        $bg_color = preg_replace('/[^A-Fa-f0-9]/', '', $bg_color);
        
        // Ensure the color is 6 characters long
        if (strlen($bg_color) !== 6) {
            return '#000000'; // Default to black for invalid input
        }

        // Convert hex to RGB
        $r = hexdec(substr($bg_color, 0, 2));
        $g = hexdec(substr($bg_color, 2, 2));
        $b = hexdec(substr($bg_color, 4, 2));

        // Calculate luminance
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Define a threshold for contrast (this can be adjusted)
        $threshold = 150;

        // Return black or white based on a softer luminance threshold
        return ($luminance > $threshold) ? '#222222' : '#FFFFFF';
    }


    public static function process_workflow_transitions($workflow) {
        $transitions = [
            'new'=> [],
            'creator'=> [],
            'creators_group_members'=> [],
            'executor'=> [],
            'executors_group_members'=> [],
            'general_by_role'=> [],
            'general_by_group'=> []
        ];
        $from_to = [
            'new'=> [],
            'creator'=> [],
            'creators_group_members'=> [],
            'executor'=> [],
            'executors_group_members'=> [],
            'general_by_role'=> [],
            'general_by_group'=> []
        ];

        $creator_member_roles = [];
        $executors_member_roles = [];
        $general_users_by_role = [];
        $general_users_by_group = [];

        if(isset($workflow->transitions)) {
            foreach ($workflow->transitions as $transition) {
                $from = $transition->status_from_id;
                $to = $transition->status_to_id;
                if($transition->transition_type ==  0)
                {
                    $from = $from==null ? 0 : $from;
                    $transitions['new'][$from."_".$to] = true;
                    $from_to['new'][$from][] = $to;
                }
                if($transition->transition_type ==  1)
                {
                    $transitions['creator'][$from."_".$to] = true;
                    $from_to['creator'][$from][] = $to;
                }
                if($transition->transition_type ==  2)
                {
                    if(isset($transition->role_id))
                    {
                        $creator_member_roles[$transition->role_id] = $transition->role_id;
                    }
                    $transitions['creators_group_members'][$from."_".$to] = true;
                    $from_to['creators_group_members'][$from][] = $to;
                }
                if($transition->transition_type ==  3)
                {
                    $transitions['executor'][$from."_".$to] = true;
                    $from_to['executor'][$from][] = $to;
                }
                if($transition->transition_type ==  4)
                {
                    if(isset($transition->role_id))
                    {
                        $executors_member_roles[$transition->role_id] = $transition->role_id;
                    }
                    $transitions['executors_group_members'][$from."_".$to] = true;
                    $from_to['executors_group_members'][$from][] = $to;
                }
                if($transition->transition_type ==  5)
                {
                    if(isset($transition->role_id))
                    {
                        $general_users_by_role[$transition->role_id] = $transition->role_id;
                    }
                    $transitions['general_by_role'][$from."_".$to] = true;
                    $from_to['general_by_role'][$from][] = $to;
                }
                if($transition->transition_type ==  6)
                {
                    if(isset($transition->group_id))
                    {
                        $general_users_by_group[$transition->group_id] = $transition->group_id;
                    }
                    $transitions['general_by_group'][$from."_".$to] = true;
                    $from_to['general_by_group'][$from][] = $to;
                }
            }
        }
        return [
            // 'transitions' => $transitions,
            'from_to' => $from_to,
            // 'creator_member_roles' => $creator_member_roles,
            // 'executors_member_roles' => $executors_member_roles,
            // 'general_users_by_role' => $general_users_by_role,
            // 'general_users_by_group' => $general_users_by_group,
        ];
    }
}
