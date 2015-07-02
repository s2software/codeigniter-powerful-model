<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * s2s loader v. 3.0.0
 */
class MY_Loader extends CI_Loader {
	
	/**
	 * (non-PHPdoc)
	 * @see CI_Loader::model()
	 */
	public function model($model, $name = '', $db_conn = FALSE)
	{
		if (is_string($model) && $name == '')
		{
			$name = $model;
			$model = $model.'_model';
		}
		
		parent::model($model, $name, $db_conn );
	}
}