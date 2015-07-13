# CodeIgniter Powerful Model
A CodeIgniter extension to work (or better, to play!) with database tables with an easy and intuitive Object Oriented / Entity Framework approach.

## Installation
Download the files from this repository and place them into the corresponding folders in your CodeIgniter project.<br>
The add-in consists of a solid `MY_Model` implementation and also redefines the `CI_Loader::model` method. If you already have a `MY_Loader` extension, just copy the `MY_Loader::model` extended method within your extended class.

## Usage
Let's make some examples with a classic `cars` table.
- [Table Schema](#table-schema)
- [Define a New Entity Object](#define-a-new-entity-object)
- [Load a Model](#load-a-model)
- [Get All Records](#get-all-records)
- [Get Some Records](#get-some-records)
- [Get a Single Record](#get-a-single-record)
- [Add a New record](#add-a-new-record)
- [Make Some Changes](#make-some-changes)
- [Automatic Join](#automatic-join)
- [Manual Join](#manual-join)
- [CodeIgniter's Query Builder](#codeigniters-query-builder)
- [Pagination](#pagination)
- [Count](#count)
- [Automatically Get a Foreign Key Object](#automatically-get-a-foreign-key-object)
- [Define Custom Methods](#define-custom-methods)
 - [Usege of the Defined Custom Methods](#usege-of-the-defined-custom-methods)
- [Support for CodeIgniter Query Builder Caching System](#support-for-codeigniter-query-builder-caching-system)
- [Delete](#delete)
- [Created/Modified Datetime](#createdmodified-datetime)
- [Soft Delete Support](#soft-delete-support)

### Table Schema
The best practice is to name your table in plural form (eg. `cars`), and name the primary key column as `id`.<br>
There's no particular preferences for other column names.<br>
Mind that you can still use other naming conventions for your tables. In this case, it's necessary to set this names by manually set the value of `table`, `id_field` and `row_type` properties in the constructor of the entity model definition (see next paragraph).

### Define a New Entity Object
In the *Models* folder, create a new *Cars_model.php* file defining the `Cars_model` extending `MY_Model` class (use the plural for the entire table) and the `Car_object` extending `Model_object` class (use the singular for the single record).

Here the code in *Models/Cars_model.php*:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cars_model extends MY_Model {
	
	public function __construct()
	{
		// If you use standard naming convention, this code can be omitted.
		/*$this->table = 'cars';
		$this->id_field = 'id';
		$this->row_type = 'Car_object';*/
		parent::__construct();
	}
}

class Car_object extends Model_object {
	
}
```

### Load a Model
```php
$this->load->model('Cars');
// or autoload like this: $autoload['model'] = array('Cars');
```

### Get All Records
```php
$all_cars = $this->Cars->get_list();
```

### Get Some Records
```php
// Get some records (apply a filter in query)
$some_cars = $this->Cars->where('brand_id', 1)->get_list();
```

### Get a Single Record
```php
$a_car = $this->Cars->get(1);	// this is a "get by id"
```

### Add a New Record
```php
$new_car = $this->Cars->new_row();
$new_car->name = "Powerful Car";
$new_car->brand_id = 1;
$id = $new_car->save();	// this produces the insert command
```

### Make Some Changes
```php
$edit_car = $this->Cars->get($id);
$edit_car->name = "Change its name";
$edit_car->save();		// this produces the update command (only for the changed fields, the CI Powerful Model tracks object changes)
```

### Automatic Join
```php
$all_cars = $this->Cars->autojoin()->get_list();	// automatic LEFT join with the brands table
// uses the CI's inflector helper to transform <entity>_id to <entities>
```

### Manual Join
```php
$this->Cars->join('brands', 'cars.brand_id = brands.id', 'LEFT');
$all_cars = $this->Cars->get_list();
```

### CodeIgniter's Query Builder
You can use all the <a href="http://www.codeigniter.com/user_guide/database/query_builder.html" target="_blank">CodeIgniter's Query Builder</a> methods, allowing method chaining.
```php
$some_cars = $this->Cars->where_in('brand_id', array(1, 2, 3))->like('name', "%Something")->order_by('name')->get_list();
```
### Pagination 
```php
// (page 1, with 10 cars per page)
$cars_page1 = $this->Cars->pagination(1, 10)->order_by('name')->get_list();
```
### Count
```php
$brand1_cars_count = $this->Cars->where('brand_id', 1)->count();
```
### Automatically Get a Foreign Key Object
```php

$this->load->model('Brands');	// just another CI Powerful Model object
$car = $this->Cars->get(1);
$brand = $car->get_brand();	// cars.brand_id => brands.id
// uses the CI inflector's helper to transform <entity>_id to <entities>.id
```

###  Define Custom Methods
Here the code in *Models/Cars_model.php*:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cars_model extends MY_Model {
	
	public function has_brand()
	{
		$this->db->where('brand_id >', 0);
		
		return $this;	// remember to return $this for method chaining support
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
#### Usege of the Defined Custom Methods
```php
$cars = $this->Cars->has_brand()->get_list();
foreach ($cars as $car)
{
	echo 'Car: '.$car->name.', Brand: '.$car->brand_name().'<br>';
}
```

### Support for CodeIgniter Query Builder Caching System
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
$to_delete = $this->Cars->get($id);
$to_delete->delete();
```

### Created/Modified Datetime
If you add a `created` (datetime) and a `modified` (datetime) field in your table, CI Powerful Model automatically write the creation date and the last change date

### Soft Delete Support
If you add a `deleted` (datetime) field in your table, the `delete` function doesn't hard delete the record, but writes the delete datetime in this field.<br>
In this case, to filter your queries excluding the logical deleted records, call the `all` method before. Example:
```php
$all_cars = $this->Cars->all()->get_list();
$some_cars = $this->Cars->all()->where_in('brand_id', array(1, 2, 3))->get_list();
```
