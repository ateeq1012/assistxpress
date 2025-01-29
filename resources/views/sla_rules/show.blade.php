<?php
    use App\Helpers\GeneralHelper;
?>
@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<link href="{{ asset('js/plugins/queryBuilder/dist/css/query-builder.default.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/queryBuilder/dist/js/query-builder.standalone.js') }}"></script>

<link href="{{ asset('css/plugins/chosen/chosen.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/chosen/chosen.jquery.js') }}"></script>

<style type="text/css">
    .rule-header { display: inline; }
    .error-container { display: inline; }
    .rule-filter-container { display: inline; }
    .rule-operator-container { display: inline; }
    .rule-value-container { display: inline; }
    .rules-group-container { width: 100%; }
    .query-builder .btn-group.pull-right.group-actions {float: right; }
    .rule-value-container { width: 40%; }
    #def_user {color: green; }
    .group-conditions .btn-primary {color: #17a084; background: #faf2dc; border-color: #18a689; }
    .blk_sel {margin-top: 5px; }
    .errorMessage {color: #cc5965; display: inline-block; margin-left: 5px; background-color: #f8d7da; padding: 5px; border-radius: 4px;}
    .invalid-feedback {display:block; }
    textarea {resize: both; width: 100% !important; }
    .rule-value-container .form-control{min-width: 200px; }
    .container {min-width: 1400px; }
    tr label {margin-bottom: 0px; }
    .panel-heading {padding: 6px 15px; }
    .panel-body {padding: 5px 5px; }
    .disabled-chosen {background-color: #f2f2f2; color: #999999; pointer-events: none;}
    .chosen-disabled {opacity: 1 !important;}
    .form-control:disabled, .form-control[readonly] {background-color: inherit;}
    .text-box{/*transform: scale(1.5);*/ opacity: 0.2}
    .table th {padding: 2px 5px !important; }
    .table td {padding: 2px 3px !important; }
    .chosen-container-multi .chosen-choices li.search-choice .search-choice-close {display: none; }
    .chosen-container-single .chosen-single div b {display: none; }
}
</style>

<div class="wrapper wrapper-content animated fadeInRight" style="overflow: auto;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-title" style="border-bottom: 1px solid #cccccc;">
                        <h5>View: {{ $sla_rule->name }}</h5>
                        <div class="ibox-tools">
                            <a href="{{ route('sla_rules.index') }}" class="btn btn-success btn-xs" style="color:white !important;">Manage SLA Rules</a>
                        </div>
                    </div>
                    <div class="ibox-content pt-2">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong style="color:#555 !important;">SLA Info:</strong></div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered mb-0">
                                    <tr>
                                        <th>Rule Name:</th>
                                        <td colspan="7">{{ $sla_rule->name }}</td>
                                    </tr>
                                    @if( isset($sla_rule->description) && $sla_rule->description != '')
                                        <tr>
                                            <th>Description:</th>
                                            <td colspan="7">{{ $sla_rule->description }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Created By:</th>
                                        <td>{{$sla_rule->creator->name}}</td>
                                        <th>Created at:</th>
                                        <td>{{$sla_rule->created_at}}</td>
                                        <th>Last Updated By:</th>
                                        <td>{{$sla_rule->updater->name}}</td>
                                        <th>Last Updated at:</th>
                                        <td>{{$sla_rule->updated_at}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="panel panel-primary">
                            <div class="panel-heading"><strong>SLA Settings:</strong></div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered mb-0">
                                    <tr>
                                        <th colspan="1" style="width:150px !important;">Response SLA</th>
                                        <td colspan="3" style="width:150px !important;"><?php echo (isset($sla_settings['response_time'])) ? '<span class="badge badge-success">' . $sla_settings['response_time'] . '</span>' : '<span class="badge">Does not Apply</span>';?></td>
                                        <th colspan="1" >Resolution SLA</th>
                                        <td colspan="3" ><?php echo (isset($sla_settings['resolution_time']))  ? '<span class="badge badge-success">' . $sla_settings['resolution_time']  . '</span>' : '<span class="badge">Does not Apply</span>';?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="1">Service Days</th>
                                        <td colspan="3">
                                            <?php
                                                if (isset($sla_settings['run_on_days']) && count($sla_settings['run_on_days']) < 7) {
                                                    echo '<span class="badge badge-primary">' . implode('</span> <span class="badge badge-primary">', $sla_settings['run_on_days']) . '</span>';
                                                } else {
                                                    echo '<span class="badge badge-info">All Week Days</span>';
                                                }
                                            ?>  
                                        </td>
                                        <th >Service Day Start</th>
                                        <td><?php echo (isset($sla_settings['start_time'])) ? '<span class="badge badge-success">' . $sla_settings['start_time'] . '</span>' : '<span class="badge">24 Hrs</span>';?></td>
                                        <th>Service Day End</th>
                                        <td><?php echo (isset($sla_settings['end_time']))   ? '<span class="badge badge-success">' . $sla_settings['end_time']   . '</span>' : '<span class="badge">24 Hrs</span>';?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="1">SLA Statuses</th>
                                        <td colspan="7">
                                            <?php
                                                if (count($status_lkp) > 0) {
                                                    foreach ($status_lkp as $stid => $stv) {
                                                        echo '<span class="badge badge-primary" style="background-color:'.$stv['color'].'; color:'. GeneralHelper::invert_color($stv['color']) .'" >'.$stv['name'].'</span> ';
                                                    }
                                                } else {
                                                    echo '<span class="badge badge-info">All Statuses</span>';
                                                }
                                            ?>  
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="panel panel-warning">
                            <div class="panel-heading"><strong>Applied Conditions:</strong></div>
                            <div class="panel-body">
                                <div id="rule_builder"></div>
                                <input type="hidden" name="qb_rules" id="qb_rules">
                            </div>
                        </div>
                        <div class="panel panel-danger">
                            <div class="panel-heading"><strong>Escalation Settings:</strong></div>
                            <div class="panel-body">
                                <table id="escalation-table" class="table table-striped table-bordered table-esc mb-0">
                                    <thead>
                                        <tr>
                                            <th>Escalation</th>
                                            <th>Notify</th>
                                            @foreach ($percentage_arr as $percentage => $label)
                                                <th>{{$label}}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($escalations_setup as $esc_key => $escalations_setup)
                                            @php
                                                $outerLoop = $loop;
                                            @endphp
                                            <tr>
                                                <td rowspan="{{ count($escalations_setup['notify']) }}">{{ $escalations_setup['label'] }}</td>
                                                @foreach ($escalations_setup['notify'] as $notif_key => $notif_label)
                                                    @php
                                                        $middleLoop = $loop;
                                                    @endphp
                                                        @if (!$middleLoop->first)
                                                            <td style="display: none;"></td> <!-- to handle replicate down conflict-->
                                                        @endif
                                                        <td>{{$notif_label}}</td>
                                                        @foreach ($percentage_arr as $percentage => $label)
                                                            <td class="checkbox-cell">
                                                                <div class="btn-group">
                                                                    @if (isset($sla_settings['escalations'][$esc_key][$notif_key][$percentage]))
                                                                        <label class="label label-danger">
                                                                            <input
                                                                                class="text-box"
                                                                                type="checkbox"
                                                                                    checked="checked"
                                                                                tabindex="-1"
                                                                                disabled
                                                                            >
                                                                        </label>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                    @if (!$middleLoop->last)
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @if ($outerLoop->last)
                                                    </tr>
                                                @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        // Replicate settings down the column
        document.querySelectorAll('a[title="Replicate Settings Down"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get the checkbox in the current cell
                const parentCheckbox = this.parentElement.querySelector('input[type="checkbox"]');
                const isChecked = parentCheckbox.checked; // Checkbox state
                const columnIndex = this.parentElement.parentElement.cellIndex; // Get column index

                // Get all rows in the table body
                const rows = this.closest("table").querySelectorAll("tbody tr");

                // Loop through rows starting from the current row
                let startReplication = false;
                rows.forEach(row => {
                    console.log(row);
                    const cell = row.cells[columnIndex];
                    if (cell === this.parentElement.parentElement) {
                        startReplication = true; // Start replicating from the current cell
                    }
                    if (startReplication) {
                        const checkbox = cell.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = isChecked; // Apply the state
                    }
                });
            });
        });

        // Replicate settings right across the row
        document.querySelectorAll('a[title="Replicate Settings Right"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get the checkbox in the current cell
                const parentCheckbox = this.parentElement.querySelector('input[type="checkbox"]');
                const isChecked = parentCheckbox.checked; // Checkbox state

                // Get all cells in the current row
                const cells = this.parentElement.parentElement.parentElement.children;
                let startReplication = false;

                // Loop through cells in the row
                Array.from(cells).forEach(cell => {
                    if (cell === this.parentElement.parentElement) {
                        startReplication = true; // Start replicating from the current cell
                    }
                    if (startReplication) {
                        const checkbox = cell.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = isChecked; // Apply the state
                    }
                });
            });
        });
    });
    const value_sla_setter = function(rule, value) {
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

        if ( input.nodeName == "SELECT" ) {
            if ( value instanceof Array && value.length > 0 ) {
                $(input).val(value);
                $(input).trigger('chosen:updated');

            } else if ( typeof value != 'undefined' && value != null && value != "" ) {
                $(input).val(value);
                $(input).trigger('chosen:updated');
            }
        } else {
            $(input).val(value);
            $(input).trigger('chosen:updated');
        }
    }

    const value_1setter = function (rule, value) {
        var change_rule = rule.$el.find('.rule-operator-container [name*=_rule_]')[0];
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
        
        if('is_one_of'==$(change_rule).val()) {
            $(input).prop('multiple', true);
        } else {
            $(input).prop('multiple', false);
        }

        $(input).val(null);
        $(input).val(value);

        $(input).trigger('chosen:updated');
    }

    const value_2setter = function (rule, value) {
        var change_rule = rule.$el.find('.rule-operator-container [name*=_rule_]')[0];
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

        $(input).val(null);
        $(input).val(value);    
    }

    const value_validation = function (value, rule) {
        if (value instanceof Array) {
            if( value.length > 0 ) {
                for (var j = 0; j < value.length; j++) {
                    if( typeof value[j] == 'undefined' || value[j] == null || value[j].trim() == "") {
                        return ['{0} cannot be empty.', "Value"];
                    }
                }
            } else {
                return ['{0} cannot be empty.', "Value"];
            }

        } else if ( typeof value == 'undefined' || value == null || value == "" ) {
            return ['{0} cannot be empty.', "Value"];
        }
        return true;
    }

    const validate_string = function (value, rule) {
        // validate slogans here
        if (value instanceof Array) {
            if( value.length > 0 ) {
                for (var j = 0; j < value.length; j++) {
                    if( typeof value[j] == 'undefined' || value[j] == null || value[j].trim() == "") {
                        return ['{0} cannot be empty.', "Value"];
                    }
                }
            } else {
                return ['{0} cannot be empty.', "Value"];
            }
        } else if ( typeof value == 'undefined' || value == null || value == "" ) {
            return ['{0} cannot be empty.', "Value"];
        }
        return true;
    }

    function value_field_maker (e, rule) {
        var ruleValueContainer = rule.$el.find('.rule-value-container');
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
        var input_name = $(input).attr("name");
        var attr_lookup = [];

        if(rule.__.filter.id == "t.project_id") attr_lookup = <?php echo json_encode($projects); ?>;
        else if(rule.__.filter.id == "t.task_type_id") attr_lookup = <?php echo json_encode($task_types); ?>;
        else if(rule.__.filter.id == "t.status_id") attr_lookup = <?php echo json_encode($statuses); ?>;
        else if(rule.__.filter.id == "t.priority_id") attr_lookup = <?php echo json_encode($priorities); ?>;

        if( rule.operator.type == "equal" && rule.__.filter.id != "t.subject" ) {
            var sel = $("<select class='chosen-select-qb' name='"+input_name+"'>");
            $.each(attr_lookup, function(k, v) {
                sel.append($("<option>").attr('value',k).text(v));
            });

            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);

            $(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
        } else if( rule.operator.type == "is_one_of" && rule.__.filter.id != "t.subject" ) {
            var sel = $("<select class='chosen-select-qb' name='"+input_name+"' multiple class=\"form-control\">");
            $.each(attr_lookup, function(k, v) {
                sel.append($("<option>").attr('value',k).text(v));
            });

            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);

            $(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
        } else if (rule.__.filter.id == "t.subject") {
            var sel = $("<textarea name='"+input_name+"'class='form-control' rows='1' ></textarea>");
        } else {
            var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);
        }
    }
    $(document).ready(function () {
        $('.select2-field').select2({
            // placeholder: "Select",
            allowClear: true,
            width: "100%",
            language: {
                noResults: function () {
                    return "Not Options Found";
                }
            }
        });

        var rules_basic = <?php echo ( $sla_rule->qb_rules !== null ? $sla_rule->qb_rules : 'null'); ?>;

        $('#rule_builder').on('afterCreateRuleInput.queryBuilder', function(e, rule)
        {
            value_field_maker(e, rule);
        });

        $("#rule_builder").on('afterUpdateRuleOperator.queryBuilder', function(e, rule)
        {
            value_field_maker(e, rule);
        });

        $('#rule_builder').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
                { type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
                { type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
                { type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
                { type: 'is_not_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
            ]),
            lang: {
                operators:
                {
                    equal_to: 'Equal to',
                    is_one_of: 'Is one of',
                    is_contains: 'Contains',
                    is_not_contains: 'Does not contain',
                }
            },
            filters: [
                {
                    id:'t.subject',
                    label:'Subject',
                    type:'string',
                    input:'textarea',
                    placeholder:'Enter one or more WO Subjects seperated by new line',
                    valueSetter: value_2setter, validation: { callback: validate_string, },
                    operators:['equal', 'is_one_of','is_contains','is_not_contains']
                },
                {
                    id:'t.project_id',
                    label:'Project',
                    values:<?php echo json_encode($projects); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.task_type_id',
                    label:'Task Type',
                    values:<?php echo json_encode($task_types); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.status_id',
                    label:'Status',
                    values:<?php echo json_encode($statuses); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.priority_id',
                    label:'Priority',
                    values:<?php echo json_encode($priorities); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
            ],
            rules: rules_basic
        });

        $('.chosen-select').chosen({width: "100%", search_contains: true});

        $('#sla_form').submit(function(e) {
            var errorr_title = "";
            var errorr_text = "";

            if ($('[name="rule_name"]').val() == '') {
                Swal.fire({
                    title: 'Input Error',
                    text: 'SLA Rule Name not given!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }
            if ($('[name="response_time"]').val() + $('[name="resolution_time"]').val() == '') {
                Swal.fire({
                    title: 'No SLA Timers Given',
                    text: 'Atleast one SLA Timers must be provided!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            var query_result = $('#rule_builder').queryBuilder('getRules');

            if (!$.isEmptyObject(query_result)) {
                $("#qb_rules").val(JSON.stringify(query_result));
            } else {
                Swal.fire({
                    title: 'Warning.',
                    text: 'There is an Error in Condition Applied Rules.',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            // console.log(query_result);
            // console.log($('#sla_form').serializeArray());
            // e.preventDefault(e);
            // return false;

            // Remove all QueryBuilder's generated input fields before submit
            // $('div[id^="rule_builder_"]').find('input, select, textarea').each(function() {
            //     $(this).remove();
            // });
        });

        $('.chosen-select').chosen({width: "100%", search_contains: true});
        $("#rule_builder input, #rule_builder select, #rule_builder textarea").prop("disabled", true);
        $('#rule_builder .chosen-select-qb').prop('disabled', true).trigger("chosen:updated");
        $("#rule_builder .group-actions").hide();
        $("#rule_builder .rules-group-header button[data-add-rule], #rule_builder .rule-header button[data-delete]").hide();
        $("#rule_builder").addClass("read-only");
    });
</script>
@endsection