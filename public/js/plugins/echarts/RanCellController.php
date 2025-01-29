<?php
class RanCellController extends KpimgmtController
{

	public function filters()
	{
		return array(
			// 'setCreds',
			'accessSimpleCheck',
		);
	}

	public function actionComputerancell ()
	{
		self::compute();
		Yii::app()->end();
	}

	public function compute()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		ini_set('memory_limit', '8192M');
		ini_set('max_execution_time', 600);
		// echo "Start: " . date('Y-m-d H:i:s') . "<br>";

		$ram_resp= array();
		$timeLog = [['Start', date("Y-m-d H:i:s")]];

		$vendor_lkp = ['Nokia'=>2, 'ZTE'=>5, 'Huawei'=>3];

		$command = Yii::app()->cm_pm_schema->createCommand();
		$sql = "TRUNCATE TABLE ran_cell";
		$command->setText($sql);
		$command->execute();

		#______________________________________________ LOOKUPS ___________________________________________________
		# Cron Settings
			$cron_settings = Yii::app()->ran_cm_pmschema->createCommand("SELECT * FROM cron_setting WHERE name IN ('ran_nokia_kpi', 'ran_zte_ume_kpi', 'ran_huawei_kpi')")->queryAll();
			$lct = [];
			if (isset($cron_settings))
			{
				foreach ($cron_settings as $settings)
				{
					if (isset($settings['params']))
					{
						$params = json_decode($settings['params'], true);
						if (json_last_error() == false && isset($params['lct']))
						{
							$lct[$settings['name']] = date('Y-m-d H:i:s', strtotime($params['lct']));
						}
						else
						{
							// LOG ERROR
						}
					}
				}
			}
			else
			{
				// LOG ERROR
			}
			$timeLog[] = ['cron_settings', date("Y-m-d H:i:s"), json_encode($lct)];
		# ZTE MOCs
			$d = self::get_data( 'ran_zte_ume_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$z_moc = [];
			foreach ($d as $k => $v)
			{
				if (isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$z_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$z_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['ZTE MOC Lookup', date("Y-m-d H:i:s")];
		# NOKIA MOCs
			$d = self::get_data( 'ran_nokia_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$n_moc = [];
			foreach ($d as $k => $v)
			{
				if (isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$n_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$n_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Nokia MOC Lookup', date("Y-m-d H:i:s")];
		# HUAWEI MOCs
			$d = self::get_data( 'ran_huawei_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$h_moc = [];
			foreach ($d as $k => $v)
			{
				if (isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$h_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$h_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Huawei MOC Lookup', date("Y-m-d H:i:s")];
		# HUAWEI MML MOCs
			$d = self::get_data( 'ran_huawei_mml_moc', ['id', 'user_label', 'parent_id'], null);
			$d = (count($d)) ? array_column($d, null, 'id') : [];
			$h_mml_moc = [];
			foreach ($d as $k => $v)
			{
				if (isset($v['parent_id']) && isset($d[$v['parent_id']]))
					$h_mml_moc[$d[$v['parent_id']]['user_label'] . "/" . $v['user_label']] = $v['id'];
				else
					$h_mml_moc[$v['user_label']] = $v['id'];
			}
			$timeLog[] = ['Huawei MML MOC Lookup', date("Y-m-d H:i:s")];

		# ZTE 2G LAC Lookup
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
			}
			$d = null;
			$timeLog[] = ['ZTE 2G LAC Lookup', date("Y-m-d H:i:s")];
		# ZTE BSC Lookup
			$d = self::get_data('ran_zte_ume_moi', ["id", "parameter_data ->> 'moId' AS bsc_id", "parameter_data ->> 'userLabel' AS user_label" ], ['moc_id'=>['int'=>$z_moc['ManagedElement/GBssFunction']]] );
			$z_BSC_lkp = (count($d)) ? array_column($d, null, 'bsc_id') : [];
			$timeLog[] = ['ZTE 4G eNodeB ENBFunctionFDD Lookup', date("Y-m-d H:i:s")];
		# ZTE 3G LAC Lookup
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
			}
			$d = null;
			$timeLog[] = ['ZTE 3G LAC Lookup', date("Y-m-d H:i:s")];
		# ZTE 4G URncFunction Lookup
			$d = self::get_data('ran_zte_ume_moi', ["id", "user_label", "parameter_data ->> 'moId' AS rnc_id"], ['moc_id'=>['int'=>$z_moc['ManagedElement/URncFunction']]] );
			$z_RNC_lkp = (count($d)) ? array_column($d, null, 'rnc_id') : [];
			$timeLog[] = ['ZTE 4G URncFunction Lookup', date("Y-m-d H:i:s")];

		# NOKIA 4G BSC Lookup
			$d = self::get_data('ran_nokia_moi', ["id", "moi_parts ->> 'BSC' AS bsc_id", "parameter_data ->> 'name' AS user_label" ], ['moc_id'=>['int'=>$n_moc['PLMN/BSC']]] );
			$n_BSC_lkp = (count($d)) ? array_column($d, null, 'bsc_id') : [];
			$timeLog[] = ['NOKIA 4G BSC Lookup', date("Y-m-d H:i:s")];
		# NOKIA 4G RNC Lookup
			$d = self::get_data('ran_nokia_moi', ["id", "moi_parts ->> 'RNC' AS rnc_id", "parameter_data ->> 'name' AS user_label" ], ['moc_id'=>['int'=>$n_moc['PLMN/RNC']]] );
			$n_RNC_lkp = (count($d)) ? array_column($d, null, 'rnc_id') : [];
			$timeLog[] = ['NOKIA 4G RNC Lookup', date("Y-m-d H:i:s")];
		# NOKIA BCCH LOOKUP
			$d = self::get_data('ran_nokia_moi', ['parent_moi_id', "parameter_data ->> 'initialFrequency' AS bcch"], ['moc_id'=>['int'=>$n_moc['BTS/TRX']], "parameter_data ->> 'preferredBcchMark'"=>['str'=>'1'] ] );
			$n_bcchTRX = (count($d)) ? array_column($d, 'bcch', 'parent_moi_id') : [];
			$d = null;
			$timeLog[] = ['NOKIA BCCH BTS/TRX LOOKUP', date("Y-m-d H:i:s")];

		# HUAWEI BCCH LOOKUP
			$d = self::get_data('ran_huawei_moi',["parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'CELLID' AS ci", "parameter_data ->> 'FREQ' AS bcch"], ['moc_id'=>['int'=>$h_moc['BSC6900GSMNE/BSC6900GSMGTRX']], "parameter_data ->> 'ISMAINBCCH'"=>['str'=>'YES']]);
			$h_bcchTRX_6900 = [];
			foreach ($d as $trx) {
				$h_bcchTRX_6900[$trx['bsc_id'] . '_' . $trx['ci']] = $trx['bcch'];
			}
			$timeLog[] = ['HUAWEI BCCH LOOKUP BSC6900GSMNE/BSC6900GSMGTRX', date("Y-m-d H:i:s")];

			$d = self::get_data('ran_huawei_moi',["parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'CELLID' AS ci", "parameter_data ->> 'FREQ' AS bcch"], ['moc_id'=>['int'=>$h_moc['BSC6910GSMNE/BSC6910GSMGTRX']], "parameter_data ->> 'ISMAINBCCH'"=>['str'=>'YES']]);
			$h_bcchTRX_6910 = [];
			foreach ($d as $trx) {
				$h_bcchTRX_6910[$trx['bsc_id'] . '_' . $trx['ci']] = $trx['bcch'];
			}
			$timeLog[] = ['HUAWEI BCCH LOOKUP BSC6910GSMNE/BSC6910GSMGTRX', date("Y-m-d H:i:s")];
		# Huawei BSC6900 lookup
			$d = self::get_data('ran_huawei_moi',["id", "parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$h_moc['EMS/BSC6900GSMNE']]]);
			$h_BSC6900_lkp = (count($d)) ? array_column($d, null, 'bsc_id') : [];
		# Huawei BSC6910 lookup
			$d = self::get_data('ran_huawei_moi',["id", "parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$h_moc['EMS/BSC6910GSMNE']]]);
			$h_BSC6910_lkp = (count($d)) ? array_column($d, null, 'bsc_id') : [];
		# Huawei RNC lookup
			$d = self::get_data('ran_huawei_moi',["id", "parameter_data ->> 'neID' AS rncneid", "parameter_data ->> 'name' AS user_label"], ['moc_id'=>['int'=>$h_moc['EMS/BSC6910UMTSNE']]]);
			$h_RNC_lkp = (count($d)) ? array_column($d, null, 'id') : [];

		#______________________________________________ 2G CELLS __________________________________________________
		NOKIA_2G_BTS:
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'SBTSId' AS bts_id",  "parameter_data ->> 'name' AS user_label", "business_ref_data AS sbzd"], ['moc_id'=>['int'=>$n_moc['BSC/BCF']], "parameter_data ->> 'adminState'"=>1] );
			$site_lkp = [];
			foreach ($d as $site)
			{
				$site_name = $site['user_label'];
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['user_label'] = $site_name;
					$site_lkp[$site['id']]['bts_id'] = $site['bts_id'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}

			$d = null;
			$timeLog[] = ['Get Nokia 2G Sites Lookup', date("Y-m-d H:i:s")];

			$BTS = array();
			$d = self::get_data(
				'ran_nokia_moi',
				[
					"id",
					"moi",
					"parent_moi_id AS p_id",
					"parameter_data ->> 'name' AS user_label",
					"moi_parts ->> 'BSC' AS bsc_id",
					"moi_parts ->> 'BCF' AS bts_id",
					"parameter_data ->> 'cellId' AS ci",
					"parameter_data ->> 'locationAreaIdLAC' AS lac",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at"
				],
				['moc_id'=>['int'=>[$n_moc['BCF/BTS']]], "parameter_data ->> 'adminState'"=>['str'=>1]]
			);

			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['ci']) &&  isset($c['user_label']) && isset($n_bcchTRX[$c['id']]) && isset($c['lac']) && isset($site_lkp[$c['p_id']]) && isset($n_BSC_lkp[$c['bsc_id']]))
					{
						$cell_name = self::convert_cell_id_format($c['user_label']);
						$bcch = $n_bcchTRX[$c['id']];
						$cell_identity_key = $c['lac'] . "_" . $c['ci'] . "_" . $bcch;

						$site_info = $site_lkp[$c['p_id']];
						
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => $n_BSC_lkp[$c['bsc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $c['bts_id'],
							'controller_id' => $c['bsc_id'],
							'cell_label' => $cell_name,
							'site_label' => $site_info['user_label'],
							'controller_label' => $n_BSC_lkp[$c['bsc_id']]['user_label'],
							'cell_moc_id' => $n_moc['BCF/BTS'],
							'site_moc_id' => $n_moc['BSC/BCF'],
							'controller_moc_id' => $n_moc['PLMN/BSC'],
							'vendor_id' => $vendor_lkp['Nokia'],
							'lat' => isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long' => isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info' => json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '2G',
						];

						$BTS[$cell_identity_key] = $this_cell;
					}
				}
			}			
			$d = null;
			$n_bcchTRX = null;

			if(count($BTS)) {
				self::insert_data($BTS);
			}
			$timeLog[] = ['Get Nokia 2G Cells', date("Y-m-d H:i:s")];
		ZTE_2G_GGsmCell:
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					'id',
					"parameter_data ->> 'moId' AS bts_id",
					"parameter_data ->> 'userLabel' AS user_label",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>['int'=>$z_moc['GBssFunction/GBtsSiteManager']]
				]
			);
			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['bts_id'] = $site['bts_id'];
					$site_lkp[$site['id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 2G Sites', date("Y-m-d H:i:s")];

			$GGsmCell = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"CONCAT(moi_parts ->> 'GBssFunction','_', moi_parts ->> 'GBtsSiteManager','_',moi_parts ->> 'GGsmCell') AS key",
					"parameter_data ->> 'userLabel' AS user_label",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'GBssFunction' AS bsc_id",
					"parameter_data ->> 'cellIdentity' AS ci",
					"parameter_data ->> 'refGLocationArea' AS reflac",
					"parameter_data ->> 'bcchFrequency' AS bcch",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				[
					'moc_id'=>['int'=>$z_moc['GBtsSiteManager/GGsmCell']],
					"parameter_data ->> 'OperState'"=>['str'=>'Unblock'],
					"parameter_data ->> 'AdmState'"=>['str'=>'Unblock']
				]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['ci']) &&  isset($c['user_label']) && isset($c['bcch']) && isset($c['reflac']) && isset($gLac[$c['reflac']]) && isset($site_lkp[$c['p_id']]) && isset($z_BSC_lkp[$c['bsc_id']]))
					{
						$cell_name = $c['user_label'];
						$lac = $gLac[$c['reflac']];
						$cell_identity_key = $lac . "_" . $c['ci'] . "_" . $c['bcch'];

						
						$site_info = $site_lkp[$c['p_id']];
						
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }


						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => $z_BSC_lkp[$c['bsc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $site_info['bts_id'],
							'controller_id' => $c['bsc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $z_BSC_lkp[$c['bsc_id']]['user_label'],
							'cell_moc_id' => $z_moc['GBtsSiteManager/GGsmCell'],
							'site_moc_id' => $z_moc['GBssFunction/GBtsSiteManager'],
							'controller_moc_id' => $z_moc['ManagedElement/GBssFunction'],
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '2G',
						];

						$GGsmCell[$cell_identity_key] = $this_cell;
					}
				}
				if(count($GGsmCell)) {
					self::insert_data($GGsmCell);
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 2G Cells Lookup', date("Y-m-d H:i:s")];
		HUAWEI_2G_GCELL_6900:
			$d = self::get_data('ran_huawei_moi',["id", "parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'BTSID' AS bts_id", "parameter_data ->> 'BTSNAME' AS user_label", "business_ref_data AS sbzd"], ['moc_id'=>['int'=>$h_moc['BSC6900GSMNE/BSC6900GSMBTS']]]);

			$site_lkp = [];
			foreach ($d as $site)
			{
				$key = $site['bsc_id'].'_'.$site['bts_id'];

				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$key]['id'] = $site['id'];
					$site_lkp[$key]['user_label'] = $site['user_label'];
					$site_lkp[$key]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$key]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$key]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$key]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$key]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$key]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}


			$d = null;
			$timeLog[] = ['Get Huawei 2G Sites Lookup BSC6900GSMNE/BSC6900GSMBTS', date("Y-m-d H:i:s")];

			$GCELL_6900 = array();
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parameter_data ->> 'neID' AS bsc_id",
					"parameter_data ->> 'BTSID' AS bts_id",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"parameter_data ->> 'LAC' AS lac",
					"parameter_data ->> 'CELLID' AS ci",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at"
				],
				[ 'moc_id'=>['int'=>[$h_moc['BSC6900GSMNE/BSC6900GSMCell']]]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					$key = $c['bsc_id']. "_" .$c['ci'];
					$site_check_key = $c['bsc_id']. '_' .$c['bts_id'];

					if (isset($c['ci']) && isset($c['user_label']) && isset($h_bcchTRX_6900[$key]) && isset($c['lac']) && isset($site_lkp[$site_check_key]) && isset($h_BSC6900_lkp[$c['bsc_id']]))
					{
						$cell_identity_key = $c['lac'] . "_" . $c['ci'] . "_" . $h_bcchTRX_6900[$key];
						$site_info = $site_lkp[$site_check_key];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [

							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => $h_BSC6900_lkp[$c['bsc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $c['bts_id'],
							'controller_id' => $c['bsc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $h_BSC6900_lkp[$c['bsc_id']]['user_label'],
							'cell_moc_id' => $h_moc['BSC6900GSMNE/BSC6900GSMCell'],
							'site_moc_id' => $h_moc['BSC6900GSMNE/BSC6900GSMBTS'],
							'controller_moc_id' => $h_moc['EMS/BSC6900GSMNE'],
							'vendor_id' => $vendor_lkp['Huawei'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '2G',

						];

						$GCELL_6900[$cell_identity_key] = $this_cell;
					}
				}
				if(count($GCELL_6900)) {
					self::insert_data($GCELL_6900);
				}
			}			
			$d = null;
			$timeLog[] = ['Get Huawei 2G Cells BSC6900GSMNE/BSC6900GSMCell', date("Y-m-d H:i:s")];
		HUAWEI_2G_GCELL_6910:
			$d = self::get_data('ran_huawei_moi',["id", "parameter_data ->> 'neID' AS bsc_id", "parameter_data ->> 'BTSID' AS bts_id",  "parameter_data ->> 'BTSNAME' AS user_label", "business_ref_data AS sbzd"], ['moc_id'=>['int'=>$h_moc['BSC6910GSMNE/BSC6910GSMBTS']]]);

			$site_lkp = [];
			foreach ($d as $site)
			{
				$key = $site['bsc_id'].'_'.$site['bts_id'];

				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$key]['id'] = $site['id'];
					$site_lkp[$key]['user_label'] = $site['user_label'];
					$site_lkp[$key]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$key]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$key]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$key]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$key]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$key]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}


			$d = null;
			$timeLog[] = ['Get Huawei 2G Sites Lookup BSC6910GSMNE/BSC6910GSMBTS', date("Y-m-d H:i:s")];

			$GCELL_6910 = array();
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parameter_data ->> 'neID' AS bsc_id",
					"parameter_data ->> 'BTSID' AS bts_id",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"parameter_data ->> 'LAC' AS lac",
					"parameter_data ->> 'CELLID' AS ci",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at"
				],
				[ 'moc_id'=>['int'=>[$h_moc['BSC6910GSMNE/BSC6910GSMGCELL']]]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					$key = $c['bsc_id']. "_" .$c['ci'];
					$site_check_key = $c['bsc_id']. '_' .$c['bts_id'];
					
					if (isset($c['ci']) && isset($c['user_label']) && isset($h_bcchTRX_6910[$key]) && isset($c['lac']) && isset($site_lkp[$site_check_key]) && isset($h_BSC6910_lkp[$c['bsc_id']]))
					{
						$cell_identity_key = $c['lac'] . "_" . $c['ci'] . "_" . $h_bcchTRX_6910[$key];
						$site_info = $site_lkp[$site_check_key];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [

							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => $h_BSC6910_lkp[$c['bsc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $c['bts_id'],
							'controller_id' => $c['bsc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $h_BSC6910_lkp[$c['bsc_id']]['user_label'],
							'cell_moc_id' => $h_moc['BSC6910GSMNE/BSC6910GSMGCELL'],
							'site_moc_id' => $h_moc['BSC6910GSMNE/BSC6910GSMBTS'],
							'controller_moc_id' => $h_moc['EMS/BSC6910GSMNE'],
							'vendor_id' => $vendor_lkp['Huawei'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '2G',

						];
						$GCELL_6910[$cell_identity_key] = $this_cell;
					}
				}
				if(count($GCELL_6910)) {
					self::insert_data($GCELL_6910);
				}
			}
			$d = null;
			$timeLog[] = ['Get Huawei 2G Cells BSC6910GSMNE/BSC6910GSMGCELL', date("Y-m-d H:i:s")];
		#______________________________________________ 5G CELLS __________________________________________________
		NOKIA_3G_WCEL:
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'name' AS user_label", "business_ref_data AS sbzd"], ['moc_id'=>['int'=>$n_moc['RNC/WBTS']]] );
			$site_lkp = [];

			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
				
			}
			$d = null;
			$timeLog[] = ['Get Nokia 3G Sites Lookup', date("Y-m-d H:i:s")];

			$WCEL = array();
			$d = self::get_data(
				'ran_nokia_moi',
				[
					"id",
					"moi",
					"CONCAT(moi_parts ->> 'RNC','_',parameter_data ->> 'CId') AS key",
					"user_label",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'RNC' AS rnc_id",
					"moi_parts ->> 'WBTS' AS site_id",
					"moi_parts ->> 'WCEL' AS cell_id",
					"parameter_data ->> 'CId' AS ci",
					"parameter_data ->> 'LAC' AS lac",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at"
				],
				['moc_id'=>['int'=>[$n_moc['WBTS/WCEL']]], "parameter_data ->> 'AdminCellState'"=>['str'=>1]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['key']) && isset($c['user_label']) && isset($site_lkp[$c['p_id']]) && isset($n_RNC_lkp[$c['rnc_id']]))
					{
						$site_info = $site_lkp[$c['p_id']];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => $n_RNC_lkp[$c['rnc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $c['site_id'],
							'controller_id' => $c['rnc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $n_RNC_lkp[$c['rnc_id']]['user_label'],
							'cell_moc_id' => $n_moc['WBTS/WCEL'],
							'site_moc_id' => $n_moc['RNC/WBTS'],
							'controller_moc_id' => $n_moc['PLMN/RNC'],
							'vendor_id' => $vendor_lkp['Nokia'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '3G',
						];

						$WCEL[$c['key']] = $this_cell;
					}
				}
				if(count($WCEL)) {
					self::insert_data($WCEL);
				}
			}			
			$d = null;
			$timeLog[] = ['Get Nokia 3G Cells', date("Y-m-d H:i:s")];
		ZTE_3G_UUtranCellFDD:
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"CONCAT(moi_parts ->> 'SubNetwork','_',moi_parts ->> 'ManagedElement','_',moi_parts ->> 'NodeBFunction') AS moi",
					"id",
					"parameter_data ->> 'userLabel' AS user_label",
					"parameter_data ->> 'nodeBId' AS site_id",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$z_moc['ManagedElement/NodeBFunction']
					]
				]
			);
			$nodeb_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site_name) && trim($site_name) != '')
				{
					$site_name_exp = explode('(', $site_name);
					if(count($site_name_exp) > 0)
					{
						$nodeb_lkp[$site['moi']]['id'] = $site['id'];
						$nodeb_lkp[$site['moi']]['site_id'] = $site['site_id'];
						$nodeb_lkp[$site['moi']]['user_label'] = $site['user_label'];
						if (isset($site['sbzd']))
						{
							$sbzd = json_decode($site['sbzd'], true);
							$nodeb_lkp[$site['moi']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
							$nodeb_lkp[$site['moi']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
							$nodeb_lkp[$site['moi']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
							$nodeb_lkp[$site['moi']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
							$nodeb_lkp[$site['moi']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
							$nodeb_lkp[$site['moi']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
						}
					}
				}
			}
			$d = null;
			$site_lkp = [];
			# ZTE 3G ULocalCell Lookup
				$d = self::get_data(
					'ran_zte_ume_moi',
					[
						"moi_parts ->> 'ManagedElement' AS ne",
						"moi_parts ->> 'NodeBFunction' AS nbf",
						"parameter_data ->> 'localCellId' AS lcid",
						"parameter_data ->> 'userLabel' AS user_label",
						"CONCAT(moi_parts ->> 'SubNetwork','_',moi_parts ->> 'ManagedElement','_',moi_parts ->> 'NodeBFunction') AS moi",
					],
					[
						'moc_id'=>[
							'int'=>$z_moc['USector/ULocalCell']
						]
					]
				);

				if(count($d))
				{
					foreach ($d as $ulc) {

						$k1 = ($ulc['nbf'] == 1) ? $ulc['ne'] : $ulc['nbf'];

						if(isset($ulc['moi']) && isset($nodeb_lkp[$ulc['moi']]))
						{
							$site_lkp[ $k1 .'_'. $ulc['lcid'] .'_'. $ulc['user_label'] ] = $nodeb_lkp[ $ulc['moi'] ];
						}
					}

				}
				$d = null;

			$UUtranCellFDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"CONCAT(moi_parts ->> 'URncFunction','_', moi_parts ->> 'UUtranCellFDD') AS key",
					"parameter_data ->> 'userLabel' AS user_label",
					"moi_parts ->> 'URncFunction' AS rnc_id",
					"parameter_data ->> 'cId' AS ci",
					"parameter_data ->> 'localCellId' AS lcid",
					"parameter_data ->> 'moId' AS cell_id",
					"parameter_data ->> 'refULocationArea' AS reflac",
					"parameter_data ->> 'refUIubLink' AS refulublink",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				[
					'moc_id'=>[
						'int'=>[$z_moc['URncFunction/UUtranCellFDD']]
					],
					"parameter_data ->> 'AdmState'"=>['str'=>'Unblock'],
					"parameter_data ->> 'OperState'"=>['str'=>'Enabled']
				]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					$nodebFunction_Management = null;

					if(isset($c['refulublink']))
					{
						$refulublink_exp = explode(',UIubLink=', $c['refulublink']);
						$nodebFunction_Management = isset($refulublink_exp[1]) ? $refulublink_exp[1] : null;						
					}
					$key = null;
					if(isset($nodebFunction_Management) && isset($c['lcid']) && isset($c['user_label']))
					{
						$key = $nodebFunction_Management.'_'.$c['lcid'].'_'.$c['user_label'];
					}
					if (isset($c['key']) && isset($c['ci'])  && isset($c['rnc_id']) && isset($c['user_label']) && isset($c['reflac']) && isset($uLac[$c['reflac']]) && isset($key) && isset($site_lkp[$key]) && isset($z_RNC_lkp[$c['rnc_id']]))
					{
						$site_info = $site_lkp[$key];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }


						$cell_identity_key = $c['rnc_id'] . '_' . $c['ci'];

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => $z_RNC_lkp[$c['rnc_id']]['id'],
							'cell_id' => $c['ci'],
							'site_id' => $site_info['site_id'],
							'controller_id' => $c['rnc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $z_RNC_lkp[$c['rnc_id']]['user_label'],
							'cell_moc_id' => $z_moc['URncFunction/UUtranCellFDD'],
							'site_moc_id' => $z_moc['ManagedElement/NodeBFunction'],
							'controller_moc_id' => $z_moc['ManagedElement/URncFunction'],
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '3G',
						];

						$UUtranCellFDD[$cell_identity_key] = $this_cell;
					}
				}
				if(count($UUtranCellFDD)) {
					self::insert_data($UUtranCellFDD);
				}
			}

			
			$d = null;
			$timeLog[] = ['Get ZTE 3G Cells Lookup', date("Y-m-d H:i:s")];
		HUAWEI_3G_BSC6910UMTSNODEB:
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parent_moi_id",
					"parameter_data ->> 'neID' AS rncneid",
					"parameter_data ->> 'NODEBID' AS nodeb_id",
					"parameter_data ->> 'NODEBNAME' AS user_label",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$h_moc['BSC6910UMTSNE/BSC6910UMTSNODEB']
					]
				]
			);
			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{

					$sbzd = json_decode($site['sbzd'], true);
					$key = $site['parent_moi_id'] . '_' . $site['rncneid'];
					$site_lkp[$key]['id'] = $site['id'];
					$site_lkp[$key]['user_label'] = $site['user_label'];
					$site_lkp[$key]['nodeb_id'] = $site['nodeb_id'];
					$site_lkp[$key]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$key]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$key]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$key]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$key]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$key]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}

			$d = null;
			$timeLog[] = ['Get Huawei 3G Sites Lookup', date("Y-m-d H:i:s")];

			$BSC6910UMTSUCELL = array();
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parent_moi_id",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"parameter_data ->> 'neID' AS rncneid",
					"parameter_data ->> 'NODEBNAME' AS nodebname",
					"parameter_data ->> 'LOGICRNCID' AS rnc_id",
					"parameter_data ->> 'CELLID' AS ci",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				[
					'moc_id'=>[
						'int'=>$h_moc['BSC6910UMTSNE/BSC6910UMTSUCELL']
					]
				]
			);		
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['ci']) && isset($c['user_label']) && isset($c['parent_moi_id']) && isset($c['rncneid']) && isset($site_lkp[$c['parent_moi_id'] . '_' . $c['rncneid']]) && isset($h_RNC_lkp[$c['parent_moi_id']]))
					{
						$key = $c['rnc_id'] . '_' . $c['ci'];
						$site_check_key = $c['parent_moi_id'] . '_' . $c['rncneid'];

						$site_info = $site_lkp[$site_check_key];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }


						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => $c['parent_moi_id'],
							'cell_id' => $c['ci'],
							'site_id' => $site_info['nodeb_id'],
							'controller_id' => $c['rnc_id'],
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => $h_RNC_lkp[$c['parent_moi_id']]['user_label'],
							'cell_moc_id' => $h_moc['BSC6910UMTSNE/BSC6910UMTSUCELL'],
							'site_moc_id' => $h_moc['BSC6910UMTSNE/BSC6910UMTSNODEB'],
							'controller_moc_id' => $h_moc['EMS/BSC6910UMTSNE'],
							'vendor_id' => $vendor_lkp['Huawei'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '3G',
						];

						$BSC6910UMTSUCELL[$key] = $this_cell;
					}
				}
				if(count($BSC6910UMTSUCELL)) {
					self::insert_data($BSC6910UMTSUCELL);
				}
			}
			$d = null;
			$timeLog[] = ['Get Huawei 3G Cells', date("Y-m-d H:i:s")];
		#______________________________________________ 4G CELLS __________________________________________________
		NOKIA_4G_LNCEL:
			$d = self::get_data('ran_nokia_moi', ['id', "parameter_data ->> 'name' AS user_label", "business_ref_data AS sbzd"], ['moc_id'=>['int'=>$n_moc['MRBTS/LNBTS']]] );
			$site_lkp = [];
			foreach ($d as $site)
			{
				$site_name = $site['user_label'];
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['user_label'] = $site_name;
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Get Nokia 4G Sites Lookup', date("Y-m-d H:i:s")];

			$LNCEL = array();

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
					"created_at",
					"updated_at",
				],
				['moc_id'=>['int'=>[$n_moc['LNBTS/LNCEL']]], "parameter_data ->> 'administrativeState'"=>['str'=>1]]
			);
			if (count($d))
			{
				foreach ($d as $c)
				{
					if (isset($c['enbid']) &&  isset($c['pci']) && isset($c['ci']) && isset($site_lkp[$c['p_id']]))
					{
						$cell_name = $c['user_label'];
						$key = $c['enbid'] . '_' . $c['pci'] . '_' . $c['ci']; 

						$site_info = $site_lkp[$c['p_id']];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [

							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $c['enbid'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $n_moc['LNBTS/LNCEL'],
							'site_moc_id' => $n_moc['MRBTS/LNBTS'],
							'controller_moc_id' => 'NULL', 
							'vendor_id' => $vendor_lkp['Nokia'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$LNCEL[$key] = $this_cell;
					}
				}
				if(count($LNCEL)) {
					self::insert_data($LNCEL);
				}
			}			
			$d = null;
			$timeLog[] = ['Get Nokia 4G Cells', date("Y-m-d H:i:s")];
		ZTE_4G_ITBBU_FDD_CUEUtranCellFDDLTE:
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"parameter_data ->> 'eNBId' AS enbid",
					"parameter_data ->> 'userLabel' AS user_label",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$z_moc['ManagedElement/ENBCUCPFunction']
					]
				]
			);
			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['id'] = $site['id'];
					$site_lkp[$site['id']]['enbid'] = $site['enbid'];
					$site_lkp[$site['id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (ENBCUCPFunction) Sites', date("Y-m-d H:i:s")];

			$d = self::get_data('ran_zte_ume_moi', ["id", "parent_moi_id"], ['moc_id'=>['int'=>$z_moc['ENBCUCPFunction/CULTE']]] );
			$culte_lkp = (count($d)) ? array_column($d, 'parent_moi_id', 'id') : [];
			$CUEUtranCellFDDLTE = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'CUEUtranCellFDDLTE' AS cueutrancellfddlte",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				[
					'moc_id'=>['int'=>$z_moc['CULTE/CUEUtranCellFDDLTE']],
					"parameter_data ->> 'adminState'"=>['str'=>0],
					"parameter_data ->> 'operState'"=>['str'=>0]
				]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['pci']) && isset($c['ci']) && isset($culte_lkp[$c['p_id']]) && isset($site_lkp[$culte_lkp[$c['p_id']]]))
					{
						$site_info = $site_lkp[$culte_lkp[$c['p_id']]];
						$key = $site_info['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];

						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $site_info['enbid'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $z_moc['CULTE/CUEUtranCellFDDLTE'],
							'site_moc_id' => $z_moc['ManagedElement/ENBCUCPFunction'],
							'controller_moc_id' => 'NULL',
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$CUEUtranCellFDDLTE[$key] = $this_cell;
					}
				}
				if(count($CUEUtranCellFDDLTE)) {
					self::insert_data($CUEUtranCellFDDLTE);
				}
			}			
			$d = null;
			$timeLog[] = ['Get ZTE 4G (CUEUtranCellFDDLTE) Cells Lookup', date("Y-m-d H:i:s")];
		ZTE_4G_ITBBU_TDD_CUEUtranCellTDDLTE:
			/*Parent is same as FDD*/
			$CUEUtranCellTDDLTE = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"parent_moi_id AS p_id",
					"moi_parts ->> 'CUEUtranCellTDDLTE' AS cueutrancelltddlte",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				['moc_id'=>['int'=>[$z_moc['CULTE/CUEUtranCellTDDLTE']]], "parameter_data ->> 'adminState'"=>['str'=>0], "parameter_data ->> 'operState'"=>['str'=>0]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['p_id']) && isset($c['pci']) && isset($c['ci']) && isset($culte_lkp[$c['p_id']]) && isset($site_lkp[$culte_lkp[$c['p_id']]]))
					{
						$site_info = $site_lkp[$culte_lkp[$c['p_id']]];
						$key = $site_info['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];
						
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $site_info['enbid'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $z_moc['CULTE/CUEUtranCellTDDLTE'],
							'site_moc_id' => $z_moc['ManagedElement/ENBCUCPFunction'],
							'controller_moc_id' => 'NULL',
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$CUEUtranCellTDDLTE[$key] = $this_cell;
					}
				}
				if(count($CUEUtranCellTDDLTE)) {
					self::insert_data($CUEUtranCellTDDLTE);
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (CUEUtranCellTDDLTE) Cells Lookup', date("Y-m-d H:i:s")];
		ZTE_4G_SDR_FDD_EUtranCellFDD:
			/* ZTE 4G SDR FDD Sites*/
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					'id',
					"parameter_data ->> 'userLabel' AS user_label",
					"parameter_data ->> 'eNBId' AS enbid",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$z_moc['ManagedElement/ENBFunctionFDD']
					]
				]
			);
			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['enbid'] = $site['enbid'];
					$site_lkp[$site['id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellFDD) Sites', date("Y-m-d H:i:s")];

			$EUtranCellFDD = array();

			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"CONCAT(moi_parts ->> 'SubNetwork','_',moi_parts ->> 'ManagedElement','_',moi_parts ->> 'ENBFunctionFDD','_',moi_parts ->> 'EUtranCellFDD') AS moi_key",
					"parent_moi_id AS p_id",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				['moc_id'=>['int'=>[$z_moc['ENBFunctionFDD/EUtranCellFDD']]]]
			);

			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['pci']) && isset($c['ci']) && isset($c['p_id']) && isset($site_lkp[$c['p_id']]))
					{
						$site_info = $site_lkp[$c['p_id']];
						$key = $site_info['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];
						
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $site_info['enbid'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $z_moc['ENBFunctionFDD/EUtranCellFDD'],
							'site_moc_id' => $z_moc['ManagedElement/ENBFunctionFDD'],
							'controller_moc_id' => 'NULL',
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$EUtranCellFDD[$key] = $this_cell;
					}
				}
				if(count($EUtranCellFDD)) {
					self::insert_data($EUtranCellFDD);
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellFDD) Cells Lookup', date("Y-m-d H:i:s")];
		ZTE_4G_SDR_TDD_EUtranCellTDD:
			
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"parameter_data ->> 'userLabel' AS user_label",
					"parameter_data ->> 'eNBId' AS site_id",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$z_moc['ManagedElement/ENBFunctionTDD']
					]
				]
			);

			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['id']]['site_id'] = $site['site_id'];
					$site_lkp[$site['id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellTDD) Sites', date("Y-m-d H:i:s")];

			$EUtranCellTDD = array();
			$d = self::get_data(
				'ran_zte_ume_moi',
				[
					"id",
					"CONCAT(moi_parts ->> 'SubNetwork','_',moi_parts ->> 'ManagedElement','_',moi_parts ->> 'ENBFunctionTDD','_',moi_parts ->> 'EUtranCellTDD') AS moi_key",
					"moi_parts ->> 'ENBFunctionTDD' AS enbid",
					"parent_moi_id AS p_id",
					"parameter_data ->> 'pci' AS pci",
					"parameter_data ->> 'cellLocalId' AS ci",
					"user_label",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				['moc_id'=>['int'=>[$z_moc['ENBFunctionTDD/EUtranCellTDD']]]]
			);
			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['enbid']) &&  isset($c['pci']) && isset($c['ci']) && isset($c['p_id']) && isset($site_lkp[$c['p_id']]))
					{
						$key = $c['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];
						
						$site_info = $site_lkp[$c['p_id']];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $c['p_id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $site_info['site_id'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $z_moc['ENBFunctionTDD/EUtranCellTDD'],
							'site_moc_id' => $z_moc['ManagedElement/ENBFunctionTDD'],
							'controller_moc_id' => 'NULL',
							'vendor_id' => $vendor_lkp['ZTE'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$EUtranCellTDD[$key] = $this_cell;
					}
				}
				if(count($EUtranCellTDD)) {
					self::insert_data($EUtranCellTDD);
				}
			}
			$d = null;
			$timeLog[] = ['Get ZTE 4G (EUtranCellTDD) Cells Lookup', date("Y-m-d H:i:s")];			
		HUAWEI_4G_BTS3900CELL:
			HUAWEI:
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parameter_data ->> 'neID' AS ne_id",
					"parameter_data ->> 'ENODEBFUNCTIONNAME' AS user_label",
					"parameter_data ->> 'ENODEBID' AS enbid",
					"business_ref_data AS sbzd"
				],
				[
					'moc_id'=>[
						'int'=>$h_moc['BTS3900NE/BTS3900ENODEBFUNCTION']
					]
				]
			);
			$site_lkp = [];
			foreach ($d as $site)
			{
				if (isset($site['sbzd']))
				{
					$sbzd = json_decode($site['sbzd'], true);
					$site_lkp[$site['ne_id']]['id'] = $site['id'];
					$site_lkp[$site['ne_id']]['enbid'] = $site['enbid'];
					$site_lkp[$site['ne_id']]['user_label'] = $site['user_label'];
					$site_lkp[$site['ne_id']]['lat'] = isset($sbzd['lat']) ? $sbzd['lat'] : null;
					$site_lkp[$site['ne_id']]['long'] = isset($sbzd['long']) ? $sbzd['long'] : null;
					$site_lkp[$site['ne_id']]['zone'] = isset($sbzd['zone']) ? $sbzd['zone'] : null;
					$site_lkp[$site['ne_id']]['thana'] = isset($sbzd['thana']) ? $sbzd['thana'] : null;
					$site_lkp[$site['ne_id']]['district'] = isset($sbzd['district']) ? $sbzd['district'] : null;
					$site_lkp[$site['ne_id']]['commercial_zone'] = isset($sbzd['commercial_zone']) ? $sbzd['commercial_zone'] : null;
				}
			}
			$d = null;
			$timeLog[] = ['Huawei 4G eNodeB BTS3900ENODEBFUNCTION Lookup', date("Y-m-d H:i:s")];

			$BTS3900CELL = array();
			$d = self::get_data(
				'ran_huawei_moi',
				[
					"id",
					"parameter_data ->> 'PHYCELLID' AS pci",
					"parameter_data ->> 'CELLID' AS ci",
					"parameter_data ->> 'neID' AS ne_id",
					"parameter_data ->> 'CELLNAME' AS user_label",
					"business_ref_data AS bzd",
					"created_at",
					"updated_at",
				],
				['moc_id'=>['int'=>[$h_moc['BTS3900NE/BTS3900CELL']]]]
			);

			if (count($d))
			{
				foreach ($d as $k=>$c)
				{
					if (isset($c['pci']) && isset($c['ci']) && isset($site_lkp[$c['ne_id']]))
					{
						$site_info = $site_lkp[$c['ne_id']];						
						$key = $site_info['enbid'] . '_' . $c['pci'] . '_' . $c['ci'];
						$geo_info = [];
						if (isset($site_info['zone']))             { $geo_info['zone'] = strtolower($site_info['zone']); }
						if (isset($site_info['thana']))            { $geo_info['thana'] = strtolower($site_info['thana']); }
						if (isset($site_info['district']))         { $geo_info['district'] = strtolower($site_info['district']); }
						if (isset($site_info['commercial_zone']))  { $geo_info['commercial_zone'] = strtolower($site_info['commercial_zone']); }

						$bzd = (isset($c['bzd'])) ? json_decode($c['bzd'], TRUE) : [];
						if (isset($bzd['azimuth']) && is_numeric($bzd['azimuth'])) {$geo_info['azimuth'] = $bzd['azimuth']; }

						$this_cell = [
							'cell_fk' => $c['id'],
							'site_fk' => $site_info['id'],
							'controller_fk' => 'NULL',
							'cell_id' => $c['ci'],
							'site_id' => $site_info['enbid'],
							'controller_id' => 'NULL',
							'cell_label' => $c['user_label'],
							'site_label' => $site_info['user_label'],
							'controller_label' => 'NULL',
							'cell_moc_id' => $h_moc['BTS3900NE/BTS3900CELL'],
							'site_moc_id' => $h_moc['BTS3900NE/BTS3900ENODEBFUNCTION'],
							'controller_moc_id' => 'NULL',
							'vendor_id' => $vendor_lkp['Huawei'],
							'lat'=>isset($site_info['lat'])?$site_info['lat']: 'NULL',
							'long'=>isset($site_info['long'])?$site_info['long']: 'NULL',
							'geo_info'=>json_encode($geo_info),
							'details' => 'NULL',
							'tech' => '4G',
						];

						$BTS3900CELL[$key] = $this_cell;
					}
				}
				if(count($BTS3900CELL)) {
					self::insert_data($BTS3900CELL);
				}
			}			
			$d = null;
			$timeLog[] = ['Get Huawei 4G Cells', date("Y-m-d H:i:s")];
			$lat_long_lkp = null;
		#__________________________________________________________________________________________________________*/
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
					
					if (isset($search['int']) && !is_array($search['int']))
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

					}
					else
					{
						$where_parts[] = $col . " IN ('" . implode("','", $search) . "')";
					}

				}
				else
				{
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
		
		$data = Yii::app()->cm_pm_schema->createCommand($qry)->queryAll();

		return $data;
	}

	public function convert_to_site( $v )
	{
		preg_match('/\d+/', $v, $matches);
		$v_exp = explode('_', $v);
		if (isset($matches[0]))
		{
			return substr($v, 0, strpos($v, $matches[0]) + strlen($matches[0]));
		}
		else
		{
			return $v;
		}
	}

	public function convert_to_generic($v)
	{
		preg_match('/\d+/', $v, $matches);
		$v_exp = explode('_', $v);
		if (isset($matches[0]))
		{
			return substr($v, 0,3) . $matches[0];
		}
		else
		{
			return $v;
		}
	}

	public function cell_to_site($input)
	{
		if (isset($input) && strlen($input) >= 10 && $input[9] === '_')
		{
			return substr($input, 0, 9);
		}
		else
		{
			return $input;
		}
	}

	public function convert_cell_id_format( $input )
	{
		# Converts Cell Name from formate "DHKX1244S4" to "DHK_X1244_4"
		$convertedString = $input;

		if (isset($convertedString) && strlen($input) > 9 && !strpos($input, "_") !== false)
		{		
			// Insert underscore between third and fourth character
			$convertedString = substr_replace($input, '_', 3, 0);

			// Replace the 9th character with an underscore
			$convertedString = substr_replace($convertedString, '_', 9, 1);

		}
		return $convertedString;
	}

	public function insert_data($data)
	{
		if(count($data))
		{
			$limit = 5000;
			$inst_data = [];

			foreach($data as $row)
			{
				$inst_data[] = "(".$row['cell_fk'].",".$row['site_fk'].",".$row['controller_fk'].",".$row['cell_id'].",".$row['site_id'].",".$row['controller_id'].",'".$row['cell_label']."','".$row['site_label']."','".$row['controller_label']."',".
				$row['cell_moc_id'].",".$row['site_moc_id'].",".$row['controller_moc_id'].",".$row['vendor_id'].",".round($row['lat'],6).",".round($row['long'],6).",'". pg_escape_string($row['geo_info'])."',".pg_escape_string($row['details']).",'".$row['tech']."')";
				if(count($inst_data) == $limit)
				{

					$command = Yii::app()->ran_cm_pmschema->createCommand();
					
					$sql = "INSERT INTO ran_cell
						(cell_fk,site_fk,controller_fk,cell_id,site_id,controller_id,cell_label,site_label,controller_label,cell_moc_id,site_moc_id,controller_moc_id,vendor_id,lat,long,geo_info,details,tech)
					VALUES " . implode(',', $inst_data);

					$command->setText($sql);
					$command->execute();
					
					$inst_data = [];
				} 
			}

			$command = Yii::app()->ran_cm_pmschema->createCommand();
			
			$sql = "INSERT INTO ran_cell
			(cell_fk,site_fk,controller_fk,cell_id,site_id,controller_id,cell_label,site_label,controller_label,cell_moc_id,site_moc_id,controller_moc_id,vendor_id,lat,long,geo_info,details,tech)
			VALUES " . implode(',', $inst_data);

			$command->setText($sql);
			$command->execute();

			$inst_data = [];
		}
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
		$first_counter = null;
		if ( sizeof($counters) > 0 )
		{
			$first_counter = "t.c_vals->>'c".$counters[0]['id']."'";
			$cntrs = array();
			foreach ($counters as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}
		if (is_null($first_counter))
		{
			return [];
		}
		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND $first_counter IS NOT NULL";
		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$bts_data = [];
		foreach ($pm_data as $v)
		{
			if ( isset($v['moi_id']) && isset($trxes_lkp[$v['moi_id']]))
			{
				$parent_id = $trxes_lkp[$v['moi_id']];
				if (!isset($bts_data[$parent_id]))
				{
					$bts_data[$parent_id] = [];
					foreach ($counter_lkp as $cid => $name)
					{
						$bts_data[$parent_id][$cid] = $v[$cid];
					}
				}
				else
				{
					foreach ($counter_lkp as $cid => $name)
					{
						if ( isset($v[$cid]) )
						{
							$bts_data[$parent_id][$cid] += $v[$cid];
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

		if (count($bts_data))
		{
			foreach ($bts_data as $bts_id => $cntrs)
			{
				if (count($cntrs))
				{
					$r = null;
					foreach ($counter_lkp as $cid => $name)
					{
						if (isset($radius_lkp[$name]) && isset($cntrs[$cid]) && $cntrs[$cid] > 0)
						{
							$r = $radius_lkp[$name];
						}
					}
					if (isset($r))
					{
						$radius_data[$bts_id] = $r;
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
		$counters_res = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, name FROM ran_nokia_pm_counter WHERE name IN ('$counters') AND counter_group_id = $cg_id  ORDER BY name DESC")->queryAll();

		$radius_ops = [
			'M1006C148' => [ 1=>['from'=>4680, 'to'=>'>4680'], 2=>['from'=>9360, 'to'=>'>4680'], 3=>['from'=>17550, 'to'=>'>4680']],
			'M1006C147' => [ 1=>['from'=>4446, 'to'=>4680],       2=>['from'=>8892, 'to'=>9360],       3=>['from'=>14976, 'to'=>17550]     ],
			'M1006C146' => [ 1=>['from'=>4212, 'to'=>4446],       2=>['from'=>8424, 'to'=>8892],       3=>['from'=>12402, 'to'=>14976]     ],
			'M1006C145' => [ 1=>['from'=>3978, 'to'=>4212],       2=>['from'=>7956, 'to'=>8424],       3=>['from'=>10296, 'to'=>12402]     ],
			'M1006C144' => [ 1=>['from'=>3744, 'to'=>3978],       2=>['from'=>7488, 'to'=>7956],       3=>['from'=>9126,  'to'=>10296]     ],
			'M1006C143' => [ 1=>['from'=>3510, 'to'=>3744],       2=>['from'=>7020, 'to'=>7488],       3=>['from'=>7956,  'to'=>9126 ]     ],
			'M1006C142' => [ 1=>['from'=>3276, 'to'=>3510],       2=>['from'=>6552, 'to'=>7020],       3=>['from'=>7020,  'to'=>7956 ]     ],
			'M1006C141' => [ 1=>['from'=>3042, 'to'=>3276],       2=>['from'=>6084, 'to'=>6552],       3=>['from'=>6084,  'to'=>7020 ]     ],
			'M1006C140' => [ 1=>['from'=>2808, 'to'=>3042],       2=>['from'=>5616, 'to'=>6084],       3=>['from'=>4914,  'to'=>6084 ]     ],
			'M1006C139' => [ 1=>['from'=>2574, 'to'=>2808],       2=>['from'=>5148, 'to'=>5616],       3=>['from'=>3978,  'to'=>4914 ]     ],
			'M1006C138' => [ 1=>['from'=>2340, 'to'=>2574],       2=>['from'=>4680, 'to'=>5148],       3=>['from'=>3510,  'to'=>3978 ]     ],		
			'M1006C137' => [ 1=>['from'=>2106, 'to'=>2340],       2=>['from'=>4212, 'to'=>4680],       3=>['from'=>3042,  'to'=>3510 ]     ],
			'M1006C136' => [ 1=>['from'=>1872, 'to'=>2106],       2=>['from'=>3744, 'to'=>4212],       3=>['from'=>2574,  'to'=>3042 ]     ],
			'M1006C135' => [ 1=>['from'=>1638, 'to'=>1872],       2=>['from'=>3276, 'to'=>3744],       3=>['from'=>2106,  'to'=>2574 ]     ],
			'M1006C134' => [ 1=>['from'=>1404, 'to'=>1638],       2=>['from'=>2808, 'to'=>3276],       3=>['from'=>1404,  'to'=>2106 ]     ],
			'M1006C133' => [ 1=>['from'=>1170, 'to'=>1404],       2=>['from'=>2340, 'to'=>2808],       3=>['from'=>1170,  'to'=>1404 ]     ],
			'M1006C132' => [ 1=>['from'=>936,  'to'=>1170],       2=>['from'=>1872, 'to'=>2340],       3=>['from'=>936,   'to'=>1170 ]     ],
			'M1006C131' => [ 1=>['from'=>702,  'to'=>936 ],       2=>['from'=>1404, 'to'=>1872],       3=>['from'=>702,   'to'=>936  ]     ],
			'M1006C130' => [ 1=>['from'=>468,  'to'=>702 ],       2=>['from'=>936,  'to'=>1404],       3=>['from'=>468,   'to'=>702  ]     ],
			'M1006C129' => [ 1=>['from'=>234,  'to'=>468 ],       2=>['from'=>468,  'to'=>936 ],       3=>['from'=>234,   'to'=>468  ]     ],
			'M1006C128' => [ 1=>['from'=>0,    'to'=>234 ],       2=>['from'=>0,    'to'=>468 ],       3=>['from'=>0,     'to'=>234  ]     ],
		];

		$sel = "";
		$counter_lkp = [];
		$primary_counter_id = null;
		if ( sizeof($counters_res) > 0 )
		{
			$cntrs = array();
			foreach ($counters_res as $cntr)
			{
				$cname = $cntr['name'];
				$cid = 'c'.$cntr['id'];
				if ($cname == $primary_counter)
				{
					$primary_counter_id = $cid;
				}
				if (isset($radius_ops[$cname]))
				{
					$radius_ops[$cid] = $radius_ops[$cname];
					unset($radius_ops[$cname]);
				}
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND t.c_vals->>'".$primary_counter_id."' IS NOT NULL ";

		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$cell_pm_data = [];
		foreach ($pm_data as $v)
		{
			$moi_id = $v['moi_id'];
			if (!isset($cell_pm_data[$moi_id]))
			{
				$cell_pm_data[$moi_id] = $v;
			}
			else
			{
				$row = $cell_pm_data[$moi_id];
				foreach ($radius_ops as $cid => $settings)
				{
					if ( isset($v[$cid]) && isset($row[$cid]))
					{
						$row[$cid] += $v[$cid];
					}
					else
					{
						$row[$cid] = $v[$cid];
					}
				}
				if ( isset($v[$primary_counter_id]) && isset($row[$primary_counter_id]) && $v[$primary_counter_id] != $row[$primary_counter_id])
				{
					$row[$primary_counter_id] = max($v[$primary_counter_id], $row[$primary_counter_id]);
				}
				else
				{
					$row[$primary_counter_id] = $v[$primary_counter_id];
				}
				$cell_pm_data[$moi_id] = $row;
			}
		}

		$radius_lkp = [];

		if (count($cell_pm_data))
		{
			foreach ($cell_pm_data as $moi_key => $wcel)
			{
				$r = null;
				if (isset($wcel[$primary_counter_id]) && in_array($wcel[$primary_counter_id], [1,2,3]))
				{
					foreach ($radius_ops as $cid => $c_ops)
					{
						if (isset($wcel[$cid]) && $wcel[$cid] >= 0)
						{
							$r = $c_ops[$wcel[$primary_counter_id]]['to'];
						}
						if ($wcel[$cid] > 0)
						{
							break;
						}
					}
					if (isset($r))
					{
						$radius_lkp[$moi_key] =  $r;
					}
				}
			}
		}
		return $radius_lkp;
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

		if ( sizeof($counters_res) > 0 )
		{
			$cntrs = array();
			foreach ($counters_res as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
				if (isset($counter_list_flipped[$cntr['name']]))
				{
					$counter_lkp_by_name[$counter_list_flipped[$cntr['name']]] = "c".$cntr['id'];
				}
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND t.c_vals->>'c2016' IS NOT NULL";

		$pm_data = Yii::app()->db_nokia_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$pm_data_tx = [];
		foreach ($pm_data as $pk => $pv)
		{
			
			if (!isset($pm_data_tx[$pv['moi_id']]))
			{
				$pm_data_tx[$pv['moi_id']] = $pv;
			}
			else
			{
				$temp_arr = $pm_data_tx[$pv['moi_id']]; 
				foreach ($pv as $key => $value)
				{
					if (array_key_exists($key, $counter_lkp))
					{
						$temp_arr[$key] += $value;
					}
				}
				$pm_data_tx[$pv['moi_id']] = $temp_arr;
			}
		}

		$radius_data = [];

		foreach ($pm_data_tx as $moi_id => $pv)
		{
			$sum_val=$pv['c2017']+$pv['c2018']+$pv['c2019']+$pv['c2020']+$pv['c2021']+$pv['c2022']+$pv['c2023']+$pv['c2024']+$pv['c2025']+$pv['c2026']+$pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030']+$pv['c2031']+
					 $pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039']+$pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045']+$pv['c2046'];
			$val=0;
			if ($sum_val!=0)
			{
				if ($pv['c2016']==1)
				{
					if ($val==null){$val=(((($pv['c2046'])/($sum_val))>0)?'>2262':null);}
					if ($val==null){$val=(((($pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045'])/($sum_val))>0)?'2262':null);}
					if ($val==null){$val=(((($pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039'])/($sum_val))>0)?'1794':null);}
					if ($val==null){$val=(((($pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034'])/($sum_val))>0)?'1404':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030'])/($sum_val))>0)?'1092':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'780':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'624':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'468':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'312':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'156':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'78':null);}
				}
				else if ($pv['c2016']==2)
				{
					if ($val==null){$val=(((($pv['c2046'])/($sum_val))>0)?'>5600':null);}
					if ($val==null){$val=(((($pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045'])/($sum_val))>0)?'5600':null);}
					if ($val==null){$val=(((($pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039'])/($sum_val))>0)?'4800':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030']+$pv['c2031'])/($sum_val))>0)?'4100':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'3400':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'2656':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'2028':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'1482':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'1014':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'468':null);}
				}
				else if ($pv['c2016']==3)
				{
					if ($val==null){$val=(((($pv['c2046'])/($sum_val))>0)?'>11100':null);}
					if ($val==null){$val=(((($pv['c2038']+$pv['c2039']+$pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045'])/($sum_val))>0)?'11100':null);}
					if ($val==null){$val=(((($pv['c2029']+$pv['c2030']+$pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037'])/($sum_val))>0)?'9500':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028'])/($sum_val))>0)?'8600':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'6900':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'5300':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'4000':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'3000':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'2000':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'1000':null);}
				}
				else if ($pv['c2016']==4)
				{
					if ($val==null){$val=(((($pv['c2046'])/($sum_val))>0)?'>16600':null);}
					if ($val==null){$val=(((($pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045'])/($sum_val))>0)?'16600':null);}
					if ($val==null){$val=(((($pv['c2030']+$pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039']+$pv['c2040'])/($sum_val))>0)?'14600':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029'])/($sum_val))>0)?'12900':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'10400':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'8000':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'6000':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'4500':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'3000':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'1500':null);}
				}
				else if ($pv['c2016']==5)
				{
					if ($val==null){$val=(((($pv['c2046'])/($sum_val))>0)?'>33000':null);}
					if ($val==null){$val=(((($pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039']+$pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044']+$pv['c2045'])/($sum_val))>0)?'33000':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030'])/($sum_val))>0)?'26000':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'21000':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'16000':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'12000':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'9000':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'6000':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'3000':null);}
				}
				else if ($pv['c2016']==6)
				{
					if ($val==null){$val=(((($pv['c2045']+$pv['c2046'])/($sum_val))>0)?'>63000':null);}
					if ($val==null){$val=(((($pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039']+$pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044'])/($sum_val))>0)?'63000':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030'])/($sum_val))>0)?'52000':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'41000':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'32000':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'24000':null);}
					if ($val==null){$val=(((($pv['c2020'])/($sum_val))>0)?'18000':null);}
					if ($val==null){$val=(((($pv['c2019'])/($sum_val))>0)?'15000':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'12000':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'6000':null);}
				}
				else if ($pv['c2016']==7)
				{
					if ($val==null){$val=(((($pv['c2045']+$pv['c2046'])/($sum_val))>0)?'>105000':null);}
					if ($val==null){$val=(((($pv['c2031']+$pv['c2032']+$pv['c2033']+$pv['c2034']+$pv['c2035']+$pv['c2036']+$pv['c2037']+$pv['c2038']+$pv['c2039']+$pv['c2040']+$pv['c2041']+$pv['c2042']+$pv['c2043']+$pv['c2044'])/($sum_val))>0)?'105000':null);}
					if ($val==null){$val=(((($pv['c2027']+$pv['c2028']+$pv['c2029']+$pv['c2030'])/($sum_val))>0)?'87000':null);}
					if ($val==null){$val=(((($pv['c2025']+$pv['c2026'])/($sum_val))>0)?'69000':null);}
					if ($val==null){$val=(((($pv['c2023']+$pv['c2024'])/($sum_val))>0)?'53000':null);}
					if ($val==null){$val=(((($pv['c2021']+$pv['c2022'])/($sum_val))>0)?'40000':null);}
					if ($val==null){$val=(((($pv['c2019']+$pv['c2020'])/($sum_val))>0)?'30000':null);}
					if ($val==null){$val=(((($pv['c2018'])/($sum_val))>0)?'20000':null);}
					if ($val==null){$val=(((($pv['c2017'])/($sum_val))>0)?'10000':null);}
				}
			}
			$radius_data[$moi_id] = $val;
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

		$trx = Yii::app()->ran_cm_pmschema->createCommand("SELECT id, parameter_data ->> 'fdn' AS fdn FROM ran_huawei_moi WHERE moc_id = $moc_id")->queryAll();

		$cell_lkp = [];
		if ($trx)
		{
			foreach ($trx as $v)
			{
				if (isset($v['id']) && isset($v['fdn']))
				{
					$fdn = str_replace(',GTRX=', '-', $v['fdn']);
					$fdn_exp = explode('_', $fdn);
					if (count($fdn_exp) > 0)
					{
						if (isset($fdn_exp[0]))
						{
							$cell_lkp[$v['id']] = $fdn_exp[0];
						}
					}
				}
			}
		}
		else
		{
			return [];
		}

		$sel = "";
		$counter_lkp = [];
		$first_counter = null;
		if ( sizeof($counters) > 0 )
		{
			$first_counter = "t.c_vals->>'c".$counters[0]['id']."'";
			$cntrs = array();
			foreach ($counters as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		if (is_null($first_counter))
		{
			return [];
		}
		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND $first_counter IS NOT NULL";

		$pm_data = Yii::app()->db_huawei_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		if (!isset($pm_data))
		{
			return [];
		}

		$pm_data_tx = [];
		foreach ($pm_data as $pk => $pv)
		{
			if (isset($cell_lkp[$pv['moi_id']]))
			{
				$cell_name = $cell_lkp[$pv['moi_id']];
				if (!isset($pm_data_tx[$cell_name]))
				{
					$pm_data_tx[$cell_name] = $pv;
				}
				else
				{
					$temp_arr = $pm_data_tx[$cell_name];
					foreach ($counter_lkp as $cntr_id => $cntr_name)
					{
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

		foreach ($counter_lkp as $cid => $cname)
		{
			if (isset($radius_ops[$cname]))
			{
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data_tx))
		{
			foreach ($pm_data_tx as $key => $cell)
			{
				if (count($cell))
				{
					$r = null;
					foreach ($radius_ops as $cid => $radius)
					{
						if (isset($cell[$cid]) && $cell[$cid] > 0)
						{
							$r = $radius;
						}
					}
					if (isset($r))
					{
						$radius_data[ $key ] = $r;
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
		foreach ($BSC6910UMTSUCELLs as $v)
		{
			$mml_moi_lkp[$v['id']] = $v['rnc'] . '_' . $v['cid'];
		}
		
		$sel = "";
		$counter_lkp = [];

		if ( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND t.c_vals->>'c".$counters[0]['id']."' IS NOT NULL ";

		$pm_data = Yii::app()->db_huawei_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$pm_data = array_column($pm_data, null, 'id');

		$radius_ops = ['73423486'=>'234','73423488'=>'468','73423490'=>'702','73423492'=>'936','73423494'=>'1170','73423496'=>'1404','73423498'=>'2340','73423510'=>'3744','73423502'=>'6084','73423504'=>'8424','73423506'=>'13104','73423508'=>'>13104'];

		$radius_data = [];

		foreach ($counter_lkp as $cid => $cname)
		{
			if (isset($radius_ops[$cname]))
			{
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data))
		{
			foreach ($pm_data as $umtscell)
			{
				if (count($umtscell) && isset($mml_moi_lkp[ $umtscell['moi_id']]))
				{
					$r = null;
					foreach ($radius_ops as $cid => $radius)
					{
						if (isset($umtscell[$cid]) && $umtscell[$cid] > 0)
						{
							$r = $radius;
						}
					}
					if (isset($r))
					{
						$radius_data[ $mml_moi_lkp[ $umtscell['moi_id']]] = $r;
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
		foreach ($BTS3900CELL as $v)
		{
			$moi_lkp[$v['id']] = $v['cname'];
		}
		
		$sel = "";
		$counter_lkp = [];

		if ( sizeof($counters) > 0 )
		{
			$cntrs = array();
			foreach ($counters as $cntr)
			{
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

		foreach ($counter_lkp as $cid => $cname)
		{
			if (isset($radius_ops[$cname]))
			{
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data))
		{
			foreach ($pm_data as $cell)
			{
				$r = null;
				if (count($cell) && isset($moi_lkp[ $cell['moi_id']]))
				{
					foreach ($radius_ops as $cid => $radius)
					{
						if (isset($cell[$cid]) && $cell[$cid] > 0)
						{
							$r = $radius;
						}
					}
					if (isset($r))
					{
						$radius_data[ $moi_lkp[ $cell['moi_id']]] = $r;
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
		$first_counter = null;
		if ( sizeof($counters) > 0 )
		{
			$first_counter = "t.c_vals->>'c".$counters[0]['id']."'";
			$cntrs = array();
			foreach ($counters as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		if (is_null($first_counter))
		{
			return [];
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND $first_counter IS NOT NULL";

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

		foreach ($counter_lkp as $cid => $cname)
		{
			if (isset($radius_ops[$cname]))
			{
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		if (count($pm_data))
		{
			foreach ($pm_data as $GCELL)
			{
				if (count($GCELL))
				{
					$r = null;
					foreach ($radius_ops as $cid => $radius)
					{
						if (isset($GCELL[$cid]) && $GCELL[$cid] > 0)
						{
							$r = $radius;
						}
					}
					if (isset($r))
					{
						$radius_data[$GCELL['moi_id']] = $r;
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

		$first_counter = null;

		if ( sizeof($counters) > 0 )
		{
			$first_counter = "t.c_vals->>'c".$counters[0]['id']."'";
			$cntrs = array();
			foreach ($counters as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		if (is_null($first_counter))
		{
			return [];
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND $first_counter IS NOT NULL";

		$pm_data = Yii::app()->db_zte_ume_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$bts_data = [];
		foreach ($pm_data as $v)
		{
			if ( isset($v['moi_id']) &&isset($uLocalCell_lkp[$v['moi_id']]))
			{
				if (!isset($bts_data[$v['moi_id']]))
				{
					$bts_data[$uLocalCell_lkp[$v['moi_id']]] =  [];
					foreach ($counter_lkp as $cid => $name)
					{
						if ( isset($v[$cid]) )
						{
							$bts_data[$uLocalCell_lkp[$v['moi_id']]][$cid] = $v[$cid];
						}
					}
				}
				else
				{
					foreach ($counter_lkp as $cid => $name)
					{
						if ( isset($v[$cid]) )
						{
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

		if (count($bts_data))
		{
			foreach ($bts_data as $bts_id => $cntrs)
			{
				if (count($cntrs))
				{
					$r = null;
					foreach ($counter_lkp as $cid => $name)
					{
						if (isset($radius_lkp[$name]) && isset($cntrs[$cid]) && $cntrs[$cid] > 0)
						{
							$r = $radius_lkp[$name];
						}
					}
					if (isset($r))
					{
						$radius_data[$bts_id] = $r;
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

		$first_counter = null;
		if ( sizeof($counters_res) > 0 )
		{
			$first_counter = "t.c_vals->>'c".$counters_res[0]['id']."'";
			$cntrs = array();
			foreach ($counters_res as $cntr)
			{
				$cntrs [] = "t.c_vals->>'c".$cntr['id']."' as c".$cntr['id'];
				$counter_lkp["c".$cntr['id']] = $cntr['name'];
			}
			$sel = "t.moi_id,t.time,".implode(",", $cntrs);
		}

		if (is_null($first_counter))
		{
			return [];
		}

		$final_cond = "t.moc_id = '$moc_id' AND time = '$cg_lct' AND $first_counter IS NOT NULL";
		$pm_data = Yii::app()->db_zte_ume_ran_pm->createCommand("SELECT $sel FROM $tbl_name t WHERE $final_cond")->queryAll();

		$radius_ops = [
			'C373556400'=>'78','C373556401'=>'234','C373556402'=>'390','C373556403'=>'546','C373556404'=>'702','C373556405'=>'858','C373556406'=>'1014','C373556407'=>'1560','C373556408'=>'2106','C373556409'=>'2652','C373556410'=>'3120','C373556411'=>'3900','C373556412'=>'6318',
			'C373556413'=>'10062','C373556415'=>'13962','C373556416'=>'19968','C373556417'=>'29952','C373556418'=>'39936','C373556419'=>'49920','C373556420'=>'59982','C373556421'=>'69966','C373556422'=>'79950','C373556423'=>'89934','C373556424'=>'99996'
		];

		foreach ($counter_lkp as $cid => $cname)
		{
			if (isset($radius_ops[$cname]))
			{
				$radius_ops[$cid] = $radius_ops[$cname];
				unset($radius_ops[$cname]);
			}
		}

		$radius_data = [];

		if (count($pm_data))
		{
			foreach ($pm_data as $wcel)
			{
				if (count($wcel))
				{
					$r = null;
					foreach ($radius_ops as $cid => $radius)
					{
						if (isset($wcel[$cid]) && $wcel[$cid] > 0)
						{
							$r = $radius;
						}
					}
					if (isset($r))
					{
						$radius_data[$wcel['moi_id']] = $r;
					}
				}
			}
		}
		return $radius_data;
	}
}