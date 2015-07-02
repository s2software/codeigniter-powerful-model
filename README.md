# CodeIgniter Powerful Model
A CodeIgniter extension to work (or better, to play!) with database tables with an easy and intuitive Object Oriented / Entity Framework approach.

## Installation
Download the files from this repository and place them into the corresponding folders in your CodeIgniter project.<br>
The extension also redefine the CI_Loader::model method. If you already have a MY_Loader extension, just copy the MY_Loader::model extended method within your extended class.

## Usage
Let's make examples with a classic *cars* table.
<!--
- [Define a New Entity Object](#define-a-new-entity-object)
- [Load a Model](#load-a-model)
-->

### Table schema
The best practice is to name your table in plural form (eg. *cars*), and name the primary key column as *id*.<br>
There's no particular preferences for other column names.<br>
Mind that you can still use other naming conventions for your table. In this case, you have to set this names manually changing the value of `table`, `row_type` and `id_field` property in the extended class constructor.

### Define a New Entity Object
In the *Models* folder, create a new *Cars_model.php* file defining the `Cars_model` extending `MY_Model` class (use the plural for the entire table) and the `Car_object` extending `Model_object` class (use the singular for the single record).

Here the code in *Models/Cars_model.php*:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cars_model extends MY_Model {

}

class Car_object extends Model_object {
	
}
```

### Load a Model
```php
$this->load->model('Cars');
// or autoload like this: $autoload['model'] = array('Cars');
```

### Get all records
```php
$all_cars = $this->Cars->get_list();
```

### Get some records
```php
// Get some records (apply a filter in query)
$some_cars = $this->Cars->where('brand_id', 1)->get_list();
```

### Get a single record
```php
$a_car = $this->Cars->get(1);	// this is a "get by id"
```

### Add a new record
```php
$new_car = $this->Cars->new_row();
$new_car->name = "Powerful Car";
$new_car->brand_id = 1;
$id = $new_car->save();	// this produces the insert command
```

### Make some changes
```php
$edit_car = $this->Cars->get($id);	// this is a "get by id"
$edit_car->name = "Change its name";
$edit_car->save();		// this produces the update command (only for the changed fields, the CI Powerful Model tracks object changes)
```

### Automatic join
```php
$all_cars = $this->Cars->autojoin()->get_list();	// automatic LEFT join with the brands table
// uses the CI's inflector helper to transform <entity>_id to <entities>
```

### Manual join
```php
$this->Cars->join('brands', 'cars.brand_id = brands.id', 'LEFT');
$all_cars = $this->Cars->get_list();
```

### CodeIgniters's Query Builder
You can use all the <a href="http://www.codeigniter.com/user_guide/database/query_builder.html" target="_blank">CodeIgniters's Query Builder</a> methods, allowing method chaining.
```php
$some_cars = $this->Cars->where_in('brand_id', array(1, 2, 3))->like('name', "%Something")->order_by('name')->get_list();
```
### Pagination 
```php
//(page 1, with 10 cars per page)
$cars_page1 = $this->Cars->pagination(1, 10)->order_by('name')->get_list();
```
### Count
```php
$brand1_cars_count = $this->Cars->where('brand_id', 1)->count();
```
### Automatic get the foreign key object
```php

$this->load->model('Brands');	// just another CI Power Model object
$car = $this->Cars->get(1);
$brand = $car->get_brand();	// cars.brand_id => brands.id
// uses the CI inflector's helper to transform <entity>_id to <entities>.id
```

###  Define custom methods
Here the code in *Models/Cars_model.php*:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cars_model extends MY_Model {
	
	public function has_brand()
	{
		$this->db->where('brand_id >', 0);
		
		return $this;	// remember this for method chaining support
	}
	
}

class Car_object extends Model_object {
	
	public function brand_name()
	{
		$CI = get_instance();
		
		$CI->load->model('Brands_model', 'Brands');	// just another CI Power Model object
		$brand = $CI->Brands->get($this->brand_id);
		if ($brand)
			return $brand->name;
		return '';
	}
}
```
#### Usege of the defined custom methods
```php
$cars = $this->Cars->has_brand()->get_list();
foreach ($cars as $car)
{
	echo 'Car: '.$car->name.', Brand: '.$car->brand_name().'<br>';
}
```

### Support for CodeIgniter Query Builder Caching system
```php
$this->Cars->start_cache();
$this->Cars->where('brand_id', 1);
$this->Cars->order_by('name');
$this->Cars->stop_cache();
$current_page_cars = $this->Cars->pagination(1, 10)->order_by('name')->get_list();
$total_cars_to_show = $this->Cars->count();	// this maintains the filter defined between start_cache() and stop_cache()
$this->Cars->flush_cache();
```

### Delete
```php
$to_delete = $this->Cars->get($id);	// this is a "get by id"
$to_delete->delete();
```

### Created/Modified date
If you add a `created` (datetime) and a `modified` (datetime) field in your table, CI Powerful Model automatically write the creation date and the last change date

### Soft delete support
If you create a `deleted` (datetime) field in your table, the delete.<br>
In this case, to filter your queries excluding the logical deleted records, call the `all` method before. Example:
```php
$all_cars = $this->Cars->all()->get_list();
$some_cars = $this->Cars->all()->where_in('brand_id', array(1, 2, 3))->get_list();
```
