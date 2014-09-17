<?php 

/**
 *	Gdtlists Class
 *
 *	@package		ExpressionEngine
 *	@author			Richard Whitmer/Godat Design, Inc.
 *	@copyright		(c) 2014, Godat Design, Inc.
 *	@license		
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a copy
 *	of this software and associated documentation files (the "Software"), to deal
 *	in the Software without restriction, including without limitation the rights
 *	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *	copies of the Software, and to permit persons to whom the Software is
 *	furnished to do so, subject to the following conditions:
 *	The above copyright notice and this permission notice shall be included in all
 *	copies or substantial portions of the Software.
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *	SOFTWARE.
 *	
 *	@link			http://godatdesign.com
 *	@since			Version 2.9
 */
 
 // ------------------------------------------------------------------------

/**
 * Good at Lists Plugin
 *
 * @package			ExpressionEngine
 * @subpackage		third_party
 * @category		Plugin
 * @author			Richard Whitmer/Godat Design, Inc.
 * @copyright		Copyright (c) 2014, Godat Design, Inc.
 * @link			http://godatdesign.com
 */
  
 // ------------------------------------------------------------------------

	$plugin_info = array(
	    'pi_name'         => 'Good at Lists',
	    'pi_version'      => '1.2',
	    'pi_author'       => 'Richard Whitmer/Godat Design, Inc.',
	    'pi_author_url'   => 'http://godatdesign.com/',
	    'pi_description'  => '
		Grab the field list items values from a custom field or
		create a list of distinct items from a channel data column
	    ',
	    'pi_usage'        => Gdtlists::usage()
	);
	

	class  Gdtlists {
		
			public	$channel_name		= '';
			public	$channel_id			= FALSE;
			public	$return_data		= '';
			public	$field_name			= '';
			public	$field_id				= '';
			public	$field_list_items	= '';
			public	$items_array		= array();
			public	$line_break			= "<br />\n";
			public	$sort						= 'ASC';
			public	$custom_fields	= array();
			public	$field_type 			= '';
			public	$channel_data_col	= '';
			
		
			public function __construct()
			{
			
				if(ee()->TMPL->fetch_param('channel_name'))
				{
				    $this->channel_name	= ee()->TMPL->fetch_param('channel_name');
				    $this->set_channel_id();
				}
			
				if(ee()->TMPL->fetch_param('channel_id'))
				{
				    $this->channel_id	= ee()->TMPL->fetch_param('channel_id');
				}
			
				if(ee()->TMPL->fetch_param('field_name'))
				{
				    $this->field_name	= ee()->TMPL->fetch_param('field_name');
				    $this->set_field_data();
				}
				
				if(ee()->TMPL->fetch_param('line_break'))
				{
					$this->line_break	= ee()->TMPL->fetch_param('line_break') . "\n";
				}

				if(ee()->TMPL->fetch_param('sort'))
				{
					$this->sort	= strtoupper(ee()->TMPL->fetch_param('sort'));

					if( ! in_array($this->sort, array('ASC','DESC')))
					{
						$this->sort = 'ASC';
					}
				}
				
				
				$this->array_sort	= ee()->TMPL->fetch_param('array_sort','string');
				
			}
			

			

			// ------------------------------------------------------------------------
			
			/**
			 *	Set some data about field_type and field list items.
			 *	@return array
			 */
			 public function set_field_data()
			 {
				 $select	= array('field_id','field_type','field_list_items');
				 
				 $query = ee()->db
				 			->select($select)
				 			->where('field_name',$this->field_name)
				 			->limit(1)
				 			->get('channel_fields');
				 
				 if($query->num_rows()==1)
				 {
					 $row 										= $query->row();
					 $this->field_type				= $row->field_type;
					 $this->field_list_items	=	$row->field_list_items;
					 $this->channel_data_col	= 'field_id_' . $row->field_id;
				 }			
			 }
			 
			 // ------------------------------------------------------------------------
			 
			 /** 
			  *	Return field_list_items string.
			  * @return string
			  */
			  public function items()
			  {
				  return preg_replace("/\r|\n/s",$this->line_break,$this->field_list_items);
			  }
			  
			  // ------------------------------------------------------------------------
			  
			  
			 /** 
			  *	Return field_list_items string.
			  * @return string
			  */
			  public function items_array()
			  {
				  $data['item']	= array();
				  $str	=	preg_replace("/(\r|\n)/s",'||',$this->field_list_items);
				  $items=	explode('||',$str);
				  
				  foreach($items as $row)
				  {
					  $data['item'][] = array('item'=>htmlentities($row));
				  }
				  
				  return ee()->TMPL->parse_variables(ee()->TMPL->tagdata,$data['item']);

			  }	
			  
			  // ------------------------------------------------------------------------
			  
			  
			  /**
			   *	Return a list of items based on a distinct group of results from
			   *	the channel data table.
			   */
			   public function items_grouped()
			   {
				  	$this->set_field_group_data();
				    $this->set_custom_fields();
				    
						$data['item']		= array();
				  	$col 				= $this->field_id();
				  	$where[$col." !="]	= '';	
				  	
				  	
				  	foreach($this->custom_fields as $key => $row)
				  	{
					  	if(ee()->TMPL->fetch_param($key))
					  	{
						  	$where[$row]	= ee()->TMPL->fetch_param($key);
					  	}
				  	}
				  	
				  	if($this->channel_id !== FALSE)
				  	{
					  	$where['channel_id']	= $this->channel_id;	
				  	}
				  	
				  	
				  	
				  	// If this is a multi_select type, get the grouped values from the multi_selects() method.
				  	if(in_array($this->field_type,array('checkboxes','multi_select')))
				  	{
					  	
					  	$sorted = $this->multi_selects();
					  	
				  	} else {
				  	

				  	$query	= ee()->db
				   				->select($col)
				   				->where($where)
				   				->group_by($col)
				   				->order_by($col,$this->sort)
				   				->get('channel_data');
				   				
				   	$rows = $query->result();		
	
				   	if($query->num_rows()>0)
				   	{

						   	foreach($rows as $key => $row)
						   	{
							   	$sorted[$key] = $row->{$col};
	
						   	}
						   	
						}
						
						}
					   	
					   	
					   	$sorted = $this->array_sorting($sorted);

					   	foreach($sorted as $key=>$row)
					   	{
						   	$data['item'][] = array('item'=>htmlentities($row));	
							}
				   	
				   
				   	
				   	

				   	return ee()->TMPL->parse_variables(ee()->TMPL->tagdata,$data['item']);
			   }	
			   
			   // ------------------------------------------------------------------------
			   
			   
			   /** Get the channel data field_id of a column name based on the field_name.
			    *	@return string
			    */
			    public function field_id()
			    {
			    	return $this->channel_data_col;
			    }
			    
			    // ------------------------------------------------------------------------
			    
			    
			    /**
			     *	Set the channel_id property.
			     */
			     private function set_channel_id()
			     {
				     if($this->channel_name !== '')
				     {
					     $query = ee()->db
					     			->select('channel_id')
					     			->where('channel_name',$this->channel_name)
					     			->limit(1)
					     			->get('channels');
					     			
					     if($query->num_rows()==1)
					     {
						     $this->channel_id = $query->row()->channel_id;
					     }
				     }
			     }
			     
			    // ------------------------------------------------------------------------
			    
			    
			    /**
			     *	Set the field group data property.
			     */
			     private function set_field_group_data()
			     {

				     $query = ee()->db
					 				->select('channel_fields.field_id,channel_fields.field_name,field_groups.group_id,field_groups.site_id,field_groups.group_name')
					     			->join('field_groups','field_groups.group_id=channel_fields.group_id')
					     			->where('channel_fields.field_name',$this->field_name)
					     			->limit(1)
					     			->get('channel_fields');
					     			
					     if($query->num_rows()==1)
					     {
						     $row = $query->row_array();
						     
						     foreach($row as $key=>$row)
						     {
						     	$this->{$key} = $row;

						     }
						     
					     }

			     }
			     
			     // ------------------------------------------------------------------------
			     
			     /** 
			      * Set custom fields.
			      */
			      private function set_custom_fields()
			      {
					  	if($this->group_id)
					  	{
					  	
					  	    $select[]	= "CONCAT('field_id_',field_id) AS field_id";
					  	    $select[]	= "field_name"; 
					  	    $query = ee()->db
					  	    			->select($select)
					  	    			->where('group_id',$this->group_id)
					  	    			->order_by('field_order','ASC')
					  	    			->get('channel_fields');

					  	    			foreach($query->result() as $key=>$row)
					  	    			{
					  	    				$this->custom_fields[$row->field_name] = $row->field_id;
					  	    			}
					  	
					  	}
				      
			      }
			    
			    // ------------------------------------------------------------------------
			      
					/**
			    *	Handle sorting of arrays.
			    * @param $data array
			    *	@return array
			    */
			    private function array_sorting($data = array())
			    {
				  		foreach($data as $key => $row)
					   	{
						   	
						   	if($this->array_sort == 'numeric')
						   	{
							   	arsort($data,SORT_NUMERIC);
							   	
							   	if($this->sort == 'ASC')
							   	{
								   	$data = array_reverse($data);
							   	}
						   	
						   	} else {
							   	
							   	arsort($data,SORT_STRING);
							   	
							   	if($this->sort == 'ASC')
							   	{
								   	$data = array_reverse($data);
							   	}
						   	}
						   	
					   	}  
					   	
					   	return $data;
				    
			    }
			    
			    // ------------------------------------------------------------------------
			    
			    
			    
			    /**
			     * Get the field type.
			     * @return string
			     */
			     public function field_type()
			     {

				     	return $this->field_type;
				     
			     }
			    
			    
			    
			    // ------------------------------------------------------------------------
			    
			    
			/**
			 *	EE stores multiple select type fields such as checkboxes & multi_select fields
			 *	as pipe dilimited strings. This function returns those values as a unique array.
			 *	@return array();
			 */
			private function multi_selects()
			{

					$data		= array();
					
					$query	= ee()->db
											->select($this->channel_data_col)
											->where('channel_id',$this->channel_id)
											->get('channel_data');
											
					if($query->num_rows()>0)
					{
						
							$rows	= $query->result();
							
							foreach($rows as $key=>$row)
							{
									$row = trim($row->{$this->channel_data_col});
									
									if( ! empty($row))
									{
										$vals = explode('|',$row);
										$data = array_merge($vals,$data);
									}
							}
						
					}
					
					$data		= array_unique($data);
					
				return $data;
				
			}
			

			// ------------------------------------------------------------------------
			

			/**
			 *	Return plugin usage documentation.
			 *	@return string
			 */
			public function usage()
			{
				
					ob_start();  ?>
					 
					SINGLE TAGS:
					----------------------------------------------------------------------------
					{exp:gdtlists:items field_name="custom_field_name"} - Returns custom field list items as a string.
					
					TAG PAIRS:
					----------------------------------------------------------------------------
					{exp:gdtlists:items_array field_name="custom_field_name"} - Returns a list items one-by-one using the {item} variable.
					
					Example: 
					{exp:gdtlists:items_array field_name="custom_field_name"}
						{item}
					{/exp:gdtlists:items_array}
					
					{exp:gdtlists:items_grouped field_name="custom_field_name" sort="desc" custom_field="some value"} - Returns a list distinct items from the the data for a custom field.
					
					Example:
					{exp:gdtlists:items_array field_name="awardee_years" sort="desc" channel_name="awardee-database"}
						{item}
					{/exp:gdtlists:items_array}
					
					
					VARIABLES: 
					----------------------------------------------------------------------------
					{item}			- List item value in tag pairs
					
					
					REQUIRED PARAMETERS: 
					----------------------------------------------------------------------------
					field_name	- name of the custom field
					
					
					OPTIONAL PARAMETERS: 
					----------------------------------------------------------------------------
					channel_name	-	limit results to a channel by name
					channel_id	-	limit results to a channel by id
					line_break	-	html, if any, to use as the after each item. Default is <br />
					sort		-	sort by value of a grouped items tag pair. Default is ASC
					custom_field	-	limit grouped_items to results with a custom field of a given value.
					
					

					<?php
					 $buffer = ob_get_contents();
					 ob_end_clean();
					
					return $buffer;
					
			}
	}
	
	
	// ------------------------------------------------------------------------
	
	/** Changelog
	 *	1.1 - Added array sorting for items_grouped method.
	 *	1.2 - Handling of grouped items pulled from checkboxes and multi_selects
	 *
	 */
	

	
/* End of file pi.gdtlists.php */
/* Location: ./system/expressionengine/third_party/gdtlists/pi.gdtlists.php */
