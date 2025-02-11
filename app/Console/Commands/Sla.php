<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use App\Models\Sla as SlaModel;
use App\Models\Task;

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
		$this->claim_tasks($sla_rules);

		foreach ($sla_rules as $key => $sla_rule) {
			// Get Tasks
			$sla_settings = json_decode($sla_rule->settings);
			$sla_status_ids = $sla_settings->sla_statuses ?? [];
			$updated_at = $sla_rule->last_run_ts;

			$tasks = Task::with('taskStatusLogs')
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
			foreach ($tasks as $task) {
				$task_created_at = date('Y-m-d H:i:s', strtotime($task->created_at));

				$total_win = [[$task_created_at, $now_ts]];

				$status_wins = [];
				if(count($task->taskStatusLogs) > 0) {
					$prev_status_time = $task_created_at;
					$current_log_index = 1;
					foreach ($task->taskStatusLogs as $status_log) {
						$log_created_at = date('Y-m-d H:i:s', strtotime($status_log->created_at));
						if(
							in_array($status_log->old_value, $sla_status_ids) || // SLA statuses defined in the Rule and this is one of these statuses
							count($sla_status_ids) < 1 // No status defined in Rule which means all statuses
						) {
							$status_wins[$status_log->old_value] = [$prev_status_time, $log_created_at];
						}
						$prev_status_time = $log_created_at;
						if($current_log_index === count($task->taskStatusLogs)) {
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
						in_array($task->status_id, $sla_status_ids) || // SLA statuses defined in the Rule and this is one of these statuses
						count($sla_status_ids) < 1 // No status defined in Rule which means all statuses
					) {
						$status_wins[$task->status_id] = [$task_created_at, $now_ts];
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

				$tto = $task->tto ?? 0;
				$ttr = $task->ttr ?? 0;
				$resp_sla_wins = $sla_wins;
				if(!isset($task->response_time) || strtotime($task->response_time) > $last_run_ts) {
					if(isset($task->response_time)) {
						// Clip time windows after response time
						$resp_sla_wins[] = [$task_created_at, date('Y-m-d H:i:s', strtotime($task->response_time))];
					}
				}

				// Calculate time Spent
				$new_tto = $this->get_range_overlap_dur($resp_sla_wins);
				$new_ttr = $this->get_range_overlap_dur($sla_wins);

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

				// Calculate Percentages
				$ttocentage = 0;
				$ttrcentage = 0;

				if($new_tto > 0) {
					$ttocentage = ($new_tto/$tto_sla) * 100;
				}
				if($new_ttr > 0) {
					$ttrcentage = ($new_ttr/$ttr_sla) * 100;
				}

				if($new_tto != $tto || $new_ttr != $ttr) {
					DB::table('tasks')
		                ->where('id', $task->id)
		                ->update([
		                    'tto' => $new_tto,
		                    'ttr' => $new_ttr,
		                ]);
				}

				// Calculate timespent
				// update task
				// Set notifications
			}
		}

		echo 'Scheduled task has completed.';
	}
	private function claim_tasks($sla_rules)
	{
		foreach ($sla_rules as $key => $sla_rule) {
			DB::table('tasks as t')
				->where('created_at', '>', $sla_rule->created_at)
				->whereNull('sla_rule_id')
				->whereRaw($sla_rule->query)
				->update([
					'sla_rule_id' => $sla_rule->id,
					'response_time' => null,
					'tto' => 0,
					'ttr' => 0,
				]);
 
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
