# CodeIgniter Powerful Model
A CodeIgniter extension to work (or better, to play!) with database tables with an easy and intuitive Object Oriented / Entity Framework approach.

## Installation
Download the files from this repository and place them into the corresponding folders in your CodeIgniter project.
The extension also redefine the CI_Loader::model method. If you already have a MY_Loader extension, just copy the MY_Loader::model extended method within your extended class.

## Usage
Let's make examples with a classic *cars* table.
- [Define a New Entity Object](#define-a-new-entity-object)
- [Load a Model](#load-a-model)

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
