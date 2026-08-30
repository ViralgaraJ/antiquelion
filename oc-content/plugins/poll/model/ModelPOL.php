<?php
class ModelPOL extends DAO {
private static $instance;

public static function newInstance() {
  if( !self::$instance instanceof self ) {
    self::$instance = new self;
  }
  return self::$instance;
}

function __construct() {
  parent::__construct();
}


public function getTable_poll() {
  return DB_TABLE_PREFIX.'t_poll';
}

public function getTable_poll_value() {
  return DB_TABLE_PREFIX.'t_poll_value';
}

public function getTable_poll_item() {
  return DB_TABLE_PREFIX.'t_item_poll';
}

public function getTable_poll_locale() {
  return DB_TABLE_PREFIX.'t_poll_locale';
}

public function getTable_poll_value_locale() {
  return DB_TABLE_PREFIX.'t_poll_value_locale';
}

public function getTable_poll_result() {
  return DB_TABLE_PREFIX.'t_poll_result';
}


public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelPOL<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install($version = '') {
  if($version == '') {
    $this->import('poll/model/struct.sql');

    osc_set_preference('version', 100, 'plugin-poll', 'INTEGER');
  }
}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_poll()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_poll_value()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_poll_item()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_poll_locale()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_poll_value_locale()));


  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-poll'";
  $this->dao->query($query);
}



// GET POLLS
public function getPolls($active = -1) {
  $this->dao->select('DISTINCT a.*, al.s_name, al.s_description, al.s_successful, al.fk_c_locale_code');
  $this->dao->from($this->getTable_poll() . ' as a');
  $this->dao->join($this->getTable_poll_locale() . ' as al', '(al.fk_i_poll_id = a.pk_i_id AND al.fk_c_locale_code = "' . pol_get_locale() . '")', 'LEFT OUTER');

  $this->dao->orderby('a.pk_i_id DESC');

  if($active > -1) {
    $this->dao->where('a.b_active', $active);
  }


  $result = $this->dao->get();
  
  if($result) {
    $polls = $result->result();

    $j = 0;
    if(count($polls) > 0) {
      foreach($polls as $a) {
        $polls[$j]['locales'] = $this->getPollLocales($a['pk_i_id']);
        $polls[$j]['values'] = $this->getPollValues($a['pk_i_id']);
        $j++;
      }
    }

    return $polls;
  }

  return array();
}



// GET POLL WITH DETAILS
public function getPollDetail($poll_id) {
  $this->dao->select('a.*, al.s_name, al.s_description, al.s_successful, al.fk_c_locale_code');
  $this->dao->from($this->getTable_poll() . ' as a');
  $this->dao->join($this->getTable_poll_locale() . ' as al', '(al.fk_i_poll_id = a.pk_i_id AND al.fk_c_locale_code = "' . pol_get_locale() . '")', 'LEFT OUTER');

  $this->dao->where('a.pk_i_id', $poll_id);

  $result = $this->dao->get();
  
  if($result) {
    $poll = $result->row();

    if(isset($poll['pk_i_id'])) {
      $poll['locales'] = $this->getPollLocales($poll['pk_i_id']);
      $poll['values'] = $this->getPollValues($poll['pk_i_id']);
    }

    return $poll;
  }

  return array();
}


// GET ACTIVE POLL ID
public function getActivePollId() {
  $this->dao->select('pk_i_id');
  $this->dao->from($this->getTable_poll());
  $this->dao->where('b_active', 1);

  $result = $this->dao->get();
  
  if($result) {
    $id = $result->row();
    
    if(isset($id['pk_i_id'])) {
      return $id['pk_i_id'];
    }
  }

  return false;
}


// GET ACTIVE POLL DETAIL
public function getActivePoll() {
  $active_id = $this->getActivePollId();

  if($active_id > 0) {
    return $this->getPollDetail($active_id);
  }

  return false;
}



// GET POLL VALUES
public function getPollValues($poll_id, $result = 0) {
  $this->dao->select('DISTINCT v.*, vl.s_name, vl.s_description, vl.fk_c_locale_code');
  $this->dao->from($this->getTable_poll_value() . ' as v');
  $this->dao->join($this->getTable_poll_value_locale() . ' as vl', '(v.pk_i_id = vl.fk_i_poll_value_id AND vl.fk_c_locale_code = "' . pol_get_locale() . '")', 'LEFT OUTER');

  $this->dao->where('v.fk_i_poll_id', $poll_id);
  $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

  $result = $this->dao->get();
  
  if($result) {
    $values = $result->result();

    if(count($values) > 0) {
      $i = 0;
      foreach($values as $v) {
        $values[$i]['votes'] = $this->getPollValueResult($poll_id, $v['pk_i_id']);
        $i++;
      }
    }

    return $values;
  }

  return array();
}



// GET POLL VALUE
public function getPollValue($poll_value_id) {
  $this->dao->select('DISTINCT v.*, vl.s_name, vl.s_description, vl.fk_c_locale_code');
  $this->dao->from($this->getTable_poll_value() . ' as v');
  $this->dao->join($this->getTable_poll_value_locale() . ' as vl', '(v.pk_i_id = vl.fk_i_poll_value_id AND vl.fk_c_locale_code = "' . pol_get_locale() . '")', 'LEFT OUTER');

  $this->dao->where('v.pk_i_id', $poll_value_id);

  $result = $this->dao->get();
  
  if($result) {
    $value = $result->row();
    $value['locales'] = $this->getPollValueLocales($poll_value_id);

    return $value;
  }

  return array();
}



// GET POLL VALUE ROW
public function getPollValueRow($poll_value_id) {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_value());
  $this->dao->where('pk_i_id', $poll_value_id);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return array();
}



// GET POLL VALUE RESULTS
public function getPollValueResult($poll_id, $poll_value_id) {
  $this->dao->select('count(*) as i_count');
  $this->dao->from($this->getTable_poll_result());
  $this->dao->where('fk_i_poll_id', $poll_id);
  $this->dao->where('fk_i_poll_value_id', $poll_value_id);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return $data['i_count'];
  }

  return 0;
}


// GET POLL VALUE RESULTS
public function getPollResult($poll_id) {
  $this->dao->select('count(*) as i_count');
  $this->dao->from($this->getTable_poll_result());
  $this->dao->where('fk_i_poll_id', $poll_id);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return $data['i_count'];
  }

  return 0;
}



// GET POLL RESULTS OVERALL
public function getOverallResult($poll_id) {
  $this->dao->select('a.*, al.s_name, al.s_description, al.s_successful, al.fk_c_locale_code');
  $this->dao->from($this->getTable_poll() . ' as a');
  $this->dao->join($this->getTable_poll_locale() . ' as al', '(al.fk_i_poll_id = a.pk_i_id AND al.fk_c_locale_code = "' . pol_get_locale() . '")', 'LEFT OUTER');

  $this->dao->where('a.pk_i_id', $poll_id);

  $result = $this->dao->get();
  
  if($result) {
    $poll = $result->row();

    if(isset($poll['pk_i_id'])) {
      $poll['locales'] = $this->getPollLocales($poll['pk_i_id']);
      $poll['values'] = $this->getPollValues($poll['pk_i_id'], 1);
      $poll['votes'] = $this->getPollResult($poll['pk_i_id']);
    }

    return $poll;
  }

  return array();
}




// CHECK IF USER HAS VOTED
public function checkUserVote($poll_id, $user_id, $cookie_id) {
  if((int)$poll_id <= 0) {
    return false;
  }
  
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_result());
  $this->dao->where('fk_i_poll_id', $poll_id);

  $this->dao->where(sprintf('((%d > 0 AND i_user_id = %d) OR (i_cookie_id = "%s"))', $user_id, $user_id, $cookie_id));


  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();

    if(isset($data['pk_i_id']) && $data['pk_i_id'] > 0) {
      return true;
    }
  }

  return false;
}


// GETIF USER VOTE
public function getUserVote($poll_id, $user_id, $cookie_id) {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_result());
  $this->dao->where('fk_i_poll_id', $poll_id);

  $this->dao->where(sprintf('((%d > 0 AND i_user_id = %d) OR (i_cookie_id = "%s"))', $user_id, $user_id, $cookie_id));


  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return false;
}



// REMOVE POLL
public function removePoll($id) {
  return $this->dao->delete($this->getTable_poll(), array('pk_i_id' => $id));
}


// REMOVE POLL VALUE
public function removePollValue($id) {
  return $this->dao->delete($this->getTable_poll_value(), array('pk_i_id' => $id));
}


// INSERT NEW POLL VALUE
public function insertPollValue($poll_id) {
  $this->dao->insert($this->getTable_poll_value(), array('fk_i_poll_id' => $poll_id, 'i_order' => 9999));
  return $this->dao->insertedId();
}


// INSERT NEW POLL
public function insertPoll() {
  $this->dao->insert($this->getTable_poll(), array('b_active' => 0, 's_pages' => 'all', 's_type' => 'RADIO-HORIZONTAL', 's_position' => 'BOTTOM-LEFT'));
  return $this->dao->insertedId();
}


// INSERT NEW POLL VOTE
public function insertPollResult($poll_id, $poll_value_id, $user_id, $cookie_id) {
  $this->dao->insert($this->getTable_poll_result(), array('fk_i_poll_id' => $poll_id, 'fk_i_poll_value_id' => $poll_value_id, 'i_user_id' => $user_id, 'i_cookie_id' => $cookie_id));
  return $this->dao->insertedId();
}


// UPDATE POLL VALUE POSITION
public function updatePollValuePosition($value_id, $order) {
  $this->dao->update($this->getTable_poll_value(), array('i_order' => $order), array('pk_i_id' => $value_id));
}


// DISABLE OTHER POLLS
public function disableOtherPolls($poll_id) {
  if($poll_id > 0) {
    $this->dao->query(sprintf('UPDATE %s SET b_active = 0 WHERE pk_i_id != %d', $this->getTable_poll(), $poll_id));
  }
}


// UPDATE POLL
public function updatePoll($data) {
  if(!isset($data['pk_i_id']) || $data['pk_i_id'] <= 0) {
    return false;
  }


  $values = array(
    's_type' => @$data['s_type'],
    's_pages' => @$data['s_pages'],
    's_position' => @$data['s_position'],
    'b_active' => (@$data['b_active'] == 'on' ? 1 : @$data['b_active'])
  );

  $where = array(
    'pk_i_id' => $data['pk_i_id']
  );


  $this->dao->update($this->getTable_poll(), $values, $where);
  $this->updatepollLocale($data['pk_i_id'], @$data['s_name'], @$data['s_description'], @$data['s_successful'], @$data['fk_c_locale_code']);
}


// UPDATE POLL LOCALE
public function updatePollLocale($id, $name, $description, $successful, $locale) {
  $values = array(
    'fk_i_poll_id' => $id,
    'fk_c_locale_code' => $locale,
    's_name' => $name,
    's_description' => $description,
    's_successful' => $successful
  );

  $where = array(
    'fk_i_poll_id' => $id,
    'fk_c_locale_code' => $locale
  );


  $check = $this->getPollLocale($id, $locale);
  if(@$check['pk_i_id'] > 0) {
    $this->dao->update($this->getTable_poll_locale(), $values, $where);
  } else {
    $this->dao->insert($this->getTable_poll_locale(), $values);
  }
}



// UPDATE POLL VALUE
public function updatePollValue($data) {
  if(!isset($data['pk_i_id']) || $data['pk_i_id'] <= 0) {
    return false;
  }

  //$values = array('s_description' => @$data['s_description']);
  $where = array('pk_i_id' => $data['pk_i_id']);


  //$this->dao->update($this->getTable_poll_value(), $values, $where);
  $this->updatepollValueLocale($data['pk_i_id'], @$data['s_name'], @$data['s_description'], @$data['fk_c_locale_code']);
}



// UPDATE poll VALUE LOCALE
public function updatePollValueLocale($id, $name, $description, $locale) {
  $values = array(
    'fk_i_poll_value_id' => $id,
    'fk_c_locale_code' => $locale,
    's_name' => $name,
    's_description' => $description
  );

  $where = array(
    'fk_i_poll_value_id' => $id,
    'fk_c_locale_code' => $locale
  );

  $check = $this->getPollValueLocale($id, $locale);
  if(@$check['pk_i_id'] > 0) {
    $this->dao->update($this->getTable_poll_value_locale(), $values, $where);
  } else {
    $this->dao->insert($this->getTable_poll_value_locale(), $values);
  }
}



// GET POLL LOCALE
public function getPollLocale($poll_id, $locale = '') {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_locale());
  $this->dao->where('fk_i_poll_id', $poll_id);

  if($locale <> '') {
    $this->dao->where('fk_c_locale_code', $locale);
  }

  $this->dao->limit(1);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return false;
}



// GET POLL LOCALES
public function getPollLocales($poll_id) {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_locale());
  $this->dao->where('fk_i_poll_id', $poll_id);

  $result = $this->dao->get();
  $array = array();

  if($result) {
    $locales = $result->result();

    if(count($locales) > 0) {
      foreach($locales as $l) {
        $array[$l['fk_c_locale_code']] = array('s_name' => $l['s_name'], 's_description' => $l['s_description'], 's_successful' => $l['s_successful']);
      }
    }
  }

  return $array;
}


// GET POLL LOCALE
public function getPollValueLocales($poll_value_id) {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_value_locale());
  $this->dao->where('fk_i_poll_value_id', $poll_value_id);

  $result = $this->dao->get();
  $array = array();

  if($result) {
    $locales = $result->result();

    if(count($locales) > 0) {
      foreach($locales as $l) {
        $array[$l['fk_c_locale_code']] = array('s_name' => $l['s_name'], 's_description' => $l['s_description']);
      }
    }
  }

  return $array;
}


// GET POLL
public function getPoll($poll_id) {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll());
  $this->dao->where('pk_i_id', $poll_id);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return false;
}



// GET POLL VALUE LOCALE
public function getPollValueLocale($poll_value_id, $locale = '') {
  $this->dao->select('*');
  $this->dao->from($this->getTable_poll_value_locale());
  $this->dao->where('fk_i_poll_value_id', $poll_value_id);

  if($locale <> '') {
    $this->dao->where('fk_c_locale_code', $locale);
  }

  $this->dao->limit(1);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return false;
}

}
?>