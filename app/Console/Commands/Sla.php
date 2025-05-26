<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use App\Models\Sla as SlaModel;
use App\Models\ServiceRequest;

use App\Helpers\SlaHelper;


class Sla extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:sla';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		echo 'Scheduled task is running...'. PHP_EOL;
		$now_ts = date('Y-m-d H:i:s');
		$sla_rules = SlaModel::orderBy('order', 'asc')->get();
		$this->claim_service_requests($sla_rules);

		foreach ($sla_rules as $key => $sla_rule) {

			// Get Service Requests
			$sla_settings = json_decode($sla_rule->settings);
			$sla_status_ids = $sla_settings->sla_statuses ?? [];
			$updated_at = $sla_rule->last_run_ts;

			$service_requests = ServiceRequest::with('serviceRequestStatusLogs')
				->where('sla_rule_id', $sla_rule->id)
				->where(function ($query) use ($sla_status_ids, $updated_at) {
					if (count($sla_status_ids) > 0) {
						$query->whereIn('status_id', $sla_status_ids)
							->orWhere(function ($query) use ($sla_status_ids, $updated_at) {
								$query->whereNotIn('status_id', $sla_status_ids);
								if ($updated_at) {
									$query->where('updated_at', '>', $updated_at);
								}
							});
					}
				})
				->get();

			$notifications = [];
			foreach ($service_requests as $service_request) {
				$service_request_created_at = date('Y-m-d H:i:s', strtotime($service_request->created_at));

				$total_win = [[$service_request_created_at, $now_ts]];

				$status_wins = [];
				if(count($service_request->serviceRequestStatusLogs) > 0) {
					$prev_status_time = $service_request_created_at;
					$current_log_index = 1;
					foreach ($service_request->serviceRequestStatusLogs as $status_log) {
						$log_created_at = date('Y-m-d H:i:s', strtotime($status_log->created_at));
						if(
							in_array($status_log->old_value, $sla_status_ids) || // SLA statuses defined in the Rule and this is one of these statuses
							count($sla_status_ids) < 1 // No status defined in Rule which means all statuses
						) {
							$status_wins[$status_log->old_value] = [$prev_status_time, $log_created_at];
						}
						$prev_status_time = $log_created_at;
						if($current_log_index === count($service_request->serviceRequestStatusLogs)) {
							if(
								in_array($status_log->new_value, $sla_status_ids) || // SLA statuses defined in the Rule and this is one of these statuses
								count($sla_status_ids) < 1 // No status defined in Rule which means all statuses
							) {
								$status_wins[$status_log->new_value] = [$prev_status_time, $now_ts];
							}
						}
						$current_log_index++;
					}
				} else {
					// status never changed
					if(
						in_array($service_request->status_id, $sla_status_ids) || // SLA statuses defined in the Rule and this is one of these statuses
						count($sla_status_ids) < 1 // No status defined in Rule which means all statuses
					) {
						$status_wins[$service_request->status_id] = [$service_request_created_at, $now_ts];
					}
				}

				$holidays = []; // Not applicable here.
				$service_days = $sla_settings->run_on_days ?? null;
				$service_window_start = $sla_settings->start_time ?? null;
				$service_window_end = $sla_settings->end_time ?? null;
				$last_run_ts = $sla_rule->last_run_ts ?? $sla_rule->created_at;

				$service_wins = $this->gen_working_slots( $holidays, $service_days, $service_window_start, $service_window_end, $last_run_ts, $now_ts );

				$sla_wins = [];
				$sla_wins[] = $total_win;
				if(count($status_wins) > 0) {
					$sla_wins[] = $status_wins;
				}
				if(count($service_wins) > 0) {
					$sla_wins[] = $service_wins;
				}

				$tto_old = $service_request->tto ?? 0;
				$ttr_old = $service_request->ttr ?? 0;
				$resp_sla_wins = $sla_wins;
				if(!isset($service_request->response_time) || strtotime($service_request->response_time) > strtotime($last_run_ts)) {
					if(isset($service_request->response_time)) {
						// Clip time windows after response time
						$resp_sla_wins[] = [[$service_request_created_at, date('Y-m-d H:i:s', strtotime($service_request->response_time))]];
					}
				}


				$tto_new = $tto_old;
				$ttr_new = $ttr_old;

				// Calculate time Spent
				if(count($service_wins) > 0) {
					$tto_new = $this->get_range_overlap_dur($resp_sla_wins);
					$ttr_new = $this->get_range_overlap_dur($sla_wins);
				}

				// Calculate SLA allowed time in seconds
				$tto_sla = null;
				$ttr_sla = null;
				if(isset($sla_settings->response_time)) {
					$rt_exp = explode(':', $sla_settings->response_time);
					$tto_sla = $rt_exp[0] * 60 * 60 + $rt_exp[1] * 60;
				}
				if(isset($sla_settings->resolution_time)) {
					$rt_exp = explode(':', $sla_settings->resolution_time);
					$ttr_sla = $rt_exp[0] * 60 * 60 + $rt_exp[1] * 60;
				}


				if($tto_new != $tto_old || $ttr_new != $ttr_old) {
					DB::table('service_requests')
						->where('id', $service_request->id)
						->update([
							'tto' => $tto_new,
							'ttr' => $ttr_new,
						]);

				/*	// Last Percentages
					$ttopercentage_old = 0;
					$ttrpercentage_old = 0;

					// Calculate last response time percentage
					if (isset($sla_settings->response_time)) {
						$rt_exp = explode(':', $sla_settings->response_time);
						$tto_sla = $rt_exp[0] * 60 * 60 + $rt_exp[1] * 60;
						if ($tto_old > 0 && $tto_sla > 0) {
							$ttopercentage_old = ($tto_old/$tto_sla) * 100;
						}
					}

					// Calculate last resolution time percentage
					if (isset($sla_settings->resolution_time)) {
						$rt_exp = explode(':', $sla_settings->resolution_time);
						$ttr_sla = $rt_exp[0] * 60 * 60 + $rt_exp[1] * 60;
						if ($ttr_old > 0 && $ttr_sla > 0) {
							$ttrpercentage_old = ($ttr_old/$ttr_sla) * 100;
						}
					}

					// New Percentages

					$ttopercentage_new = 0;
					$ttrpercentage_new = 0;

					if($tto_new > 0 && $tto_sla > 0) {
						$ttopercentage_new = ($tto_new/$tto_sla) * 100;
					}
					if($ttr_new > 0 && $ttr_sla > 0) {
						$ttrpercentage_new = ($ttr_new/$ttr_sla) * 100;
					}

					$res = SlaHelper::getSlaNotificationDetails($sla_rule, 50, 55, 55, 60, 92996, 92996, 94996, 93996);
					// $res = SlaHelper::getSlaNotificationDetails($sla_rule, $ttopercentage_old, $ttrpercentage_old, $ttopercentage_new, $ttrpercentage_new, $tto_old, $ttr_old, $tto_new, $ttr_new);
					echo "res: " . __LINE__ . " " . __FILE__ . "<br><pre>"; print_r($res); echo "</pre><br>"; exit();
				*/
				
				}

				// Calculate timespent
				// update service_request
				// Set notifications
			}
		}

		echo 'Scheduled task has completed.';
	}
	/*private function claim_service_requests($sla_rules)
	{
		foreach ($sla_rules as $key => $sla_rule) {
			// get ids of rows to be affected
			// updated rows

			DB::table('service_requests as t')
				->where('created_at', '>', $sla_rule->created_at)
				->whereNull('sla_rule_id')
				->whereRaw($sla_rule->query)
				->update([
					'sla_rule_id' => $sla_rule->id,
					'response_time' => null,
					'tto' => 0,
					'ttr' => 0,
				]);

		// Insert history for the affected rows in service_request table that sla rule was attached to service request
 
		}
	}*/

	private function claim_service_requests($sla_rules)
	{
	    foreach ($sla_rules as $key => $sla_rule) {
	        DB::transaction(function () use ($sla_rule) {
	            $service_request_ids = DB::table('service_requests as t')
	                ->where('created_at', '>', $sla_rule->created_at)
	                ->whereNull('sla_rule_id')
	                ->whereRaw($sla_rule->query)
	                ->pluck('id')
	                ->toArray();

	            if (!empty($service_request_ids)) {
	                $affected_rows = DB::table('service_requests as t')
	                    ->whereIn('id', $service_request_ids)
	                    ->update([
	                        'sla_rule_id' => $sla_rule->id,
	                        'response_time' => null,
	                        'tto' => 0,
	                        'ttr' => 0,
	                    ]);

	                $history_records = array_map(function ($service_request_id) use ($sla_rule) {
	                    return [
	                        'service_request_id' => $service_request_id,
	                        'field_name' => 'sla_rule_id',
	                        'field_type' => 1,
	                        'old_value' => null,
	                        'new_value' => $sla_rule->id,
	                        'created_by' => 1,
	                        'created_at' => date('Y-m-d H:i:s'),
	                    ];
	                }, $service_request_ids);

	                DB::table('service_request_audit_logs')->insert($history_records);
	            } else {
	                \Log::info("No service requests claimed for SLA rule", [
	                    'sla_rule_id' => $sla_rule->id,
	                ]);
	            }
	        });

	        $sla_rule->update(['last_run_ts' => now()]);
	    }
	}

	public static function gen_working_slots( $holidays, $service_days, $service_window_start, $service_window_end, $last_run_time, $now_ts )
	{
		if (isset($last_run_time) && isset($now_ts) ) {
			
			$service_days = is_null($service_days) ? ["Monday", "Tuesday", "Wednesday" , "Thursday", "Friday", "Saturday", "Sunday"] : $service_days;

			$service_days_arr = array_flip($service_days);
			$start_date = date("Y-m-d", strtotime($last_run_time));
			$end_date = date("Y-m-d", strtotime($now_ts));
			
			$start_time = (is_null($service_window_start) || trim($service_window_start) == '') ? '00:00' : trim($service_window_start);
			$end_time = (is_null($service_window_end) || trim($service_window_end) == '') ? '23:59' : trim($service_window_end);

			$result = array();
			$num_days = date_diff( date_create( $start_date ),date_create( $end_date ) )->format("%a");
			$moving_date = date('Y-m-d', strtotime($start_date));
			$all_day_service_day = is_null($service_days);
			
			$end_in_next_day = (strtotime($start_time) >= strtotime($end_time)) ? true : false;

			for ($i=0; $i <= $num_days; $i++) {
				$next_date = date('Y-m-d', strtotime($moving_date . ' +1 day'));
				$day_num_of_week = date('l', strtotime($moving_date));
				$is_service_day = ($all_day_service_day) ? TRUE : isset($service_days_arr[$day_num_of_week]);

				if (!isset($holidays[$moving_date]) && $is_service_day ) {

					$interval_end_date = $moving_date;
					$interval_end_time = $end_time;
					if ($end_in_next_day) {
						$interval_end_date = $next_date;
					} else if ($end_time == '23:59') {
						$interval_end_date = $next_date;
						$interval_end_time = '00:00';
					}
					
					$result[] = array( date("Y-m-d H:i:s", strtotime($moving_date.' '.$start_time)), date("Y-m-d H:i:s", strtotime($interval_end_date.' '.$interval_end_time)));
				}

				$moving_date = $next_date;
			}
			return $result;
		}
		return NULL;
	}

	public static function get_range_overlap_dur( $ranges )
	{
		// Placeholder array to contain the periods when everyone is available.
		$periods = [];

		while (true) {
			// Select every entity's earliest date, then choose the latest of these
			// dates.
			$start = array_reduce($ranges, function($carry, $ranges) {
				$start = array_reduce($ranges, function($carry, $range) {
					// This entity's earliest start date.
					return !$carry ? $range[0] : min($range[0], $carry);
				});
				// The latest of all the start dates.
				return !$carry ? $start : max($start, $carry);
			});
			// Select each entity's range which contains this date.
			$matching_ranges = array_filter(array_map(function($ranges) use($start) {
				return current(array_filter($ranges, function($range) use($start) {
					// The range starts before and ends after the start date.
					return $range[0] <= $start && $range[1] >= $start;
				}));
			}, $ranges));

			// Patch for if the ranges are not get
			$should_break = false;
			while( count($matching_ranges) < count($ranges) ) {
				array_walk($ranges, function(&$ranges) use ($start) {
					$ranges = array_filter($ranges, function($range) use($start) {
						return !($range[1] <= $start);
					});
				});

				foreach ($ranges as $series) {
					if (count($series) == 0 ) {
						$should_break = true;
						break;
					}
				}

				if ($should_break ) {
					break;
				}

				$start = array_reduce($ranges, function($carry, $ranges) {
					$start = array_reduce($ranges, function($carry, $range) {
						// This entity's earliest start date.
						return !$carry ? $range[0] : min($range[0], $carry);
					});
					// The latest of all the start dates.
					return !$carry ? $start : max($start, $carry);
				});

				$matching_ranges = array_filter(array_map(function($ranges) use($start) {
					return current(array_filter($ranges, function($range) use($start) {
						// The range starts before and ends after the start date.
						return $range[0] <= $start && $range[1] >= $start;
					}));
				}, $ranges));
			}

			if ($should_break ) {
				break;
			}

			// Find the earliest of the ranges' end dates, and this completes our
			// first period that everyone can attend.
			$end = array_reduce($matching_ranges, function($carry, $range) {
				return !$carry ? $range[1] : min($range[1], $carry);
			});

			// Add it to our list of periods.
			$periods[] = [$start, $end];

			// Remove any availability periods which finish before the end of this
			// new period.
			array_walk($ranges, function(&$ranges) use ($end) {
				$ranges = array_filter($ranges, function($range) use($end) {
					return $range[1] > $end;
				});
			});
		}

		$duration = 0;
		if (count($periods) > 0 ) {
			foreach ($periods as $key => $period) {
				$duration += strtotime($period[1]) - strtotime($period[0]);
			}
		}
		return $duration;
	}
}