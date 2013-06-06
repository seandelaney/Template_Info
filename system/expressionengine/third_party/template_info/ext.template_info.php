<?php
if(!defined('BASEPATH')) :
	exit('No direct script access allowed');
endif;

/* EE 2.6.0 backward compat */
if(!function_exists('ee')) {
	function ee() {
		static $EE;
		
		if(!$EE) :
			$EE =& get_instance();
		endif;
		
		return $EE;
	} // END ee
}

/**
 * Template info Class
 *
 * @package		ExpressionEngine
 * @category	Extension
 * @author		Sean Delaney @seandelaney
 * @copyright	Copyright (c) 2013
 * @link		http://www.seandelaney.ie
 *
 * Template Info is a simple plugin that displays basic template information about the primary template being rendered.
 *
 * A huge thanks to Leevi Graham @leevigraham for allowing me to use his LGTemplateInfo plugin and port it EE 2.x
 *
 * Leevi's original plugin for EE 1.x can be found here: http://leevigraham.com/cms-customisation/expressionengine/addon/lg-template-info/
 * 
 * Change Log
 *
 * v2.0.0 - Added template info as early parsed global variables. 
 		  - Change from a plugin to an extension.
 
 * v1.1.0 - Added < EE 2.6.0 backward compatibility.
 
 * v1.0.2 - Fixed an issue where template_group_name was never being set. Also added some 404 love.
 *
 * v1.0.1 - Fixed an issue where URI's where not matching due to a leading slash missing.
 *
 * v1.0.0 - Init release
 */
 
class Template_info_ext {
	public $name;
	public $version;
	public $description;
	public $settings_exist;
	public $docs_url;
	private $settings;
	private $class_name;
	private $site_id;
	private $site_404;
	private $page_uri;
	private $site_pages;	
    
	/**
     * Class Constructor
     *
     * @access public
     * @return null
     */
	/**
	 * PHP4 Constructor
	 *
	 * @see __construct()
	 */ 
	public function Template_info_ext($settings = false) {
		$this->__construct($settings);
	} // END Template_info_ext

	/**
	 * Constructor
	 *
	 * @param   mixed   Settings array or empty string if none exist.
	 */		 
	public function __construct($settings = array()) {
		$this->name = 'Template Info';
	    $this->version = '2.0.0';
	    $this->description = 'Template Info is a simple plugin that displays basic template information about the primary template being rendered.';
	    $this->settings_exist = 'n';
	    $this->docs_url = '';
		$this->settings = $settings;
		$this->site_pages = array();
		$this->site_id = ee()->config->item('site_id');
        $this->site_404 = ee()->config->item('site_404');
		$this->page_uri = '/'.trim(ee()->uri->uri_string());
        $this->class_name = strtolower(str_replace('_ext','',__CLASS__));
	} // END __construct
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://ellislab.com/codeigniter/user-guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension() {
		$data = array(
			'class' => __CLASS__,
			'method' => 'sessions_start',
			'hook' => 'sessions_start',
			'settings' => serialize($this->settings),
			'priority' => 10,
			'version' => $this->version,
			'enabled' => 'y'
		);
		
		ee()->db->insert('extensions',$data);
	} // END activate_extension
	
	/** 
	 * This function returns template information.
	 *
	 * @access	public
	 */
	public function sessions_start() {
		$this->_get_site_pages();
		
		if(count($this->site_pages) == 0) :
			ee()->TMPL->log_item('template_info: No pages are currently setup, so nothing to output!');
			
			return false;
		endif;
		
		$template_name = 'index';
		$template_id = '';
		$template_group_name = '';
		$template_group_id = '';
		
		// If there are pages and the size of the pages uri array is more than one and the page exists in the pages uri array set the entry id
		if(count($this->site_pages) > 0 && sizeof($this->site_pages['uris']) > 0 && ($entry_id = array_search($this->page_uri,$this->site_pages['uris'])) !== false) :
			// Query the DB to get the template and template group
			$query = ee()->db->query('SELECT t.template_name, t.template_id, tg.group_name, tg.group_id FROM '.ee()->db->dbprefix.'templates t, '.ee()->db->dbprefix.'template_groups tg WHERE t.group_id = tg.group_id AND t.template_id = "'.ee()->db->escape_str($this->site_pages['templates'][$entry_id]).'" LIMIT 1');
			
			// We should have a template and template group here now
			if($query->num_rows > 0) :
				// set them
				foreach($query->result() as $row) :
					$template_name = $row->template_name;
					$template_id = $row->template_id;
					$template_group_name = $row->group_name;
					$template_group_id = $row->group_id;
				endforeach;
			endif;
		// Else not a page		
		else :
			// If there is no segment_1 or segment_1 is for pagination, this must be the site index
			if(ee()->uri->segment(1) === false || sizeof(ee()->uri->total_segments()) == 1 && preg_match("#^(P\d+)$#",ee()->uri->segment(1),$match)) :
				// Get the template default group
				$query = ee()->db->query('SELECT t.template_name, t.template_id, tg.group_name, tg.group_id FROM '.ee()->db->dbprefix.'templates t, '.ee()->db->dbprefix.'template_groups tg WHERE tg.is_site_default = "y" AND t.group_id = tg.group_id AND t.template_name = "index" AND tg.site_id = '.ee()->db->escape_str($this->site_id).' LIMIT 1');
				
				// We should have a template and template group here now
				if($query->num_rows > 0) :
					// set them
					foreach($query->result() as $row) :
						$template_name = $row->template_name;
						$template_id = $row->template_id;
						$template_group_name = $row->group_name;
						$template_group_id = $row->group_id;
					endforeach;
				endif;
			else :
				// Is the first segment the name of a template group?
				$query = ee()->db->query('SELECT tg.group_id FROM '.ee()->db->dbprefix.'template_groups tg WHERE tg.group_name = "'.ee()->db->escape_str(ee()->uri->segment(1)).'" AND tg.site_id = "'.ee()->db->escape_str($this->site_id).'" LIMIT 1');
				
				// No?
				if($query->num_rows == 0) :
					// If we're not using the 404 feature we need to fetch the name of the default template group
					if($this->site_404 == '') :
						// get the template default group
						$query = ee()->db->query('SELECT tg.group_name, tg.group_id FROM '.ee()->db->dbprefix.'template_groups tg WHERE tg.is_site_default = "y" AND tg.site_id = '.ee()->db->escape_str($this->site_id).' LIMIT 1');
						
						if($query->num_rows > 0) :
							// set them
							foreach($query->result() as $row) :
								$template_group_name = $row->group_name;
								$template_group_id = $row->group_id;
							endforeach;
						endif;
				
						// Is the first segment the name of a template?			
						$query = ee()->db->query('SELECT t.template_id FROM '.ee()->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "'.ee()->db->escape_str(ee()->uri->segment(1)).'" LIMIT 1');

						// Yes!
						if($query->num_rows > 0) :
							// grab it
							$template_name = ee()->uri->segment(1);
						else :
							$query = ee()->db->query('SELECT t.template_id FROM '.ee()->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "index" LIMIT 1');
						endif;
						
						if($query->num_rows > 0) :
							foreach($query->result() as $row) :
								$template_id = $row->template_id;
							endforeach;
						endif;
					else :
						// 404 template set
						$page_not_found_uri = explode('/',$this->site_404);
						
						$query = ee()->db->query('SELECT t.template_name, t.template_id, tg.group_name, tg.group_id FROM '.ee()->db->dbprefix.'templates t, '.ee()->db->dbprefix.'template_groups tg WHERE tg.is_site_default = "y" AND t.group_id = tg.group_id AND t.template_name = "'.$page_not_found_uri[1].'" AND tg.site_id = '.ee()->db->escape_str($this->site_id).' LIMIT 1');
				
						if($query->num_rows > 0) :
							// set them
							foreach($query->result() as $row) :
								$template_name = $row->template_name;
								$template_id = $row->template_id;
								$template_group_name = $row->group_name;
								$template_group_id = $row->group_id;
							endforeach;
						endif;
					endif;
				// Yes!
				else :
					foreach($query->result() as $row) :
						$template_group_id = $row->group_id;
					endforeach;
					
					// Set the template group
					$template_group_name = ee()->uri->segment(1);

					// If there is no segment two it must be the index
					if(ee()->uri->segment(2)) :
						// Is the second segment the name of a template?
						$query = ee()->db->query('SELECT t.template_id FROM '.ee()->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "'.ee()->db->escape_str(ee()->uri->segment(2)).'" LIMIT 1');

						// Yes!
						if($query->num_rows > 0) :
							// Grab it
							$template_name = ee()->uri->segment(2);
						else :
							// Gotta grab the index template id
							$query = ee()->db->query('SELECT t.template_id FROM '.ee()->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "index" LIMIT 1');
						endif;
						
						if($query->num_rows > 0) :
							foreach($query->result() as $row) :
								$template_id = $row->template_id;
							endforeach;
						endif;
					endif;
				endif;
			endif;
		endif;
		
		$early = array(
			$this->class_name.':template_id' => $template_id,
			$this->class_name.':template_name' => $template_name,
			$this->class_name.':template_group_id' => $template_group_id,
			$this->class_name.':template_group_name' => $template_group_name
		);		
		
		ee()->config->_global_vars = array_merge($early,ee()->config->_global_vars);
	} // END sessions_start
	
	/** 
	 * This function returns site pages.
	 *
	 * @access	private
	 */
	private function _get_site_pages() {
		if(isset(ee()->session->cache[$this->class_name]['site_pages_'.$this->site_id])) :
			$this->site_pages = ee()->session->cache[$this->class_name]['site_pages_'.$this->site_id];
		else :
			ee()->db->select('site_pages');
			ee()->db->where('site_id',$this->site_id);
			
			$query = ee()->db->get('sites');
		
			$this->site_pages = unserialize(base64_decode($query->row('site_pages')));
			$this->site_pages = $this->site_pages[$this->site_id];
		
			ee()->session->cache[$this->class_name]['site_pages_'.$this->site_id] = $this->site_pages;
		endif;
		
		return;
	} // END _get_site_pages
	
	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return  mixed   void on update / false if none
	 */
	public function update_extension($current = '') {
		if($current == '' or $current == $this->version) :
			return false;
		endif;
		
		if($current < '1.0') :
			// Update to version 1.0
		endif;
		
		ee()->db->where('class',__CLASS__);
		ee()->db->update('extensions',array('version' => $this->version));
	} // END update_extension
	
	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	public function disable_extension() {
	    ee()->db->where('class',__CLASS__);
	    ee()->db->delete('extensions');
	} // END disable_extension	
}

// END CLASS

/* End of file ext.template_info.php */
/* Location: /system/expressionengine/third_party/template_info/ext.template_info.php */ 