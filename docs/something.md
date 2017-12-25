# some idea

## controller

```php
    protected function commandConfigure($definition)
    {
        // old: own create.
        $this->createDefinition()->addArgument();
        
        // maybe: get by argument.
        $definition->addArgument();
        
        // ....
    }
```

```php
    /**
     * the group controller metadata. to define name, description
     * @return array
     */
    public static function metadata()
    {
        return [
            'name' => 'model',
            'description' => 'some console command handle for model user data.',

            // for command
            'aliases' => [
                'i', 'in',
            ],
            
            // for controller
            'aliases' => [
                'i' => 'install',
                'up' => 'update',
            ]
        ];
    }
```