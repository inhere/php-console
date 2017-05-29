# console tool 

a php console application library.

- console application, run command/controller
- console input/output
- console color support
- console interactive

## usage

```
use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\App;

$input = new Input;
$output = new Output;
$app = new App([], $input, $output);
```

## input

example(in terminal):

```
$ examples/app home/useArg status=2 name=john arg0 -s=test --page=23 --id=23 --id=154 -e dev -v vvv -d -rf --debug --test=false
```

**NOTICE:**

- These words will be as a Boolean(`true`) value: `on|yes|true` 
- These words will be as a Boolean(`false`) value: `off|no|false` 

get command info:

```
echo $input->getScript();   // 'examples/app'
echo $input->getCommand(); // 'home/useArg'
```

get parsed arguments:

```php
var_dump($input->getArgs());
```

output:

```php
array(3) {            
  'status' => string(1) "2"       
  'name' =>  array(3) {          
    [0] => string(4) "john"  
    [1] => string(3) "tom"   
    [2] => string(4) "jack"  
  }                   
  [0] => string(4) "arg0"    
}                     
```

get parsed options:

```php
var_dump($input->getOpts());
```

output:

```php
array(8) {                  
  's' => string(4) "test"   
  'page' => string(2) "23"  
  'id' => array(3) {        
    [0] => string(2) "23"   
    [1] => string(3) "154"  
    [2] => string(3) "456"  
  }                         
  'd' => bool(true)         
  'r' => bool(true)         
  'f' => bool(true)         
  'debug' => bool(true)     
  'test' => bool(false)     
}                           
```

more method:

```php

// argument
$first = $input->getFirstArg(); // 'arg0'
$status = $input->get('status', 'default'); // '2'

// option
$page = $input->getOpt('page') // '23'
$debug = $input->boolOpt('debug') // True
$test = $input->boolOpt('test') // False
```

### get user input:

```
echo "Your name:";

$text = $input->read(); 
// in terminal
// Your name: simon

echo $text; // 'simon'
```

## output

basic output:

```
$output->write($message);
```

### formatted output

#### use color style 

![alt text](images/output-color-text.jpg "Title")

#### special format  

- `$output->title()`
- `$output->section()`
- `$output->panel()`
- `$output->table()`
- `$output->helpPanel()`

![alt text](images/output-panel-table-title.jpg "Title")

## more interactive

in the class `inhere\console\utils\Interact`

interactive method:

### `Interact::select()` (alias `Interact::chioce()`)

Select one of the options

```
select($description, $options, $default = null, $allowExit=true)
choice($description, $options, $default = null, $allowExit=true)
```

- example 1:

 only values, no setting option

```
$select = Interact::select('Your city is ?', [
    'chengdu', 'beijing', 'shanghai'
]);

```

output in terminal:
```
Your city is ? 
  0) chengdu
  1) beijing
  2) shanghai
  q) Quit // quit option. is auto add. can setting it by 4th argument.
You choice: 0
```

```
echo $select; // '0'
```

- example 2:

custom option, setting a default value.

```
$select = Interact::select('Your city is ?', [
    'a' => 'chengdu',
    'b' => 'beijing',
    'c' => 'shanghai'
], 'a');
```

output in terminal:

```
Your city is? 
  a) chengdu
  b) beijing
  c) shanghai
  q) Quit // quit option. is auto add. can setting it by 4th argument.
You choice[default:a] : b
```

```
echo $select; // 'b'
```

### `Interact::confirm()`

```
confirm(string $question, bool $default = true) bool
```

usage:


```
$result = Interact::confirm('Whether you want to continue ?');

```

output in terminal:

```
Whether you want to continue ?
Please confirm (yes|no) [default:yes]: n
```

result: 

```
var_dump($result); // bool(false)
```


### `Interact::question()`

### `Interact::loopAsk()`

```

```

## License

MIT
