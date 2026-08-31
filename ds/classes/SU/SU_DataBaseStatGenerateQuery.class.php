<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_DataBaseStatGenerateQuery
{					    
	function GenerateQuery($tables, $CountOfRow, $action)
	{
		$query = "set STATEMENT_TIMEOUT to 120000;";
		
		
		$query .= "
					SELECT
					    relname,
					    COALESCE((seq_scan + idx_scan), 0) as sel,
					    COALESCE((seq_tup_read + idx_tup_fetch), 0) as selq,
					    COALESCE(n_tup_ins, 0) as ins, COALESCE(n_tup_upd, 0) as upd,
					    COALESCE(n_tup_del, 0) as del,
					    (n_tup_ins + n_tup_upd + n_tup_del) as si";
			
			
		if ($CountOfRow == 'yes')
		{
			$query .= ", count_table_rows(relname::varchar) AS rows_count";
		}
		
		$query .= " FROM pg_stat_user_tables ";
		
		$where = 'WHERE';
		
		if ($tables != 'all tables')
		{
			$query .= " WHERE relname = '".$tables[0]."'";
			for ($i = 1; $i < count($tables);  $i ++)
			{
				$trimtable = trim($tables[$i]);
				$query .= " OR relname = '".$trimtable."'";
			}
			$where = 'AND';
		}
		
		if ($CountOfRow == 'yes')
		{
			$query .= " $where relname like 'ds_%'";
		}

		switch ($action) 
		{
			case 'SortSel':
				$query .= " ORDER BY sel DESC";
				break;
				
			case 'SortIns':
				$query .= " ORDER BY n_tup_ins DESC";
				break;

			case 'SortUp':
				$query .= " ORDER BY n_tup_upd DESC";
				break;

			case 'SortDel':
				$query .= " ORDER BY n_tup_del DESC";
				break;
				
			case 'SortTab':
				$query .= " ORDER BY relname";
				break;
				
			case 'SortSi':
				$query .= " ORDER BY si DESC";
				break;
				
			case 'SortRow':
				$query .= " ORDER BY rows_count DESC";
				break;

			default:
				break;
		}
		return $query;
	}

}
?>