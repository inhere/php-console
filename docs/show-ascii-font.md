# show cli font

```php

  $name = '404';
  ArtFont::create()->show($name, ArtFont::INTERNAL_GROUP,[
      'type' => $this->input->getBoolOpt('italic') ? 'italic' : '',
      'style' => $this->input->getOpt('style'),
  ]);

```
