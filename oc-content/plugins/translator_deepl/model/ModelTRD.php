<?php
class ModelTRD extends DAO {
private static $instance;

public static function newInstance() {
  if(!self::$instance instanceof self) {
    self::$instance = new self;
  }
  return self::$instance;
}

function __construct() {
  parent::__construct();
}


public function getTable_item() {
  return DB_TABLE_PREFIX.'t_item';
}

public function getTable_translation() {
  return DB_TABLE_PREFIX.'t_trd_translation';
}



public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);
  
  $sql = str_replace('/*LOCALE_CODE*/', osc_language(), $sql);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelTRD<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install() {
  $this->import('translator_deepl/model/struct.sql');
}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_translation()));
  
  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-translator_deepl'";
  $this->dao->query($query);
}


// EXECUTE QUERIES ON VERSION UPDATE
public function versionUpdate($ignore_error = false) {
  $version = (int)trd_param('version');     // v100 is initial
  $version = ($version >= 100 ? $version : 0);
  $plugin = 'translator_deepl';
  
  // Version not yet available - it's installation process now
  if($version == 0) {
    return true;
  }
  
  // $queries = array(
    // array('version' => 101, 'query' => sprintf("CREATE TABLE %st_trd_values (pk_i_id INT NOT NULL AUTO_INCREMENT, fk_c_locale_code CHAR(5) NOT NULL, s_type VARCHAR(20), s_name VARCHAR(100) NOT NULL, PRIMARY KEY(pk_i_id, fk_c_locale_code)) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX)),
    // array('version' => 102, 'query' => sprintf("ALTER TABLE %st_user_translator_deepl ADD COLUMN s_gallery VARCHAR(2000);", DB_TABLE_PREFIX)),
  // );
  
  $queries = array();
  
  if(is_array($queries) && count($queries) > 0) {
    foreach($queries as $query) {
      if($version < $query['version'] && $query['version'] <= trd_VERSION_ID) {
        $result = $this->dao->query($query['query']);
        
        if($result === false && $ignore_error !== true) {
          $message  = sprintf(__('Update of plugin "%s" failed on DB version "%s". Please enable %s to see error details. Failed query is listed below.', 'translator_deepl'), __('Business Profile Plugin', 'translator_deepl'), $query['version'], '<a href="https://docs.osclasspoint.com/debug-mode" target="_blank">' . __('DB debug mode', 'translator_deepl') . '</a>');
          $message .= '<pre style="font-size:11px;">' . $query['query'] . '</pre>';
          $message .= '<a href="' . osc_admin_base_url(true) . '?page=plugins&forceupdateplugin=' . $plugin . '">' . __('Ignore error and force plugin update', 'translator_deepl') . '</a>. ';
          $message .= __('Never force update plugin until you are sure that your database structure match to model/struct.sql file! It may lead to unexpected plugin functionality. Try to reinstall plugin.', 'translator_deepl');

          osc_add_flash_error_message($message, 'admin');
          return false;
        }
      }
    }
  }
  
  return true;
}



public function getTranslationById($id) {
  if($id <= 0) { return false; }

  $this->dao->select();
  $this->dao->from($this->getTable_translation());
  $this->dao->where('pk_i_id', $id);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return $data;
  }
  
  return false;
}


public function getTranslationByStringAndCode($original_text, $lang_code) {
  $this->dao->select();
  $this->dao->from($this->getTable_translation());
  $this->dao->where('s_original_text', $original_text);
  $this->dao->where('fk_c_locale_code', $lang_code);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    
    if(isset($data['s_translated_text']) && trim((string)$data['s_translated_text']) != '') {
      return (string)$data['s_translated_text'];
    }
  }
  
  return false;
}


public function countTranslations($lang_code = '') {
  $this->dao->select('count(*) as rec_count, count(DISTINCT fk_c_locale_code) as lang_count');
  $this->dao->from($this->getTable_translation());
  
  if($lang_code != '') {
    $this->dao->where('fk_c_locale_code', $lang_code);
  }
  
  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return $data;
  }
  
  return false;
}



// GET ALL INVOICES
public function getTranslations($options = array(), $only_count = false) {
  $selector = '';
  
  if($only_count === true) {
    $selector = 'count(pk_i_id) as i_count';
  }
  
  $this->dao->select($selector);
  $this->dao->from($this->getTable_translation());

  if(isset($options['original_text']) && trim($options['original_text']) != '') {
    $this->dao->where(sprintf('s_original_text like "%%%s%%"', trim($options['original_text'])));
  } 
  
  if(isset($options['translated_text']) && trim($options['translated_text']) != '') {
    $this->dao->where(sprintf('s_translated_text like "%%%s%%"', trim($options['translated_text'])));
  } 
  
  if(isset($options['date']) && trim($options['date']) != '') {
    $this->dao->where(sprintf('dt_create_date like "%%%s%%"', trim($options['date'])));
  } 
  
  if(isset($options['locale']) && trim($options['locale']) != '') {
    $this->dao->where('fk_c_locale_code', $options['locale']);
  } 

 
  if($only_count !== true) {
    // $limit[0] == limit; $limit[1] == page
    $page = (isset($options['pageId']) ? $options['pageId'] : 0);
    $per_page = (isset($options['per_page']) ? $options['per_page'] : -1);
    
    if($per_page < 0) {
      $per_page = 20;
    }
      
    if($page > 0 && $per_page > 0) {
      $this->dao->limit(($page-1)*$per_page, $per_page);
    } else if($per_page > 0) {
      $this->dao->limit($per_page);
    }  

    $this->dao->orderby('pk_i_id DESC');
  }

  $result = $this->dao->get();
  
  if($result) {
    if($only_count === true) {
      $data = $result->row();
      return isset($data['i_count']) ? $data['i_count'] : 0;
    } else {
      return $result->result();
    }
  }

  return ($only_count ? 0 : array());
}



public function updateTranslation($id, $data) {
  return $this->dao->update($this->getTable_translation(), $data, array('pk_i_id' => $id));
}


public function deleteTranslation($id) {
  return $this->dao->delete($this->getTable_translation(), array('pk_i_id' => $id));
}


public function deleteAllTranslations() {
  return $this->dao->delete($this->getTable_translation(), array('1' => '1'));
}


public function insertTranslation($data) {
  $this->dao->insert($this->getTable_translation(), $data);
  return $this->dao->insertedId();
}




}
?>