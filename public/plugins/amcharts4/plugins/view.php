<style type="text/css">
	.table td, .table th {
		padding: 0.25rem !important;
	}
	.ibox-content {
		overflow-x: auto;
	}
</style>
<div class="ibox">
	<div class="ibox-title">
		<h5>Attendance Policy: <?php echo $model->name; ?></h5>
		<div class="ibox-tools">
			<a href="<?php echo $this->createUrl('hrattendancepolicy/admin') ?>"><button type="button" class="btn btn-primary btn-xs" >Attendance Policy List</button></a>
			<a href="<?php echo $this->createUrl('hrattendancepolicy/update&id=' . $model->id) ?>"><button type="button" class="btn btn-warning btn-xs" >Update</button></a>
        </div>
	</div>
	<div class="ibox-content">
		<?php $this->widget('zii.widgets.CDetailView', array(
			'data'=>$model,
			'htmlOptions' => array('class' => 'table table-striped'),
			'attributes'=>array(
				'name',
				'description',
				'time_in',
				'time_in_buffer',
				'time_out',
				'time_out_buffer',
				'break_start',
				'break_start_buffer',
				'break_end',
				'break_end_buffer',
				array(
					'name' => 'created_by',
					'value'=>function($model){
		                return $model->employeeCreatedBy->first_name . " " . $model->employeeCreatedBy->last_name;
		            },
				),
				 // Need to get Full Name
				'created_at',
				array(
					'name' => 'updated_by',
					'value'=>function($model){
						if(isset($model->updated_by))
		                	return $model->employeeUpdatedBy->first_name . " " . $model->employeeUpdatedBy->last_name;
		            },
				),
				'updated_at',
			),
		)); ?>
	</div>
</div>

<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/jquery-3.1.1.min.js"></script>
<!-- Mainly scripts -->


<!-- Custom and plugin javascript -->
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/inspinia.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/pace/pace.min.js"></script>


<!-- Mainly scripts -->
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/fullcalendar/moment.min.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/popper.min.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/bootstrap.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

<!-- jQuery UI  -->
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/jquery-ui/jquery-ui.min.js"></script>

<!-- Full Calendar -->
<script src="<?php echo Yii::app()->theme->baseUrl ?>/js/plugins/fullcalendar/fullcalendar.min.js"></script>

<script>

    $(document).ready(function() {
		$('body').addClass('mini-navbar');
	});
</script>