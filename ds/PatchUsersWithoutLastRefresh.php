<?php
//PatchUsersWithoutLastRefresh.php
ini_set('memory_limit', '50M');
set_time_limit(0);

require_once 'Main.php';

$dbId = 3;
$log = 'Миры level_3_tlc
	';


	$_SESSION["dbId"] = $dbId;
	
	$common = DS_Common::CreateForCurentDB($dbId);
	
	

		echo "<h1>Выбираем игроков с обломками центральных ворот</h1><br>";
		SU_DebugTools::LogAll('Выбираем игроков с обломками центральных ворот в единороге', 'UsersWithoutLastrefreshTest.log');
	
		// Выбираем игроков с обломками центральных ворот
		$sql = "
			SELECT t.username
			FROM 
			(
			SELECT
      				i.id_userinstance,
                    u.username,
                       (SELECT
			attb.active
			FROM
				ds_user u
				INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
				LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
				LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
				INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
				INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
				INNER JOIN ds_building b ON (attb.id_building = b.id_building)
                
			WHERE
				ui.id_userinstance = i.id_userinstance
				AND
							b.id_building = 5
							AND ri.ringnumber != 4) as build
                
				FROM
					ds_userinstance AS i
					INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
				WHERE
					u.admin = false
    				AND
    				i.id_userinstance > 0				
					AND NOT EXISTS					
    				(
        				SELECT id_user
        				FROM  ds_loginhistory
        				WHERE  id_user = u.id_user
     				)
                    AND NOT EXISTS
      				(
          			SELECT id_user
          			FROM ds_userprofidata
          			WHERE
               			id_user = i.id_user
               			AND
               			active = true
		       			AND
               			enddate > now()
    				)
                        AND NOT EXISTS
	 				(	
	 				SELECT * FROM 
                    ds_vocationmode WHERE enddate IS NULL 
                    AND id_userinstance = i.id_userinstance
                    AND NOT password LIKE 'pereezd_freez_%'
                    )
	 			
          ) as t
          WHERE build
              ";	
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		
		SU_DebugTools::LogAll($res, 'UsersWithoutLastrefreshTest.log');
		
		$count = count($res);
		
		$log .= 'Выбрано '.$count.' игроков с обломками ворот
';
		$comma = '';
		foreach ($res as $k => $d)
		{
			$log .= $d['username'].'
';
			//$comma = ',';
		}
		$log .= '
';
	
		echo "Выбрано $count игроков<br>";
		
		// Начинаем выставлять дату последнего рефреша с 110 дней назад
		$lastReresh = 110;
		
		// Каждый день удаляем по 50 игроков
		$limit = 50;
		
		if ($count > 0) {
			// Каждый день удаляем по 50 игроков
			for ($i = 0; $i <= $count; $i=$i+$limit) {
	//echo "i = $i<br>";			
	//echo "$count = $count<br>";			
	//echo "$limit = $limit<br>";			
				// Смотрим, что бы дата последнего рефреша не дошла до нуля(т е до сегодняшнего дня)
				assert($lastReresh > 0);
				
				$sql = "
					INSERT INTO ds_loginhistory
						(id_user, lastvisit, ip, ip_proxy, id_cookie, ip_internal)	
					SELECT
	      				id_user,
	      				now() - interval '$lastReresh days',
	      				'1.1.1.1',
	      				'1.1.1.1',
	      				(
	      				 SELECT min(id_cookie)
	                     FROM ds_logincookie AS e
	                    ),
	      				'1.1.1.1'
					FROM 
						(
						SELECT
		      				i.id_user,
		                    i.id_userinstance,
		                    u.username,
	                       (SELECT
								attb.active
							FROM
								ds_user u
								INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
								INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
								LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
								LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
								INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
								INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
								INNER JOIN ds_building b ON (attb.id_building = b.id_building)
				                
							WHERE
								ui.id_userinstance = i.id_userinstance
								AND
								b.id_building = 5
								AND ri.ringnumber != 4) as build
	                
						FROM
							ds_userinstance AS i
						INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
	                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
						WHERE
							u.admin = false
		    				AND
		    				i.id_userinstance > 0				
							AND NOT EXISTS					
		    				(
		        				SELECT id_user
		        				FROM  ds_loginhistory
		        				WHERE  id_user = u.id_user
		     				)
		                    AND NOT EXISTS
		      				(
		          			SELECT id_user
		          			FROM ds_userprofidata
		          			WHERE
		               			id_user = i.id_user
		               			AND
		               			active = true
				       			AND
		               			enddate > now()
		    				)
		                    AND NOT EXISTS
			 				(	
			 				SELECT * FROM 
		                    ds_vocationmode WHERE enddate IS NULL 
		                    AND id_userinstance = i.id_userinstance
		                    AND NOT password LIKE 'pereezd_freez_%'
		                    )
		 			
			          	) as t
			      WHERE build
			      LIMIT $limit";
		echo "<pre>$sql</pre><br>";
		echo "--------------------------------------------------------------<br><br>";			
	
				
				$res = $common->getResultArray('', $sql, '', '', '');
				//assert(empty($common->errormsg));
				
				$lastReresh --;
			
			}
		}
	
		echo "<h1>Выбираем игроков с закрытыми воротами</h1><br>";
		SU_DebugTools::LogAll('Выбираем игроков с закрытыми воротами', 'UsersWithoutLastrefreshTest.log');

		// Начинаем выставлять дату последнего рефреша с 110 дней назад
		$lastReresh = 100;
			
		// Выбираем игроков с закрытыми воротами
		$sql = "
			SELECT t.username
			FROM 
			(
			SELECT
      				i.id_userinstance,
                    u.username,
                       (SELECT
			attb.active
			FROM
				ds_user u
				INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
				LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
				LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
				INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
				INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
				INNER JOIN ds_building b ON (attb.id_building = b.id_building)
                
			WHERE
				ui.id_userinstance = i.id_userinstance
				AND
							b.id_building = 6
							AND ri.ringnumber != 4) as build
                
				FROM
					ds_userinstance AS i
					INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
				WHERE
					u.admin = false
    				AND
    				i.id_userinstance > 0				
					AND NOT EXISTS					
    				(
        				SELECT id_user
        				FROM  ds_loginhistory
        				WHERE  id_user = u.id_user
     				)
                    AND NOT EXISTS
      				(
          			SELECT id_user
          			FROM ds_userprofidata
          			WHERE
               			id_user = i.id_user
               			AND
               			active = true
		       			AND
               			enddate > now()
    				)
                        AND NOT EXISTS
	 				(	
	 				SELECT * FROM 
                    ds_vocationmode WHERE enddate IS NULL 
                    AND id_userinstance = i.id_userinstance
                    AND NOT password LIKE 'pereezd_freez_%'
                    )
	 			
          ) as t
          WHERE build = 'f'
              ";	
		$res = $common->getResultArray('', $sql, '', '', '');
		assert(empty($common->errormsg));
		
		SU_DebugTools::LogAll($res, 'UsersWithoutLastrefreshTest.log');
		
		$log .= 'Выбрано '.$count.' игроков с закрытыми воротами
';
		$comma = '';
		foreach ($res as $k => $d)
		{
			$log .= $d['username'].'
';
			//$comma = ',';
		}
		$log .= '
';
	
		
		$count = count($res);
	
		echo "Выбрано $count игроков<br>";
		
		if ($count > 0) {
			// Каждый день удаляем по 50 игроков
			for ($i = 0; $i <= $count; $i=$i+$limit) {
				// Смотрим, что бы дата последнего рефреша не дошла до нуля(т е до сегодняшнего дня)
				assert($lastReresh > 0);
				
				$sql = "
					INSERT INTO ds_loginhistory
						(id_user, lastvisit, ip, ip_proxy, id_cookie, ip_internal)	
					SELECT
	      				id_user,
	      				now() - interval '$lastReresh days',
	      				'1.1.1.1',
	      				'1.1.1.1',
	      				(
	      				 SELECT min(id_cookie)
	                     FROM ds_logincookie AS e
	                    ),
	      				'1.1.1.1'
					FROM 
					(
					SELECT
		      				i.id_user,
		      				i.id_userinstance,
		                    u.username,
		                       (SELECT
					attb.active
					FROM
						ds_user u
						INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
						INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
						LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
						LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
						INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
						INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
						INNER JOIN ds_building b ON (attb.id_building = b.id_building)
		                
					WHERE
						ui.id_userinstance = i.id_userinstance
						AND
									b.id_building = 6
									AND ri.ringnumber != 4) as build
		                
						FROM
							ds_userinstance AS i
							INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
		                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
						WHERE
							u.admin = false
		    				AND
		    				i.id_userinstance > 0				
							AND NOT EXISTS					
		    				(
		        				SELECT id_user
		        				FROM  ds_loginhistory
		        				WHERE  id_user = u.id_user
		     				)
		                    AND NOT EXISTS
		      				(
		          			SELECT id_user
		          			FROM ds_userprofidata
		          			WHERE
		               			id_user = i.id_user
		               			AND
		               			active = true
				       			AND
		               			enddate > now()
		    				)
		                        AND NOT EXISTS
			 				(	
			 				SELECT * FROM 
		                    ds_vocationmode WHERE enddate IS NULL 
		                    AND id_userinstance = i.id_userinstance
		                    AND NOT password LIKE 'pereezd_freez_%'
		                    )
			 			
		          ) as t
		          WHERE build = 'f'
			      LIMIT $limit";
				
				$res = $common->getResultArray('', $sql, '', '', '');
				//assert(empty($common->errormsg));
	
	echo "<pre>$sql</pre><br>";			
				
				$lastReresh --;
			}
		}
		
		echo "<h1>Выбираем игроков с открытыми воротами</h1><br>";
		SU_DebugTools::LogAll('Выбираем игроков с открытыми воротами', 'UsersWithoutLastrefreshTest.log');

		// Выбираем игроков с открытыми воротами
		$sql = "
			SELECT t.username
			FROM 
			(
			SELECT
      				i.id_userinstance,
                    u.username,
                       (SELECT
			attb.active
			FROM
				ds_user u
				INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
				INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
				LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
				LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
				INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
				INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
				INNER JOIN ds_building b ON (attb.id_building = b.id_building)
                
			WHERE
				ui.id_userinstance = i.id_userinstance
				AND
							b.id_building = 6
							AND ri.ringnumber != 4) as build
                
				FROM
					ds_userinstance AS i
					INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
				WHERE
					u.admin = false
    				AND
    				i.id_userinstance > 0				
					AND NOT EXISTS					
    				(
        				SELECT id_user
        				FROM  ds_loginhistory
        				WHERE  id_user = u.id_user
     				)
                    AND NOT EXISTS
      				(
          			SELECT id_user
          			FROM ds_userprofidata
          			WHERE
               			id_user = i.id_user
               			AND
               			active = true
		       			AND
               			enddate > now()
    				)
                        AND NOT EXISTS
	 				(	
	 				SELECT * FROM 
                    ds_vocationmode WHERE enddate IS NULL 
                    AND id_userinstance = i.id_userinstance
                    AND NOT password LIKE 'pereezd_freez_%'
                    )
	 			
          ) as t
          WHERE build = 't'
              ";	
		$res = $common->getResultArray('', $sql, '', '', '');
		//assert(empty($common->errormsg));
		
		$count = count($res);
		
		$log .= 'Выбрано '.$count.' игроков с открытыми воротами
';
		$comma = '';
		foreach ($res as $k => $d)
		{
			$log .= $d['username'].'
';
			//$comma = ',';
		}
		$log .= '
';
		SU_DebugTools::LogAll($res, 'UsersWithoutLastrefreshTest.log');
		
		// Начинаем выставлять дату последнего рефреша с 80 дней назад
		$lastReresh = 80;
	
		echo "Выбрано $count игроков<br>";
		
		if ($count > 0) {
			// Каждый день удаляем по 50 игроков
			for ($i = 0; $i <= $count; $i=$i+$limit) {
				// Смотрим, что бы дата последнего рефреша не дошла до нуля(т е до сегодняшнего дня)
				assert($lastReresh > 0);
				
				$sql = "
					INSERT INTO ds_loginhistory
						(id_user, lastvisit, ip, ip_proxy, id_cookie, ip_internal)	
					SELECT
	      				id_user,
	      				now() - interval '$lastReresh days',
	      				'1.1.1.1',
	      				'1.1.1.1',
	      				(
	      				 SELECT min(id_cookie)
	                     FROM ds_logincookie AS e
	                    ),
	      				'1.1.1.1'
					FROM 
					(
					SELECT
		      				i.id_user,
		      				i.id_userinstance,
		                    u.username,
		                       (SELECT
					attb.active
					FROM
						ds_user u
						INNER JOIN ds_userinstance ui ON (u.id_user = ui.id_user)
						INNER JOIN ds_comb c ON (ui.id_userinstance = c.id_userinstance)
						LEFT JOIN ds_combcomplex cc ON ( c.id_combcomplex = cc.id_combcomplex )
						LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
						INNER JOIN ds_combname cn ON (c.id_comb = cn.id_comb)
						INNER JOIN ds_attachedbuilding attb ON (cn.id_comb = attb.id_comb)
						INNER JOIN ds_building b ON (attb.id_building = b.id_building)
		                
					WHERE
						ui.id_userinstance = i.id_userinstance
						AND
									b.id_building = 6
									AND ri.ringnumber != 4) as build
		                
						FROM
							ds_userinstance AS i
							INNER JOIN ds_user AS u ON (u.id_user = i.id_user)
		                    LEFT JOIN ds_vocationmode v ON (v.id_userinstance = i.id_userinstance)	
						WHERE
							u.admin = false
		    				AND
		    				i.id_userinstance > 0				
							AND NOT EXISTS					
		    				(
		        				SELECT id_user
		        				FROM  ds_loginhistory
		        				WHERE  id_user = u.id_user
		     				)
		                    AND NOT EXISTS
		      				(
		          			SELECT id_user
		          			FROM ds_userprofidata
		          			WHERE
		               			id_user = i.id_user
		               			AND
		               			active = true
				       			AND
		               			enddate > now()
		    				)
		                        AND NOT EXISTS
			 				(	
			 				SELECT * FROM 
		                    ds_vocationmode WHERE enddate IS NULL 
		                    AND id_userinstance = i.id_userinstance
		                    AND NOT password LIKE 'pereezd_freez_%'
		                    )
			 			
		          ) as t
		          WHERE build = 't'
			      LIMIT $limit";
				
				$res = $common->getResultArray('', $sql, '', '', '');
				//assert(empty($common->errormsg));
	
	echo "<pre>$sql</pre><br>";		
		
				$lastReresh --;
			}
		}
				
		SU_DebugTools::LogAll($log, 'UsersWithoutLastrefresh.log');

		echo "<h1>END</h1><br>";

?>