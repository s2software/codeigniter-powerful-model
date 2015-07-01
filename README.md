# CodeIgniter Powerful Model
A CodeIgniter extension to insert/update/query database tables with an easy and intuitive Object Oriented / Entity Framework approach.

## Installation
Download the files from this repository and place them into the corresponding folders in your CodeIgniter project.
The extension also redefine the CI_Loader::model method. If you already have a MY_Loader extension, just copy the MY_Loader::model extended method within your extended class.

## Usage

### Define a New Entity Object
In the *Models* folder, create a new *Entities*_model.php defining the *Entities*_model extending MY_Model class (use the plural for the entire table) and the *Entity*_object extending Model_object class (use the singular for the single record).

See the example with a *cars* table in a *Cars_model.php* file:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cars_model extends MY_Model {

}

class Car_object extends Model_object {
	
}
```
