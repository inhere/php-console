# input and output

## input

## output

### output buffer

how tu use

- use `Output`

```php
    // open buffer
    $this->output->startBuffer();
    
    $this->output->write('message 0');
    $this->output->write('message 1');
    // ....
    $this->output->write('message n');
    
    // stop and output buffer
    $this->output->stopBuffer();
```

- use `Show`

```php
    // open buffer
    Show::startBuffer();
    
    Show::write('message 0');
    Show::write('message 1');
    // ....
    Show::write('message n');
    
    // stop and output buffer
    Show::stopBuffer();
```
