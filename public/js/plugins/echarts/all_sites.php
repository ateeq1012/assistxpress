<?php
	$baseUrl = Yii::app()->baseUrl;
	$cs = Yii::app()->getClientScript();
	/*Chosen*/
	$cs->registerCssFile($baseUrl. "/themes/inspinia/css/plugins/chosen/bootstrap-chosen.css");
	$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/plugins/chosen/chosen.jquery.js", CClientScript::POS_BEGIN);
	/*Leaflet*/
	$cs->registerCssFile($baseUrl. "/js/leaflet/leaflet.css");
	$cs->registerScriptFile($baseUrl. "/js/leaflet/leaflet.js", CClientScript::POS_BEGIN);
	$cs->registerScriptFile($baseUrl. "/js//leaflet/leaflet-src.js", CClientScript::POS_BEGIN);
	
	$cs->registerScriptFile($baseUrl. "/js/leaflet/leaflet-heat.js", CClientScript::POS_BEGIN);


$cs->registerCssFile($baseUrl. "/themes/inspinia/js/dd_page/gridstack.css");
$cs->registerCssFile($baseUrl. "/themes/inspinia/js/dd_page/anicollection.css");
$cs->registerCssFile($baseUrl. "/themes/inspinia/css/plugins/chosen/bootstrap-chosen.css");
$cs->registerCssFile($baseUrl . '/themes/inspinia/js/plugins/select2_new/select2.min.css');
$cs->registerCssFile($baseUrl . '/themes/inspinia/css/plugins/iCheck/custom.css');
$cs->registerCssFile($baseUrl . '/themes/inspinia/css/plugins/colorpicker/bootstrap-colorpicker.min.css');

$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/plugins/chosen/chosen.jquery.js", CClientScript::POS_BEGIN);
$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/plugins/select2_new/select2.min.js", CClientScript::POS_BEGIN);
$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/dd_page/highcharts.js", CClientScript::POS_BEGIN);
$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/plugins/iCheck/icheck.min.js", CClientScript::POS_BEGIN);
$cs->registerScriptFile($baseUrl. "/themes/inspinia/js/plugins/colorpicker/bootstrap-colorpicker.min.js", CClientScript::POS_BEGIN);
?>

<script type="text/javascript" src="<?php echo $baseUrl;?>/themes/inspinia/js/dd_page/jquery-ui.js"></script>   
<script type="text/javascript" src="<?php echo $baseUrl;?>/themes/inspinia/js/dd_page/shim.min.js"></script>    
<script type="text/javascript" src="<?php echo $baseUrl;?>/themes/inspinia/js/dd_page/gridstack.js"></script>   
<script type="text/javascript" src="<?php echo $baseUrl;?>/themes/inspinia/js/dd_page/gridstack.jQueryUI.min.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl;?>/themes/inspinia/js/dd_page/anijs.js"></script>

<script src="<?php echo Yii::app()->request->baseUrl; ?>/js/echarts/echarts.min.js"></script>

<link href="<?php echo Yii::app()->request->baseUrl; ?>/themes/inspinia/js/plugins/selectize/dist/css/selectize.bootstrap3.css" rel="stylesheet">
<link href="<?php echo Yii::app()->request->baseUrl; ?>/themes/inspinia/js/plugins/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<link href="<?php echo Yii::app()->request->baseUrl; ?>/themes/inspinia/js/plugins/QueryBuilder/dist/css/query-builder.default.min.css" rel="stylesheet">


<style
	>
	.big-map { height: calc(100vh - 100px); width:100%; }
	#map-configs { height: calc(100vh - 140px); }
	#main_content_div { margin: -1px -12px 0px -20px !important; }

	.nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
		color: #fff;
		border: 1px solid #0dc6c8;
		border-radius: 3px;
		background: #0dc6c8;
	}
	.nav-tabs .nav-link {
		border: 1px solid #dee2e6;
		border-radius: 3px;
		margin: 2px;
		max-width: 120px;
		min-width: 76px;
		padding: 18px 5px;
		text-align: center;
	}
	.tabs-container .nav-tabs {
		border: none;
	}
	.info {
		padding: 6px 8px;
		font: 14px/16px Arial, Helvetica, sans-serif;
		background: white;
		background: rgba(255,255,255,0.8);
		box-shadow: 0 0 15px rgba(0,0,0,0.2);
		border-radius: 5px;
	}
	.info h4 {
		margin: 0 0 5px;
		color: #777;
	}
	.btn-map-opts {
		text-transform: capitalize;
		border: 1px solid #fff;
		margin: 0px 1px;
		border-radius: 5px;
	}
	#bblLagend {
		border: none;
	}
	.data-col-style {
		overflow: auto;
		height: 865px;
		padding: 15px 15px 6px 30px !important;
	}
	th {
		text-transform: capitalize;
	}
	td {
		padding: 2px 3px !important;
	}
</style>

<div id="main_content_div" class="wrapper wrapper-content animated fadeIn m-0 p-0">
	<div class="row">
		<div class="col-3 pr-0">
			<div class="ibox">
				<div class="ibox-title"><h5>GIS KPI Analysis</h5></div>
				<div id="map-configs" class="ibox-content">
					<div class="form-group">
						<select id="integration_ems" class="form-control chosen-select">
							<option value="">Select Integration</option>
							<?php
								foreach ($integ_list as $emsk => $ems)
								{
									echo '<option value="'.$emsk.'">'.$ems.'</option>';
								}
							?>
							<option value="topology_mapping">Universal Topology Mappings</option>
						</select>
					</div>
					<div class="form-group">
						<select id="integ_moc" class="form-control chosen-select">
							<option value="">Select MOC/Mapping</option>
						</select>
					</div>
					<div class="form-group">
						<select id="cntr_dur" class="form-control">
							<option value="">Select Duration</option>
							<option value="1">15 min</option>
							<option value="2">30 min</option>
							<option value="3">Hourly</option>
							<option value="4">Daily</option>
							<option value="5">Weekly</option>
							<option value="6">Monthly</option>
							<option value="7">Yearly</option>
						</select>
					</div>
					<div class="form-group">
						<select id="mois" class="form-control" multiple >
						</select>
					</div>

					<div class="form-group">
						<select id="kpis" class="form-control">
						</select>
					</div>
					<div class="form-group">
						<input type="number" min="0" placeholder="Select Interval" id="interval" class="form-control">
					</div>
					<div class="form-group">
						<div class="col-lg-12 p-0 m-0">
							<div class="tabs-container">
								<ul id="geo-mode" class="nav nav-tabs" role="tablist">
									<li><a id="scatter" class="nav-link active" data-toggle="tab" href="#scatter">Scatter</a></li>
									<li><a id="heatmap" class="nav-link" data-toggle="tab" href="#heatmap">Heatmap</a></li>
									<li><a id="choropleth" class="nav-link" data-toggle="tab" href="#choropleth">Choropleth</a></li>
									<li><a id="grouped" class="nav-link" data-toggle="tab" href="#grouped">Auto Grouped</a></li>
									<li><a id="mois" class="nav-link" data-toggle="tab" href="#mois">MOIs</a></li>
								</ul>
							</div>
						</div>
					</div>
					<div class="form-group">
						<button id="get_data" type="button" class="btn btn-primary" style="width:100%">Get Data</button>
					</div>
				</div>
			</div>
		</div>
		<div class="col-9 ml-0 pl-0">
			<div class="row">
				<div id="data-col" class="col-0 m-0 p-0">
				</div>
				<div id="map-col" class="col-12 m-0 p-0 pr-3">
					<div id="map" class="big-map"></div>
					<div id="bar-chart" style="width:500px; height:300px;"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	var layers = {};
	var controls = [];
	var res_data = {};
	var gMode = $('#geo-mode .nav-link.active').attr('id');
	var drill_props = {
		'_l1':{'layer':'zone','administrative_info':'small_bangladesh_geojson_adm1_8_divisions_bibhags.json', 'next_level': '_l2'},
		'_l2':{'layer':'district','administrative_info':'small_bangladesh_geojson_adm2_64_districts_zillas.json', 'next_level': '_l3'},
		'_l3':{'layer':'thana','administrative_info':'small_bangladesh_geojson_adm3_492_upozila.json'},
		'zone':{'district':{'thana':{}}},
		'district':{'thana':{}},
		'drill_level': '_l1',
	};
	var bubble_props = [ 'zone', 'district', 'thana', 'region', 'rnc', 'bsc', 'vendor', 'commercial_zone' ];
	$(document).ready(function()
	{
		$('.chosen-select').chosen({width: "100%",no_results_text: "No result found."});
	});

	var val_integ_moc = null;
	var val_kpis = null;
	var val_mois = null;

	var defaultLatitude = 23.6850;
	var defaultLongitude = 90.3563;
	var defaultZoomLevel = 2;
	var map = L.map('map').setView([23.6850, 90.3563], 2);
	
	var tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
		// maxZoom: 19,
		minZoom: 7,
		attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo(map);

	$("#integration_ems").change(function() {
		var val = $("#integration_ems").val().trim();
		if( val != "" )
		{
			$('#create_report_cont').children('.ibox-content').addClass('sk-loading');
			$.post( "<?php echo Yii::app()->createUrl('kpimgmt/reporting/getEmsMocs'); ?>", 
			{
				integ_id: val,
			})
			.done(function( resp_dt )
			{
				$('#create_report_cont').children('.ibox-content').removeClass('sk-loading');

				var resp_data = JSON.parse(resp_dt);

				$('#integ_moc').empty();
				$('#integ_moc').append('<option value="">Select MOC/Mapping</option>');

				$.each( resp_data, function ( index, item )
				{
					$('#integ_moc').append('<option value="'+item.id+'">'+item.text+'</option>');
				});

				$("#integ_moc").val("");
				$('#integ_moc').trigger("chosen:updated");
				$('#integ_moc').trigger("change");

			}).fail(function() 
			{
				$('#create_report_cont').children('.ibox-content').removeClass('sk-loading');

				toastr.options = {
					closeButton: true,
					progressBar: true,
					showMethod: 'slideDown',
					timeOut: 10000
				};
				toastr.error( "We encountered an error while fetching data. Kindly Refresh the Page. If Error Presists please contact Administrator.", 'Error' );
			});
		}
		else
		{
			$('#integ_moc').empty();
			$('#integ_moc').append('<option value="">Select MOC/Mapping</option>');
			$('#integ_moc').val("");
			$('#integ_moc').trigger("chosen:updated");
			$('#integ_moc').trigger("change");
		}
		$('#cntr_dur').prop('selectedIndex',0);
		
	});

	$("#integ_moc").change(function() {
		if( val_mois != null )
		{
			val_mois.forEach(option => {
				$("#mois").select2("trigger", "select", {data: { id: option.id, text: option.text }});
			});

			val_mois = null;
		}
		else
		{
			$("#mois").val([]).trigger("change");
		}

		if( val_kpis != null )
		{
			$("#kpis").select2("trigger", "select", {data: { id: val_kpis.id, text: val_kpis.text }  });				
			val_kpis = null;
		}
		else
		{
			$("#kpis").val("").trigger("change");
		}
		$('#cntr_dur').prop('selectedIndex',0);
	});

	select2_maker_gen("#mois", "Select MOI(s)", "<?php echo Yii::app()->createUrl('kpimgmt/dashboarding/optDropMois'); ?>");
	
	select2_maker_gen("#kpis", "Select KPI", "<?php echo Yii::app()->createUrl('kpimgmt/dashboarding/optDropKpis'); ?>");

	function select2_maker_gen(elem_id, placeholder, URL) {
		$.fn.select2.amd.require(['select2/selection/search'], function (Search) {
			var oldRemoveChoice = Search.prototype.searchRemoveChoice;
			
			Search.prototype.searchRemoveChoice = function () {
				oldRemoveChoice.apply(this, arguments);
				this.$search.val('');
			};
			
			$(elem_id).select2({
				width: '100%',
				placeholder: placeholder,
				allowClear: true,
				closeOnSelect: false,
				delay: 1000,
				ajax: {
					url: URL,
					dataType: 'json',
					type: "GET",
					data: function (params) {
						
						var integ = $("#integration_ems").val().trim();
						var moc = $("#integ_moc").val().trim();
						var dur_id = $("#cntr_dur").val().trim();

						var query = {
							search: params.term,
							page: params.page || 1,
							integ: integ,
							moc: moc,
							dur_id: dur_id,
						}
						return query;
					},
					processResults: function (resp)
					{
						return {
							results: resp.data,
							pagination: {
								more: resp.more,
							}
						};
					}
				}
			});
		});
	}

	$("#get_data").on('click', function(){
		var integration_ems = $("#integration_ems").val();
		var integ_moc = $("#integ_moc").val();
		var cntr_dur = $("#cntr_dur").val();
		var mois = $("#mois").val();
		var kpis = $("#kpis").val();
		var interval = $("#interval").val();

		var errors = "";
		var request_data = {};
		request_data.drill_level = drill_props.drill_level;
		if( typeof integration_ems == 'undefined' || integration_ems == '' ) {
			errors = errors + '<li class="label label-warning">Integration</li>';
		} else {
			request_data.integration_ems = integration_ems;
		}

		if( typeof integ_moc == 'undefined' || integ_moc == '' ) {
			errors = errors + '<li class="label label-warning">MOC</li>';
		} else {
			request_data.integ_moc = integ_moc;
		}

		if( typeof cntr_dur == 'undefined' || cntr_dur == '' ) {
			errors = errors + '<li class="label label-warning">Data Duration</li>';
		} else {
			request_data.cntr_dur = cntr_dur;
		}

		if( typeof mois == 'undefined' || mois.Length > 0 ) {
			request_data.mois = mois;
		} else {
			request_data.mois = [];
		}

		if( typeof kpis == 'undefined' || kpis == '' || kpis == null ) {
			errors = errors + '<li class="label label-warning">KPIs</li>';
		} else {
			request_data.kpis = kpis;
		}

		if( typeof interval == 'undefined' || interval == '' ) {
			errors = errors + '<li class="label label-warning">Interval</li>';
		} else {
			request_data.interval = interval;
		}

		if( /*errors == ''*/ true ) {
			$.ajax({
				type: 'POST',
				dataType: 'JSON',
				url:"<?php echo Yii::app()->createUrl('kpimgmt/gis/getdata') ?>",
				data: {
					'request_data': JSON.stringify(request_data)
				},
				success: function(data, textStatus, request)
				{
					res_data = data;
					initialzeMaps();
					// $('#wrap_config_smoi_mkpi_block').children('.ibox-content').removeClass('sk-loading');
				},
				error: function (request, textStatus, errorThrown)
				{
					// $('#wrap_config_smoi_mkpi_block').children('.ibox-content').removeClass('sk-loading');
					swal({
						type: 'error',
						title: 'Error Fetching Data!',
						text: "Error in fetching data: "+textStatus,
					});
					console.log(request);
					console.log(errorThrown);
					console.log(textStatus);
				}
			});
		}
		else {
			swal({title: 'Invalid Query!', html: 'Please provide the following inputs:<br><br><ul class="todo-list m-t small-list">' + errors + "<ul>", type: 'warning'});
		}

	});
	$(".nav-link").on('click', function( ){
		gMode = $(this).attr('id');
		if (res_data.hasOwnProperty("data")) {
			initialzeMaps();
		}
	});
	$(".btn-map-opts").on('click', function( ) {
		alert('hellow');
		var bubbleLagendBtnId = $(this).attr('id');
		console.log(bubbleLagendBtnId);
		$(".btn-map-opts").attr('class', '');
	});

	// $('.leaflet-control-attribution').hide();

	function splitLayout() {
		// Update classes and styles for map-col
		$('#map-col').removeClass('col-12').addClass('col-4');
		$('#map').css('height', '600px'); // Adjust the height as needed

		// Update classes for data-col
		$('#data-col').removeClass('col-0').addClass('col-8 data-col-style');
	  
		// Reset the map center using Leaflet's setView method
		map.setView([defaultLatitude, defaultLongitude], defaultZoomLevel);

		// Fit the map to the resized container
		map.invalidateSize();

		// Disable Pan
		// toggleDragging();

	}

	function defaultLayout() {
		// Update classes and styles for map-col
		$('#map-col').removeClass('col-4').addClass('col-12');
		$('#map').css('height', 'calc(100vh - 100px)'); // Adjust the height as needed

		// Update classes for data-col
		$('#data-col').removeClass('col-8 data-col-style').addClass('col-0');
		$('#data-col').html('');
	  
		// Reset the map center using Leaflet's setView method
		map.setView([defaultLatitude, defaultLongitude], defaultZoomLevel);

		// Fit the map to the resized container
		map.invalidateSize();

		// Disable Pan
		toggleDragging();

	}

	function toggleDragging() {
		map.dragging.enabled() ? map.dragging.disable() : map.dragging.enable();
	}

	function addControl(control) {
		control.addTo(map);
		controls.push(control);
	}

	function removeAllControls() {
		for (var i = 0; i < controls.length; i++) {
			map.removeControl(controls[i]);
		}
		controls = []; // Clear the controls array
	}

	function initialzeMaps() {	
		if ( gMode.length ) {
			resetMap();
			if( gMode == 'scatter' ) {
				plotscatter();
			} else if( gMode == 'heatmap' ) {
				plotHeatmap(res_data.data);
			} else if( gMode == 'choropleth' ) {
				plotChoropleth(res_data.data, res_data.administrative_into );
			} else if( gMode == 'grouped' ) {
			} else if( gMode == 'mois' ) {
				plotMois(res_data.data);
			} else {
				console.log('Invalid gMode #1.');
				swal({title: 'Invalid Mode!', html: 'Please select a map mode and try again', type: 'warning'});							
			}
		} else {
			console.log('Invalid gMode #2.');
			swal({title: 'Invalid Mode!', html: 'Please select a map mode and try again', type: 'warning'});
		}
	}

	function resetMap() {
		tileLayer.addTo(map);
		defaultLayout();
		removeAllControls();
		map.invalidateSize();
		map.dragging.enabled();
		$.each(layers, function(index, layer) {
			map.removeLayer(layer);
		});
	}

	function plotscatter( dl=null, filters={'level':null, 'filterVals':[]}, admInfo = null) {
		resetMap();
		$('#data-col, #map-col').removeClass('col-12 m-0 p-0');
		$('#data-col').addClass('col-8 m-0 p-0');
		$('#map-col').addClass('col-4 m-0 p-0');
		splitLayout();
		tileLayer.removeFrom(map);
		var drill_level = drill_props._l1.layer;
		if( dl != null ){
			drill_level = dl;
		}
		plotAdmLayer(admInfo).done( function() {
			var aggResp = aggregateGeoJSON(res_data.data, drill_level, 'sum', filters);
			layers.scatter = L.geoJSON(aggResp.aggregatedGeoJSON, {
				pointToLayer: function (feature, latlng) {
					var circleOptions = {
						radius: feature.properties.kpi_val * ( 30 / aggResp.maxKpiValue ),
						fillColor: "#ff7800",
						color: "#000",
						weight: 1,
						opacity: 1,
						fillOpacity: 0.8
					};
					var circleMarker = L.circleMarker(latlng, circleOptions);
		  
					circleMarker.on('click', function () {
						var thisData = $(this).attr(feature);
						var level = thisData[0].feature.properties.layer;
						console.log(level);
						console.log(new_level);
						var levelName = thisData[0].feature.properties.layerName;
						if(drill_props.hasOwnProperty(level) && Object.keys(drill_props[level]).length > 0 ) {
							var new_level = Object.keys(drill_props[level])[0];
							console.log(new_level);
							console.log(dl);
							plotscatter(
								new_level,
								{
									'level':level,
									'filterVals':[levelName]
								},
								dl
							);
						} else {
							resetMap();
							plotMois(filterGeoJSON(res_data.data, level, levelName));
						}
					});

					return circleMarker;
				},
			}).addTo(map);

			if( aggResp.admLayers.length > 1 ) {
				if(!layers.hasOwnProperty("bubbleLagend")) {
					layers.bubbleLagend = L.control({ position: 'topright' });
					layers.bubbleLagend.onAdd = function () {
						var div = L.DomUtil.create('div', 'legend');
						div.innerHTML = '<div id="bblLagend" class="ibox-content float-e-margins m-0 p-0">'+
											'<p class="p-0 m-0">';
												$.each( aggResp.admLayers, function( key, value ) {
													div.innerHTML += '<a id="adm_layer_'+value+'" class="btn btn-default btn-rounded btn-map-opts" onClick="plotscatter(\''+value+'\',\''+{}+'\',\''+value+'\')">'+value+'</a>';
												});
											div.innerHTML += '</p>'+
										'</div>';
						return div;
					};
				}
			}
			addControl(layers.bubbleLagend);
			displayTable(aggResp.aggregatedGeoJSON, drill_level, 'kpi_val');

			var _tableData = [];
			var _xAxisData = [];
			var _seriesData = [];

			aggResp.aggregatedGeoJSON.features.forEach(function (feature) {
				_tableData.push([
					feature.properties.layerName,
					feature.properties.kpi_val,
				]);
			});

			_tableData.sort(function(a, b) {
				return b[1] - a[1];
			});
			$(_tableData).each(function(index, data){
				_xAxisData.push(data[0]);
				_seriesData.push(data[1]);
			});
			plotBarChart('bar-chart', barChartOptions(), _xAxisData, _seriesData);
		});
	}
	
	// function plotAdmLayer ( adm_level = null ) {
		// 	var res = null;
		// 	var errors = "";
		// 	if( errors == '' ) {
		// 		$.ajax({
		// 			type: 'POST',
		// 			dataType: 'JSON',
		// 			url:"<?php echo Yii::app()->createUrl('kpimgmt/gis/getAdmGeoJson') ?>",
		// 			data: {
		// 				'request_data': JSON.stringify({ 'drill_level': adm_level })
		// 			},
		// 			success: function(data, textStatus, request)
		// 			{
		// 				console.log(data.administrative_info);
		// 				layers.administrative_info = L.geoJson(data.administrative_info, {
		// 					style: {
		// 						weight: 1, // opacity: 1, // color: 'white', // fillOpacity: 0.7
		// 					},
		// 				}).addTo(map);
		// 			},
		// 			error: function (request, textStatus, errorThrown)
		// 			{
		// 				// $('#wrap_config_smoi_mkpi_block').children('.ibox-content').removeClass('sk-loading');
		// 				swal({
		// 					type: 'error',
		// 					title: 'Error Fetching Data!',
		// 					text: 'Fetching adminstrative information',
		// 				});
		// 				console.log(request);
		// 				console.log(errorThrown);
		// 				console.log(textStatus);
		// 			}
		// 		});
		// 	}
		// 	else
		// 	{
		// 		swal({title: 'Invalid Query!', html: errors, type: 'warning'});
		// 	}
	// };
	function plotAdmLayer(adm_level = null) {
	  var deferred = $.Deferred();

	  var res = null;
	  var errors = "";

	  if (errors == "") {
		$.ajax({
		  type: 'POST',
		  dataType: 'JSON',
		  url: "<?php echo Yii::app()->createUrl('kpimgmt/gis/getAdmGeoJson') ?>",
		  data: {
			'request_data': JSON.stringify({ 'drill_level': adm_level })
		  },
		  success: function(data, textStatus, request) {
			layers.administrative_info = L.geoJson(data.administrative_info, {
			  style: {
				weight: 1,
			  },
			}).addTo(map);
			deferred.resolve(); // Resolve the promise when the operation is done
		  },
		  error: function(request, textStatus, errorThrown) {
			swal({
			  type: 'error',
			  title: 'Error Fetching Data!',
			  text: 'Fetching administrative information',
			});
			console.log(request);
			console.log(errorThrown);
			console.log(textStatus);
			deferred.reject(); // Reject the promise if an error occurs
		  }
		});
	  } else {
		swal({ title: 'Invalid Query!', html: errors, type: 'warning' });
		deferred.reject(); // Reject the promise if there are errors
	  }

	  return deferred.promise();
	}

	function displayTable(geoJSON, layer, kpi_key) {
		var tableHTML = '<table class = "table table-bordered table-striped">';
		tableHTML += '<tr><th>'+layer+'</th><th>Number Of MOIs</th><th>KPI Value</th></tr>';
		var tableData = [];
		geoJSON.features.forEach(function (feature) {
			tableData.push([
				feature.properties.layerName,
				feature.properties.count,
				feature.properties.kpi_val,
			]);
		});

		tableData.sort(function(a, b) {
			return b[2] - a[2];
		});

		$(tableData).each(function(index, data){
			tableHTML += '<tr>';
				tableHTML += '<td>' + data[0] + '</td>';
				tableHTML += '<td>' + data[1] + '</td>';
				tableHTML += '<td>' + data[2] + '</td>';
			tableHTML += '</tr>';
		});
		tableHTML += '</table>';

		// Update the #data-col div with the table HTML
		$('#data-col').html(tableHTML);
	}

	function aggregateGeoJSON(geoJSON, propertyName, aggregationType, filters ) {
		// Create an empty object to store aggregated data
		var aggregatedData = {};
		var admLayers = [];
		// Loop through the features in the original GeoJSON
		geoJSON.features.forEach(function(feature) {
			var properties = feature.properties;
			var propertyValue = properties[propertyName];
			if( filters.hasOwnProperty("level") && filters.hasOwnProperty("filterVals") && $.isArray(filters.filterVals ) && filters.filterVals.length > 0 && $.inArray(properties[filters.level], filters.filterVals ) === -1 ) {
				return;
			}

			if( admLayers.length < 1 ) {
				var admKeys = Object.keys(properties);
				admLayers = $.grep(bubble_props, function(element) {
					return $.inArray(element, admKeys ) !== -1;
				});
			}
			// Check if the property value already exists in the aggregatedData object
			if (propertyValue in aggregatedData) {
				// Aggregate the kpi_val based on the aggregationType
				aggregatedData[propertyValue].count++;
				aggregatedData[propertyValue].latSum += feature.geometry.coordinates[0];
				aggregatedData[propertyValue].lngSum += feature.geometry.coordinates[1];
				if (aggregationType === 'min') {
					aggregatedData[propertyValue].kpi_val = Math.min(
						aggregatedData[propertyValue].kpi_val,
						parseFloat(properties.kpi_val)
					);
				} else if (aggregationType === 'max') {
					aggregatedData[propertyValue].kpi_val = Math.max(
						aggregatedData[propertyValue].kpi_val,
						parseFloat(properties.kpi_val)
					);
				} else if (aggregationType === 'avg') {
					aggregatedData[propertyValue].kpi_valSum += parseFloat(properties.kpi_val);
					aggregatedData[propertyValue].kpi_val = aggregatedData[propertyValue].kpi_valSum / aggregatedData[propertyValue].count;
				} else if (aggregationType === 'sum') {
					aggregatedData[propertyValue].kpi_val += parseFloat(properties.kpi_val);
				}
			} else {
				// Create a new entry for the property value in the aggregatedData object
				aggregatedData[propertyValue] = {
					count: 1,
					kpi_val: parseFloat(properties.kpi_val),
					kpi_valSum: parseFloat(properties.kpi_val),
					latSum: feature.geometry.coordinates[0],
					lngSum: feature.geometry.coordinates[1]
				};
			}
		});

		// Create a new GeoJSON object with one feature for each unique property value
		var aggregatedGeoJSON = {
			type: 'FeatureCollection',
			features: []
		};

		// Loop through the aggregated data and create features for each property value
		var id = 1;
		var maxKpiValue = null;
		for (var propertyValue in aggregatedData) {
			var kpiValue = aggregatedData[propertyValue].kpi_val;
			
			maxKpiValue = kpiValue > maxKpiValue ? kpiValue : maxKpiValue;
			
			var aggregatedFeature = {
				type: 'Feature',
				id: id++,
				properties: {
					kpi_val: aggregatedData[propertyValue].kpi_val,
					count: aggregatedData[propertyValue].count,
					layer: propertyName,
					layerName: propertyValue,
					// ro: null, // office: null, // thana: null, // district: null, // zone: null, // c_zone: null
				},
				geometry: {
					type: 'Point',
					coordinates: [
						(aggregatedData[propertyValue].latSum / aggregatedData[propertyValue].count).toFixed(5),
						(aggregatedData[propertyValue].lngSum / aggregatedData[propertyValue].count).toFixed(5)
					]
				}
			};

			aggregatedGeoJSON.features.push(aggregatedFeature);
		}

		// Return the aggregated GeoJSON object
		return {
			'aggregatedGeoJSON': aggregatedGeoJSON,
			'maxKpiValue': maxKpiValue,
			'admLayers': admLayers
		};
	}

	function plotMois(geoJson) {

		layers.mois = L.geoJSON(geoJson, {
			pointToLayer: function (feature, latlng) {
				var circleOptions = {
					radius: feature.properties.kpi_val > 10 ? 10 : feature.properties.kpi_val,
					fillColor: "#ff7800",
					color: "#000",
					weight: 1,
					opacity: 1,
					fillOpacity: 0.8
				};
				return L.circleMarker(latlng, circleOptions);
			}
		}).addTo(map);

		var bounds = layers.mois.getBounds();
		map.fitBounds(bounds);
	}

	function filterGeoJSON(geoJSON, propertyKey, propertyValue) {
		// Filter the features based on the property
		var filteredFeatures = geoJSON.features.filter(function (feature) {
			return feature.properties[propertyKey] === propertyValue;
		});

		// Create a new GeoJSON object with the filtered features
		var filteredGeoJSON = {
			type: "FeatureCollection",
			features: filteredFeatures
		};

		return filteredGeoJSON;
	}
	
	function plotOnePointPerProperty(property) {
		var propertyPoints = {};

		layers.mois.eachLayer(function (layer) {
			var propertyValue = layer.feature.properties[property];

			if (!propertyPoints[propertyValue]) {
				propertyPoints[propertyValue] = [];
			}

			propertyPoints[propertyValue].push(layer.getLatLng());
			map.removeLayer(layer);
		});

		var bubbleLayerGroup = [];

		for (var value in propertyPoints) {
			var points = propertyPoints[value];
			var totalPoints = points.length;
			var sumLat = points.reduce(function (acc, point) {
				return acc + point.lat;
			}, 0);
			var sumLng = points.reduce(function (acc, point) {
				return acc + point.lng;
			}, 0);

			var midLat = sumLat / totalPoints;
			var midLng = sumLng / totalPoints;

			bubbleLayerGroup[value] = L.circleMarker([midLat, midLng]).bindPopup(value);
		}
		if( bubbleLayerGroup.length ) {
			layers.scatter = L.layerGroup(bubbleLayerGroup);
		}
	}
	
	function plotHeatmap() {
		var heatmapData = [];
		res_data.data.features.forEach(function (feature) {
			var coordinates = feature.geometry.coordinates;
			var kpiValue = parseFloat(feature.properties.kpi_val);
			heatmapData.push([coordinates[1], coordinates[0], kpiValue]);
		});

		var gradientColors = {
			0.2: 'red',
			0.3: 'yellow',
			0.9: 'green',
			1.0: 'blue'
		};

		layers.heatmap = L.heatLayer(heatmapData, {
			radius: 20,
			gradient: gradientColors
		}).addTo(map);

		// var legend = L.control({ position: 'bottomright' });

		// legend.onAdd = function () {
		// 	var div = L.DomUtil.create('div', 'legend');
		// 	div.innerHTML = '<h4>KPI Legend</h4>' +
		//     	'<span style="background: green"></span> Low<br>' +
		//     	'<span style="background: yellow"></span> Medium<br>' +
		//     	'<span style="background: red"></span> High';
		// 	return div;
		// };
		// legend.addTo(map);
	}

	// layers.choroplethinfo = L.control();
	function plotChoropleth(geoJson, administrative_info) {
		layers.administrative_info = L.geoJson(administrative_info, {
			style: style,
			onEachFeature: onEachFeature
		}).addTo(map);

		// layers.choroplethinfo.onAdd = function (map) {
		// 	this._div = L.DomUtil.create('div', 'choroplethinfo'); // create a div with a class "info"
		// 	this.update();
		// 	return this._div;
		// };

		// // method that we will use to update the control based on feature properties passed
		// layers.choroplethinfo.update = function (props) {
		// 	this._div.innerHTML = '<h4>US Population Density</h4>' +  (props ?
		// 		'<b>' + props.ADM1_EN + '</b><br />' + props.kpi_val + ' people / mi<sup>2</sup>'
		// 		: 'Hover over a state');
		// };

		// layers.choroplethinfo.addTo(map);
	}

	function getColor(d) {
		return d > 100 ? '#800026' :
				d > 80 ? '#BD0026' :
				d > 70 ? '#E31A1C' :
				d > 60 ? '#FC4E2A' :
				d > 50 ? '#FD8D3C' :
				d > 20 ? '#FEB24C' :
				d > 10 ? '#FED976' :
						 '#FFEDA0';
	}

	function style(feature) {
		return {
			fillColor: getColor(feature.properties.kpi_val),
			weight: 1,
			opacity: 1,
			color: 'white',
			dashArray: '3',
			fillOpacity: 0.7
		};
	}

	function highlightFeature(e) {
		var layer = e.target;

		layer.setStyle({
			weight: 2,
			color: '#666',
			dashArray: '',
			fillOpacity: 0.7
		});

		layer.bringToFront();
		layers.choroplethinfo.update(layer.feature.properties);
	}

	function resetHighlight(e) {
		layers.administrative_info.resetStyle(e.target);
		layers.choroplethinfo.update();
	}
	
	function zoomToFeature(e) {
		map.fitBounds(e.target.getBounds());
	}
	
	function onEachFeature(feature, layer) {
		layer.on({
			mouseover: highlightFeature,
			mouseout: resetHighlight,
			click: zoomToFeature
		});
	}

	function plotBarChart(div_id, opts, xAxisData, seriesData) {
		if( $("#"+div_id).length < 1 ) {
			console.log("Invalid id for the graph box");
			return false;
		}
		if( typeof opts != 'object' || typeof opts.xAxis == 'undefined' || typeof opts.series == 'undefined') {
			console.log("Invalid graph options");
			return false;
		}
		if( $.isArray(xAxisData) && $(xAxisData).length < 1 ) {
			console.log("Invalid xAxisData");
			return false;
		}
		if( $.isArray(seriesData) &&  $(seriesData).length < 1 ) {
			console.log("Invalid seriesData");
			return false;
		}
		opts.xAxis.data = xAxisData;
		opts.series[0].data = seriesData
		// $("#"+div_id).attr('width', '500px');
		// $("#"+div_id).attr('height', '500px');
		barChart = echarts.init(document.getElementById(div_id));
		barChart.setOption(opts);
		// $("#"+div_id).attr( 'data-resize', graph_div );
	}
	
	function barChartOptions() {
		return {
			"xAxis": {
				"type": "category",
				"data": []
			},
			"yAxis": {
				"type": "value",
				"axisLabel": {
				"formatter": function(value) {
					var suffixes = ['', 'K', 'M', 'B', 'T'];
					var suffixNum = 0;
					while (value >= 1000 && suffixNum < suffixes.length - 1) {
					  value /= 1000;
					  suffixNum++;
					}
					return value.toFixed(1) + suffixes[suffixNum];
				  }
				}
			},
			"series": [{
				"data": [],
				"type": "bar"
			}],
			"tooltip": [{
				"show": true,
			}],
		};
	}

</script>