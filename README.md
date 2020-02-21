# atk4m2m
Provides a PHP trait to atk4/data models to enable many-to-many relationship building.
## Features
- Easy and fluent declarations to define many-to-many relationships.
- Automatically creates getter and setter functions for the target model.
## Installation
`composer require sirjasongo/atk4m2m`
## Brief Overview of Many-to-many Relationships
Assuming you want to create many-to-many relationships between Student model and Teacher model.

In a many-to-many relationships, a bridge table is needed to reference the links between a student and the teachers it has, and vice versa.

For example, Student #3 have 4 teachers, Teachers #1, #2, #5, and #9. You can’t directly place the teachers’ ID to the students table because that will make too many redundant row entries.

You may just place a JSON string or serialize PHP array of the teachers ID in a column “teachers” but this will not be searchable in SQL.

Therefore a bridge table is needed. Something like this is possible for the relationships above:
```sql
student_teacher table
student_id	|	teacher_id
3		|		1
3		|		2
3		|		5
3		|		9
5		|		9
5		|		9
```
As you can see, students may have many teachers and teachers may have many students as well. Overlaps are possible with many-to-many relationships.
## Usage of atk4m2m
Atk4m2m provides helper functions that makes it easy to define many-to-many relationship. At the heart of the trait are functions hasManyToMany() and addBridgeBetween(). But first we discuss how to create ATK4 models suitable for our trait.

First we create a Student class just like how we do with ATK4 Models.

```php
class Student extends \atk4\data\Model
{
    use \sirjasongo\atk4m2m\ManyToMany;
    
    public $table = 'students';
    const our_field = 'id';
    const their_field = 'student_id';

    public function init()
    {
        parent::init();

        $this->addField('name', ['required' => true, 'type' => 'string']);
        $this->hasManyToMany(Teacher::class, Student_Teacher::class, 'name');
    }
}

class Teacher extends \atk4\data\Model
{
    use \sirjasongo\atk4m2m\ManyToMany;
    
    public $table = 'teachers';
    const our_field = 'id';
    const their_field = 'teacher_id';

    public function init()
    {
        parent::init();

        $this->addField('name', ['required' => true, 'type' => 'string']);
        $this->addField('email', ['required' => true, 'type' => 'string']);
        $this->hasManyToMany(Student::class, Student_Teacher::class, 'name');
    }
}

// The bridge class
class Student_Teacher extends \atk4\data\Model
{
    use \sirjasongo\atk4m2m\ManyToMany;
    
    public $table = 'student_teacher';

    public function init()
    {
        parent::init();
        $this->addBridgeBetween(Student::class, Teacher::class);
    }
}
```

To use the trait, place this in all the classes and bridge classes that are involved in the many-to-many relationships:
```php
use \sirjasongo\atk4m2m\ManyToMany;
```
Atk4m2m enforces the use of constants our\_field and their\_field to refer to the table fields that will be referenced in the bridge table. The our\_field is the table field that primarily identifies the student while the their\_field is the name of the field in the bridge table that corresponds to the student’s id.

In the usual ATK4 Model, hasMany() defaults to id and table’s name + id as the our\_field and their\_field respectively. Due to the complex declarations needed for many-to-many relationships, using constants within the model ensures clear patterns of referencing across different functions.

## Method $this-\>hasManyToMany()
This method is placed on the first model and the target model in the many-to-many relationships.
```php
$this->hasManyToMany(Teacher::class, Student_Teacher::class, 'name');
```
The first argument is a class name of the target model, in our case it’s the Teacher::class.

The second argument is the bridge class, Student\_Teacher::class. It is customary to name the class and the table similarly, only differing in the case of the first letter (e.g., Student class and student table) but you can use whatever names you want.

The third argument is optional and refers to the name of the field that will be used in model referencing. It defaults to “id” but you can define a different field, in our example it is the teacher’s name that will be referenced.

```php
$students = new Student($db);
$student = $students->load(3);
if ($student->hasTeacher('Hagrid')) {
	echo "Student of Hagrid.\r\n"
	echo $student->Teacher('Hagrid')->Schedule();
}
```

In the example above, the method hasTeacher() is automatically created by calling hasManyToMany() during model creation. The argument accepted by hasTeacher() and Teacher() uses the name of the teacher as a reference instead of the default teacher’s id.
## Method $this-\>addBridgeBetween()
In the bridge class example above, we created the bridge between Student class and Teacher class using the method addBridgeBetween(). You can interchange which class is placed as first and second argument, it does not matter.

This method is mandatory to be called in the bridge class.
## Getters and Setters
In the above example, the following getters and setters will be made:
```php
// Returns an iterable class of all the teachers for the loaded student
foreach ($student->Teacher() as $teacher) {
	echo $teacher['name'] . "\r\n";
}

// Returns a teacher model for Ms. Minchin.
$teacher = $student->Teacher('Ms. Minchin');
echo $teacher['email'];

// Adds a teacher in the student_teacher bridge table.
$student->addTeacher('Rasmus');

// Removes a teacher
$student->removeTeacher('Hagrid');

// Returns a number of student's teacher with John Smith as its name.
echo $student->hasTeacher('John Smith');
// Long method
echo $student->Teacher('John Smith')->action('count')->getOne();
// Longer method
echo $student->ref('Student_Teacher')->ref('Teacher')->action('count')->getOne();

// Any of the teachers in the comma-separated string
echo $student->hasTeacher('John Smith,Elon Musk,Mark Zuck');
// Any of the teachers in the array
echo $student->hasTeacher(['John Smith', 'Elon Musk', 'Mark Zuck']);
```
## TODO
- Use arrays as arguments for getters and setters.