# console tool 

a php console application library.

- console application, run command/controller
- console input/output
- console color support
- console interactive

## usage

```php
use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\App;

$config = [];
$input = new Input;
$output = new Output;
$app = new App($config, $input, $output);

// add command routes
$app->command('demo', function (Input $in, Output $out) {
    $cmd = $in->getCommand();

    $out->info('hello, this is a test command: ' . $cmd);
});

// run
$app->run();
```

## input

example(in terminal):

```bash
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
  'name' => string(4) "john"
  [0] => string(4) "arg0"
}
```

get parsed options:

```php
var_dump($input->getOpts());
```

output:

```php
array(10) {          
  's' => string(4) "test"   
  'e' => string(3) "dev"    
  'v' => string(3) "vvv"    
  'd' => bool(true)         
  'r' => bool(true)         
  'f' => bool(true)         
  'page' => string(2) "23"     
  'id' =>   string(3) "154"    
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

```php
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

![alt text](images/output-color-text.png "Title")

#### special format output

- `$output->title()`
- `$output->section()`
- `$output->panel()`
- `$output->table()`
- `$output->helpPanel()`

![alt text](images/output-format-msg.png "Title")

## more interactive

in the class `inhere\console\utils\Interact`

interactive method:

### `Interact::select()` (alias `Interact::chioce()`)

Select one of the options

```php
select($description, $options, $default = null, $allowExit=true)
choice($description, $options, $default = null, $allowExit=true)
```

- example 1:

 only values, no setting option

```php
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

```php
echo $select; // '0'
```

- example 2:

custom option, setting a default value.

```php
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

```php
echo $select; // 'b'
```

### `Interact::confirm()`

```php
public static function confirm($question, $default = true) bool
```

usage:


```php
$result = Interact::confirm('Whether you want to continue ?');
```

output in terminal:

```
Whether you want to continue ?
Please confirm (yes|no) [default:yes]: n
```

result: 

```php
var_dump($result); // bool(false)
```


### `Interact::question()`/`Interact::ask()`

```php
public static function ask($question, $default = null, \Closure $validator = null)
public static function question($question, $default = null, \Closure $validator = null)
```

```php
 $answer = Interact::ask('Please input your name?', null, function ($answer) {
     if ( !preg_match('/\w+/', $answer) ) {
         Interact::error('The name must match "/\w+/"');

         return false;
     }

     return true;
  });
```
## License

MIT
