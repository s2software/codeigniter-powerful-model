# CodeIgniter Powerful Model
A CodeIgniter extension to work (or better, to play!) with database tables with an easy and intuitive Object Oriented / Entity Framework approach.

## Installation
Download the files from this repository and place them into the corresponding folders in your CodeIgniter project.
The extension also redefine the CI_Loader::model method. If you already have a MY_Loader extension, just copy the MY_Loader::model extended method within your extended class.

## Usage
Let's make examples with a classic *cars* table.
- [Define a New Entity Object](#define-a-new-entity-object)

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

### Load a 
