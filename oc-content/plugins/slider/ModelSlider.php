<?php
/*
	*      OSCLass – software for creating and publishing online classified
	*                           advertising platforms
	*
	*                        Copyright (C) 2010 OSCLASS
	*
	*       This program is free software: you can redistribute it and/or
	*     modify it under the terms of the GNU Affero General Public License
	*     as published by the Free Software Foundation, either version 3 of
	*            the License, or (at your option) any later version.
	*
	*     This program is distributed in the hope that it will be useful, but
	*         WITHOUT ANY WARRANTY; without even the implied warranty of
	*        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	*             GNU Affero General Public License for more details.
	*
	*      You should have received a copy of the GNU Affero General Public
	* License along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/
class ModelSlider extends DAO {
	/**
		* It references to self object: Modelslider
		* It is used as a singleton
		* 
		* @access private
		* @since unknown
		* @var Currency
		*/
	private static $instance ;
	/**
		* It creates a new Modelslider object class ir if it has been created
		* before, it return the previous object
		* 
		* @access public
		* @since unknown
		* @return Currency
		*/
	public static function newInstance() {
		if( !self::$instance instanceof self ) {
			self::$instance = new self ;
		}
		return self::$instance ;
	}
	/**
		* Construct
		*/
	function __construct() {
		parent::__construct();
		$this->setTableName('t_slider') ;
		$this->setPrimaryKey('id') ;
		$this->setFields( array('id', 'uniqname', 'imagename', 'caption' , 'link') ) ;
	}
		/**
		* Return table name Slider 
		* @return string
		*/
	public function getTable() {
		return DB_TABLE_PREFIX.'t_slider';
	}
		/**
		* Import sql file
		* @param type $file 
		*/
	public function import($file) {
		$path = osc_plugin_resource($file) ;
		$sql = file_get_contents($path);
		if(! $this->dao->importSQL($sql) ){
			throw new Exception( $this->dao->getErrorLevel().' - '.$this->dao->getErrorDesc() ) ;
		}
	}
		/**
		* Remove data and tables related to the plugin.
		*/
	public function uninstall() {
		$this->dao->query(sprintf('DROP TABLE %s', $this->getTable()) ) ;
	}
		public function saveSlider($uniqname,$imagename,$caption,$link) {
		ModelSlider::newInstance()->insert(array(
		'id' => '',
		'uniqname' => $uniqname,
		'imagename' => $imagename,
		'caption' => $caption,
		'link' => $link
		));
	}
		public function getSlider() {
		$this->dao->select() ;
		$this->dao->from($this->getTable()) ;
		$results = $this->dao->get() ;
		if( !$results ) {
			return array() ;
		}
		return $results->result();
	}
		public function getSliderById($id) {
		$this->dao->select() ;
		$this->dao->from($this->getTable()) ;
		$this->dao->where('id', $id );
		$result = $this->dao->get() ;
		if( !$result ) {
			return array() ;
		}
		return $result->row();
	}
		public function getSliderByImage($imagename) {
		$this->dao->select() ;
		$this->dao->from($this->getTable()) ;
		$this->dao->where('uniqname', $imagename );
		$result = $this->dao->get() ;
		if( !$result ) {
			return array() ;
		}
		return $result->row();
	}
	function updateSlider($id,$uniqname,$imagename,$caption,$link){
		$this->dao->from($this->getTable()) ;
		$this->dao->set(array(
		'uniqname' => $uniqname,
		'imagename' => $imagename,
		'caption' => $caption,
		'link' => $link
		)) ;
		$this->dao->where(array(
		'id' => $id
		)) ;
		return $this->dao->update() ;
	}
		public function deleteSlider($id)
	{
		$this->dao->from($this->getTable()) ;
		$this->dao->where(array(
		'id' => $id
		)) ;
		return $this->dao->delete();
	}
	}
?>