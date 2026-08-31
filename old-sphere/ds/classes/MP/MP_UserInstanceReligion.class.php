<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_UserInstanceReligion extends MP_Patch
{
	private $userInstanceData;

	public function __construct($userInstanceData)
	{
		assert(is_array($userInstanceData) && count($userInstanceData) > 0);
		$this->userInstanceData = $userInstanceData;
	}

	public function patch()
	{
		self::_patchReligionConnect();
		self::_patchReligionCancel();
	}

	private function _patchReligionConnect()
	{
		$sql = "
				SELECT
					uhr.id_religion, uhr.id_userinstance
				FROM
					ds_userhasreligion uhr
				WHERE
					uhr.id_status = 1
					AND uhr.id_userinstance = ".$this->userInstanceData['id_userinstance']."
					AND not exists
					(
					SELECT
    					ep.id_userinstance
 					FROM
    					ds_eventparticipant ep,
    					ds_eventqueue eq,
    					ds_event ev
 					WHERE
	    				ev.eventname = 'RELIGION_CONNECT'
	    				and ev.id_event = eq.id_event
    					and eq.id_eventqueue = ep.id_eventqueue
    					and eq.istrash = false
    					and ep.id_userinstance = uhr.id_userinstance
					)
		";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));

		if(sizeof($rs) === 0)
		{
			return true;
		}

		$str =  "	".__CLASS__.": ERROR!  'RELIGION_CONNECT'";

		foreach ($rs as $k=>$v)
		{
			$sql1a = "SELECT nextval('public.ds_eventqueue_id_eventqueue_seq'::text) AS ID";
			$res1a = self::$common->getResultArray ( "", $sql1a, "", "", "" );
			assert(empty(self::$common->errormsg));


			$ID = (int)$res1a[0]['id'];
			assert(isset($ID));

			$tmp = self::$common->executeQuery('begin');
			assert(empty(self::$common->errormsg));

			$sql1 = "INSERT INTO
           				ds_eventqueue
           				(id_eventqueue, id_event, eventstarttime,eventendtime, istrash)
           			VALUES
           				($ID, 30, now() , (now() + '1 second'::interval),'f')";


			$res1 = self::$common->executeQuery($sql1);
			assert(empty(self::$common->errormsg));


			$sql3 = sprintf("INSERT INTO
           							ds_eventparticipant
           							(id_eventqueue, id_userinstance)
           						VALUES
           							(%d,%d)",
			$ID,
			$v['id_userinstance']);
			$res3 = self::$common->executeQuery($sql3);
			assert(empty(self::$common->errormsg));

			$sql4 = sprintf("INSERT INTO
           							ds_eventhasoption
           							(id_eventqueue, id_eventoption, value)
           						VALUES
           							(%d, 220, %d)", $ID, $v['id_religion']);
			$res4 = self::$common->executeQuery($sql4);
			assert(empty(self::$common->errormsg));

			$tmp2 = self::$common->executeQuery('commit');
			assert(empty(self::$common->errormsg));
			$str .= " Fixed!";
		}

		self::_out($str);
		return true;
	}



	private function _patchReligionCancel()
	{
		$sql = "
				SELECT
					uhr.id_religion, uhr.id_userinstance
				FROM
					ds_userhasreligion uhr
				WHERE
					uhr.id_status = 3
					AND uhr.id_userinstance = ".$this->userInstanceData['id_userinstance']."
					AND not exists
					(
					SELECT
						ep.id_userinstance
					FROM
						ds_eventparticipant ep,
						ds_eventqueue eq,
						ds_event ev
					WHERE
						ev.eventname = 'RELIGION_CANCEL'
						and ev.id_event = eq.id_event
						and eq.id_eventqueue = ep.id_eventqueue
						and eq.istrash = false
						and ep.id_userinstance = uhr.id_userinstance
					)
				";

		$res = self::$common->getResultArray ( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));

		if(sizeof($res) === 0)
		{
			return true;
		}

		$str =  "	".__CLASS__.": ERROR!  'RELIGION_CANCEL'";

		foreach ($res as $k=>$v)
		{
			$sql1a = "SELECT nextval('public.ds_eventqueue_id_eventqueue_seq'::text) AS ID";
			$res1a = self::$common->getResultArray ( "", $sql1a, "", "", "" );
			assert(empty(self::$common->errormsg));

			$ID = (int)$res1a[0]['id'];

			assert(isset($ID));

			self::$common->executeQuery('begin');
			$sql1 = "
						INSERT INTO
							ds_eventqueue
							(id_eventqueue, id_event, eventstarttime,eventendtime, istrash)
						VALUES
							($ID, 31, now() , (now() + '1 second'::interval),'f')";

			$res1 = self::$common->executeQuery($sql1);
			assert(empty(self::$common->errormsg));


			$sql3 = sprintf("
						INSERT INTO
							ds_eventparticipant
							(id_eventqueue, id_userinstance)
						VALUES
							(%d,%d)",
			$ID,
			$v['id_userinstance']);
			$res3 = self::$common->executeQuery($sql3);
			assert(empty(self::$common->errormsg));

			$sql4 = sprintf("
						INSERT INTO
							ds_eventhasoption
							(id_eventqueue, id_eventoption, value)
						VALUES
							(%d, 221, %d)",
			$ID,$v['id_religion']);

			$res4 = self::$common->executeQuery($sql4);
			assert(empty(self::$common->errormsg));

			$tmp2 = self::$common->executeQuery('commit');
			assert(empty(self::$common->errormsg));
			$str .= " Fixed!";
		}

		self::_out($str);
		return true;
	}
}

?>