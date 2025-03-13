<div class="ibox">
	<div class="ibox-title">
		<h5>Create Holiday</h5>
		<div class="ibox-tools">
			<a href="<?php echo $this->createUrl('hrholidays/admin') ?>"><button type="button" class="btn btn-primary btn-xs" >Back</button></a>
        </div>
	</div>
	<div class="ibox-content">
		<?php $this->renderPartial('_form', array('model'=>$model)); ?>
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