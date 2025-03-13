<table class="table table-striped table-bordered">
	<tr><th style="width:300px;">Rule Name</th><th>{{ $slaInfo['sla_rule_name'] }}</th></tr>
	
	<tr><th>Response SLA</th><td>{{ $slaInfo['response_time_sla'] }}</td></tr>
	<tr><th>Response Time</th><td>{{ $slaInfo['response_time_spent'] }}</td></tr>
	<tr><th>Response SLA Percentage</th><td>{{ $slaInfo['response_sla_percentage'] }} %</td></tr>
	<tr><th>Response SLA Status</th><td>{{ $slaInfo['response_sla_status'] }}</td></tr>

	<tr><th>Resolution SLA</th><td>{{ $slaInfo['resolution_time_sla'] }}</td></tr>
	<tr><th>Resolution Time</th><td>{{ $slaInfo['resolution_time_spent'] }}</td></tr>
	<tr><th>Resolution SLA Percentage</th><td>{{ $slaInfo['resolution_sla_percentage'] }} %</td></tr>
	<tr><th>Resolution SLA Status</th><td>{{ $slaInfo['resolution_sla_status'] }}</td></tr>
	

</table>

<!-- 
	[sla_rule_name] => Test Rule
    [response_time_sla] => 01:00
    [response_time_sla_sec] => 3600
    [response_time_spent] => 01:02
    [response_time_spent_sec] => 3720
    [response_sla_percentage] => 103.33
    [response_sla_status] => SLA Breach
    [resolution_time_sla] => 02:00
    [resolution_time_sla_sec] => 7200
    [resolution_time_spent] => 03:45
    [resolution_time_spent_sec] => 13539
    [resolution_sla_percentage] => 188.04
    [resolution_sla_status] => SLA Breach
-->