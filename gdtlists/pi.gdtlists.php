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
	    'pi_version'      => '1.0',
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
			public	$field_id			= '';
			public	$field_list_items	= '';
			public	$items_array		= array();
			public	$line_break			= "<br />\n";
			public	$sort				= 'ASC';
			
		
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
				    $this->set_field_list_items();
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
			}
			
			// ------------------------------------------------------------------------
			
			/**
			 *	Fetch the field list items values from the channel_fields table.
			 *	@return array
			 */
			 public function set_field_list_items()
			 {
				 $query = ee()->db
				 			->select('field_list_items')
				 			->where('field_name',$this->field_name)
				 			->limit(1)
				 			->get('channel_fields');
				 
				 if($query->num_rows()==1)
				 {
					 $this->field_list_items = $query->row()->field_list_items;
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
				  	$data['item']		= array();
				  	$col 				= $this->field_id();
				  	$where[$col." !="]	= '';	
				  	
				  	if($this->channel_id !== FALSE)
				  	{
					  $where['channel_id']	= $this->channel_id;	
				  	}
				  	

				  	$query	= ee()->db
				   				->select($col)
				   				->where($where)
				   				->group_by($col)
				   				->order_by($col,$this->sort)
				   				->get('channel_data');
				   				
				   	
				   	if($query->num_rows()>0)
				   	{
					   	foreach($query->result() as $row)
					   	{
						   	$data['item'][] = array('item'=>htmlentities($row->{$col}));	
						}
				   	
				   	} else {
					
						$data['item'][] = array('item'=>'');   	
				   	
				   	}
				   	
				   	

				   	return ee()->TMPL->parse_variables(ee()->TMPL->tagdata,$data['item']);
			   }	
			   
			   // ------------------------------------------------------------------------
			   
			   
			   /** Get the channel data field_id of a column name based on the field_name.
			    *	@return string
			    */
			    public function field_id()
			    {
				    
				    $query = ee()->db
				    			->select('field_id')
				    			->limit(1)
				    			->where('field_name',$this->field_name)
				    			->get('channel_fields');
				    			
				    if($query->num_rows()==1)
				    {
					    return 'field_id_' . $query->row()->field_id;
				    } else {
					    return NULL;
				    }
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
					
					{exp:gdtlists:items_grouped field_name="custom_field_name" sort="desc"} - Returns a list distinct items from the the data for a custom field.
					
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
					channel_id		-	limit results to a channel by id
					line_break		-	html, if any, to use as the after each item. Default is <br />
					sort			-	sort by value of a grouped items tag pair. Default is ASC
					
					

					<?php
					 $buffer = ob_get_contents();
					 ob_end_clean();
					
					return $buffer;
					
			}
		
		
	}
/* End of file pi.gdtlists.php */
/* Location: ./system/expressionengine/third_party/gdtlists/pi.gdtlists.php */
