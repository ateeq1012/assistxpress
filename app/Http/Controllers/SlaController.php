<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Exception;

use App\Models\Sla;
use App\Models\User;
use App\Models\Project;
use App\Models\TaskType;
use App\Models\Status;
use App\Models\TaskPriority;
use App\Helpers\GeneralHelper;

class SlaController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'order');
        $sortDirection = $request->input('direction', 'asc');

        // Query the sla_rules with optional search
        $query = Sla::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $sla_rules = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('sla_rules.index', [
            'sla_rules' => $sla_rules,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }
    public function create()
    {
        $projects = Project::pluck('name', 'id')->toArray();
        $task_types = TaskType::pluck('name', 'id')->toArray();
        $priorities = TaskPriority::pluck('name', 'id')->toArray();
        $statuses = Status::whereNotIn('type', [3,4])->pluck('name', 'id')->toArray();
        $users_resp = User::select('id', 'name', 'email')->get();

        $sla_reminders_setup = config('lookup')['sla_reminders'];
        $sla_escalations_setup = config('lookup')['sla_escalations'];

        return view('sla_rules.create', compact('projects', 'task_types', 'statuses', 'priorities', 'sla_reminders_setup' , 'sla_escalations_setup' ));
    }
    public function store(Request $request)
    {
        $now_ts = date('Y-m-d H:i:s');
        // echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $request->all() ); echo "</pre><br>"; exit;
        $status_ids = Status::pluck('id')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sla_rules',
            'description' => 'nullable|string',
            'color' => 'required|min:7|max:7',
            'response_time' => [
                'required_without:resolution_time',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d+:[0-5][0-9]$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format HH:MM (e.g., 25:45). Hours can be any number.');
                    }
                },
            ],
            'resolution_time' => [
                'required_without:response_time',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->has('response_time') && !preg_match('/^\d+:[0-5][0-9]$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format HH:MM (e.g., 26:45). Hours can be any number.');
                    }
                },
            ],
            'run_on_days'=> 'nullable|array',
            'sla_statuses'=> [
                'nullable',
                'array',
                'in:'.implode(',', $status_ids),
            ],
            'start_time'=> 'nullable|date_format:H:i',
            'end_time'=> 'nullable|date_format:H:i',
            'qb_rules'=> [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $json = json_decode($value);
                    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                        $fail('Invalid ' . $attribute . ' provided.');
                    }
                },
            ],
            'reminder'=> [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if($value !== null && !is_array($value)) {
                        fail("Invalid escalation data received.");
                        return;
                    }

                    $valid_keys = config('lookup')['sla_reminders_validation_lkp'];
                    
                    foreach ($value as $escalationType => $escalationData) {
                        if (!array_key_exists($escalationType, $valid_keys)) {
                            $fail('Invalid escalation type: ' . $escalationType);
                            return;
                        }
                        foreach ($escalationData as $key => $val) {
                            if (!array_key_exists($key, $valid_keys[$escalationType])) {
                                $fail('Invalid key "' . $key . '" in ' . $escalationType . ' escalation.');
                                return;
                            }
                            if (empty($val)) {
                                $fail('Value for key "' . $key . '" in ' . $escalationType . ' escalation cannot be empty.');
                                return;
                            }
                        }
                    }
                },
            ],
            'escalation'=> [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if($value !== null && !is_array($value)) {
                        fail("Invalid escalation data received.");
                        return;
                    }

                    $valid_keys = config('lookup')['sla_escalation_validation_lkp'];
                    
                    foreach ($value as $escalationType => $escalationData) {
                        if (!array_key_exists($escalationType, $valid_keys)) {
                            $fail('Invalid escalation type: ' . $escalationType);
                            return;
                        }
                        foreach ($escalationData as $key => $val) {
                            if (!array_key_exists($key, $valid_keys[$escalationType])) {
                                $fail('Invalid key "' . $key . '" in ' . $escalationType . ' escalation.');
                                return;
                            }
                            if (empty($val)) {
                                $fail('Value for key "' . $key . '" in ' . $escalationType . ' escalation cannot be empty.');
                                return;
                            }
                        }
                    }
                },
            ],
            'issuer_esc_l1' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l1_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'issuer_esc_l2' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l2_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'issuer_esc_l3' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l3_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l1' => [
                'nullable',
                'array'
            ],
            'executor_esc_l1_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l2' => [
                'nullable',
                'array',
            ],
            'executor_esc_l2_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l3' => [
                'nullable',
                'array'
            ],
            'executor_esc_l3_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
        ], [
            //msgs
        ], [
            'name' => 'Rule Name',
            'description' => 'Description',
            'color' => 'Color',
            'response_time' => 'Response Time',
            'resolution_time' => 'Resolution Time',
            'run_on_days' => 'Service Days',
            'sla_statuses' => 'SLA Statuses',
            'start_time' => 'Service Window Start',
            'end_time' => 'Service Window End',
            'qb_rules' => 'Conditions',
            'reminder' => 'Reminders',
            'escalation' => 'Escalations',
            'issuer_esc_l1' => 'Issuer Escalation L1',
            'issuer_esc_l1_emails' => 'Issuer Escalation L1 non-system Emails',
            'issuer_esc_l2' => 'Issuer Escalation L2',
            'issuer_esc_l2_emails' => 'Issuer Escalation L2 non-system Emails',
            'issuer_esc_l3' => 'Issuer Escalation L3',
            'issuer_esc_l3_emails' => 'Issuer Escalation L3 non-system Emails',
            'executor_esc_l1' => 'Executor Escalation L1',
            'executor_esc_l1_emails' => 'Executor Escalation L1 non-system Emails',
            'executor_esc_l2' => 'Executor Escalation L2',
            'executor_esc_l2_emails' => 'Executor Escalation L2 non-system Emails',
            'executor_esc_l3' => 'Executor Escalation L3',
            'executor_esc_l3_emails' => 'Executor Escalation L3 non-system Emails',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $json = json_decode($validatedData['qb_rules']);

        $qb_query = self::prep_algo_condition($json);

        $sla_rule = new Sla();

        $sla_settings = [
            'response_time' => $validatedData['response_time'] ?? null,
            'resolution_time' => $validatedData['resolution_time'] ?? null,
            'run_on_days' => $validatedData['run_on_days'] ?? null,
            'sla_statuses' => $validatedData['sla_statuses'] ?? null,
            'start_time' => $validatedData['start_time'] ?? null,
            'end_time' => $validatedData['end_time'] ?? null,
            'reminders' => $validatedData['reminder'] ?? null,
            'escalations' => $validatedData['escalation'] ?? null,
            'escalation_users' => [
                'l1' => [
                    'issuer_esc_l1' => $validatedData['issuer_esc_l1'] ?? [],
                    'issuer_esc_l1_emails' => trim($validatedData['issuer_esc_l1_emails']) ?? '',
                    'executor_esc_l1' => $validatedData['executor_esc_l1'] ?? [],
                    'executor_esc_l1_emails' => trim($validatedData['executor_esc_l1_emails']) ?? '',
                ],
                'l2' => [
                    'issuer_esc_l2' => $validatedData['issuer_esc_l2'] ?? [],
                    'issuer_esc_l2_emails' => trim($validatedData['issuer_esc_l2_emails']) ?? '',
                    'executor_esc_l2' => $validatedData['executor_esc_l2'] ?? [],
                    'executor_esc_l2_emails' => trim($validatedData['executor_esc_l2_emails']) ?? '',
                ],
                'l3' => [
                    'issuer_esc_l3' => $validatedData['issuer_esc_l3'] ?? [],
                    'issuer_esc_l3_emails' => trim($validatedData['issuer_esc_l3_emails']) ?? '',
                    'executor_esc_l3' => $validatedData['executor_esc_l3'] ?? [],
                    'executor_esc_l3_emails' => trim($validatedData['executor_esc_l3_emails']) ?? '',
                ],
            ],
        ];

        $sla_rule->name = $validatedData['name'];
        $sla_rule->description = $validatedData['description'];
        $sla_rule->color = $validatedData['color'];
        $sla_rule->order = $request->order ?? Sla::max('order') + 1;
        $sla_rule->settings = json_encode ($sla_settings);
        $sla_rule->qb_rules = $validatedData['qb_rules'];
        $sla_rule->query = $qb_query;
        $sla_rule->created_by = Auth::user()->id;
        $sla_rule->updated_by = Auth::user()->id;
        $sla_rule->created_at = $now_ts;
        $sla_rule->updated_at = $now_ts;

        if($sla_rule->save()) {
            return response()->json(['success' => true, 'message' => 'SLA rule created successfully.'], 201);
        } else {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
    }

    public function edit($id)
    {
        $sla_rule = Sla::findOrFail($id);
        $sla_settings = json_decode($sla_rule->settings, true);
        // echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $sla_settings ); echo "</pre><br>"; exit;
        $projects = Project::pluck('name', 'id')->toArray();
        $task_types = TaskType::pluck('name', 'id')->toArray();
        $priorities = TaskPriority::pluck('name', 'id')->toArray();
        $statuses = Status::whereNotIn('type', [3,4])->pluck('name', 'id')->toArray();

        $escalation_user_ids = [];
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l1']) && isset($sla_settings['escalation_users']['l1']['issuer_esc_l1']) && count($sla_settings['escalation_users']['l1']['issuer_esc_l1']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l1']['issuer_esc_l1']);
        }
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l1']) && isset($sla_settings['escalation_users']['l1']['executor_esc_l1']) && count($sla_settings['escalation_users']['l1']['executor_esc_l1']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l1']['executor_esc_l1']);
        }
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l2']) && isset($sla_settings['escalation_users']['l2']['issuer_esc_l2']) && count($sla_settings['escalation_users']['l2']['issuer_esc_l2']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l2']['issuer_esc_l2']);
        }
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l2']) && isset($sla_settings['escalation_users']['l2']['issuer_esc_l2']) && count($sla_settings['escalation_users']['l2']['issuer_esc_l2']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l2']['issuer_esc_l2']);
        }
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l3']) && isset($sla_settings['escalation_users']['l3']['issuer_esc_l3']) && count($sla_settings['escalation_users']['l3']['issuer_esc_l3']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l3']['issuer_esc_l3']);
        }
        if (isset($sla_settings['escalation_users']) && isset($sla_settings['escalation_users']['l3']) && isset($sla_settings['escalation_users']['l3']['issuer_esc_l3']) && count($sla_settings['escalation_users']['l3']['issuer_esc_l3']) > 0) {
            $escalation_user_ids =  array_merge($escalation_user_ids, $sla_settings['escalation_users']['l3']['issuer_esc_l3']);
        }

        $users = [];
        if(count($escalation_user_ids) > 0) {
            $users_resp = User::select('id', 'name', 'email')->whereIn('id', $escalation_user_ids)->get();
            foreach ($users_resp as $user) {
                $users[$user->id] = $user->name . "(". $user->email .")";
            }
        }

        $sla_reminders_setup = config('lookup')['sla_reminders'];
        $sla_escalations_setup = config('lookup')['sla_escalations'];

        return view('sla_rules.edit', compact('sla_rule', 'sla_settings', 'projects', 'task_types', 'statuses', 'priorities', 'sla_reminders_setup', 'sla_escalations_setup', 'users'));
    }

    public function update(Request $request, $id)
    {
        // echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $request->all() ); echo "</pre><br>"; exit;
        $now_ts = date('Y-m-d H:i:s');
        $sla_rule = Sla::findOrFail($id);

        $status_ids = Status::pluck('id')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sla_rules,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'required|min:7|max:7',
            'response_time' => [
                'required_without:resolution_time',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d+:[0-5][0-9]$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format HH:MM (e.g., 200:45). Hours can be any number.');
                    }
                },
            ],
            'resolution_time' => [
                'required_without:response_time',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->has('response_time') && !preg_match('/^\d+:[0-5][0-9]$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format HH:MM (e.g., 200:45). Hours can be any number.');
                    }
                },
            ],
            'run_on_days'=> 'nullable|array',
            'sla_statuses'=> [
                'nullable',
                'array',
                'in:'.implode(',', $status_ids),
            ],
            'start_time'=> 'nullable|date_format:H:i',
            'end_time'=> 'nullable|date_format:H:i',
            'qb_rules'=> [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $json = json_decode($value);
                    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                        $fail('Invalid ' . $attribute . ' provided.');
                    }
                },
            ],
            'reminder'=> [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if($value !== null && !is_array($value)) {
                        fail("Invalid escalation data received.");
                        return;
                    }

                    $valid_keys = config('lookup')['sla_reminders_validation_lkp'];
                    
                    foreach ($value as $escalationType => $escalationData) {
                        if (!array_key_exists($escalationType, $valid_keys)) {
                            $fail('Invalid escalation type: ' . $escalationType);
                            return;
                        }
                        foreach ($escalationData as $key => $val) {
                            if (!array_key_exists($key, $valid_keys[$escalationType])) {
                                $fail('Invalid key "' . $key . '" in ' . $escalationType . ' escalation.');
                                return;
                            }
                            if (empty($val)) {
                                $fail('Value for key "' . $key . '" in ' . $escalationType . ' escalation cannot be empty.');
                                return;
                            }
                        }
                    }
                },
            ],
            'escalation'=> [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if($value !== null && !is_array($value)) {
                        fail("Invalid escalation data received.");
                        return;
                    }

                    $valid_keys = config('lookup')['sla_escalation_validation_lkp'];
                    
                    foreach ($value as $escalationType => $escalationData) {
                        if (!array_key_exists($escalationType, $valid_keys)) {
                            $fail('Invalid escalation type: ' . $escalationType);
                            return;
                        }
                        foreach ($escalationData as $key => $val) {
                            if (!array_key_exists($key, $valid_keys[$escalationType])) {
                                $fail('Invalid key "' . $key . '" in ' . $escalationType . ' escalation.');
                                return;
                            }
                            if (empty($val)) {
                                $fail('Value for key "' . $key . '" in ' . $escalationType . ' escalation cannot be empty.');
                                return;
                            }
                        }
                    }
                },
            ],
            'issuer_esc_l1' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l1_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'issuer_esc_l2' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l2_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'issuer_esc_l3' => [
                'nullable',
                'array'
            ],
            'issuer_esc_l3_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l1' => [
                'nullable',
                'array'
            ],
            'executor_esc_l1_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l2' => [
                'nullable',
                'array',
            ],
            'executor_esc_l2_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
            'executor_esc_l3' => [
                'nullable',
                'array'
            ],
            'executor_esc_l3_emails' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = explode("\n", $value);

                    $emails = [];
                    foreach ($lines as $line) {
                        $emails = array_merge($emails, explode(',', $line));
                    }

                    $emails = array_filter(array_map('trim', $emails));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute contains an invalid email address: $email.");
                        }
                    }
                },
            ],
        ], [
            //msgs
        ], [
            'name' => 'Rule Name',
            'description' => 'Description',
            'color' => 'Color',
            'response_time' => 'Response Time',
            'resolution_time' => 'Resolution Time',
            'run_on_days' => 'Service Days',
            'sla_statuses' => 'SLA Statuses',
            'start_time' => 'Service Window Start',
            'end_time' => 'Service Window End',
            'qb_rules' => 'Conditions',
            'reminder' => 'Reminders',
            'escalation' => 'Escalations',
            'issuer_esc_l1' => 'Issuer Escalation L1',
            'issuer_esc_l1_emails' => 'Issuer Escalation L1 non-system Emails',
            'issuer_esc_l2' => 'Issuer Escalation L2',
            'issuer_esc_l2_emails' => 'Issuer Escalation L2 non-system Emails',
            'issuer_esc_l3' => 'Issuer Escalation L3',
            'issuer_esc_l3_emails' => 'Issuer Escalation L3 non-system Emails',
            'executor_esc_l1' => 'Executor Escalation L1',
            'executor_esc_l1_emails' => 'Executor Escalation L1 non-system Emails',
            'executor_esc_l2' => 'Executor Escalation L2',
            'executor_esc_l2_emails' => 'Executor Escalation L2 non-system Emails',
            'executor_esc_l3' => 'Executor Escalation L3',
            'executor_esc_l3_emails' => 'Executor Escalation L3 non-system Emails',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $validatedData = $validator->validated();

        $json = json_decode($validatedData['qb_rules']);

        $qb_query = self::prep_algo_condition($json);

        $sla_settings = [
            'response_time' => $validatedData['response_time'] ?? null,
            'resolution_time' => $validatedData['resolution_time'] ?? null,
            'run_on_days' => $validatedData['run_on_days'] ?? null,
            'sla_statuses' => $validatedData['sla_statuses'] ?? null,
            'start_time' => $validatedData['start_time'] ?? null,
            'end_time' => $validatedData['end_time'] ?? null,
            'reminders' => $validatedData['reminder'] ?? null,
            'escalations' => $validatedData['escalation'] ?? null,
            'escalation_users' => [
                'l1' => [
                    'issuer_esc_l1' => $validatedData['issuer_esc_l1'] ?? [],
                    'issuer_esc_l1_emails' => trim($validatedData['issuer_esc_l1_emails']) ?? '',
                    'executor_esc_l1' => $validatedData['executor_esc_l1'] ?? [],
                    'executor_esc_l1_emails' => trim($validatedData['executor_esc_l1_emails']) ?? '',
                ],
                'l2' => [
                    'issuer_esc_l2' => $validatedData['issuer_esc_l2'] ?? [],
                    'issuer_esc_l2_emails' => trim($validatedData['issuer_esc_l2_emails']) ?? '',
                    'executor_esc_l2' => $validatedData['executor_esc_l2'] ?? [],
                    'executor_esc_l2_emails' => trim($validatedData['executor_esc_l2_emails']) ?? '',
                ],
                'l3' => [
                    'issuer_esc_l3' => $validatedData['issuer_esc_l3'] ?? [],
                    'issuer_esc_l3_emails' => trim($validatedData['issuer_esc_l3_emails']) ?? '',
                    'executor_esc_l3' => $validatedData['executor_esc_l3'] ?? [],
                    'executor_esc_l3_emails' => trim($validatedData['executor_esc_l3_emails']) ?? '',
                ],
            ],
        ];

        $sla_rule->name = $validatedData['name'];
        $sla_rule->description = $validatedData['description'];
        $sla_rule->color = $validatedData['color'];
        $sla_rule->order = $request->order ?? Sla::max('order') + 1;
        $sla_rule->settings = json_encode ($sla_settings);
        $sla_rule->qb_rules = $validatedData['qb_rules'];
        $sla_rule->query = $qb_query;
        $sla_rule->updated_by = Auth::user()->id;
        $sla_rule->updated_at = $now_ts;

        if($sla_rule->save()) {
            return response()->json(['success' => true, 'message' => 'SLA rule saved successfully.'], 201);
        } else {
            return back()->withErrors(['msg' => ['Failed to save status.']])->withInput();
        }
    }

    public function show($id)
    {
        $sla_rule = Sla::with('creator', 'updater')->findOrFail($id);
        $sla_settings = json_decode($sla_rule->settings, true);
        $projects = Project::pluck('name', 'id')->toArray();
        $task_types = TaskType::pluck('name', 'id')->toArray();
        $priorities = TaskPriority::pluck('name', 'id')->toArray();
        $statuses = Status::select('id', 'name', 'color')->get()->toArray();
        $status_lkp = [];
        if(isset($sla_settings['sla_statuses']) && count($sla_settings['sla_statuses']) != count($statuses)) {            
            foreach ($statuses as $st) {
                if(in_array($st['id'], $sla_settings['sla_statuses'])) {
                    $st['inv_color'] = GeneralHelper::invert_color($st['color']);
                    $status_lkp[] = $st;
                }
            }
        }

        $statuses = array_column($statuses, 'name', 'id');

        $sla_reminders_setup = config('lookup')['sla_reminders'];
        $sla_escalations_setup = config('lookup')['sla_escalations'];

        return view('sla_rules.show', compact('sla_rule', 'sla_settings', 'projects', 'task_types', 'statuses', 'status_lkp', 'priorities', 'sla_reminders_setup', 'sla_escalations_setup' ));
    }
    
    public function destroy($id) {

        DB::beginTransaction();
        
        try {
            
            $status = Sla::findOrFail($id);

            DB::table('tasks')
                ->where('sla_rule_id', $id)
                ->update([
                    'sla_rule_id' => null,
                    'response_time' => null,
                    'tto' => 0,
                    'ttr' => 0
                ]);

            $status->delete();

            DB::commit();
            return redirect()->route('sla_rules.index')->with('success', 'SLA Rule deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sla_rules.index')->with('error', 'Failed to delete SLA Rule. Please try again.');
        }
    }


    public static function prep_algo_condition(&$condition_obj)
    {
        $condition_string = "";
        if( isset($condition_obj->condition) && isset($condition_obj->rules) ) {
            if( count($condition_obj->rules) > 0 ) {
                $first = true;
                foreach ($condition_obj->rules as $nested_rule) {
                    if ( $condition_obj->condition == "AND") {
                        if($first == true) {
                            $condition_string = $condition_string." ". self::prep_algo_condition($nested_rule) ." ";
                            $first = false;
                        } else {
                            $condition_string = $condition_string." AND ". self::prep_algo_condition($nested_rule) ." ";
                        }
                    } else if ( $condition_obj->condition == "OR") {
                        if($first == true) {
                            $condition_string = $condition_string." ". self::prep_algo_condition($nested_rule) ." ";
                            $first = false;
                        } else {
                            $condition_string = $condition_string." OR ". self::prep_algo_condition($nested_rule) ." ";
                        }
                        //Empty is AND
                    } else {
                        throw new Exception("Invalid Condition Found in Filter Description"); 
                    }
                }
                $condition_string = " ( ". $condition_string ." ) ";
            } else {
                $condition_string = " ( true ) ";
            }
        } else if( isset($condition_obj->id) && isset($condition_obj->operator) ) {
            $condition_string = self::getRuleToQuery($condition_obj);
        } else {
            throw new Exception("Invalid Element Found in Filter Description"); 
        }
        return $condition_string;
    }

    public static function getRuleToQuery($rule)
    {
        // Strings
        $str_fields = ['t.subject' => 'Subject'];
        // integers
        $int_fields = ['t.project_id' => 'Project', 't.task_type_id' => 'Task Type', 't.status_id' => 'Status', 't.priority_id' => 'Priority'];

        $filter_str = "";
        if( isset($rule->id) && isset($rule->operator) ) {
            if ( $rule->operator == 'equal' ) { 
                if(isset($str_fields[$rule->id])) {
                    if( trim($rule->value ) == '' ) {
                        throw new Exception('Empty ' . $str_fields[$rule->id] . " given in Condition");
                    } else {
                        return " lower(".$rule->field. ") = '" . pg_escape_string(strtolower($rule->value)) . "' ";
                    }
                } else if(isset($int_fields[$rule->id])) {
                    if($rule->id == 'wew.wo_id'){
                        if($rule->value != 1)
                        {
                            return " EXISTS(SELECT wo_id FROM wo_execution_windows wew WHERE  wew.wo_id = w.id) " ;
                        } else {
                            return " NOT EXISTS(SELECT wo_id FROM wo_execution_windows wew WHERE  wew.wo_id = w.id) " ;
                        }
                        
                    } else {
                        return " ".$rule->field. " = " . $rule->value . " ";
                    }                   
                }
            } else if ( $rule->operator == 'is_one_of' ) {
                if(isset($str_fields[$rule->id])) {
                    if( trim($rule->value ) == '' ) {
                        throw new Exception('Empty ' . $str_fields[$rule->id] . " given in Condition");
                    } else {
                        $vals = self::break_string_to_array( $rule->value, array("\n"), 'escape_string' );
                        if( count( $vals ) > 1 ) {
                            return " lower(".$rule->field. ") IN ('" . implode( "','", array_map('strtolower', $vals ) ) . "') ";
                        } else if( count( $vals ) == 1 ) {
                            return " lower(".$rule->field. ")  = '" . strtolower($vals[0]) . "' ) ";
                        } else {
                            throw new Exception('Invalid ' . $str_fields[$rule->id] . " given in Condition");
                        }
                    }
                } else if(isset($int_fields[$rule->id])) {
                    if(is_array($rule->value) && count($rule->value) > 0) {
                        if(count($rule->value) > 1) {
                            return " ".$rule->field. " IN (" . implode( ",", $rule->value ) . ") ";
                        } else if(count($rule->value) == 1) {
                            return " ".$rule->field. " = " . $rule->value[0] . " ";
                        } else {
                            throw new Exception('Invalid ' . $int_fields[$rule->id] . " given in Condition");
                        }
                    } else {
                        return " ".$rule->field. " = " . $rule->value . " ";
                        // throw new Exception('Invalid ' . $int_fields[$rule->id] . " given in Condition");
                    }
                }
            } else if ( $rule->operator == 'contains' || $rule->operator == 'is_contains' ) {
                if(isset($str_fields[$rule->id])) {
                    if( trim($rule->value ) == '' ) {
                        throw new Exception('Empty ' . $str_fields[$rule->id] . " given in Condition");
                    } else {
                        $vals = self::break_string_to_array( $rule->value, array("\n"), 'escape_string' );
                        if( count( $vals ) > 1 ) {
                            return " lower(".$rule->field. ") SIMILAR TO '%(" . implode( "|", array_map('strtolower', $vals ) ) . ")%' ";
                        } else if( count( $vals ) == 1 ) {
                            return " lower(".$rule->field. ") LIKE '%" . strtolower($vals[0]) . "%' ";
                        } else {
                            throw new Exception('Invalid ' . $str_fields[$rule->id] . " given in Condition");
                        }
                    }
                }
            } else if ( $rule->operator == 'not_contains' || $rule->operator == 'is_not_contains' ) {
                if(isset($str_fields[$rule->id])) {
                    if( trim($rule->value ) == '' ) {
                        throw new Exception($rule->label . " empty");
                    } else {
                        $vals = self::break_string_to_array( $rule->value, array("\n"), 'escape_string' );
                        if( count( $vals ) > 1 ) {
                            return " lower(".$rule->field. ") NOT SIMILAR TO '%(" . implode( "|", array_map('strtolower', $vals ) ) . ")%' ";
                        } else if( count( $vals ) == 1 ) {
                            return " lower(".$rule->field. ") NOT LIKE '%" . strtolower($vals[0]) . "%' ";
                        } else {
                            throw new Exception('Invalid ' . $str_fields[$rule->id] . " given in Condition");
                        }
                    }
                }
            }

        } else {
            throw new Exception("Invalid Element Found in Filter Description"); 
        }
        return $filter_str;
    }

    public static function break_string_to_array($str_main, $delimiters, $escape_str)
    {
        /*
         * Breaks an array based on multiple delimiters
         * Input can be one delimiter as string or and array or multiple delimiters
         */
        $result = array($str_main);
        foreach ($delimiters as $value) {
            $str_main = str_replace($value, '^_^', $str_main);
        }
        $result = explode('^_^', $str_main);

        foreach ($result as $key => $value) {
            if( trim( $value ) == '' ) {
                unset($result[$key]);
            } else {
                if($escape_str == 'escape_string') {
                    $result[$key] = pg_escape_string($value);
                }
            }
        }
        return $result;
    }
}
