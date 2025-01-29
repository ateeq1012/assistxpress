<?php
require Yii::app()->basePath.'/extensions/phpSpreadsheet/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NbrcellController extends IntegrationController
{
	public function filters()
	{
		return array(
			// 'setCreds',
			'accessSimpleCheck',
		);
	}

	public function actionIndex()
	{

		/*
		 *
		 * TDL
		 * GET Virtual Attributes as well
		 *
		 */

		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		ini_set('memory_limit', '8192M');
		ini_set('max_execution_time', 600);
		// echo "Start: " . date('Y-m-d H:i:s') . "<br>";

		$ram_resp= array();
		$timeLog = [['Start', date("Y-m-d H:i:s")]];

		$elevation_arr = [];
		$eirp_arr = ['2g'=>[],'3g'=>[],'4g'=>[]];

		// $elevation_arr = self::get_elevation_lkp();
		$timeLog[] = ['elevation_arr', date("Y-m-d H:i:s")];
		// $eirp_arr = self::get_eirp_lkp();
		// if(!is_array($elevation_arr) ) {
		// 	$elevation_arr = [];
		// }
		// if(!is_array($eirp_arr) ) {
		// 	$eirp_arr = ['2g'=>[],'3g'=>[],'4g'=>[]];
		// }
		$timeLog[] = ['eirp_arr', date("Y-m-d H:i:s")];

		/* MAKE LOOKUPS: */
			/*Cron Settings*/
			$cron_settings = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM cron_setting WHERE name IN ('ran_nokia_kpi', 'ran_zte_ume_kpi', 'ran_huawei_kpi')")->queryAll();
			$lct = [];
			if(isset($cron_settings)) {
				foreach ($cron_settings as $settings) {
					if (isset($settings['params'])) {
						$params = json_decode($settings['params'], true);
						if (json_last_error() == false && isset($params['lct'])) {
							$lct[$settings['name']] = $params['lct'];
						} else {
							// LOG ERROR
						}
					}
				}
			} else {
				// LOG ERROR
			}
			$timeLog[] = ['cron_settings', date("Y-m-d H:i:s")];

			/*ZTE MOCs*/
			$d = self::get_data( 'ran_zte_ume_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$z_moc = [];
			foreach ($d as $k => $v)
			{
				if(isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$z_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$z_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['ZTE MOC Lookup', date("Y-m-d H:i:s")];

			/*NOKIA MOCs*/
			$d = self::get_data( 'ran_nokia_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$n_moc = [];
			foreach ($d as $k => $v)
			{
				if(isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$n_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$n_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Nokia MOC Lookup', date("Y-m-d H:i:s")];

			/*HUAWEI MOCs*/
			$d = self::get_data( 'ran_huawei_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$h_moc = [];
			foreach ($d as $k => $v)
			{
				if(isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$h_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$h_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Huawei MOC Lookup', date("Y-m-d H:i:s")];

			/*HUAWEI MML MOCs*/
			$d = self::get_data( 'ran_huawei_mml_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$h_mml_moc = [];
			foreach ($d as $k => $v)
			{
				if(isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$h_mml_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$h_mml_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Huawei MML MOC Lookup', date("Y-m-d H:i:s")];

			/*ZTE 2G LAC Lookup*/
			$gLac = [];
			$d = self::get_data('ran_zte_ume_moi', ["parameter_data ->> 'lac' AS lac", "moi_parts ->> 'GBssFunction' AS gbssfunction", "moi_parts ->> 'GLocationArea' AS glocationarea" ], ['moc_id'=>['int'=>[$z_moc['GBssFunction/GLocationArea']]]]);
			if (count($d))
			{
				foreach ($d as $k=>$v)
				{
					if (isset($v['gbssfunction']) && isset($v['glocationarea']))
					{
						$gLac[ "GBssFunction=" . $v['gbssfunction'] . ",GLocationArea=" . $v['glocationarea'] ] = $v['lac'];
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$timeLog[] = ['ZTE 2G LAC Lookup', date("Y-m-d H:i:s")];

			/*ZTE 3G LAC Lookup*/
			$uLac = [];
			$d = self::get_data('ran_zte_ume_moi', ["parameter_data ->> 'lac' AS lac", "moi_parts ->> 'URncFunction' AS urncfunction", "moi_parts ->> 'ULocationArea' AS ulocationarea" ], ['moc_id'=>['int'=>[$z_moc['URncFunction/ULocationArea']]]]);
			if (count($d))
			{
				foreach ($d as $k=>$v)
				{
					if (isset($v['urncfunction']) && isset($v['ulocationarea']) )
					{
						$uLac[ "URncFunction=" . $v['urncfunction'] . ",ULocationArea=" . $v['ulocationarea'] ] = $v['lac'];
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$timeLog[] = ['ZTE 3G LAC Lookup', date("Y-m-d H:i:s")];

			/*HUAWEI 4G eNodeB Lookup*/
			$d = self::get_data('ran_huawei_mml_moi', ["parameter_data ->> 'eNodeBId' AS enbid", "moi_parts ->> 'sys_bts3900' AS enbname" ], ['moc_id'=>['int'=>$h_mml_moc['BTS3900/eNodeBFunction']]] );
			// $h_eNodeBFunction = (count($d)) ? array_column($d, 'enbid', 'enbname') : [];
			$exclude = ['test', 'swap', 'noa', 'lock', 'h2z', 'n2h', 'deactivated'];
			$h_eNodeBFunction = [];
			foreach ($d as $site) {
				$skp = false;
				$enbid = $site['enbid'];
				$enbname = $site['enbname'];
				if(isset($enbid) && isset($enbname)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($enbname), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$h_eNodeBFunction[$enbname] = $enbid;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Huawei 4G eNodeB Lookup', date("Y-m-d H:i:s")];

			/*ZTE 4G eNodeB Lookup*/
			$d = self::get_data('ran_zte_ume_moi', ["parameter_data ->> 'eNBId' AS enbid", "parameter_data ->> 'userLabel' AS enbname" ], ['moc_id'=>['int'=>$z_moc['ManagedElement/ENBFunctionFDD']]] );
			$z_ENBFunctionFDD = (count($d)) ? array_column($d, 'enbid', 'enbname') : [];
			$timeLog[] = ['ZTE 4G eNodeB ENBFunctionFDD Lookup', date("Y-m-d H:i:s")];

			$d = self::get_data('ran_zte_ume_moi', ["parameter_data ->> 'eNBId' AS enbid", "parameter_data ->> 'userLabel' AS enbname" ], ['moc_id'=>['int'=>$z_moc['ManagedElement/ENBFunctionTDD']]] );
			$z_ENBFunctionTDD = (count($d)) ? array_column($d, 'enbid', 'enbname') : [];
			$timeLog[] = ['ZTE 4G eNodeB ENBFunctionTDD Lookup', date("Y-m-d H:i:s")];
			
			/*NOKIA 4G eNodeB Lookup*/
			$d = self::get_data('ran_nokia_moi', ["moi_parts ->> 'LNBTS' AS enbid", "parameter_data ->> 'name' AS enbname" ], ['moc_id'=>['int'=>$n_moc['MRBTS/LNBTS']]] );
			$n_LNBTS = (count($d)) ? array_column($d, 'enbid', 'enbname') : [];
			$timeLog[] = ['NOKIA 4G eNodeB Lookup', date("Y-m-d H:i:s")];

			/*NOKIA BCCH LOOKUP*/
			$d = self::get_data('ran_nokia_moi', ['parent_moi_id', "parameter_data ->> 'initialFrequency' AS bcch"], ['moc_id'=>['int'=>$n_moc['BTS/TRX']], "parameter_data ->> 'preferredBcchMark'"=>['str'=>'1'] ] );
			$n_bcchTRX = (count($d)) ? array_column($d, 'bcch', 'parent_moi_id') : [];
			$d = null;
			$timeLog[] = ['NOKIA BCCH BTS/TRX LOOKUP', date("Y-m-d H:i:s")];

			/*NOKIA LNCEL LOOKUPS FROM LNCEL_FDD AND LNCEL_TDD*/
			$d = self::get_data('ran_nokia_moi', ['parent_moi_id', "parameter_data ->> 'earfcnDL' AS earfcndl"], ['moc_id'=>['int'=>$n_moc['LNCEL/LNCEL_FDD']]] );
			$n_LNCEL_FDD = (count($d)) ? array_column($d, null, 'parent_moi_id') : [];
			$d = null;
			$timeLog[] = ['NOKIA LNCEL LOOKUPS FROM LNCEL_FDD MO', date("Y-m-d H:i:s")];

			$d = self::get_data('ran_nokia_moi', ['parent_moi_id', "parameter_data ->> 'earfcn' AS earfcn"], ['moc_id'=>['int'=>$n_moc['LNCEL/LNCEL_TDD']]] );
			$n_LNCEL_TDD = (count($d)) ? array_column($d, null, 'parent_moi_id') : [];
			$d = null;
			$timeLog[] = ['NOKIA LNCEL LOOKUPS FROM LNCEL_TDD MO', date("Y-m-d H:i:s")];

			/*HUAWEI BCCH LOOKUP*/
			$d = self::get_data('ran_huawei_mml_moi',["CONCAT(moi_parts ->> 'sys_bsc', '_', parameter_data ->> 'CELLID' ) AS key", "parameter_data ->> 'FREQ' AS bcch"], ['moc_id'=>['int'=>$h_mml_moc['BSC/GTRX']], "parameter_data ->> 'ISMAINBCCH'"=>['str'=>'YES']]);
			$h_bcchTRX = (count($d)) ? array_column($d, 'bcch', 'key') : [];
			$timeLog[] = ['HUAWEI BCCH LOOKUP', date("Y-m-d H:i:s")];

			/*HUAWEI LOCKED 2G CELLS*/
			$d = self::get_data('ran_huawei_mml_moi',["CONCAT(moi_parts ->> 'sys_bsc', '_', parameter_data ->> 'CELLID' ) AS key"], ['moc_id'=>['int'=>$h_mml_moc['BSC/GCELLADMSTAT']]]);
			$h_locked_cells = (count($d)) ? array_column($d, 'key', 'key') : [];		
			$timeLog[] = ['HUAWEI LOCKED 2G CELLS', date("Y-m-d H:i:s")];

		$n_radius_4g_lkp = (isset($n_moc['LNBTS/LNCEL']) && isset($lct['ran_nokia_kpi'])) ? self::get_nokia_4g_cell_radius($n_moc['LNBTS/LNCEL'], $lct['ran_nokia_kpi']) : [];
		echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $n_radius_4g_lkp ); echo "</pre><br>"; exit;
		$timeLog[] = ['Get Nokia 2G Cells Radius Lookup', date("Y-m-d H:i:s")];

		$n_radius_2g_lkp = (isset($n_moc['BTS/TRX']) && isset($lct['ran_nokia_kpi'])) ? self::get_nokia_2g_cell_radius($n_moc['BTS/TRX'], $lct['ran_nokia_kpi']) : [];
		$timeLog[] = ['Get Nokia 2G Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$n_radius_3g_lkp = (isset($n_moc['WBTS/WCEL']) && isset($lct['ran_nokia_kpi'])) ? self::get_nokia_3G_cell_radius($n_moc['WBTS/WCEL'], $lct['ran_nokia_kpi']) : [];
		$timeLog[] = ['Get Nokia 3G Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$z_radius_2g_lkp = (isset($z_moc['GBtsSiteManager/GGsmCell']) && isset($lct['ran_zte_ume_kpi'])) ? self::get_zte_2G_cell_radius($z_moc['GBtsSiteManager/GGsmCell'], $lct['ran_zte_ume_kpi']) : [];
		$timeLog[] = ['Get ZTE 2G Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$z_radius_3g_lkp = (isset($z_moc['USector/ULocalCell']) && isset($lct['ran_zte_ume_kpi'])) ? self::get_zte_3G_cell_radius($z_moc['USector/ULocalCell'], $lct['ran_zte_ume_kpi']) : [];
		$timeLog[] = ['Get ZTE 3G Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$z_radius_4g_CUEUtranCellFDDLTE_lkp = (isset($z_moc['CULTE/CUEUtranCellFDDLTE']) && isset($lct['ran_zte_ume_kpi'])) ? self::get_zte_4G_cell_radius($z_moc['CULTE/CUEUtranCellFDDLTE'], $lct['ran_zte_ume_kpi']) : [];
		$timeLog[] = ['Get ZTE 4G (CUEUtranCellFDDLTE) Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$z_radius_4g_EUtranCellFDD_lkp = (isset($z_moc['ENBFunctionFDD/EUtranCellFDD']) && isset($lct['ran_zte_ume_kpi'])) ? self::get_zte_4G_cell_radius($z_moc['ENBFunctionFDD/EUtranCellFDD'], $lct['ran_zte_ume_kpi']) : [];
		$timeLog[] = ['Get ZTE 4G (EUtranCellFDD) Cells Radius Lookup', date("Y-m-d H:i:s")];
		
		$h_radius_2g_lkp6910 = (isset($h_moc['BSC6910GSMNE/BSC6910GSMGTRX']) && isset($lct['ran_huawei_kpi'])) ? self::get_huawei_2g_cell_radius($h_moc['BSC6910GSMNE/BSC6910GSMGTRX'], $lct['ran_huawei_kpi']) : [];
		$timeLog[] = ['Get Huawei 2G Cells (BSC6910GSMNE/BSC6910GSMGTRX) Radius Lookup', date("Y-m-d H:i:s")];

		$h_radius_2g_lkp6900 = (isset($h_moc['BSC6900GSMNE/BSC6900GSMGTRX']) && isset($lct['ran_huawei_kpi'])) ? self::get_huawei_2g_cell_radius($h_moc['BSC6900GSMNE/BSC6900GSMGTRX'], $lct['ran_huawei_kpi']) : [];
		$timeLog[] = ['Get Huawei 2G Cells (BSC6900GSMNE/BSC6900GSMGTRX) Radius Lookup', date("Y-m-d H:i:s")];
			
		$h_radius_3g_lkp = (isset($h_moc['BSC6910UMTSNE/BSC6910UMTSUCELL']) && isset($lct['ran_huawei_kpi'])) ? self::get_huawei_3g_cell_radius($h_moc['BSC6910UMTSNE/BSC6910UMTSUCELL'], $lct['ran_huawei_kpi']) : [];
		$timeLog[] = ['Get Huawei 3G Cells Radius Lookup', date("Y-m-d H:i:s")];

		$h_radius_4g_lkp = (isset($h_moc['BTS3900NE/BTS3900CELL']) && isset($lct['ran_huawei_kpi'])) ? self::get_huawei_4g_cell_radius($h_moc['BTS3900NE/BTS3900CELL'], $lct['ran_huawei_kpi']) : [];
		$timeLog[] = ['Get Huawei 4G Cells Radius Lookup', date("Y-m-d H:i:s")];

		// echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r([
		// 	'n_radius_2g_lkp' => count($n_radius_2g_lkp),
		// 	'n_radius_3g_lkp' => count($n_radius_3g_lkp),
		// 	'z_radius_2g_lkp' => count($z_radius_2g_lkp),
		// 	'z_radius_3g_lkp' => count($z_radius_3g_lkp),
		// 	'z_radius_4g_CUEUtranCellFDDLTE_lkp' => count($z_radius_4g_CUEUtranCellFDDLTE_lkp),
		// 	'z_radius_4g_EUtranCellFDD_lkp' => count($z_radius_4g_EUtranCellFDD_lkp),
		// 	'h_radius_2g_lkp6910' => count($h_radius_2g_lkp6910),
		// 	'h_radius_2g_lkp6900' => count($h_radius_2g_lkp6900),
		// 	'h_radius_3g_lkp' => count($h_radius_3g_lkp),
		// 	'h_radius_4g_lkp' => count($h_radius_4g_lkp),
		// 	$timeLog,
		// ]); echo "</pre><br>"; exit;



		/*NOKIA 2G BTS*/
			/* NOKIA 2G Sites*/
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$n_moc['BSC/BCF']], "parameter_data ->> 'adminState'"=>1] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_2g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_2g_site_lkp[$site['id']] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get Nokia 2G Sites Lookup', date("Y-m-d H:i:s")];

			$BTS = array();
			$BTS_moi_lkp = array();
			$d = self::get_data(
				'ran_nokia_moi',
				[
					'id',
					'moi',
					"parent_moi_id AS p_id",
					"parameter_data ->> 'name' AS user_label",
					"moi_parts ->> 'BSC' AS bsc",
					"parameter_data ->> 'cellId' AS ci",
					"parameter_data ->> 'locationAreaIdLAC' AS lac",
					"parameter_data ->> 'bsIdentityCodeNCC' AS ncc",
					"parameter_data ->> 'bsIdentityCodeBCC' AS bcc",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$n_moc['BCF/BTS']]], "parameter_data ->> 'adminState'"=>['str'=>1]]
			);

			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) &&  isset($c['user_label']) && isset($n_bcchTRX[$c['id']]) && isset($c['lac']) && isset($_2g_site_lkp[$c['p_id']]))
					{
						$cell_name = self::convert_cell_id_format($c['user_label']);

						$skp = false;
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$bcch = $n_bcchTRX[$c['id']];
						$cell_identity_key = $c['lac'] . "_" . $c['ci'] . "_" . $bcch;
						$site_name = self::convert_to_generic($_2g_site_lkp[$c['p_id']]);

						$bsic = null;
						if (
							isset($c['ncc']) && isset($c['bcc']) &&
							($c['ncc'] >= 0 && $c['ncc'] <= 63) &&
							($c['bcc'] >= 0 && $c['bcc'] <= 63)
						) {
							// Calculate the BSIC; left shift ncc by 06 digits and add with bcc
							$bsic = ($c['ncc'] << 3) + $c['bcc'];
						}

						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'n',
							'lac'=>$c['lac'],
							'rci'=>$c['bsc'],
							'ci'=>$c['ci'],
							'bsic'=>$bsic,
							'arfcn'=>self::_ARFCN($bcch),
							'dl_freq'=>$bcch,
							'eirp'=>(isset($eirp_arr['2g'][$cell_name])) ? $eirp_arr['2g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'r'=>(isset($n_radius_2g_lkp[$c['id']])) ? $n_radius_2g_lkp[$c['id']] : null,
							'lat'=>null,
							'long'=>null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$BTS[$cell_identity_key] = $this_cell;
						if (isset($c['moi']))
						{
							$BTS_moi_lkp[$c['moi']] = $cell_identity_key;
						}
					}
				}
			}
			$d = null;
			$n_bcchTRX = null;
			$_2g_site_lkp = null;
			$n_radius_2g_lkp = null;
			$timeLog[] = ['Get Nokia 2G Cells', date("Y-m-d H:i:s")];
		/*NOKIA 3G WCEL*/
			/* NOKIA 3G Sites*/
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$n_moc['RNC/WBTS']]] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_3g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_3g_site_lkp[$site['id']] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get Nokia 3G Sites Lookup', date("Y-m-d H:i:s")];

			$WCEL = array();
			$WCEL_moi_lkp = array();
			$d = self::get_data(
				'ran_nokia_moi',
				[
					"id",
					"moi",
					"CONCAT(moi_parts ->> 'RNC','_', parameter_data ->> 'CId') AS key",
					"user_label",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'RNC' AS rnc",
					"parameter_data ->> 'CId' AS ci",
					"parameter_data ->> 'LAC' AS lac",
					"parameter_data ->> 'UARFCN' AS uarfcn",
					"parameter_data ->> 'PRACHScramblingCode' AS psc",
					"business_ref_data AS bzd"
				],
				['moc_id'=>['int'=>[$n_moc['WBTS/WCEL']]], "parameter_data ->> 'AdminCellState'"=>['str'=>1]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) &&  isset($c['ci']) && isset($c['user_label']) && isset($c['lac']) && isset($_3g_site_lkp[$c['p_id']]))
					{

						$cell_name = $c['user_label'];

						$skp = false;
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$_3g_site_lkp[$c['p_id']],
							'v'=>'n',
							'lac'=>$c['lac'],
							'ci'=>$c['ci'],
							'rci'=>$c['rnc'],
							'psc'=>$c['psc'],
							'arfcn'=>$c['uarfcn'],
							'eirp'=>(isset($eirp_arr['3g'][$cell_name])) ? $eirp_arr['3g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>(isset($n_radius_3g_lkp[$c['id']])) ? $n_radius_3g_lkp[$c['id']] : null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}
						$WCEL[$c['key']] = $this_cell;
						if(isset($c["moi"]))
							$WCEL_moi_lkp[$c["moi"]] = $c['key'];
					}
				}
			}			
			$d = null;
			$_3g_site_lkp = null;
			$n_radius_3g_lkp = null;
			$timeLog[] = ['Get Nokia 3G Cells', date("Y-m-d H:i:s")];
		/*NOKIA 4G LNCEL*/
			/* NOKIA 4G Sites*/
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$n_moc['MRBTS/LNBTS']]] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_4g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_4g_site_lkp[$site['id']] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get Nokia 4G Sites Lookup', date("Y-m-d H:i:s")];

			$LNCEL = array();
			$LNCEL_moi_lkp = array();
			// $radius_lkp = (isset($n_moc['xxxxxxxxxx']) && isset($lct['ran_nokia_kpi'])) ? self::get_nokia_4G_cell_radius($n_moc['xxxxxxxxxx'], $lct['ran_nokia_kpi']) : [];
			$timeLog[] = ['Get Nokia 4G Cells Radius Lookup', date("Y-m-d H:i:s")];

			$d = self::get_data(
				'ran_nokia_moi',
				[
					"id",
					"moi",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'LNBTS' AS enbid",
					"parameter_data ->> 'phyCellId' AS pci",
					"parameter_data ->> 'lcrId' AS ci",
					"user_label",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$n_moc['LNBTS/LNCEL']]]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['enbid']) &&  isset($c['pci']) && isset($c['ci']) && isset($_4g_site_lkp[$c['p_id']]))
					{

						$cell_name = $c['user_label'];
						$site_name = $_4g_site_lkp[$c['p_id']];

						$skp = false;
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid


						$key = $c['enbid'] . '_' . $c['pci'] . '_' . $c['ci']; 
						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'n',
							'lac'=>$c['enbid'],
							'ci'=>$c['ci'],
							'pci'=>$c['pci'],
							'arfcn'=>(isset($n_LNCEL_FDD[$c['id']])) ? $n_LNCEL_FDD[$c['id']]['earfcndl'] : ((isset($n_LNCEL_TDD[$c['id']])) ? $n_LNCEL_TDD[$c['id']]['earfcn'] : null),
							'eirp'=>(isset($eirp_arr['4g'][$cell_name])) ? $eirp_arr['4g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>'TBD',
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}
						$LNCEL[$key] = $this_cell;
						if(isset($c["moi"]))
							$LNCEL_moi_lkp[$c["moi"]] = $key;
					}
				}
			}
			$d = null;
			$n_LNCEL_FDD = null;
			$timeLog[] = ['Get Nokia 4G Cells', date("Y-m-d H:i:s")];

		/*ZTE 2G GGsmCell*/
			/* ZTE 2G Sites*/
			$d = self::get_data('ran_zte_ume_moi', ['id', "parameter_data ->> 'userLabel' AS user_label"], ['moc_id'=>['int'=>$z_moc['GBssFunction/GBtsSiteManager']]] );

			$exclude = ['test', 'lock', 'deactivated'];
			$_2g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_2g_site_lkp[$site['id']] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 2G Sites', date("Y-m-d H:i:s")];

			$GGsmCell = array();
			$GGsmCell_moi_lkp = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"CONCAT(moi_parts ->> 'GBssFunction','_', moi_parts ->> 'GBtsSiteManager','_',moi_parts ->> 'GGsmCell') AS key",
					"parameter_data ->> 'userLabel' AS user_label",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'GBssFunction' AS bsc",
					"parameter_data ->> 'cellIdentity' AS ci",
					"parameter_data ->> 'refGLocationArea' AS reflac",
					"parameter_data ->> 'bcchFrequency' AS bcch",
					"parameter_data ->> 'ncc' AS ncc",
					"parameter_data ->> 'bcc' AS bcc",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$z_moc['GBtsSiteManager/GGsmCell']]]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['ci']) &&  isset($c['user_label']) && isset($c['bcch']) && isset($c['reflac']) && isset($gLac[$c['reflac']]) && isset($_2g_site_lkp[$c['p_id']]))
					{
						$cell_name = $c['user_label'];

						$skp = false;
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid


						$lac = $gLac[$c['reflac']];
						$cell_identity_key = $lac . "_" . $c['ci'] . "_" . $c['bcch'];
						$site_name = self::convert_to_generic($_2g_site_lkp[$c['p_id']]);

						$bsic = null;
						if (
							isset($c['ncc']) && isset($c['bcc']) &&
							($c['ncc'] >= 0 && $c['ncc'] <= 63) &&
							($c['bcc'] >= 0 && $c['bcc'] <= 63)
						) {
							// Calculate the BSIC; left shift ncc by 06 digits and add with bcc
							$bsic = ($c['ncc'] << 3) + $c['bcc'];
						}

						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'z',
							'lac'=>$lac,
							'rci'=>$c['bsc'],
							'ci'=>$c['ci'],
							'bsic'=>$bsic,
							'arfcn'=>self::_ARFCN($c['bcch']),
							'dl_freq'=>$c['bcch'],
							'eirp'=>(isset($eirp_arr['2g'][$cell_name])) ? $eirp_arr['2g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'r'=>(isset($z_radius_2g_lkp[$c['id']])) ? $z_radius_2g_lkp[$c['id']] : null,
							'lat'=>null,
							'long'=>null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = self::deg_to_trig($bzd['Azimuth']);

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$GGsmCell[$cell_identity_key] = $this_cell;
						if (isset($c['key']))
						{
							$GGsmCell_moi_lkp[$c['key']] = $cell_identity_key;
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$z_radius_2g_lkp = null;
			$timeLog[] = ['Get ZTE 2G Cells Lookup', date("Y-m-d H:i:s")];
		/*ZTE 3G UUtranCellFDD */
			/* ZTE 3G Sites*/
			$d = self::get_data('ran_zte_ume_moi', ["parameter_data ->> 'userLabel' AS user_label"], ['moc_id'=>['int'=>$z_moc['ManagedElement/NodeBFunction']]] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_3g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp && isset($site_name)) {
						$_3g_site_lkp[$site_name] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 3G Sites', date("Y-m-d H:i:s")];

			$UUtranCellFDD = array();
			$UUtranCellFDD_moi_lkp = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"CONCAT(moi_parts ->> 'URncFunction','_', moi_parts ->> 'UUtranCellFDD') AS key",
					"user_label",
					"moi_parts ->> 'URncFunction' AS rnc",
					"parameter_data ->> 'cId' AS ci",
					"parameter_data ->> 'refULocationArea' AS reflac",
					"parameter_data ->> 'uarfcnDl' AS uarfcndl",
					"parameter_data ->> 'primaryScramblingCode' AS psc",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$z_moc['URncFunction/UUtranCellFDD']]], "parameter_data ->> 'AdmState'"=>['str'=>'Unblock'], "parameter_data ->> 'OperState'"=>['str'=>'Enabled']]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{

					if (isset($c['key']) && isset($c['ci'])  && isset($c['rnc']) && isset($c['user_label']) && isset($c['reflac']) && isset($uLac[$c['reflac']]))
					{

						$cell_name = $c['user_label'];
						$site_name = self::cell_to_site($cell_name);
						$cell_identity_key = $c['rnc'] . '_' . $c['ci'];
						
						$skp = false;
						if(!isset($_3g_site_lkp[$site_name])) { $skp = true; }
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'z',
							'lac'=>$uLac[$c['reflac']],
							'ci'=>$c['ci'],
							'rci'=>$c['rnc'],
							'psc'=>$c['psc'],
							'arfcn'=>$c['uarfcndl'],
							'dl_freq'=>'TBD',
							'eirp'=>(isset($eirp_arr['3g'][$cell_name])) ? $eirp_arr['3g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'r'=>(isset($z_radius_3g_lkp[$cell_name])) ? $z_radius_3g_lkp[$cell_name] : null,
							'lat'=>null,
							'long'=>null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}
						$UUtranCellFDD[$cell_identity_key] = $this_cell;
						$UUtranCellFDD_moi_lkp[$c['key']] = $cell_identity_key;
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$z_radius_3g_lkp = null;
			$timeLog[] = ['Get ZTE 3G Cells Lookup', date("Y-m-d H:i:s")];
		/*ZTE 4G CUEUtranCellFDDLTE*/
			/* ZTE 4G Sites*/
			$d = self::get_data('ran_zte_ume_moi', ["moi_parts ->> 'ENBCUCPFunction' AS enbid", "moi_parts ->> 'CULTE' AS culte", "parameter_data ->> 'userLabel' AS user_label"], ['moc_id'=>['int'=>$z_moc['ManagedElement/ENBCUCPFunction']]] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_4g_site_lkp = [];
			foreach ($d as $site) {
				$skp = false;
				if(isset($site['enbid']) && isset($site['culte']) && isset($site['user_label'])) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site['user_label']), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_4g_site_lkp[$site['enbid'] . '_' . $site['culte']] = $site['user_label'];
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (CUEUtranCellFDDLTE) Sites', date("Y-m-d H:i:s")];

			$CUEUtranCellFDDLTE = array();
			$CUEUtranCellFDDLTE_moi_lkp = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"moi_parts ->> 'ENBCUCPFunction' AS enb",
					"moi_parts ->> 'CULTE' AS culte",
					"moi_parts ->> 'CUEUtranCellFDDLTE' AS cueutrancellfddlte",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"parameter_data ->> 'earfcnDl' AS earfcndl",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$z_moc['CULTE/CUEUtranCellFDDLTE']]], "parameter_data ->> 'adminState'"=>['str'=>0], "parameter_data ->> 'operState'"=>['str'=>0]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['enb']) && isset($c['culte']) && isset($c['pci']) && isset($c['ci']))
					{
						$cell_name = $c['user_label'];
						$site_name = self::cell_to_site($cell_name);
						
						// $skp = false;
						// if(!isset($_4g_site_lkp[$site_name])) { $skp = true; }
						// foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						// if($skp) { continue; } // Skip if not valid

						$enbid = (strpos($c['enb'], '_') !== false ) ? explode('_', $c['enb'])[1] : $c['enb'];
						$key = $enbid . '_' . $c['pci'] . '_' . $c['ci'];
						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'z',
							'lac'=>$enbid,
							'ci'=>$c['ci'],
							'pci'=>$c['pci'],
							'arfcn'=>$c['earfcndl'],
							'eirp'=>(isset($eirp_arr['4g'][$cell_name])) ? $eirp_arr['4g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>(isset($z_radius_4g_CUEUtranCellFDDLTE_lkp[$c['id']])) ? $z_radius_4g_CUEUtranCellFDDLTE_lkp[$c['id']] : null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$CUEUtranCellFDDLTE[$key] = $this_cell;
						$CUEUtranCellFDDLTE_moi_lkp[$c['enb'] . '_' . $c['culte'] . '_' . $c['cueutrancellfddlte'] ] = $key;
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$z_radius_4g_CUEUtranCellFDDLTE_lkp = null;
			$timeLog[] = ['Get ZTE 4G (CUEUtranCellFDDLTE) Cells Lookup', date("Y-m-d H:i:s")];
		/*ZTE 4G EUtranCellFDD*/
			/* NOKIA 4G Sites*/
			$d = self::get_data('ran_zte_ume_moi', ['id', "parameter_data ->> 'userLabel' AS user_label"], ['moc_id'=>['int'=>$z_moc['ManagedElement/ENBFunctionFDD']]] );
			$exclude = ['test', 'lock', 'deactivated'];
			$_4g_site_lkp = [];
			foreach ($d as $site) {
				$site_name = $site['user_label'];
				$skp = false;
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_4g_site_lkp[$site['id']] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellFDD) Sites', date("Y-m-d H:i:s")];

			$EUtranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"moi_parts ->> 'ENBFunctionFDD' AS enbid",
					"parent_moi_id AS p_id",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"parameter_data ->> 'earfcnDl' AS earfcndl",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$z_moc['ENBFunctionFDD/EUtranCellFDD']]]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['enbid']) &&  isset($c['pci']) && isset($c['ci']))
					{

						$cell_name = $c['user_label'];
						$site_name = $_4g_site_lkp[$c['p_id']];

						$skp = false;
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$key = $c['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];
						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'z',
							'lac'=>$c['enbid'],
							'ci'=>$c['ci'],
							'pci'=>$c['pci'],
							'arfcn'=>$c['earfcndl'],
							'eirp'=>(isset($eirp_arr['4g'][$cell_name])) ? $eirp_arr['4g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>(isset($z_radius_4g_EUtranCellFDD_lkp[$c['id']])) ? $z_radius_4g_EUtranCellFDD_lkp[$c['id']] : null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$EUtranCellFDD[$key] = $this_cell;
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$z_radius_4g_EUtranCellFDD_lkp = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellFDD) Cells Lookup', date("Y-m-d H:i:s")];

		/*HUAWEI 2G GCELL*/
			/* HUAWEI 2G Sites*/
			$d = self::get_data('ran_huawei_mml_moi', ["moi_parts ->> 'sys_bsc' AS bsc", "parameter_data ->> 'BTSNAME' AS user_label"], ['moc_id'=>['int'=>$h_mml_moc['BSC/BTS']]] );
			$exclude = ['test', 'swap', 'noa', 'lock', 'h2z', 'n2h', 'deactivated'];
			$_2g_site_lkp = [];
			foreach ($d as $site) {
				$skp = false;
				$bsc = $site['bsc'];
				$site_name = $site['user_label'];
				if(isset($bsc) && isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_2g_site_lkp[$bsc. '_' .$site_name] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get Huawei 2G Sites Lookup', date("Y-m-d H:i:s")];

			$GCELL = array();
			$GCELL_moi_lkp = array();
			$d = self::get_data(
				'ran_huawei_mml_moi',
				[
					"moi_parts ->> 'sys_bsc' AS bsc",
					"parameter_data ->> 'CELLID' AS cid",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"parameter_data ->> 'LAC' AS lac",
					"parameter_data ->> 'CI' AS ci",
					"parameter_data ->> 'NCC' AS ncc",
					"parameter_data ->> 'BCC' AS bcc",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$h_mml_moc['BSC/GCELL']]]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if(!isset($c['bsc']) && !isset($c['cid']) ) {continue;}

					$key = $c['bsc']. "_" .$c['cid'];

					if (isset($c['ci']) && isset($c['user_label']) && isset($h_bcchTRX[$key]) && isset($c['lac']) &&!isset($h_locked_cells[$key]) )
					{
						$cell_name = $c['user_label'];
						$site_name = self::cell_to_site($cell_name);
						$site_check_key = $c['bsc']. '_' .$site_name;
						
						$skp = false;
						if(!isset($_2g_site_lkp[$site_check_key])) { $skp = true; }
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$bsic = null;
						if (
							isset($c['ncc']) && isset($c['bcc']) &&
							($c['ncc'] >= 0 && $c['ncc'] <= 63) &&
							($c['bcc'] >= 0 && $c['bcc'] <= 63)
						) {
							// Calculate the BSIC; left shift ncc by 06 digits and add with bcc
							$bsic = ($c['ncc'] << 3) + $c['bcc'];
						}

						$s_key = $c['lac'] . "_" . $c['ci'] . "_" . $h_bcchTRX[$key];
						$this_cell = [
							'l'=>$c['user_label'],
							'sid'=>$site_name,
							'v'=>'h',
							'lac'=>$c['lac'],
							'rci'=>'Logic TBD',
							'ci'=>$c['ci'],
							'bsic'=>$bsic,
							'arfcn'=>self::_ARFCN($h_bcchTRX[$key]),
							'dl_freq'=>$h_bcchTRX[$key],
							'eirp'=>(isset($eirp_arr['2g'][$c['user_label']])) ? $eirp_arr['2g'][$c['user_label']] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'r'=> (isset($h_radius_2g_lkp6900[$c['user_label']])) ? $h_radius_2g_lkp6900[$c['user_label']] : ((isset($h_radius_2g_lkp6910[$c['user_label']])) ? $h_radius_2g_lkp6910[$c['user_label']] : ''),
							'lat'=>null,
							'long'=>null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$GCELL[$s_key] = $this_cell;

						$GCELL_moi_lkp[$key] = $s_key;

					}
				}
			}
			$d = null;
			$_2g_site_lkp = null;
			$h_radius_2g_lkp6910 = null;
			$h_radius_2g_lkp6900 = null;
			$timeLog[] = ['Get Huawei 2G Cells', date("Y-m-d H:i:s")];

		/*HUAWEI 3G UCELLSETUP*/
			/* HUAWEI 3G Sites*/
			$d = self::get_data('ran_huawei_mml_moi', ["parameter_data ->> 'NODEBFUNCTIONNAME' AS user_label"], ['moc_id'=>['int'=>$h_mml_moc['BTS3900/NODEBFUNCTION']]] );
			$exclude = ['test', 'swap', 'noa', 'lock', 'h2z', 'n2h', 'deactivated'];
			$_3g_site_lkp = [];
			foreach ($d as $site) {
				$skp = false;
				$site_name = $site['user_label'];
				if(isset($site_name)) {
					foreach ($exclude as $v) {
						if(strpos(strtolower($site_name), $v) !== false ) {
							$skp = true;
							break;
						}	
					}
					if(!$skp) {
						$_3g_site_lkp[$site_name] = $site_name;
					}
				}
			}
			$d = null;
			$timeLog[] = ['Get Huawei 3G Sites Lookup', date("Y-m-d H:i:s")];

			$UCELLSETUP = array();
			$UCELLSETUP_moi_lkp = array();
			$d = self::get_data(
				'ran_huawei_mml_moi',
				[
					"CONCAT(parameter_data ->> 'LOGICRNCID', '_', parameter_data ->> 'CELLID') AS key",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"parameter_data ->> 'LOGICRNCID' AS rnc",
					"parameter_data ->> 'CELLID' AS ci",
					"parameter_data ->> 'LAC' AS hexlac",
					"parameter_data ->> 'UARFCNDOWNLINK' AS uarfcndl",
					"parameter_data ->> 'PSCRAMBCODE' AS psc",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$h_mml_moc['RNC/UCELLSETUP']]]]
			);		
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) &&  isset($c['ci']) && isset($c['user_label']) && isset($c['hexlac']))
					{

						$cell_name = $c['user_label'];
						$site_name = self::cell_to_site($cell_name);
						
						$skp = false;
						if(!isset($_3g_site_lkp[$site_name])) { $skp = true; }
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid


						$lac = hexdec( str_replace("H'", '', $c['hexlac']));
						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'h',
							'lac'=>$lac,
							'ci'=>$c['ci'],
							'rci'=>$c['rnc'],
							'psc'=>$c['psc'],
							'arfcn'=>$c['uarfcndl'],
							'eirp'=>(isset($eirp_arr['3g'][$cell_name])) ? $eirp_arr['3g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>(isset($h_radius_3g_lkp[$c['key']])) ? $h_radius_3g_lkp[$c['key']] : null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$UCELLSETUP[$c['key']] = $this_cell;
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$h_radius_3g_lkp = null;
			$timeLog[] = ['Get Huawei 3G Cells', date("Y-m-d H:i:s")];
		/*HUAWEI 4G Cell*/
			$exclude = ['test', 'swap', 'noa', 'lock', 'h2z', 'n2h', 'deactivated'];
			$Cell = array();
			$d = self::get_data(
				'ran_huawei_mml_moi',
				[
					"parameter_data ->> 'PhyCellId' AS pci",
					"parameter_data ->> 'CellId' AS ci",
					"parameter_data ->> 'CellName' AS user_label",
					"parameter_data ->> 'DlEarfcn' AS earfcndl",
					"business_ref_data AS bzd",
				],
				['moc_id'=>['int'=>[$h_mml_moc['BTS3900/Cell']]]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['pci']) && isset($c['ci']))
					{
						$cell_name = $c['user_label'];
						$site_name = self::cell_to_site($cell_name);
						
						$skp = false;
						if(!isset($h_eNodeBFunction[$site_name])) { $skp = true; }
						foreach ($exclude as $v) {if(strpos(strtolower($cell_name), $v) !== false ) {$skp = true; break; } }
						if($skp) { continue; } // Skip if not valid

						$enbid = $h_eNodeBFunction[$site_name];
						$key = $enbid . '_' . $c['pci'] . '_' . $c['ci'];
						$this_cell = [
							'l'=>$cell_name,
							'sid'=>$site_name,
							'v'=>'h',
							'lac'=>$enbid,
							'ci'=>$c['ci'],
							'pci'=>$c['pci'],
							'arfcn'=>$c['earfcndl'],
							'eirp'=>(isset($eirp_arr['4g'][$cell_name])) ? $eirp_arr['4g'][$cell_name] : null,
							'g_h'=>(isset($elevation_arr[$site_name])) ? $elevation_arr[$site_name] : null,
							'lat'=>null,
							'long'=>null,
							'r'=>(isset($h_radius_4g_lkp[$cell_name])) ? $h_radius_4g_lkp[$cell_name] : null,
							'a_s'=>null,
							'a_e'=>null,
							'a_o'=>null,
							'a_op'=>null,
							'height'=>null,
							'tilt'=>null,
						];

						if(isset($c['bzd']))
						{
							$bzd = json_decode($c['bzd'], TRUE);
							if(isset($bzd['lat']) && is_numeric($bzd['lat']))
								$this_cell['lat'] = $bzd['lat'];

							if(isset($bzd['long']) && is_numeric($bzd['long']))
								$this_cell['long'] = $bzd['long'];

							if(isset($bzd['M-Tilt']) && is_numeric($bzd['M-Tilt']))
								$this_cell['tilt'] = $bzd['M-Tilt'];

							if(isset($bzd['Azimuth']) && is_numeric($bzd['Azimuth']))
								$this_cell['a_o'] = $bzd['Azimuth'];

							if(isset($bzd['Altitude']) && is_numeric($bzd['Altitude']))
								$this_cell['height'] = $bzd['Altitude'];
						}

						$Cell[$key] = $this_cell;
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$h_radius_4g_lkp = null;
			$timeLog[] = ['Get Huawei 4G Cells', date("Y-m-d H:i:s")];

		/*ZTE 4G-2G ITBBU EXTERNAL RELATION ExternalGsmCellLTE*/
			$ExternalGsmCellLTE = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'ENBCUCPFunction','_',moi_parts ->> 'CULTE','_',moi_parts ->> 'NbrCell','_',moi_parts ->> 'ExternalGsmCellLTE') AS key", "parameter_data ->> 'lac' AS lac", "parameter_data ->> 'cellIdentity' AS ci", "parameter_data ->> 'bcchFrequency' AS bcch"],
				['moc_id'=>['int'=>$z_moc['NbrCell/ExternalGsmCellLTE']]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['lac']) && isset($c['ci']) && isset($c['bcch']))
					{
						$ExternalGsmCellLTE[ $c['key'] ] = $c['lac'] . "_" . $c['ci'] . "_" . $c['bcch'];
					}
				}
			}
			$d = null;
		/*ZTE 4G-2G ITBBU RELATION GsmRelationFDDLTE*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'ENBCUCPFunction','_', moi_parts ->> 'CULTE','_', moi_parts ->> 'CUEUtranCellFDDLTE') AS key", "parameter_data ->> 'refExternalGsmCellLTE' AS rec"],
				['moc_id'=>['int'=>$z_moc['NeighbouringRelationFDD/EUtranRelationFDDLTE']]]
			);
			
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) && isset($c['rec']))
					{
						$d_key = $c['rec'];
						$d_cell = null;
						if (isset($CUEUtranCellFDDLTE_moi_lkp[$c['key']]) && isset($CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]))
						{
							if (isset($ExternalGsmCellLTE[$d_key]) && isset($BTS[$ExternalGsmCellLTE[$d_key]])) {
								$d_cell = $BTS[$ExternalGsmCellLTE[$d_key]];
							}
							else if (isset($ExternalGsmCellLTE[$d_key]) && isset($GCELL[$ExternalGsmCellLTE[$d_key]])) {
								$d_cell = $GCELL[$ExternalGsmCellLTE[$d_key]];
							}
							else if (isset($ExternalGsmCellLTE[$d_key]) && isset($GGsmCell[$ExternalGsmCellLTE[$d_key]])) {
								$d_cell = $GGsmCell[$ExternalGsmCellLTE[$d_key]];
							}
						}
						if($d_cell) {
							$CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g ext"];
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$ExternalGsmCellLTE = null;

		/*HUAWEI NBRs:----------------------------------------------------*/
		/*HUAWEI 2G-2G EXTERNAL RELATION*/
			$GEXT2GCELL = array();
			$d = self::get_data('ran_huawei_mml_moi', ["CONCAT(moi_parts ->> 'sys_bsc', '_', parameter_data ->> 'EXT2GCELLID' ) AS key", "parameter_data ->> 'CI' AS ci", "parameter_data ->> 'LAC' AS lac", "parameter_data ->> 'BCCH' AS bcch", ], ['moc_id'=>['int'=>$h_mml_moc['BSC/GEXT2GCELL']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) && isset($c['bcch']) && isset($c['lac']) && isset($c['key']))
					{
						$GEXT2GCELL[ $c['key'] ] = $c['lac'] . "_" . $c['ci'] . "_" . $c['bcch'];
					}
				}
			}
			$d = null;
		/*HUAWEI 2G-2G RELATION*/
			$d = self::get_data( 'ran_huawei_mml_moi', [ "moi_parts ->> 'sys_bsc' as bsc", "parameter_data ->> 'SRC2GNCELLID' AS s", "parameter_data ->> 'NBR2GNCELLID' AS n", "parameter_data ->> 'ISNCELL' AS f"], ['moc_id'=>['int'=>$h_mml_moc['BSC/G2GNCELL']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if(isset($c['bsc']) && isset($c['s']) && isset( $c['n'] ))
					{
						$s_key = $c['bsc']."_".$c['s'];
						$n_key = $c['bsc']."_".$c['n'];
						if(isset($GCELL_moi_lkp[$s_key]))
						{
							$d_cell = null;
							if(isset($c['f']) && $c['f'] == 'INNCELL' && isset($GCELL_moi_lkp[$n_key]) && isset($GCELL[$GCELL_moi_lkp[$s_key]]) && isset($GCELL[$GCELL_moi_lkp[$n_key]])) {
								$d_cell = $GCELL[$GCELL_moi_lkp[$n_key]];

							} else if(isset($GEXT2GCELL[$n_key])) {
								
								$d_key = $GEXT2GCELL[$n_key];
								
								if ( isset($BTS[$d_key])) {
									$d_cell = $BTS[$d_key];
								} else if (isset($GGsmCell[$d_key])) {
									$d_cell = $GGsmCell[$d_key];
								} else if (isset($GCELL[$d_key])) {
									$d_cell = $GCELL[$d_key];
								}
							}
							if($d_cell) {
								$GCELL[$GCELL_moi_lkp[$s_key]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g"];
							}
						}
					}
				}
			}
			$d = null;
			$GEXT2GCELL = null;

		/*HUAWEI 2G-3G EXTERNAL RELATION*/
			$GEXT3GCELL = array();
			$d = self::get_data('ran_huawei_mml_moi', ["CONCAT(moi_parts ->> 'sys_bsc', '_', parameter_data ->> 'EXT3GCELLID' ) AS key", "parameter_data ->> 'CI' AS ci", "parameter_data ->> 'RNCID' AS rncid"], ['moc_id'=>['int'=>$h_mml_moc['BSC/GEXT3GCELL']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['rncid']) && isset($c['ci']))
					{
						$GEXT3GCELL[ $c['key'] ] = $c['rncid'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*HUAWEI 2G-3G RELATION*/
			$d = self::get_data( 'ran_huawei_mml_moi', [ "moi_parts ->> 'sys_bsc' as bsc", "parameter_data ->> 'SRC3GNCELLID' AS s", "parameter_data ->> 'NBR3GNCELLID' AS n"], ['moc_id'=>['int'=>$h_mml_moc['BSC/G3GNCELL']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if(isset($c['bsc']) && isset($c['s']) && isset( $c['n'] ))
					{
						$s_key = $c['bsc']."_".$c['s'];
						$n_key = $c['bsc']."_".$c['n'];
						if(isset($GCELL_moi_lkp[$s_key]))
						{
							$d_cell = null;
							if(isset($GEXT3GCELL[$n_key])) {
								$d_key = $GEXT3GCELL[$n_key];
								
								if ( isset($WCEL[$d_key])) {
									$d_cell = $WCEL[$d_key];
								} else if (isset($UUtranCellFDD[$d_key])) {
									$d_cell = $UUtranCellFDD[$d_key];
								} else if (isset($UCELLSETUP[$d_key])) {
									$d_cell = $UCELLSETUP[$d_key];
								}
							}
							if($d_cell) {
								$GCELL[$GCELL_moi_lkp[$s_key]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 3g"];
							}
						}
					}
				}
			}
			$d = null;
			$GEXT3GCELL = null;

		/*HUAWEI 2G-4G EXTERNAL RELATION*/
			$GEXTLTECELL = array();
			$d = self::get_data('ran_huawei_mml_moi', ["CONCAT(moi_parts ->> 'sys_bsc', '_', parameter_data ->> 'EXTLTECELLID' ) AS key", "parameter_data ->> 'CI' AS ci", "parameter_data ->> 'PCID' AS pci", "parameter_data ->> 'EXTLTECELLNAME' AS cellname"], ['moc_id'=>['int'=>$h_mml_moc['BSC/GEXTLTECELL']]] );
			if (count($d)) {
				foreach ($d as $c) {
					if (isset($c['key']) && isset($c['ci']) && isset($c['pci']) && isset($c['cellname'])) {
						$cell_exp = explode("_", $c['cellname']);
						if(count($cell_exp) > 2 ) {
							$enbname = $cell_exp[0] .'_'. $cell_exp[1];
							if (isset($h_eNodeBFunction[$enbname])) {
								$GEXTLTECELL[ $c['key'] ] = $h_eNodeBFunction[$enbname] . "_" . $c['pci'] . "_" . $cell_exp[2];
							} else if (isset($z_ENBFunctionFDD[$enbname])) {
								$GEXTLTECELL[ $c['key'] ] = $z_ENBFunctionFDD[$enbname] . "_" . $c['pci'] . "_" . $cell_exp[2];
							} else if (isset($z_ENBFunctionTDD[$enbname])) {
								$GEXTLTECELL[ $c['key'] ] = $z_ENBFunctionTDD[$enbname] . "_" . $c['pci'] . "_" . $cell_exp[2];
							} else if (isset($n_LNBTS[$enbname])) {
								$GEXTLTECELL[ $c['key'] ] = $n_LNBTS[$enbname] . "_" . $c['pci'] . "_" . $cell_exp[2];
							}
						}
					}
				}
			}
			$d = null;
		/*HUAWEI 2G-4G RELATION*/
			$d = self::get_data( 'ran_huawei_mml_moi', [ "moi_parts ->> 'sys_bsc' as bsc", "parameter_data ->> 'SRCLTENCELLID' AS s", "parameter_data ->> 'NBRLTENCELLID' AS n"], ['moc_id'=>['int'=>$h_mml_moc['BSC/GLTENCELL']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if(isset($c['bsc']) && isset($c['s']) && isset( $c['n'] ))
					{
						$s_key = $c['bsc']."_".$c['s'];
						$n_key = $c['bsc']."_".$c['n'];
						if(isset($GCELL_moi_lkp[$s_key]))
						{
							$d_cell = null;
							if(isset($GEXTLTECELL[$n_key])) {
								$d_key = $GEXTLTECELL[$n_key];
								
								if ( isset($LNCEL[$d_key])) {
									$d_cell = $LNCEL[$d_key];
								} else if (isset($CUEUtranCellFDDLTE[$d_key])) {
									$d_cell = $CUEUtranCellFDDLTE[$d_key];
								} else if (isset($EUtranCellFDD[$d_key])) {
									$d_cell = $EUtranCellFDD[$d_key];
								} else if (isset($Cell[$d_key])) {
									$d_cell = $Cell[$d_key];
								}
							}
							if($d_cell) {
								$GCELL[$GCELL_moi_lkp[$s_key]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g"];
							}
						}
					}
				}
			}
			$d = null;
			$GEXTLTECELL = null;

		/*NOKIA NBRs:----------------------------------------------------*/
		/*NOKIA 2G-2G EXTERNAL RELATION*/
			$EXGCE = array();
			$d = self::get_data( 'ran_nokia_moi', [ 'moi', "parameter_data ->> 'cellIdentity' AS ci", "parameter_data ->> 'lac' AS lac", "parameter_data ->> 'bcchFrequency' AS bcch", ], ['moc_id'=>['int'=>$n_moc['EXCCG/EXGCE']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) && isset($c['bcch']) && isset($c['lac']) && isset($c['moi']))
					{
						$EXGCE[ $c['moi'] ] = $c['lac'] . "_" . $c['ci'] . "_" . $c['bcch'];
					}
				}
			}
			$d = null;
		/*NOKIA 2G-2G RELATION*/
			$d = self::get_data( 'ran_nokia_moi', [ 'moi', "parameter_data ->> 'targetCellDN' AS d_key" ], ['moc_id'=>['int'=>$n_moc['BTS/ADCE']]] );
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					$s_key = (isset($c['moi']) && strpos($c['moi'], '/ADCE-') !== false ) ? explode('/ADCE-', $c['moi'])[0] : null;
					if (!is_null($s_key) && isset($BTS_moi_lkp[$s_key]) && isset($BTS[$BTS_moi_lkp[$s_key]]) && isset($c['d_key']))
					{
						$s_key = $BTS_moi_lkp[$s_key];
						if (isset($BTS_moi_lkp[$c['d_key']]) && isset($BTS[$BTS_moi_lkp[$c['d_key']]]))
						{
							$BTS[$s_key]['nbr'][] = ['l'=>$BTS[$BTS_moi_lkp[$c['d_key']]]['l'], 'v'=>$BTS[$BTS_moi_lkp[$c['d_key']]]['v']];
						}
						else if (isset($EXGCE[$c['d_key']]))
						{
							$d_key = $EXGCE[$c['d_key']];
							if ( isset($BTS[$d_key]))
							{
								$BTS[$s_key]['nbr'][] = ['l'=>$BTS[$d_key]['l'], 'v'=>$BTS[$d_key]['v']];
							}
							else if (isset($GGsmCell[$d_key]))
							{
								$BTS[$s_key]['nbr'][] = ['l'=>$GGsmCell[$d_key]['l'], 'v'=>$GGsmCell[$d_key]['v']];
							}
							else if (isset($GCELL[$d_key]))
							{
								$BTS[$s_key]['nbr'][] = ['l'=>$GCELL[$d_key]['l'], 'v'=>$GCELL[$d_key]['v']];
							}
						}
					}

				}
				unset($d[$k]);
			}
			$d = null;
			$EXGCE = null;

		/*NOKIA 2G-3G EXTERNAL RELATION*/
			$EXUCE = array();
			$d = self::get_data( 'ran_nokia_moi', [ 'moi', "parameter_data ->> 'cId' AS ci", "parameter_data ->> 'rncId' AS rncid" ], ['moc_id'=>['int'=>$n_moc['EXCCU/EXUCE']]] );
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) && isset($c['moi']) && isset($c['rncid']))
					{
						$EXUCE[ $c['moi'] ] = $c['rncid'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*NOKIA 2G-3G RELATION*/
			$d = self::get_data( 'ran_nokia_moi', [ 'moi', "parameter_data ->> 'targetCellDN' AS d_key" ], ['moc_id'=>['int'=>$n_moc['BTS/ADJW']]] );
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					$s_key = (isset($c['moi']) && strpos($c['moi'], '/ADJW-')!== false) ? explode('/ADJW-', $c['moi'])[0] : null;
					if (!is_null($s_key) && isset($BTS_moi_lkp[$s_key]) && isset($BTS[$BTS_moi_lkp[$s_key]]) && isset($c['d_key']))
					{
						$s_key = $BTS_moi_lkp[$s_key];
						$d_cell = null;
						
						if (strpos($c['moi'], '/WCEL-')!== false && isset($WCEL_moi_lkp[$c['d_key']]) && isset($WCEL[$WCEL_moi_lkp[$c['d_key']]]))
						{
							$d_cell = $WCEL[$WCEL_moi_lkp[$c['d_key']]];
						}
						else if (isset($EXUCE[$c['d_key']]))
						{
							$d_key = $EXUCE[$c['d_key']];
							if ( isset($WCEL[$d_key])) {
								$d_cell = $WCEL[$d_key];
							}
							else if (isset($UUtranCellFDD[$d_key])) {
								$d_cell = $UUtranCellFDD[$d_key];
							}
							else if (isset($UCELLSETUP[$d_key])) {
								$d_cell = $UCELLSETUP[$d_key];
							}
						}
						if($d_cell)
							$BTS[$s_key]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']];
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$EXUCE = null;

		/*NOKIA 2G-4G EXTERNAL RELATION*/
			// TBD it was advised in the discussions about NBR that 4G-4G nbrs are not required
		/*NOKIA 2G-4G RELATION*/
			// TBD it was advised in the discussions about NBR that 4G-4G nbrs are not required

		/*ZTE NBRs:----------------------------------------------------*/
		/*ZTE 2G-2G EXTERNAL RELATION*/
			$GExternalGsmCell = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_',moi_parts ->> 'GExternalGsmCell') AS key", "parameter_data ->> 'cellIdentity' AS ci", "parameter_data ->> 'lac' AS lac", "parameter_data ->> 'bcchFrequency' AS bcch"],
				['moc_id'=>['int'=>$z_moc['GBssFunction/GExternalGsmCell']]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) && isset($c['bcch']) && isset($c['lac']) && isset($c['key']))
					{
						$GExternalGsmCell[ $c['key'] ] = $c['lac'] . "_" . $c['ci'] . "_" . $c['bcch'];
					}
				}
			}
			$d = null;
		/*ZTE 2G-2G RELATION*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_', moi_parts ->> 'GBtsSiteManager','_',moi_parts ->> 'GGsmCell') AS key", "parameter_data ->> 'refGGsmCell' AS rc","parameter_data ->> 'refGExternalGsmCell' AS rec"],
				['moc_id'=>['int'=>$z_moc['GGsmCell/GGsmRelation']]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']))
					{
						if (isset($c['rc']))
						{
							$x_moi = self::get_topology_ids([$c['rc'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['GBssFunction']) && isset($x_moi['GBtsSiteManager']) && isset($x_moi['GGsmCell']))
							{
								$d_key = $x_moi['GBssFunction'] . "_" . $x_moi['GBtsSiteManager'] . "_" . $x_moi['GGsmCell'];

								if (isset($GGsmCell_moi_lkp[$c['key']]) && isset($GGsmCell[$GGsmCell_moi_lkp[$c['key']]]) && isset($GGsmCell_moi_lkp[$d_key]) && isset($GGsmCell[$GGsmCell_moi_lkp[$d_key]]))
								{
									$GGsmCell[$GGsmCell_moi_lkp[$c['key']]]['nbr'][] = [$GGsmCell[$GGsmCell_moi_lkp[$d_key]]['l'], 'v'=>$GGsmCell[$GGsmCell_moi_lkp[$d_key]]['v']." 2g"];
								}
							}
						}
						else if (isset($c['rec']))
						{
							$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['GBssFunction']) && isset($x_moi['GExternalGsmCell']))
							{
								$d_key = $x_moi['GBssFunction'] . "_" . $x_moi['GExternalGsmCell'];
								$d_cell = null;
								if (isset($GGsmCell_moi_lkp[$c['key']]) && isset($GGsmCell[$GGsmCell_moi_lkp[$c['key']]]))
								{
									if (isset($GExternalGsmCell[$d_key]) && isset($BTS[$GExternalGsmCell[$d_key]])) {
										$d_cell = $BTS[$GExternalGsmCell[$d_key]];
									}
									else if (isset($GExternalGsmCell[$d_key]) && isset($GCELL[$GExternalGsmCell[$d_key]])) {
										$d_cell = $GCELL[$GExternalGsmCell[$d_key]];
									}
									else if (isset($GExternalGsmCell[$d_key]) && isset($GGsmCell[$GExternalGsmCell[$d_key]])) {
										$d_cell = $GGsmCell[$GExternalGsmCell[$d_key]];
									}
								}
								if($d_cell) {
									$GGsmCell[$GGsmCell_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 2g ext"];
								}
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$GExternalGsmCell = null;

		/*ZTE 2G-3G EXTERNAL RELATION*/
			$GExternalUtranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_',moi_parts ->> 'GExternalUtranCellFDD') AS key", "parameter_data ->> 'ci' AS ci", "parameter_data ->> 'rnc_id' AS rncid"],
				['moc_id'=>['int'=>$z_moc['GBssFunction/GExternalUtranCellFDD']]]
			);
			if (count($d)) {
				foreach ($d as $c) {
					if (isset($c['key']) && isset($c['rncid']) && isset($c['ci'])) {
						$GExternalUtranCellFDD[ $c['key'] ] = $c['rncid'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*ZTE 2G-3G RELATION*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_', moi_parts ->> 'GBtsSiteManager','_',moi_parts ->> 'GGsmCell') AS key", "parameter_data ->> 'refGExternalUtranCellFDD' AS rec"],
				['moc_id'=>['int'=>$z_moc['GGsmCell/GUtranRelation']]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) && isset($c['rec']))
					{
						$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
						if (isset($x_moi['GBssFunction']) && isset($x_moi['GExternalUtranCellFDD']))
						{
							$s_key = $c['key'];
							$d_key = $x_moi['GBssFunction'] . "_" . $x_moi['GExternalUtranCellFDD'];
							$d_cell = null;
							
							if (isset($GGsmCell_moi_lkp[$s_key]) && isset($GGsmCell[$GGsmCell_moi_lkp[$s_key]]))
							{
								if (isset($GExternalUtranCellFDD[$d_key]) && isset($WCEL[$GExternalUtranCellFDD[$d_key]])) {
									$d_cell = $WCEL[$GExternalUtranCellFDD[$d_key]];
								}
								else if (isset($GExternalUtranCellFDD[$d_key]) && isset($UCELLSETUP[$GExternalUtranCellFDD[$d_key]])) {
									$d_cell = $UCELLSETUP[$GExternalUtranCellFDD[$d_key]];
								}
								else if (isset($GExternalUtranCellFDD[$d_key]) && isset($UUtranCellFDD[$GExternalUtranCellFDD[$d_key]])) {
									$d_cell = $UUtranCellFDD[$GExternalUtranCellFDD[$d_key]];
								}
							}
							if($d_cell) {
								$GGsmCell[$GGsmCell_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 3g"];
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$GExternalUtranCellFDD = null;

		/*ZTE 2G-4G EXTERNAL RELATION*/
			$GExternalEutranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_',moi_parts ->> 'GExternalEutranCellFDD') AS rec", "parameter_data ->> 'enbId' AS enbid", "parameter_data ->> 'PCID' AS pci", "parameter_data ->> 'EUTRANID' AS ci"],
				['moc_id'=>['int'=>$z_moc['GBssFunction/GExternalEutranCellFDD']]]
			);
			if (count($d)) {
				foreach ($d as $c) {
					if (isset($c['rec']) && isset($c['enbid']) && isset($c['pci']) && isset($c['ci'])) {
						$GExternalEutranCellFDD[ $c['rec'] ] = $c['enbid'] . "_" . $c['pci'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*ZTE 2G-4G RELATION*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'GBssFunction','_', moi_parts ->> 'GBtsSiteManager','_',moi_parts ->> 'GGsmCell') AS key", "parameter_data ->> 'refGExternalEutranCellFDD' AS rec"],
				['moc_id'=>['int'=>$z_moc['GGsmCell/GEutranRelation']]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) && isset($c['rec']))
					{
						$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
						if (isset($x_moi['GBssFunction']) && isset($x_moi['GExternalEutranCellFDD']))
						{
							$s_key = $c['key'];
							$d_key = $x_moi['GBssFunction'] . "_" . $x_moi['GExternalEutranCellFDD'];
							$d_cell = null;
							
							if (isset($GGsmCell_moi_lkp[$s_key]) && isset($GGsmCell[$GGsmCell_moi_lkp[$s_key]]))
							{
								if (isset($GExternalEutranCellFDD[$d_key]) && isset($LNCEL[$GExternalEutranCellFDD[$d_key]])) {
									$d_cell = $LNCEL[$GExternalEutranCellFDD[$d_key]];
								}
								else if (isset($GExternalEutranCellFDD[$d_key]) && isset($CUEUtranCellFDDLTE[$GExternalEutranCellFDD[$d_key]])) {
									$d_cell = $CUEUtranCellFDDLTE[$GExternalEutranCellFDD[$d_key]];
								}
								else if (isset($GExternalEutranCellFDD[$d_key]) && isset($EUtranCellFDD[$GExternalEutranCellFDD[$d_key]])) {
									$d_cell = $EUtranCellFDD[$GExternalEutranCellFDD[$d_key]];
								}
								else if (isset($GExternalEutranCellFDD[$d_key]) && isset($Cell[$GExternalEutranCellFDD[$d_key]])) {
									$d_cell = $Cell[$GExternalEutranCellFDD[$d_key]];
								}
							}

							if($d_cell) {
								$GGsmCell[$GGsmCell_moi_lkp[$s_key]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g"];
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$GExternalEutranCellFDD = null;

		/*ZTE 3G-3G EXTERNAL RELATION UExternalUtranCellFDD*/
			$UExternalUtranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'URncFunction','_',moi_parts ->> 'UExternalRncFunction','_',moi_parts ->> 'UExternalUtranCellFDD') AS key", "parameter_data ->> 'rncId' AS rnc", "parameter_data ->> 'cId' AS ci"],
				['moc_id'=>['int'=>$z_moc['UExternalRncFunction/UExternalUtranCellFDD']]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['rnc']) && isset($c['ci']))
					{
						$UExternalUtranCellFDD[ $c['key'] ] = $c['rnc'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*ZTE 3G-3G RELATION UUtranRelation*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'URncFunction','_', moi_parts ->> 'UUtranCellFDD') AS key", "parameter_data ->> 'refUUtranCellFDD' AS rc","parameter_data ->> 'refUExternalUtranCellFDD' AS rec"],
				['moc_id'=>['int'=>$z_moc['UUtranCellFDD/UUtranRelation']]]
			);

			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']))
					{
						if (isset($c['rc']))
						{
							$x_moi = self::get_topology_ids([$c['rc'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['URncFunction']) && isset($x_moi['UUtranCellFDD']))
							{
								$d_key = $x_moi['URncFunction'] . "_" . $x_moi['UUtranCellFDD'];

								if (isset($UUtranCellFDD_moi_lkp[$c['key']]) && isset($UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]) && isset($UUtranCellFDD_moi_lkp[$d_key]) && isset($UUtranCellFDD[$UUtranCellFDD_moi_lkp[$d_key]]))
								{
									$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]['nbr'][] = [$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$d_key]]['l'], 'v'=>$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$d_key]]['v']." 3g"];
								}
							}
						}
						else if (isset($c['rec']))
						{
							$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['URncFunction']) && isset($x_moi['UExternalRncFunction']) && isset($x_moi['UExternalUtranCellFDD']))
							{
								$d_key = $x_moi['URncFunction'] . "_" . $x_moi['UExternalRncFunction'] . "_" . $x_moi['UExternalUtranCellFDD'];
								$d_cell = null;
								if (isset($UUtranCellFDD_moi_lkp[$c['key']]) && isset($UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]))
								{
									if (isset($UExternalUtranCellFDD[$d_key]) && isset($WCEL[$UExternalUtranCellFDD[$d_key]])) {
										$d_cell = $WCEL[$UExternalUtranCellFDD[$d_key]];
									}
									else if (isset($UExternalUtranCellFDD[$d_key]) && isset($UCELLSETUP[$UExternalUtranCellFDD[$d_key]])) {
										$d_cell = $UCELLSETUP[$UExternalUtranCellFDD[$d_key]];
									}
									else if (isset($UExternalUtranCellFDD[$d_key]) && isset($UUtranCellFDD[$UExternalUtranCellFDD[$d_key]])) {
										$d_cell = $UUtranCellFDD[$UExternalUtranCellFDD[$d_key]];
									}
								}
								if($d_cell) {
									$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 3g ext"];
								}
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$UExternalUtranCellFDD = null;

		/*ZTE 3G-4G EXTERNAL RELATION UExternalEUtranCellFDD*/
			$UExternalEUtranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"CONCAT('URncFunction=',moi_parts ->> 'URncFunction',',UExternalENBFunction=',moi_parts ->> 'UExternalENBFunction',',UExternalEUtranCellFDD=',moi_parts ->> 'UExternalEUtranCellFDD') AS key",
					"moi_parts ->> 'UExternalENBFunction' AS enbid",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellIdentity' AS ci"
				],
				['moc_id'=>['int'=>$z_moc['UExternalENBFunction/UExternalEUtranCellFDD']]]
			);

			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['enbid']) && isset($c['pci']) && isset($c['ci']))
					{
						$UExternalEUtranCellFDD[ $c['key'] ] = $c['enbid'] . "_" . $c['pci'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*ZTE 3G-4G RELATION UEUtranRelation*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'URncFunction','_', moi_parts ->> 'UUtranCellFDD') AS key","parameter_data ->> 'refUExternalEUtranCellFDD' AS rec"],
				['moc_id'=>['int'=>$z_moc['UUtranCellFDD/UEUtranRelation']]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) && isset($c['rec']))
					{
						$d_key = $c['rec'];
						$d_cell = null;
						if (isset($UUtranCellFDD_moi_lkp[$c['key']]) && isset($UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]))
						{
							if (isset($UExternalEUtranCellFDD[$d_key]) && isset($LNCEL[$UExternalEUtranCellFDD[$d_key]])) {
								$d_cell = $LNCEL[$UExternalEUtranCellFDD[$d_key]];
							}
							else if (isset($UExternalEUtranCellFDD[$d_key]) && isset($Cell[$UExternalEUtranCellFDD[$d_key]])) {
								$d_cell = $Cell[$UExternalEUtranCellFDD[$d_key]];
							}
							else if (isset($UExternalEUtranCellFDD[$d_key]) && isset($CUEUtranCellFDDLTE[$UExternalEUtranCellFDD[$d_key]])) {
								$d_cell = $CUEUtranCellFDDLTE[$UExternalEUtranCellFDD[$d_key]];
							}
							else if (isset($UExternalEUtranCellFDD[$d_key]) && isset($EUtranCellFDD[$UExternalEUtranCellFDD[$d_key]])) {
								$d_cell = $EUtranCellFDD[$UExternalEUtranCellFDD[$d_key]];
							}
						}
						if($d_cell) {
							$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g ext"];
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$UExternalEUtranCellFDD = null;

		/*ZTE 3G-2G EXTERNAL RELATION UExternalGsmCell*/
			$UExternalGsmCell = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'URncFunction','_',moi_parts ->> 'UExternalGsmCell') AS key", "parameter_data ->> 'lac' AS lac", "parameter_data ->> 'cellIdentity' AS ci", "parameter_data ->> 'bcchFrequency' AS bcch"],
				['moc_id'=>['int'=>$z_moc['URncFunction/UExternalGsmCell']]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['lac']) && isset($c['ci']) && isset($c['bcch']))
					{
						$UExternalGsmCell[ $c['key'] ] = $c['lac'] . "_" . $c['ci'] . "_" . $c['bcch'];
					}
				}
			}
			$d = null;
		/*ZTE 3G-2G RELATION UGsmRelation*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'URncFunction','_', moi_parts ->> 'UUtranCellFDD') AS key","parameter_data ->> 'refUExternalGsmCell' AS rec"],
				['moc_id'=>['int'=>$z_moc['UUtranCellFDD/UGsmRelation']]]
			);

			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']) && isset($c['rec']))
					{
						$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
						if (isset($x_moi['URncFunction']) && isset($x_moi['UExternalGsmCell']))
						{
							$d_key = $x_moi['URncFunction'] . "_" . $x_moi['UExternalGsmCell'];
							$d_cell = null;
							if (isset($UUtranCellFDD_moi_lkp[$c['key']]) && isset($UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]))
							{
								if (isset($UExternalGsmCell[$d_key]) && isset($BTS[$UExternalGsmCell[$d_key]])) {
									$d_cell = $BTS[$UExternalGsmCell[$d_key]];
								}
								else if (isset($UExternalGsmCell[$d_key]) && isset($GCELL[$UExternalGsmCell[$d_key]])) {
									$d_cell = $GCELL[$UExternalGsmCell[$d_key]];
								}
								else if (isset($UExternalGsmCell[$d_key]) && isset($GGsmCell[$UExternalGsmCell[$d_key]])) {
									$d_cell = $GGsmCell[$UExternalGsmCell[$d_key]];
								}
							}
							if($d_cell) {
								$UUtranCellFDD[$UUtranCellFDD_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 2g ext"];
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$UExternalGsmCell = null;
			// echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $UUtranCellFDD ); echo "</pre><br>"; exit;

		/*ZTE 4G-4G ITBBU EXTERNAL RELATION ExternalEUtranCellFDDLTE*/
			$ExternalEUtranCellFDDLTE = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'ENBCUCPFunction','_',moi_parts ->> 'CULTE','_',moi_parts ->> 'NbrCell','_',moi_parts ->> 'ExternalEUtranCellFDDLTE') AS key", "parameter_data ->> 'eNBId' AS enbid", "parameter_data ->> 'pci' AS pci", "parameter_data ->> 'cellLocalId' AS ci"],
				['moc_id'=>['int'=>$z_moc['NbrCell/ExternalEUtranCellFDDLTE']]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['enbid']) && isset($c['pci']) && isset($c['ci']))
					{
						$ExternalEUtranCellFDDLTE[ $c['key'] ] = $c['enbid'] . "_" . $c['pci'] . "_" . $c['ci'];
					}
				}
			}
			$d = null;
		/*ZTE 4G-4G ITBBU RELATION EUtranRelationFDDLTE*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				["CONCAT(moi_parts ->> 'ENBCUCPFunction','_', moi_parts ->> 'CULTE','_', moi_parts ->> 'CUEUtranCellFDDLTE') AS key", "parameter_data ->> 'refCUEUtranCellFDDLTE' AS rc","parameter_data ->> 'refExternalEUtranCellFDDLTE' AS rec"],
				['moc_id'=>['int'=>$z_moc['NeighbouringRelationFDD/EUtranRelationFDDLTE']]]
			);
			
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['key']))
					{
						if (isset($c['rc']))
						{
							$x_moi = self::get_topology_ids([$c['rc'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['ENBCUCPFunction']) && isset($x_moi['CULTE']) && isset($x_moi['CUEUtranCellFDDLTE']))
							{
								$enbid = (strpos($x_moi['ENBCUCPFunction'], '_') !== false ) ? explode('_', $x_moi['ENBCUCPFunction'])[1] : $x_moi['ENBCUCPFunction'];
								$d_key = $enbid . "_" . $x_moi['CULTE'] . "_" . $x_moi['CUEUtranCellFDDLTE'];

								if (isset($CUEUtranCellFDDLTE_moi_lkp[$c['key']]) && isset($CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]) && isset($CUEUtranCellFDDLTE_moi_lkp[$d_key]) && isset($CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$d_key]]))
								{
									$CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]['nbr'][] = [$CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$d_key]]['l'], 'v'=>$CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$d_key]]['v']." 3g"];
								}
							}
						}
						else if (isset($c['rec']))
						{
							$x_moi = self::get_topology_ids([$c['rec'], [ "seperators" => [",", "="], "return" => "*" ] ] );
							if (isset($x_moi['ENBCUCPFunction']) && isset($x_moi['CULTE']) && isset($x_moi['NbrCell']) && isset($x_moi['ExternalEUtranCellFDDLTE']))
							{
								$d_key = $x_moi['ENBCUCPFunction'] . "_" . $x_moi['CULTE'] . "_" . $x_moi['NbrCell'] . "_" . $x_moi['ExternalEUtranCellFDDLTE'];
								$d_cell = null;
								if (isset($CUEUtranCellFDDLTE_moi_lkp[$c['key']]) && isset($CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]))
								{
									if (isset($ExternalEUtranCellFDDLTE[$d_key]) && isset($LNCEL[$ExternalEUtranCellFDDLTE[$d_key]])) {
										$d_cell = $LNCEL[$ExternalEUtranCellFDDLTE[$d_key]];
									}
									else if (isset($ExternalEUtranCellFDDLTE[$d_key]) && isset($Cell[$ExternalEUtranCellFDDLTE[$d_key]])) {
										$d_cell = $Cell[$ExternalEUtranCellFDDLTE[$d_key]];
									}
									else if (isset($ExternalEUtranCellFDDLTE[$d_key]) && isset($CUEUtranCellFDDLTE[$ExternalEUtranCellFDDLTE[$d_key]])) {
										$d_cell = $CUEUtranCellFDDLTE[$ExternalEUtranCellFDDLTE[$d_key]];
									}
									else if (isset($ExternalEUtranCellFDDLTE[$d_key]) && isset($EUtranCellFDD[$ExternalEUtranCellFDDLTE[$d_key]])) {
										$d_cell = $EUtranCellFDD[$ExternalEUtranCellFDDLTE[$d_key]];
									}
								}
								if($d_cell) {
									$CUEUtranCellFDDLTE[$CUEUtranCellFDDLTE_moi_lkp[$c['key']]]['nbr'][] = ['l'=>$d_cell['l'], 'v'=>$d_cell['v']." 4g ext"];
								}
							}
						}
					}
				}
				unset($d[$k]);
			}
			$d = null;
			$ExternalEUtranCellFDDLTE = null;

		// Create a new Spreadsheet object
		$spreadsheet = new Spreadsheet();

		// Create a new worksheet and name it
		$_2g = $spreadsheet->getActiveSheet();
		$_2g->setTitle('2G');

		$_3g = $spreadsheet->createSheet();
		$_3g->setTitle('3G');

		$_4g = $spreadsheet->createSheet();
		$_4g->setTitle('4G');

		$stats = $spreadsheet->createSheet();
		$stats->setTitle('Info');
		$stats->fromArray(
			$timeLog,
			NULL,
			'A1'
		);

		// Headers
		$headers = array('cell_kind', 'Cell Name', 'Site Name', 'Vendor', 'mcc', 'mnc', 'lac', 'ci', 'radio_controller_id', 'BSIC', 'PSC', 'PCI', 'longitude', 'latitude', 'radius', 'angle_start', 'angle_end', 'angle_orientation_north_clockwise', 'angle_opening', 'ARFCN', 'dl_frequency', 'EIRP', 'ground_height', 'antenna_height', 'neighbors', 'tilt', 'environment', 'bandwidth', 'tac');
		$_2g->fromArray(array($headers), NULL, 'A1');
		$_3g->fromArray(array($headers), NULL, 'A1');
		$_4g->fromArray(array($headers), NULL, 'A1');


		$MOCs = array(
			'BTS' => array(
				'sheet_name' => '_2g',
				'fix_cols' => array('cell_kind'=>'2G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'GCELL' => array(
				'sheet_name' => '_2g',
				'fix_cols' => array('cell_kind'=>'2G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'GGsmCell' => array(
				'sheet_name' => '_2g',
				'fix_cols' => array('cell_kind'=>'2G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'WCEL' => array(
				'sheet_name' => '_3g',
				'fix_cols' => array('cell_kind'=>'3G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'UCELLSETUP' => array(
				'sheet_name' => '_3g',
				'fix_cols' => array('cell_kind'=>'3G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'UUtranCellFDD' => array(
				'sheet_name' => '_3g',
				'fix_cols' => array('cell_kind'=>'3G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'LNCEL' => array(
				'sheet_name' => '_4g',
				'fix_cols' => array('cell_kind'=>'4G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'Cell' => array(
				'sheet_name' => '_4g',
				'fix_cols' => array('cell_kind'=>'4G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'CUEUtranCellFDDLTE' => array(
				'sheet_name' => '_4g',
				'fix_cols' => array('cell_kind'=>'4G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
			'EUtranCellFDD' => array(
				'sheet_name' => '_4g',
				'fix_cols' => array('cell_kind'=>'4G', 'mcc'=>'470', 'mnc'=>'03'),
				'col_map' => array(
					'Cell Name'=>'l', 'Site Name'=>'sid', 'Vendor'=>'v', 'lac'=>'lac', 'ci'=>'ci', 'radio_controller_id'=>'rci', 'BSIC'=>'bsic', 'longitude'=>'lat', 'latitude'=>'long', 'radius'=>'r', 'angle_start'=>'a_s', 'angle_end'=>'a_e',
					'angle_orientation_north_clockwise'=>'a_o', 'angle_opening'=>'a_op', 'ARFCN'=>'arfcn', 'dl_frequency'=>'dl_freq', 'EIRP'=>'eirp', 'ground_height'=>'g_h', 'antenna_height'=>'height', 'tilt'=>'tilt'
				),
			),
		);

		foreach ($MOCs as $mk => $mv)
		{
			$data = [];
			foreach (${$mk} as $mkk => $cell) {

				$template = array(
					'cell_kind'=>null,'Cell Name'=>null,'Site Name'=>null,'Vendor'=>null,'mcc'=>null,'mnc'=>null,'lac'=>null,'ci'=>null,'radio_controller_id'=>null,'BSIC'=>null,'PSC'=>null,'PCI'=>null,'longitude'=>null,'latitude'=>null,'radius'=>null,'angle_start'=>null,
					'angle_end'=>null,'angle_orientation_north_clockwise'=>null,'angle_opening'=>null,'ARFCN'=>null,'dl_frequency'=>null,'EIRP'=>null,'ground_height'=>null,'antenna_height'=>null,'neighbors'=>null,'tilt'=>null,'environment'=>null,'bandwidth'=>null,'tac'=>null
				);

				if(isset($mv['fix_cols'])) {
					foreach ($mv['fix_cols'] as $k => $v) {
						if(array_key_exists($k, $template)) {
							$template[$k] = $v;
						}
					}
				}

				foreach ($mv['col_map'] as $ck => $cv) {
					if(isset($cell[$cv])) {
						$template[$ck] = $cell[$cv];
					}
				}

				if(isset($cell['nbr']) && count($cell['nbr']) > 0 ) {
					$template['neighbors'] = implode(' ', array_column($cell['nbr'], 'l'));
				}

				$data[] = array_values($template);
				unset(${$mk}[$mkk]);
			}

			$highestRowWithData = ${$mv['sheet_name']}->getHighestRow();

			// Insert all data in bulk
			${$mv['sheet_name']}->fromArray(
				$data,
				NULL,
				'A' . ($highestRowWithData + 1)
			);
		}

		// Create a new Xlsx Writer
		$writer = new Xlsx($spreadsheet);

		// Set the appropriate headers for download
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="BL_BSA_'.date("Ymdhis").'.xlsx"');
		header('Cache-Control: max-age=0');

		// Write the spreadsheet to the output stream
		$writer->save('php://output');


		// $ram_resp["ram_now_use"] = self::convert(memory_get_usage());
		// $ram_resp["ram_peak_use"] = self::convert(memory_get_peak_usage());
		// $ram_resp["ram_now_alloc"] = self::convert(memory_get_usage(true));
		// $ram_resp["ram_peak_alloc"] = self::convert(memory_get_peak_usage(true));
		// echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $ram_resp ); echo "</pre><br>";
		// exit;
	}

	public function get_elevation_lkp()
	{
		try {

			$elevation = Yii::app()->basePath.'/data/planet_db/elevation.xlsx';

			$elevation_arr = [];
			
			$elevation_spreadsheet = IOFactory::load($elevation);

			$elevation_sheet = $elevation_spreadsheet->getActiveSheet();
			$elevation_arr = ($elevation_sheet != null) ? $elevation_sheet->toArray(null, true, false, false) : [];
			$elevation_sheet = null;
			if(count($elevation_arr) > 2 && count($elevation_arr[0]) > 1) {
				unset($elevation_arr[0]);
				unset($elevation_arr[1]);
				$elevation_arr = array_column($elevation_arr, 1, 0);
			}

			$elevation_spreadsheet = null;

			return $elevation_arr;

			// $elevation_spreadsheet = IOFactory::load($elevation);
			// $elevation_sheet = $elevation_spreadsheet->getActiveSheet();

			// $highestRow = $elevation_sheet->getHighestRow();
			// $highestColumn = $elevation_sheet->getHighestColumn();

			// $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

			// for ($row = 1; $row <= $highestRow; $row++) {
			// 	$site_name = $elevation_sheet->getCellByColumnAndRow(1, $row)->getValue();
			// 	$elevation = $elevation_sheet->getCellByColumnAndRow(2, $row)->getValue();
			// 	$elevation_arr[$site_name] = $elevation;
			// }


			// $eirp_spreadsheet = null;
			// $eirp_sheet = null;

		} catch (Exception $e) {
			echo 'Error reading the Elevation Source file: ', $e->getMessage();
		}
	}

	public function get_eirp_lkp()
	{
		try {

			$eirp_arr = [];

			$eirp = Yii::app()->basePath.'/data/planet_db/eirp.xlsx';

			$eirp_spreadsheet = IOFactory::load($eirp);
			$eirp_sheet = $eirp_spreadsheet->getSheetByName('2G');
			$eirp_arr['2g'] = ($eirp_sheet != null) ? $eirp_sheet->toArray(null, true, false, false) : [];
			$eirp_sheet = null;
			if(count($eirp_arr['2g'])>2 && count($eirp_arr['2g'][0]) > 1) {
				unset($eirp_arr['2g'][0]);
				$eirp_arr['2g'] = array_column($eirp_arr['2g'], 1, 0);
			}

			$eirp_sheet = $eirp_spreadsheet->getSheetByName('3G');
			$eirp_arr['3g'] = ($eirp_sheet != null) ? $eirp_sheet->toArray(null, true, false, false) : [];
			$eirp_sheet = null;
			if(count($eirp_arr['3g'])>2 && count($eirp_arr['3g'][0]) > 1) {
				unset($eirp_arr['3g'][0]);
				$eirp_arr['3g'] = array_column($eirp_arr['3g'], 1, 0);
			}

			$eirp_sheet = $eirp_spreadsheet->getSheetByName('4G');
			$eirp_arr['4g'] = ($eirp_sheet != null) ? $eirp_sheet->toArray(null, true, false, false) : [];
			$eirp_sheet = null;
			if(count($eirp_arr['4g'])>2 && count($eirp_arr['4g'][0]) > 1) {
				unset($eirp_arr['4g'][0]);
				$eirp_arr['4g'] = array_column($eirp_arr['4g'], 1, 0);
			}

			$eirp_spreadsheet = null;

			return $eirp_arr;

			// $eirp_spreadsheet = IOFactory::load($eirp);
			// $eirp_sheet = $eirp_spreadsheet->getSheetByName('2G');
			// $highestRow = $eirp_sheet->getHighestRow();
			// $highestColumn = $eirp_sheet->getHighestColumn();

			// $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

			// for ($row = 1; $row <= $highestRow; $row++) {
			// 	$cell_name = $eirp_sheet->getCellByColumnAndRow(1, $row)->getValue();
			// 	$eirp_val = $eirp_sheet->getCellByColumnAndRow(2, $row)->getValue();
			// 	$eirp_arr['2g'][$cell_name] = $eirp_val;
			// }
			// $eirp_sheet = null;

			// $eirp_sheet = $eirp_spreadsheet->getSheetByName('3G');
			// $highestRow = $eirp_sheet->getHighestRow();
			// $highestColumn = $eirp_sheet->getHighestColumn();

			// $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

			// for ($row = 1; $row <= $highestRow; $row++) {
			// 	$cell_name = $eirp_sheet->getCellByColumnAndRow(1, $row)->getValue();
			// 	$eirp_val = $eirp_sheet->getCellByColumnAndRow(2, $row)->getValue();
			// 	$eirp_arr['3g'][$cell_name] = $eirp_val;
			// }
			// $eirp_sheet = null;

			// $eirp_sheet = $eirp_spreadsheet->getSheetByName('4G');
			// $highestRow = $eirp_sheet->getHighestRow();
			// $highestColumn = $eirp_sheet->getHighestColumn();

			// $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

			// for ($row = 1; $row <= $highestRow; $row++) {
			// 	$cell_name = $eirp_sheet->getCellByColumnAndRow(1, $row)->getValue();
			// 	$eirp_val = $eirp_sheet->getCellByColumnAndRow(2, $row)->getValue();
			// 	$eirp_arr['4g'][$cell_name] = $eirp_val;
			// }

			// $eirp_spreadsheet = null;
			// $eirp_sheet = null;

		} catch (Exception $e) {
			echo 'Error reading the EIRP source file: ', $e->getMessage();
		}
	}

	public function get_data($table, $cols=NULL, $col_name_search_arr = null)
	{
		$qry = "SELECT {{_select_cols_}} FROM $table {{_WHERE_}}";
		$select_cols = '*';
		$where = "";

		if (isset($cols))
		{
			$select_cols = implode(', ', $cols);
		}

		if (isset($col_name_search_arr) && is_array($col_name_search_arr))
		{
			
			$where_parts = array();
			foreach ($col_name_search_arr as $col => $search)
			{
				
				if (isset($search) && is_array($search))
				{
					
					if(isset($search['int']) && !is_array($search['int']))
					{
						$where_parts[] = $col . " = " . $search['int'];

					}
					else if (isset($search['int']) && is_array($search['int']) && count($search['int']) == 1)
					{
						$where_parts[] = $col . " = " . $search['int'][0];
					
					}
					else if (isset($search['int']) && is_array($search['int']) && count($search['int']) > 1)
					{
						$where_parts[] = $col . " IN (" . implode(",", $search['int']) . ")";

					}
					else if (isset($search['str']) && !is_array($search['str']))
					{
						$where_parts[] = $col . " = '" . $search['str'] . "'";

					}
					else if (isset($search['str']) && is_array($search['str']) && count($search['str']) == 1)
					{
						$where_parts[] = $col . " = '" . $search['str'][0] . "'";

					}
					else if (isset($search['str']) && is_array($search['str']) && count($search['str']) > 1)
					{
						$where_parts[] = $col . " IN ('" . implode("','", $search['str']) . "')";

					} else {
						$where_parts[] = $col . " IN ('" . implode("','", $search) . "')";
					}

				} else {
					$where_parts[] = $col . " = '" . $search . "'";
				}
			}

			if (count($where_parts) == 1)
				$where = "WHERE " . $where_parts[0];
			else
				$where = "WHERE (" . implode(") AND (", $where_parts) . ")";
		}

		$qry = str_replace('{{_select_cols_}}', $select_cols, $qry);
		$qry = str_replace('{{_WHERE_}}', $where, $qry);
		
		// echo "<strong>" .date("Y-m-d H:i:s"). "</strong>: <small>"; print_r( $qry ); echo "</small><br>";

		$data = Yii::app()->cm_pm_schema->createCommand($qry)->queryAll();

		return $data;
	}

	public function get_topology_ids( $input )
	{
		if (isset($input[0]) && isset($input[1]) && isset($input[1]["seperators"]) && count($input[1]["seperators"]) > 0 )
		{
			$result = array();
			if (isset( $input[1]["col"] ))
			{
				$col_name = $input[1]["col"];
			}
			$val_to_exp = "";
			if (isset( $col_name ))
			{
				$val_to_exp = $input[0][$col_name];
			}
			else if (is_string( $input[0] ))
			{
				$val_to_exp = $input[0];
			}
			$x_input = explode($input[1]["seperators"][0], $val_to_exp);
			if (count($x_input) > 0 ){
				foreach ($x_input as $v)
				{
					$x_v = explode($input[1]["seperators"][1], $v);
					$result[trim($x_v[0])] = trim($x_v[1]);
				}
			}
			if (count($result) > 0 )
			{
				if (isset( $result[ $input[1]["return"] ] ))
				{
					return $result[ $input[1]["return"] ];
				}
				return $result;
			}
			return [];
		}
		return [];
	}

	public function convert($size)
	{
		$unit=array('b','kb','mb','gb','tb','pb');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}

	public function deg_to_trig( $deg )
	{
		$angleMicroDegrees = null;
		if(is_numeric($deg)) {
			$angleDegrees = $deg; // Angle in degrees (0 means north)
			$trigonometricAngleDegrees = 90 - $angleDegrees; // Convert the angle to trigonometric convention (0 means east)
			$trigonometricAngleDegrees = fmod($trigonometricAngleDegrees, 360); // Ensure the angle is between 0 and 360 degrees
			if ($trigonometricAngleDegrees < 0) {
				$trigonometricAngleDegrees += 360;
			}
			$angleMicroDegrees = round($trigonometricAngleDegrees * 1000000); // Convert the trigonometric angle to the specified format (10^-6 degrees)
		}
		return $angleMicroDegrees;
	}

	public function convert_to_generic($input)
	{
		if (isset($input) && strlen($input) >= 5 && $input[3] === '_') {
			return substr($input, 0, 3) . substr($input, 5);
		} else {
			return $input;
		}
	}
	public function cell_to_site($input)
	{
		if (isset($input) && strlen($input) >= 10 && $input[9] === '_') {
			return substr($input, 0, 9);
		} else {
			return $input;
		}
	}

	public function convert_cell_id_format( $input )
	{
		# Converts Cell Name from formate "DHKX1244S4" to "DHK_X1244_4"
		$convertedString = $input;

		if(isset($convertedString) && strlen($input) > 9 && !strpos($input, "_") !== false) {		
			// Insert underscore between third and fourth character
			$convertedString = substr_replace($input, '_', 3, 0);

			// Replace the 9th character with an underscore
			$convertedString = substr_replace($convertedString, '_', 9, 1);

		}
		return $convertedString;
	}

	public function _ARFCN( $freq = NULL )
	{
		$arfcn_lkp = [
			"2g" => [
				"27" => [ "arfcn" => "27", "ul_freq" => "895.4", "dl_freq" => "940.4", "band" => "900" ],
				"28" => [ "arfcn" => "28", "ul_freq" => "895.6", "dl_freq" => "940.6", "band" => "900" ],
				"29" => [ "arfcn" => "29", "ul_freq" => "895.8", "dl_freq" => "940.8", "band" => "900" ],
				"30" => [ "arfcn" => "30", "ul_freq" => "896", "dl_freq" => "941", "band" => "900" ],
				"31" => [ "arfcn" => "31", "ul_freq" => "896.2", "dl_freq" => "941.2", "band" => "900" ],
				"32" => [ "arfcn" => "32", "ul_freq" => "896.4", "dl_freq" => "941.4", "band" => "900" ],
				"33" => [ "arfcn" => "33", "ul_freq" => "896.6", "dl_freq" => "941.6", "band" => "900" ],
				"34" => [ "arfcn" => "34", "ul_freq" => "896.8", "dl_freq" => "941.8", "band" => "900" ],
				"35" => [ "arfcn" => "35", "ul_freq" => "897", "dl_freq" => "942", "band" => "900" ],
				"36" => [ "arfcn" => "36", "ul_freq" => "897.2", "dl_freq" => "942.2", "band" => "900" ],
				"37" => [ "arfcn" => "37", "ul_freq" => "897.4", "dl_freq" => "942.4", "band" => "900" ],
				"38" => [ "arfcn" => "38", "ul_freq" => "897.6", "dl_freq" => "942.6", "band" => "900" ],
				"39" => [ "arfcn" => "39", "ul_freq" => "897.8", "dl_freq" => "942.8", "band" => "900" ],
				"40" => [ "arfcn" => "40", "ul_freq" => "898", "dl_freq" => "943", "band" => "900" ],
				"41" => [ "arfcn" => "41", "ul_freq" => "898.2", "dl_freq" => "943.2", "band" => "900" ],
				"42" => [ "arfcn" => "42", "ul_freq" => "898.4", "dl_freq" => "943.4", "band" => "900" ],
				"43" => [ "arfcn" => "43", "ul_freq" => "898.6", "dl_freq" => "943.6", "band" => "900" ],
				"44" => [ "arfcn" => "44", "ul_freq" => "898.8", "dl_freq" => "943.8", "band" => "900" ],
				"45" => [ "arfcn" => "45", "ul_freq" => "899", "dl_freq" => "944", "band" => "900" ],
				"46" => [ "arfcn" => "46", "ul_freq" => "899.2", "dl_freq" => "944.2", "band" => "900" ],
				"47" => [ "arfcn" => "47", "ul_freq" => "899.4", "dl_freq" => "944.4", "band" => "900" ],
				"48" => [ "arfcn" => "48", "ul_freq" => "899.6", "dl_freq" => "944.6", "band" => "900" ],
				"49" => [ "arfcn" => "49", "ul_freq" => "899.8", "dl_freq" => "944.8", "band" => "900" ],
				"50" => [ "arfcn" => "50", "ul_freq" => "900", "dl_freq" => "945", "band" => "900" ],
				"762" => [ "arfcn" => "762", "ul_freq" => "1760.2", "dl_freq" => "1855.2", "band" => "1800" ],
				"763" => [ "arfcn" => "763", "ul_freq" => "1760.4", "dl_freq" => "1855.4", "band" => "1800" ],
				"764" => [ "arfcn" => "764", "ul_freq" => "1760.6", "dl_freq" => "1855.6", "band" => "1800" ],
				"765" => [ "arfcn" => "765", "ul_freq" => "1760.8", "dl_freq" => "1855.8", "band" => "1800" ],
				"766" => [ "arfcn" => "766", "ul_freq" => "1761", "dl_freq" => "1856", "band" => "1800" ],
				"767" => [ "arfcn" => "767", "ul_freq" => "1761.2", "dl_freq" => "1856.2", "band" => "1800" ],
				"768" => [ "arfcn" => "768", "ul_freq" => "1761.4", "dl_freq" => "1856.4", "band" => "1800" ],
				"769" => [ "arfcn" => "769", "ul_freq" => "1761.6", "dl_freq" => "1856.6", "band" => "1800" ],
				"770" => [ "arfcn" => "770", "ul_freq" => "1761.8", "dl_freq" => "1856.8", "band" => "1800" ],
				"771" => [ "arfcn" => "771", "ul_freq" => "1762", "dl_freq" => "1857", "band" => "1800" ],
				"772" => [ "arfcn" => "772", "ul_freq" => "1762.2", "dl_freq" => "1857.2", "band" => "1800" ],
				"773" => [ "arfcn" => "773", "ul_freq" => "1762.4", "dl_freq" => "1857.4", "band" => "1800" ],
				"774" => [ "arfcn" => "774", "ul_freq" => "1762.6", "dl_freq" => "1857.6", "band" => "1800" ],
				"775" => [ "arfcn" => "775", "ul_freq" => "1762.8", "dl_freq" => "1857.8", "band" => "1800" ],
				"776" => [ "arfcn" => "776", "ul_freq" => "1763", "dl_freq" => "1858", "band" => "1800" ],
				"777" => [ "arfcn" => "777", "ul_freq" => "1763.2", "dl_freq" => "1858.2", "band" => "1800" ],
				"778" => [ "arfcn" => "778", "ul_freq" => "1763.4", "dl_freq" => "1858.4", "band" => "1800" ],
				"779" => [ "arfcn" => "779", "ul_freq" => "1763.6", "dl_freq" => "1858.6", "band" => "1800" ],
				"780" => [ "arfcn" => "780", "ul_freq" => "1763.8", "dl_freq" => "1858.8", "band" => "1800" ],
				"781" => [ "arfcn" => "781", "ul_freq" => "1764", "dl_freq" => "1859", "band" => "1800" ],
				"842" => [ "arfcn" => "842", "ul_freq" => "1776.2", "dl_freq" => "1871.2", "band" => "1800" ],
				"843" => [ "arfcn" => "843", "ul_freq" => "1776.4", "dl_freq" => "1871.4", "band" => "1800" ],
				"844" => [ "arfcn" => "844", "ul_freq" => "1776.6", "dl_freq" => "1871.6", "band" => "1800" ],
				"845" => [ "arfcn" => "845", "ul_freq" => "1776.8", "dl_freq" => "1871.8", "band" => "1800" ],
				"846" => [ "arfcn" => "846", "ul_freq" => "1777", "dl_freq" => "1872", "band" => "1800" ],
				"847" => [ "arfcn" => "847", "ul_freq" => "1777.2", "dl_freq" => "1872.2", "band" => "1800" ],
				"848" => [ "arfcn" => "848", "ul_freq" => "1777.4", "dl_freq" => "1872.4", "band" => "1800" ],
				"854" => [ "arfcn" => "854", "ul_freq" => "1778.6", "dl_freq" => "1873.6", "band" => "1800" ],
				"855" => [ "arfcn" => "855", "ul_freq" => "1778.8", "dl_freq" => "1873.8", "band" => "1800" ],
				"856" => [ "arfcn" => "856", "ul_freq" => "1779", "dl_freq" => "1874", "band" => "1800" ],
				"857" => [ "arfcn" => "857", "ul_freq" => "1779.2", "dl_freq" => "1874.2", "band" => "1800" ],
				"858" => [ "arfcn" => "858", "ul_freq" => "1779.4", "dl_freq" => "1874.4", "band" => "1800" ],
				"859" => [ "arfcn" => "859", "ul_freq" => "1779.6", "dl_freq" => "1874.6", "band" => "1800" ],
				"860" => [ "arfcn" => "860", "ul_freq" => "1779.8", "dl_freq" => "1874.8", "band" => "1800" ]
			],
			"3g" => [
				"ul_10588" => ["dl_uarfcn" => "10588", "ul_uarfcn" => "9638", "dl_center_freq" => "2117.6"],
				"dl_9638" => ["dl_uarfcn" => "10588", "ul_uarfcn" => "9638", "dl_center_freq" => "2117.6"],
				"ul_10613" => ["dl_uarfcn" => "10613", "ul_uarfcn" => "9663", "dl_center_freq" => "2122.6"],
				"dl_9663" => ["dl_uarfcn" => "10613", "ul_uarfcn" => "9663", "dl_center_freq" => "2122.6"],

			],
			"4g" => [
				"3626" => ["c_earfcn" => "3626", "center_freq" => "942.6", "band" => "900"],
				"25" => ["c_earfcn" => "25", "center_freq" => "2112.5", "band" => "2100"],
				"50" => ["c_earfcn" => "50", "center_freq" => "2115.0", "band" => "2100"],
				"75" => ["c_earfcn" => "75", "center_freq" => "2117.5", "band" => "2100"],
				"1800" => ["c_earfcn" => "1800", "center_freq" => "1865", "band" => "1800"],
			],
		];
		if($freq == NULL ) {
			return $arfcn_lkp;
		} else {
			if(isset( $arfcn_lkp["2g"][$freq] ) ) {
				return $arfcn_lkp["2g"][$freq]["band"];
			// } else if(isset( $arfcn_lkp["4g"][$freq] ) ) {
				// NEED TO Understand EARFCN mapping for 3G
			} else if(isset( $arfcn_lkp["4g"][$freq] ) ) {
				return $arfcn_lkp["4g"][$freq]["band"];
			}
		}
		return NULL;
	}

	/*NOKIA 2G Cell RADIUS*/
	public function get_nokia_2G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "PTMADV";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));
		
		$counter_list = [
			'c136000','c136001','c136002','c136003','c136004','c136005','c136006','c136007','c136008','c136009','c136010','c136011','c136012','c136013','c136014','c136015','c136016','c136017','c136018','c136019','c136020','c136021',
			'c136022','c136023','c136024','c136025','c136026','c136027','c136028','c136029','c136030','c136031','c136032','c136033','c136034','c136035','c136036','c136037','c136038','c136039','c136040','c136041','c136042','c136043',
			'c136044','c136045','c136046','c136047','c136048','c136049','c136050','c136051','c136052','c136053','c136054','c136055','c136056','c136057','c136058','c136059','c136060','c136061','c136062','c136063'
		];

		$trx_counters = implode("','", $counter_list);

		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_nokia_pm_counter WHERE name IN ('$trx_counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();
		$trxes = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parent_moi_id FROM ran_nokia_moi WHERE moc_id = $moc_id")->queryAll();

		$trxes_lkp = array_column($trxes, 'parent_moi_id', 'id');
		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";
		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$bts_data = [];
		foreach ($pm_data as $v) {
			if( isset($v['moi_id']) && isset($trxes_lkp[$v['moi_id']])) {
				if(!isset($bts_data[$v['moi_id']])) {
					$bts_data[$trxes_lkp[$v['moi_id']]] =  [];
					foreach ($counter_lkp as $cid => $name) {
						if( isset($v[$cid]) ) {
							$bts_data[$trxes_lkp[$v['moi_id']]][$cid] = $v[$cid];
						}
					}
				} else {
					foreach ($counter_lkp as $cid => $name) {
						if( isset($v[$cid]) ) {
							$bts_data[$trxes_lkp[$v['moi_id']]][$cid] += $v[$cid];
						}
					}
				}
			}
		}

		$radius_lkp = [
			'c136000' => 550, 'c136001' => 1100, 'c136002' => 1650, 'c136003' => 2200, 'c136004' => 2750, 'c136005' => 3300, 'c136006' => 3850, 'c136007' => 4400, 'c136008' => 4950, 'c136009' => 5500, 'c136010' => 6050, 'c136011' => 6600,
			'c136012' => 7150, 'c136013' => 7700, 'c136014' => 8250, 'c136015' => 8800, 'c136016' => 9350, 'c136017' => 9900, 'c136018' => 10450, 'c136019' => 11000, 'c136020' => 11550, 'c136021' => 12100, 'c136022' => 12650, 'c136023' => 13200,
			'c136024' => 13750, 'c136025' => 14300, 'c136026' => 14850, 'c136027' => 15400, 'c136028' => 15950, 'c136029' => 16500, 'c136030' => 17050, 'c136031' => 17600, 'c136032' => 18150, 'c136033' => 18700, 'c136034' => 19250, 'c136035' => 19800,
			'c136036' => 20350, 'c136037' => 20900, 'c136038' => 21450, 'c136039' => 22000, 'c136040' => 22550, 'c136041' => 23100, 'c136042' => 23650, 'c136043' => 24200, 'c136044' => 24750, 'c136045' => 25300, 'c136046' => 25850, 'c136047' => 26400,
			'c136048' => 26950, 'c136049' => 27500, 'c136050' => 28050, 'c136051' => 28600, 'c136052' => 29150, 'c136053' => 29700, 'c136054' => 30250, 'c136055' => 30800, 'c136056' => 31350, 'c136057' => 31900, 'c136058' => 32450, 'c136059' => 33000,
			'c136060' => 33550, 'c136061' => 34100, 'c136062' => 34650, 'c136063' => 35200
		];

		$radius_data = [];

		if(count($bts_data)) {
			foreach ($bts_data as $bts_id => $cntrs) {
				if(count($cntrs)) {
					foreach ($counter_lkp as $cid => $name) {
						if (isset($radius_lkp[$name]) && isset($cntrs[$cid]) && $cntrs[$cid] > 0) {
							$radius_data[$bts_id] = $radius_lkp[$name];
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*NOKIA 3G Cell RADIUS*/
	public function get_nokia_3G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "RRC";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$primary_counter = 'M1006C169';
		$counter_list = ['M1006C128','M1006C129','M1006C130','M1006C131','M1006C132','M1006C133','M1006C134','M1006C135','M1006C136','M1006C137','M1006C138','M1006C139','M1006C140','M1006C141','M1006C142','M1006C143','M1006C144','M1006C145','M1006C146','M1006C147','M1006C148'];

		$counters = implode("','", array_merge($counter_list, [$primary_counter]));
		$counters_res = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_nokia_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters_res) > 0 )
		{
			$cntrs = array();
			foreach ($counters_res as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$radius_ops = [
			'M1006C128' => [ 1=>['from'=>0,    'to'=>234 ],       2=>['from'=>0,    'to'=>468 ],       3=>['from'=>0,     'to'=>234  ]     ],
			'M1006C129' => [ 1=>['from'=>234,  'to'=>468 ],       2=>['from'=>468,  'to'=>936 ],       3=>['from'=>234,   'to'=>468  ]     ],
			'M1006C130' => [ 1=>['from'=>468,  'to'=>702 ],       2=>['from'=>936,  'to'=>1404],       3=>['from'=>468,   'to'=>702  ]     ],
			'M1006C131' => [ 1=>['from'=>702,  'to'=>936 ],       2=>['from'=>1404, 'to'=>1872],       3=>['from'=>702,   'to'=>936  ]     ],
			'M1006C132' => [ 1=>['from'=>936,  'to'=>1170],       2=>['from'=>1872, 'to'=>2340],       3=>['from'=>936,   'to'=>1170 ]     ],
			'M1006C133' => [ 1=>['from'=>1170, 'to'=>1404],       2=>['from'=>2340, 'to'=>2808],       3=>['from'=>1170,  'to'=>1404 ]     ],
			'M1006C134' => [ 1=>['from'=>1404, 'to'=>1638],       2=>['from'=>2808, 'to'=>3276],       3=>['from'=>1404,  'to'=>2106 ]     ],
			'M1006C135' => [ 1=>['from'=>1638, 'to'=>1872],       2=>['from'=>3276, 'to'=>3744],       3=>['from'=>2106,  'to'=>2574 ]     ],
			'M1006C136' => [ 1=>['from'=>1872, 'to'=>2106],       2=>['from'=>3744, 'to'=>4212],       3=>['from'=>2574,  'to'=>3042 ]     ],
			'M1006C137' => [ 1=>['from'=>2106, 'to'=>2340],       2=>['from'=>4212, 'to'=>4680],       3=>['from'=>3042,  'to'=>3510 ]     ],
			'M1006C138' => [ 1=>['from'=>2340, 'to'=>2574],       2=>['from'=>4680, 'to'=>5148],       3=>['from'=>3510,  'to'=>3978 ]     ],
			'M1006C139' => [ 1=>['from'=>2574, 'to'=>2808],       2=>['from'=>5148, 'to'=>5616],       3=>['from'=>3978,  'to'=>4914 ]     ],
			'M1006C140' => [ 1=>['from'=>2808, 'to'=>3042],       2=>['from'=>5616, 'to'=>6084],       3=>['from'=>4914,  'to'=>6084 ]     ],
			'M1006C141' => [ 1=>['from'=>3042, 'to'=>3276],       2=>['from'=>6084, 'to'=>6552],       3=>['from'=>6084,  'to'=>7020 ]     ],
			'M1006C142' => [ 1=>['from'=>3276, 'to'=>3510],       2=>['from'=>6552, 'to'=>7020],       3=>['from'=>7020,  'to'=>7956 ]     ],
			'M1006C143' => [ 1=>['from'=>3510, 'to'=>3744],       2=>['from'=>7020, 'to'=>7488],       3=>['from'=>7956,  'to'=>9126 ]     ],
			'M1006C144' => [ 1=>['from'=>3744, 'to'=>3978],       2=>['from'=>7488, 'to'=>7956],       3=>['from'=>9126,  'to'=>10296]     ],
			'M1006C145' => [ 1=>['from'=>3978, 'to'=>4212],       2=>['from'=>7956, 'to'=>8424],       3=>['from'=>10296, 'to'=>12402]     ],
			'M1006C146' => [ 1=>['from'=>4212, 'to'=>4446],       2=>['from'=>8424, 'to'=>8892],       3=>['from'=>12402, 'to'=>14976]     ],
			'M1006C147' => [ 1=>['from'=>4446, 'to'=>4680],       2=>['from'=>8892, 'to'=>9360],       3=>['from'=>14976, 'to'=>17550]     ],
			'M1006C148' => [ 1=>['from'=>4680, 'to'=>'infinite'], 2=>['from'=>9360, 'to'=>'infinite'], 3=>['from'=>17550, 'to'=>'infinite']],
		];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if($primary_counter == $cname){
				$primary_counter = $cid;
			}
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data)) {
			foreach ($pm_data as $wcel) {
				if (count($wcel) && isset($wcel[$primary_counter]) && in_array($wcel[$primary_counter], [1,2,3])) {
					foreach ($radius_ops as $cid => $c_ops) {
						if (isset($wcel[$cid]) && $wcel[$cid] > 0) {
							$radius_data[$wcel['moi_id']] = $c_ops[$wcel[$primary_counter]]['to'];
						}
					}
				}
			}
		}
		return $radius_data;
	}


	/*NOKIA 4G Cell RADIUS*/
	public function get_nokia_4G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "LTE_MAC";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_nokia_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));


		$counter_list = [
			'lmac_ext.timing_adv_set_index' => 'M8029C0',
			'LMAC.timing_adv_bin_1' => 'M8029C1',
			'LMAC.timing_adv_bin_2' => 'M8029C2',
			'LMAC.timing_adv_bin_3' => 'M8029C3',
			'LMAC.timing_adv_bin_4' => 'M8029C4',
			'LMAC.timing_adv_bin_5' => 'M8029C5',
			'LMAC.timing_adv_bin_6' => 'M8029C6',
			'LMAC.timing_adv_bin_7' => 'M8029C7',
			'LMAC.timing_adv_bin_8' => 'M8029C8',
			'LMAC.timing_adv_bin_9' => 'M8029C9',
			'LMAC.timing_adv_bin_10' => 'M8029C10',
			'LMAC.timing_adv_bin_11' => 'M8029C11',
			'LMAC.timing_adv_bin_12' => 'M8029C12',
			'LMAC.timing_adv_bin_13' => 'M8029C13',
			'LMAC.timing_adv_bin_14' => 'M8029C14',
			'LMAC.timing_adv_bin_15' => 'M8029C15',
			'LMAC.timing_adv_bin_16' => 'M8029C16',
			'LMAC.timing_adv_bin_17' => 'M8029C17',
			'LMAC.timing_adv_bin_18' => 'M8029C18',
			'LMAC.timing_adv_bin_19' => 'M8029C19',
			'LMAC.timing_adv_bin_20' => 'M8029C20',
			'LMAC.timing_adv_bin_21' => 'M8029C21',
			'LMAC.timing_adv_bin_22' => 'M8029C22',
			'LMAC.timing_adv_bin_23' => 'M8029C23',
			'LMAC.timing_adv_bin_24' => 'M8029C24',
			'LMAC.timing_adv_bin_25' => 'M8029C25',
			'LMAC.timing_adv_bin_26' => 'M8029C26',
			'LMAC.timing_adv_bin_27' => 'M8029C27',
			'LMAC.timing_adv_bin_28' => 'M8029C28',
			'LMAC.timing_adv_bin_29' => 'M8029C29',
			'LMAC.timing_adv_bin_30' => 'M8029C30',
		];

		$counter_list_flipped = array_flip($counter_list);

		$counters = implode("','", $counter_list);
		$counters_res = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_nokia_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$sel = "";
		$counter_lkp = [];
		$counter_lkp_by_name = [];

		if( sizeof($counters_res) > 0 )
		{
			$cntrs = array();
			foreach ($counters_res as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
				if(isset($counter_list_flipped[$cntr['name']])) {
					$counter_lkp_by_name[$counter_list_flipped[$cntr['name']]] = "c".$cntr['id'];
				}
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}
					echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $counter_lkp_by_name ); echo "</pre><br>"; exit;

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND t.c_vals->>'c2016' IS NOT NULL AND moi_id IN (6043397,2414102,4313507,4174689,2414305,2414303,8649457,5620653,2413933,5620702,8259054,4940279,2413911,5620576,5620575,2413830,5620640,4195609)";

		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();
		echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $pm_data ); echo "</pre><br>"; exit;

		$radius_ops = [];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if($primary_counter == $cname){
				$primary_counter = $cid;
			}
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data)) {
			foreach ($pm_data as $wcel) {
				if (count($wcel) && isset($wcel[$primary_counter]) && in_array($wcel[$primary_counter], [1,2,3])) {
					foreach ($radius_ops as $cid => $c_ops) {
						if (isset($wcel[$cid]) && $wcel[$cid] > 0) {
							$radius_data[$wcel['moi_id']] = $c_ops[$wcel[$primary_counter]]['to'];
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*HUAWEI 2G Cell RADIUS*/
	public function get_huawei_2g_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "1275071625";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = [
			'1278277417','1278277418','1278277419','1278277420','1278277421','1278277422','1278277423','1278277424','1278277425','1278277426','1278277427','1278277428','1278277429','1278277430','1278277431','1278277432','1278277433','1278277434','1278277435','1278277436',
			'1278277437','1278277438','1278277439','1278277440','1278277441','1278277442','1278277443','1278277444','1278277445','1278277446','1278277447','1278277448','1278277449','1278277450','1278277451','1278277452','1278277453','1278277454','1278277455','1278277456'
		];

		$counters = implode("','", $counter_list);

		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_huawei_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$BSC6900GSMGTRX = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'name' AS name FROM ran_huawei_moi WHERE moc_id = $moc_id")->queryAll();

		$cell_lkp = [];
		foreach ($BSC6900GSMGTRX as $v) {
			if(isset($v['name'])) {
				$name_xp = explode('TRX Name=', $v['name']);
				if(count($name_xp) > 1) {
					$name_xp = explode('-', $name_xp[1]);
					if(isset($name_xp[0])) {
						$cell_lkp[$v['id']] = $name_xp[0];
					}
				}
			}
		}

		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_huawei_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		if(!isset($pm_data)) {
			return [];
		}

		$pm_data_tx = [];
		foreach ($pm_data as $pk => $pv) {
			if(isset($cell_lkp[$pv['moi_id']])) {
				$cell_name = $cell_lkp[$pv['moi_id']];
				if(!isset($pm_data_tx[$cell_name])) {
					$pm_data_tx[$cell_name] = $pv;
				} else {
					$temp_arr = $pm_data_tx[$cell_name];
					foreach ($counter_lkp as $cntr_id => $cntr_name) {
						$temp_arr[$cntr_id] += $pv[$cntr_id];
					}
					$pm_data_tx[$cell_name] = $temp_arr;
				}
			}
		}

		$radius_ops = [
			'1278277417'=>'550',   '1278277418'=>'1100',  '1278277419'=>'1650',  '1278277420'=>'2200',  '1278277421'=>'2750',  '1278277422'=>'3300',  '1278277423'=>'3850',  '1278277424'=>'4400',  '1278277425'=>'4950',  '1278277426'=>'5500',
			'1278277427'=>'6050',  '1278277428'=>'6600',  '1278277429'=>'7150',  '1278277430'=>'7700',  '1278277431'=>'8250',  '1278277432'=>'8800',  '1278277433'=>'9350',  '1278277434'=>'9900',  '1278277435'=>'10450', '1278277436'=>'11000',
			'1278277437'=>'11550', '1278277438'=>'12100', '1278277439'=>'12650', '1278277440'=>'13200', '1278277441'=>'13750', '1278277442'=>'14300', '1278277443'=>'14850', '1278277444'=>'15400', '1278277445'=>'15950', '1278277446'=>'16500',
			'1278277447'=>'17050', '1278277448'=>'17600', '1278277449'=>'18150', '1278277450'=>'18700', '1278277451'=>'19250', '1278277452'=>'19800', '1278277453'=>'20350', '1278277454'=>'20900', '1278277455'=>'21450', '1278277456'=>'>21450',
		];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data_tx)) {
			foreach ($pm_data_tx as $key => $cell) {
				if (count($cell)) {
					foreach ($radius_ops as $cid => $radius) {
						if (isset($cell[$cid]) && $cell[$cid] > 0) {
							$radius_data[ $key ] = $radius;
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*HUAWEI 3G Cell RADIUS*/
	public function get_huawei_3g_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "67109365";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = ['73423486','73423488','73423490','73423492','73423494','73423496','73423498','73423510','73423502','73423504','73423506','73423508'];

		$counters = implode("','", $counter_list);
		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_huawei_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$BSC6910UMTSUCELLs = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'CELLID' AS cid,  parameter_data ->> 'LOGICRNCID' AS rnc FROM ran_huawei_moi WHERE moc_id = $moc_id")->queryAll();

		$mml_moi_lkp = [];
		foreach ($BSC6910UMTSUCELLs as $v) {
			$mml_moi_lkp[$v['id']] = $v['rnc'] . '_' . $v['cid'];
		}
		
		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_huawei_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$pm_data = array_column($pm_data, null, 'id');

		$radius_ops = ['73423486'=>'234','73423488'=>'468','73423490'=>'702','73423492'=>'936','73423494'=>'1170','73423496'=>'1404','73423498'=>'2340','73423510'=>'3744','73423502'=>'6084','73423504'=>'8424','73423506'=>'13104','73423508'=>'>13104'];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data)) {
			foreach ($pm_data as $umtscell) {
				if (count($umtscell) && isset($mml_moi_lkp[ $umtscell['moi_id']])) {
					foreach ($radius_ops as $cid => $radius) {
						if (isset($umtscell[$cid]) && $umtscell[$cid] > 0) {
							$radius_data[ $mml_moi_lkp[ $umtscell['moi_id']]] = $radius;
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*HUAWEI 4G Cell RADIUS*/
	public function get_huawei_4g_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "1526726694";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_huawei_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = ['1526728956','1526728957','1526728958','1526728959','1526728960','1526728961','1526728962','1526728963','1526728964','1526728965','1526728966','1526728967'];

		$counters = implode("','", $counter_list);
		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_huawei_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$BTS3900CELL = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'CELLNAME' AS cname FROM ran_huawei_moi WHERE moc_id = $moc_id")->queryAll();

		$moi_lkp = [];
		foreach ($BTS3900CELL as $v) {
			$moi_lkp[$v['id']] = $v['cname'];
		}
		
		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_huawei_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$pm_data = array_column($pm_data, null, 'id');

		$radius_ops = ['1526728956'=>'156','1526728957'=>'234','1526728958'=>'546','1526728959'=>'1014','1526728960'=>'1950','1526728961'=>'3510','1526728962'=>'6630','1526728963'=>'14430','1526728964'=>'30030','1526728965'=>'53430','1526728966'=>'76830','1526728967'=>'>76830'];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data)) {
			foreach ($pm_data as $cell) {
				if (count($cell) && isset($moi_lkp[ $cell['moi_id']])) {
					foreach ($radius_ops as $cid => $radius) {
						if (isset($cell[$cid]) && $cell[$cid] > 0) {
							$radius_data[ $moi_lkp[ $cell['moi_id']]] = $radius;
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*ZTE 2G Cell RADIUS*/
	public function get_zte_2G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "MSTAMEASUREMENT";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = [
			'C901140001', 'C901140034', 'C901140035', 'C901140036', 'C901140037', 'C901140038', 'C901140039', 'C901140040', 'C901140041', 'C901140042', 'C901140043', 'C901140044', 'C901140045', 'C901140046', 'C901140047', 'C901140048',
			'C901140049', 'C901140050', 'C901140051', 'C901140052', 'C901140053', 'C901140054', 'C901140055', 'C901140056', 'C901140057', 'C901140058', 'C901140059', 'C901140060', 'C901140061', 'C901140062', 'C901140063', 'C901140064',
			'C901140065', 'C901140066', 'C901140067', 'C901140068', 'C901140069', 'C901140070', 'C901140071', 'C901140072', 'C901140073', 'C901140074', 'C901140075', 'C901140076', 'C901140077', 'C901140078', 'C901140079', 'C901140080',
			'C901140081', 'C901140082', 'C901140083', 'C901140084', 'C901140085', 'C901140086', 'C901140087', 'C901140088', 'C901140089', 'C901140090', 'C901140091', 'C901140092', 'C901140093', 'C901140094', 'C901140095', 'C901140096'
		];

		$counters = implode("','", $counter_list);

		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_zte_ume_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();

		$GCELLs = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'userLabel' AS userlabel FROM ran_zte_ume_moi WHERE moc_id = $moc_id")->queryAll();

		$GCELLs_lkp = array_column($GCELLs, 'userlabel', 'id');
		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}



		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_zte_ume_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$pm_data = array_column($pm_data, null, 'id');

		$radius_ops = [
			'C901140001'=>550, 'C901140034'=>1100, 'C901140035'=>1650, 'C901140036'=>2200, 'C901140037'=>2750, 'C901140038'=>3300, 'C901140039'=>3850, 'C901140040'=>4400, 'C901140041'=>4950, 'C901140042'=>5500, 'C901140043'=>6050, 'C901140044'=>6600, 'C901140045'=>7150,
			'C901140046'=>7700, 'C901140047'=>8250, 'C901140048'=>8800, 'C901140049'=>9350, 'C901140050'=>9900, 'C901140051'=>10450, 'C901140052'=>11000, 'C901140053'=>11550, 'C901140054'=>12100, 'C901140055'=>12650, 'C901140056'=>13200, 'C901140057'=>13750, 'C901140058'=>14300,
			'C901140059'=>14850, 'C901140060'=>15400, 'C901140061'=>15950, 'C901140062'=>16500, 'C901140063'=>17050, 'C901140064'=>17600, 'C901140065'=>18150, 'C901140066'=>18700, 'C901140067'=>19250, 'C901140068'=>19800, 'C901140069'=>20350, 'C901140070'=>20900, 'C901140071'=>21450,
			'C901140072'=>22000, 'C901140073'=>22550, 'C901140074'=>23100, 'C901140075'=>23650, 'C901140076'=>24200, 'C901140077'=>24750, 'C901140078'=>25300, 'C901140079'=>25850, 'C901140080'=>26400, 'C901140081'=>26950, 'C901140082'=>27500, 'C901140083'=>28050, 'C901140084'=>28600,
			'C901140085'=>29150, 'C901140086'=>29700, 'C901140087'=>30250, 'C901140088'=>30800, 'C901140089'=>31350, 'C901140090'=>31900, 'C901140091'=>32450, 'C901140092'=>33000, 'C901140093'=>33550, 'C901140094'=>34100, 'C901140095'=>34650, 'C901140096'=>35200
		];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname) {
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data)) {
			foreach ($pm_data as $GCELL) {
				if (count($GCELL)) {
					foreach ($radius_ops as $cid => $radius) {
						if (isset($GCELL[$cid]) && $GCELL[$cid] > 0) {
							$radius_data[$GCELL['moi_id']] = $radius;
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*ZTE 3G Cell RADIUS*/
	public function get_zte_3G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "AUMTSCELLKPI";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = [
			'C372480078','C372480079','C372480080','C372480081','C372480082','C372480083','C372480084','C372480085','C372480086','C372480087','C372480088','C372480089','C372480090','C372480091','C372480092','C372480093','C372480094','C372480095','C372480096','C372480097',
			'C372480098','C372480099','C372480100','C372480101','C372480102','C372480103','C372480104','C372480105','C372480106','C372480107','C372480108','C372480109','C372480110','C372480111','C372480112','C372480113','C372480114','C372480115','C372480116'
		];

		$counters = implode("','", $counter_list);

		$counters = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_zte_ume_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name ASC")->queryAll();
		$uLocalCell = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'userLabel' AS userlabel FROM ran_zte_ume_moi WHERE moc_id = $moc_id")->queryAll();

		$uLocalCell_lkp = array_column($uLocalCell, 'userlabel', 'id');

		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";
		$pm_data = Yii::app()->db_zte_ume_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$bts_data = [];
		foreach ($pm_data as $v) {
			if( isset($v['moi_id']) &&isset($uLocalCell_lkp[$v['moi_id']])) {
				if(!isset($bts_data[$v['moi_id']])) {
					$bts_data[$uLocalCell_lkp[$v['moi_id']]] =  [];
					foreach ($counter_lkp as $cid => $name) {
						if( isset($v[$cid]) ) {
							$bts_data[$uLocalCell_lkp[$v['moi_id']]][$cid] = $v[$cid];
						}
					}
				} else {
					foreach ($counter_lkp as $cid => $name) {
						if( isset($v[$cid]) ) {
							$bts_data[$uLocalCell_lkp[$v['moi_id']]][$cid] += $v[$cid];
						}
					}
				}
			}
		}

		$radius_lkp = [
			'C372480078'=>234, 'C372480079'=>703, 'C372480080'=>1172, 'C372480081'=>1641, 'C372480082'=>2109, 'C372480083'=>2578, 'C372480084'=>3281, 'C372480085'=>3984, 'C372480086'=>4688, 'C372480087'=>5391, 'C372480088'=>6328, 'C372480089'=>7266, 'C372480090'=>8203,
			'C372480091'=>9141, 'C372480092'=>10078, 'C372480093'=>11953, 'C372480094'=>13828, 'C372480095'=>15703, 'C372480096'=>17578, 'C372480097'=>19453, 'C372480098'=>21328, 'C372480099'=>25078, 'C372480100'=>28828, 'C372480101'=>32578, 'C372480102'=>36328, 'C372480103'=>40078,
			'C372480104'=>47578, 'C372480105'=>55078, 'C372480106'=>62578, 'C372480107'=>70078, 'C372480108'=>77578, 'C372480109'=>85078, 'C372480110'=>100078, 'C372480111'=>115078, 'C372480112'=>130078, 'C372480113'=>160078, 'C372480114'=>190078, 'C372480115'=>220078, 'C372480116'=>240000,
		];

		$radius_data = [];

		if(count($bts_data)) {
			foreach ($bts_data as $bts_id => $cntrs) {
				if(count($cntrs)) {
					foreach ($counter_lkp as $cid => $name) {
						if (isset($radius_lkp[$name]) && isset($cntrs[$cid]) && $cntrs[$cid] > 0) {
							$radius_data[$bts_id] = $radius_lkp[$name];
						}
					}
				}
			}
		}
		return $radius_data;
	}

	/*ZTE 4G Cell RADIUS*/
	public function get_zte_4G_cell_radius($moc_id, $lct)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$cg = "CELLTA";

		$cg = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_group WHERE name = '$cg' AND moc_id = $moc_id")->queryRow();
		$cg_id = $cg['id'];
		
		$cg_dur = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_counter_duration WHERE counter_group_id = $cg_id")->queryRow();
		$cg_dur_id = $cg_dur['duration_id'];

		$cg_dur_val = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM ran_zte_ume_pm_duration WHERE id = $cg_dur_id")->queryRow();
		$tbl_name = $cg_dur_val['table_name'];

		$cg_dur_sec = $cg_dur_val['no_of_sec'];

		$cg_lct = date('Y-m-d H:i:s', (floor((strtotime($lct) - $cg_dur_sec) / $cg_dur_sec) * $cg_dur_sec));

		$counter_list = [
			'C373556400','C373556401','C373556402','C373556403','C373556404','C373556405','C373556406','C373556407','C373556408','C373556409','C373556410','C373556411',
			'C373556412','C373556413','C373556415','C373556416','C373556417','C373556418','C373556419','C373556420','C373556421','C373556422','C373556423','C373556424'
		];

		$counters = implode("','", $counter_list);
		$counters_res = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_zte_ume_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id ORDER BY name ASC")->queryAll();

		$sel = "";
		$counter_lkp = [];

		if( sizeof($counters_res) > 0 )
		{
			$cntrs = array();
			foreach ($counters_res as $cntr) {
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct'";

		$pm_data = Yii::app()->db_zte_ume_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();


		$radius_ops = [
			'C373556400'=>'78','C373556401'=>'234','C373556402'=>'390','C373556403'=>'546','C373556404'=>'702','C373556405'=>'858','C373556406'=>'1014','C373556407'=>'1560','C373556408'=>'2106','C373556409'=>'2652','C373556410'=>'3120','C373556411'=>'3900','C373556412'=>'6318',
			'C373556413'=>'10062','C373556415'=>'13962','C373556416'=>'19968','C373556417'=>'29952','C373556418'=>'39936','C373556419'=>'49920','C373556420'=>'59982','C373556421'=>'69966','C373556422'=>'79950','C373556423'=>'89934','C373556424'=>'99996'
		];

		foreach ($counter_lkp as $cid => $cname) {
			if (isset($radius_ops[$cname])) {
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		$radius_data = [];

		if (count($pm_data)) {
			foreach ($pm_data as $wcel) {
				if (count($wcel)) {
					foreach ($radius_ops as $cid => $radius) {
						if (isset($wcel[$cid]) && $wcel[$cid] > 0) {
							$radius_data[$wcel['moi_id']] = $radius;
						}
					}
				}
			}
		}
		return $radius_data;
	}
}