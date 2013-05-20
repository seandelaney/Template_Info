<?php
if(!defined('BASEPATH')) :
	exit('No direct script access allowed');
endif;

$plugin_info = array(
	'pi_name' => 'Template info',
	'pi_version' => '1.0.0',
	'pi_author' => 'Sean Delaney',
	'pi_author_url' => 'http://www.seandelaney.ie',
	'pi_description' => 'Template Info is a simple plugin that displays basic template information about the primary template being rendered.',
	'pi_usage' => template_info::usage()
);

/**
 * Template info Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Sean Delaney @seandelaney
 * @copyright		Copyright (c) 2013
 * @link			http://www.seandelaney.ie
 *
 * A huge thanks to Leevi Graham @leevigraham for allowing me to use his LGTemplateInfo plugin and port it EE 2.x
 *
 * Leevi's original plugin for EE 1.x can be found here: http://leevigraham.com/cms-customisation/expressionengine/addon/lg-template-info/
 * 
 * Change Log
 *
 * v1.0.0 - Init release
 */
 
class Template_info {
	private $EE;
	private $class_name;
	private $site_id;
	private $site_404;
	private $page_uri;
	private $site_pages;
	public $return_data;

	/**
	 * Constructor
	 *
	 * Usage
	 *
	 * This function returns template information.
	 *
	 * @access	public
	 * @return	string
	 */
	public function template_info() {
		$this->EE =& get_instance();
		
		$this->return_data = '';
		
		$this->site_pages = array();
		
		$this->site_id = $this->EE->config->item('site_id');
        
        $this->site_404 = $this->EE->config->item('site_404');
		
		// Create a nice uri to match
		$this->page_uri = $this->EE->uri->uri_string();
        
		$this->class_name = strtolower(__CLASS__);
		
		if(!isset($this->EE->session->cache[$this->class_name])) : 
			$this->EE->session->cache[$this->class_name] = array();
		endif;
		
		if(isset($this->EE->session->cache[$this->class_name][__FUNCTION__]) === false) :
			$this->get_site_pages();
			
			if(count($this->site_pages) == 0) :
				$this->EE->TMPL->log_item('template_info: No pages are currently setup, so nothing to output!');
				
				return false;
			endif;
			
			$template = 'index';
			$template_id = '';
			$template_group = '';
			$template_group_id = '';
			
			// If there are pages and the size of the pages uri array is more than one and the page exists in the pages uri array set the entry id
			if(count($this->site_pages) > 0 && sizeof($this->site_pages['uris']) > 0 && ($entry_id = array_search($this->page_uri,$this->site_pages['uris'])) !== false) :
				// Query the DB to get the template and template group
				$query = $this->EE->db->query('SELECT t.template_name, t.template_id, tg.group_name, tg.group_id FROM '.$this->EE->db->dbprefix.'templates t, '.$this->EE->db->dbprefix.'template_groups tg WHERE t.group_id = tg.group_id AND t.template_id = "'.$this->EE->db->escape_str($this->site_pages['templates'][$entry_id]).'" LIMIT 1');
				
				// We should have a template and template group here now
				if($query->num_rows > 0) :
					// set them
					foreach($query->result() as $row) :
						$template = $row->template_name;
						$template_id = $row->template_id;
						$template_group = $row->group_name;
						$template_group_id = $row->group_id;
					endforeach;
				endif;
			// Else not a page		
			else :
				// If there is no segment_1 or segment_1 is for pagination, this must be the site index
				if($this->EE->uri->segment(1) === false || sizeof($this->EE->uri->total_segments()) == 1 && preg_match("#^(P\d+)$#",$this->EE->uri->segment(1),$match)) :
					// Get the template default group
					$query = $this->EE->db->query('SELECT t.template_name, t.template_id, tg.group_name, tg.group_id FROM '.$this->EE->db->dbprefix.'templates t, '.$this->EE->db->dbprefix.'template_groups tg WHERE tg.is_site_default = "y" AND t.group_id = tg.group_id AND t.template_name = "index" AND tg.site_id = '.$this->EE->db->escape_str($this->site_id).' LIMIT 1');
					
					// We should have a template and template group here now
					if($query->num_rows > 0) :
						// set them
						foreach($query->result() as $row) :
							$template = $row->template_name;
							$template_id = $row->template_id;
							$template_group = $row->group_name;
							$template_group_id = $row->group_id;
						endforeach;
					endif;
				else :
					// Is the first segment the name of a template group?
					$query = $this->EE->db->query('SELECT tg.group_id FROM '.$this->EE->db->dbprefix.'template_groups tg WHERE tg.group_name = "'.$this->EE->db->escape_str($this->EE->uri->segment(1)).'" AND tg.site_id = "'.$this->EE->db->escape_str($this->site_id).'" LIMIT 1');
					
					// No?
					if($query->num_rows == 0) :
						// If we're not using the 404 feature we need to fetch the name of the default template group
						
						if($this->site_404 == '') :
							// get the template default group
							$query = $this->EE->db->query('SELECT tg.group_name, tg.group_id FROM '.$this->EE->db->dbprefix.'template_groups tg WHERE tg.is_site_default = "y" AND tg.site_id = '.$this->EE->db->escape_str($this->site_id).' LIMIT 1');
							
							if($query->num_rows > 0) :
								// set them
								foreach($query->result() as $row) :
									$template_group = $row->group_name;
									$template_group_id = $row->group_id;
								endforeach;
							endif;
					
							// Is the first segment the name of a template?			
							$query = $this->EE->db->query('SELECT t.template_id FROM '.$this->EE->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "'.$this->EE->db->escape_str($this->EE->uri->segment(1)).'" LIMIT 1');

							// Yes!
							if($query->num_rows > 0) :
								// grab it
								$template = $this->EE->uri->segment(1);
							else :
								$query = $this->EE->db->query('SELECT t.template_id FROM '.$this->EE->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "index" LIMIT 1');
							endif;
							
							if($query->num_rows > 0) :
								foreach($query->result() as $row) :
									$template_id = $row->template_id;
								endforeach;
							endif;
						else :
							// I think we made need some 404 love here
						endif;
					// Yes!
					else :
						foreach($query->result() as $row) :
							$template_group_id = $row->group_id;
						endforeach;
						
						// Set the template group
						$template_group = $this->EE->uri->segment(1);

						// If there is no segment two it must be the index
						if($this->EE->uri->segment(2)) :
							// Is the second segment the name of a template?
							$query = $this->EE->db->query('SELECT t.template_id FROM '.$this->EE->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "'.$this->EE->db->escape_str($this->EE->uri->segment(2)).'" LIMIT 1');

							// Yes!
							if($query->num_rows > 0) :
								// Grab it
								$template = $this->EE->uri->segment(2);
							else :
								// Gotta grab the index template id
								$query = $this->EE->db->query('SELECT t.template_id FROM '.$this->EE->db->dbprefix.'templates t WHERE t.group_id = '.$template_group_id.' AND t.template_name = "index" LIMIT 1');
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
			
			$this->EE->session->cache[$this->class_name]['template_id'] = $template_id;
			$this->EE->session->cache[$this->class_name]['template_name'] = $template;
			$this->EE->session->cache[$this->class_name]['template_group_id'] = $template_group_id;
			$this->EE->session->cache[$this->class_name]['template_group_name'] = $template_group;
		endif;
			
		$attribute = $this->EE->TMPL->fetch_param('attribute','');
		
		if(!empty($attribute)) :
			$this->return_data = $this->EE->session->cache[$this->class_name][$attribute];
		else :
			$this->return_data = $this->EE->TMPL->tagdata;
		endif;
		
		return;
	} // END template_info
	
	private function get_site_pages() {
		if(isset($this->EE->session->cache[$this->class_name]['site_pages_'.$this->site_id])) :
			$this->site_pages = $this->EE->session->cache[$this->class_name]['site_pages_'.$this->site_id];
		else :
			$this->EE->db->select('site_pages');
			$this->EE->db->where('site_id',$this->site_id);
			
			$query = $this->EE->db->get('sites');
		
			$this->site_pages = unserialize(base64_decode($query->row('site_pages')));
			$this->site_pages = $this->site_pages[$this->site_id];
		
			$this->EE->session->cache[$this->class_name]['site_pages_'.$this->site_id] = $this->site_pages;
		endif;
		
		return;
	} // END get_site_pages
	
	/**
	 * Usage
	 *
	 * This function describes how the plugin is used.
	 *
	 * @access	public
	 * @return	string
	 */
	function usage() {
		ob_start(); 
		?>
		* Template ID
		* Template Name
		* Template Group ID
		* Template Group Name

		Just add one or all of the following tags to your template:

		{exp:template_info attribute="template_id"}
		{exp:template_info attribute="template_name"}
		{exp:template_info attribute="template_group_id"}
		{exp:template_info attribute="template_group_name"}
		<?php
		$buffer = ob_get_contents();

		ob_end_clean(); 

		return $buffer;
	}
	// END
}

/* End of file pi.template_info.php */
/* Location: ./system/expressionengine/third_party/template_info/pi.template_info.php */