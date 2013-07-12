<?php
if (!function_exists('createMMFormField')) {
	function createMMFormField($label, $name, $value, $type, $options=null)
	{
		$output = '';
		$field = '';
		$useField = true;

		switch ($type)
		{
			case 'text':
				$field = createMMInput($label, $value, $type, $options);
			break;
			case 'textarea':
				$field = createMMTextArea($label, $value, $options);
			break;
			case 'select':
				$field = createMMSelect($label, $value, $options);
			break;
			case 'editor':
				$useField = false;
			break;
		}

		echo '<div class="control-group">' .
						'<label class="control-label" for="' . $label . '">' . $name . '</label>' .
						'<div class="controls">';

		if ($useField)
		{
			echo $field;
		}
		else
		{
			wp_editor( $value, $label, $settings = array() );
		}

		echo '</div></div>';
					
		//return $output;
	}

	function mergeMM_options($pairs, $atts) {
	    $atts = (array)$atts;
	    $out = array();
	    foreach($pairs as $name => $default) {
	            if ( array_key_exists($name, $atts) )
	                    $out[$name] = $atts[$name];
	            else
	                    $out[$name] = $default;
	    }
	    return $out;
	}

	function createMMInput($label, $value, $type="text", $options = null)
	{
		extract( merge_MM_options(
			array("class" => "", "placeholder" => "", "note" => ""), $options)
		);

		$output = sprintf('<input type="%s" id="%s" class="%s" name="%s" value="%s" placeholder="%s" />', $type,
			 $label, //id
			 $class,
			 $label, //name
			 stripslashes($value), //value
			 $placeholder
		);
		
		if (isset($note)) {
			$output .= sprintf('<p class="help-block">%s</p>', $note);
		}
		
		return $output;
	}

	function createMMTextArea($label, $value, $options = null)
	{
		extract( mergeMM_options(
			array("class" => "", "placeholder" => "", "rows" => 3, "note" => ""), $options)
		);

		$output = sprintf('<textarea id="%s" class="%s" rows="%s" name="%s" placeholder="%s">%s</textarea>', 
			 $label, //id
			 $class,
	 		 $rows,
			 $label, //name
			 $placeholder,
			 stripslashes($value) //value
		);
		
		if ($note) {
			$output .= sprintf('<p class="help-block">%s</p>', $note);
		}
		
		return $output;
	}

	function createMMSelect($label, $value, $options)
	{
		return createMMSelectOptionsFromArray($label, $value, $options);
	}

	function createMMSelectOptionsFromArray($label, $selectedKey, $options)
	{
		extract( merge_MM_options(
			array("class" => "", "placeholder" => "", "note" => "", "data" => array()), $options)
		);
		
		$output = sprintf('<select id="%s" class="%s" name="%s">', $label, $class, $label);
		$optionTemplate = '<option value="%s"%s>%s</option>\n';
		$output .= sprintf($optionTemplate, "", "", $placeholder);

		foreach ($data as $key => $value)
		{
			if ($selectedKey == $key)
			{
				$output .= sprintf($optionTemplate, $key, ' selected', $value);
			}
			else
			{
				$output .= sprintf($optionTemplate, $key, '', $value);
			}
		}
		
		$output .= '</select>';
		
		if ($note) {
			$output .= sprintf('<p class="help-block">%s</p>', $note);
		}
		
		return $output;
	}

	function getMMCategorySelectArray()
	{
		$categories = get_categories(array('hide_empty' => 0));
		
		$catArray = array();
		foreach ($categories as $category)
		{
			$catArray[$category->term_id] = $category->cat_name;
		}
		
		return $catArray;
	}

	function getMMPagesSelectArray()
	{
		return getMMTaxonomySelectArray('page');
	}

	function getMMPostsSelectArray()
	{
		return getMMTaxonomySelectArray('post');
	}

	function getMMTaxonomySelectArray($taxonomy)
	{
		$args = array('post_type' => $taxonomy);
		$posts = get_posts($args);
		
		$postArray = array();
		foreach ($posts as $post)
		{
			$postArray[$post->ID] = $post->post_title;
		}
		
		return $postArray;
	}
}

?>