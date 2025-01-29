<?php

return [
    'transition_types' => [
        0=>'New',
        1=>'Issuer',
        2=>'Issuer Group Users',
        3=>'Receiver',
        4=>'Receiver Group Users',
        5=>'General Users By Role',
        6=>'General Users By Group'
    ],
    'sla_reminders' => [
        'reminder_roles' => [
            'response' => [
                'label' => 'Response Delay',
                'notify' => [
                    'issuer' => 'Issuer',
                    'issuer_group' => 'Issuer Group',
                    'executor' => 'Executor',
                    'executor_group' => 'Executor Group',
                ]
            ],
            'resolution' => [
                'label' => 'Resolution Delay',
                'notify' => [
                    'issuer' => 'Issuer',
                    'issuer_group' => 'Issuer Group',
                    'executor' => 'Executor',
                    'executor_group' => 'Executor Group',
                ]
            ],
        ],
        'percentage_arr' => [10=>'> 10%',20=>'> 20%',30=>'> 30%',40=>'> 40%',50=>'> 50%',60=>'> 60%',70=>'> 70%',80=>'> 80%',90=>'> 90%' ],
    ],
    'sla_reminders_validation_lkp' => [
        'response' => [
            'issuer' => true,
            'issuer_group' => true,
            'executor' => true,
            'executor_group' => true,
        ],
        'resolution' => [
            'issuer' => true,
            'issuer_group' => true,
            'executor' => true,
            'executor_group' => true,
        ],
    ],
    'sla_escalations' => [
        'escalation_roles' => [
            'response' => [
                'label' => 'Response SLA Breach',
                'notify' => [
                    'issuer' => 'Issuer',
                    'issuer_group' => 'Issuer Group',
                    'issuer_escalation_1' => 'Issuer Escalation L1',
                    'issuer_escalation_2' => 'Issuer Escalation L2',
                    'issuer_escalation_3' => 'Issuer Escalation L3',
                    'executor' => 'Executor',
                    'executor_group' => 'Executor Group',
                    'executor_escalation_1' => 'Executor Escalation L1',
                    'executor_escalation_2' => 'Executor Escalation L2',
                    'executor_escalation_3' => 'Executor Escalation L3',
                ]
            ],
            'resolution' => [
                'label' => 'Resolution SLA Breach',
                'notify' => [
                    'issuer' => 'Issuer',
                    'issuer_group' => 'Issuer Group',
                    'issuer_escalation_1' => 'Issuer Escalation L1',
                    'issuer_escalation_2' => 'Issuer Escalation L2',
                    'issuer_escalation_3' => 'Issuer Escalation L3',
                    'executor' => 'Executor',
                    'executor_group' => 'Executor Group',
                    'executor_escalation_1' => 'Executor Escalation L1',
                    'executor_escalation_2' => 'Executor Escalation L2',
                    'executor_escalation_3' => 'Executor Escalation L3',
                ]
            ],
        ],
        'percentage_arr' => [1=>'Breach', 30=>'> 30min', 60=>'> 1hr', 90=>'> 1hr 30min', 120=>'> 2hrs', 180=>'> 3hrs', 240=>'> 4hrs', 300=>'> 5hrs', 360=>'> 6hrs', 420=>'> 7hrs', 480=>'> 8hrs' ],
    ],
    'sla_escalation_validation_lkp' => [
        'response' => [
            'issuer' => true,
            'issuer_group' => true,
            'issuer_escalation_1' => true,
            'issuer_escalation_2' => true,
            'issuer_escalation_3' => true,
            'executor' => true,
            'executor_group' => true,
            'executor_escalation_1' => true,
            'executor_escalation_2' => true,
            'executor_escalation_3' => true,
        ],
        'resolution' => [
            'issuer' => true,
            'issuer_group' => true,
            'issuer_escalation_1' => true,
            'issuer_escalation_2' => true,
            'issuer_escalation_3' => true,
            'executor' => true,
            'executor_group' => true,
            'executor_escalation_1' => true,
            'executor_escalation_2' => true,
            'executor_escalation_3' => true,
        ],
    ],
];