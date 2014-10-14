Settings Loader
===============

The settings loader is a library that loads settings from several sources (currently only XML is supported).

XML Loader
----------

This loader loads the settings from an XML file with a given structure:

``` xml
<!-- settings.xml -->

<?xml version="1.0" encoding="UTF-8"?>
<s:abstract xmlns="http://github.com/superruzafa/settings-loader">

	<country>Japan</country>

	<s:settings>
    	<company>Nintendo</company>
    </s:settings>

	<s:settings>
    	<company>Sony</company>
    </s:settings>

</s:abstract>
```

``` php
<?php

use \DomDocument;
use Superruzafa\Settings\Loader\XmlLoader;

$doc = new DomDocument();
$doc->load(__DIR__ . '/settings.xml');
$loader = new XmlLoader($doc);
$loader->load();
$settings = $loader->getSettings();

// $settings = array(
//   array(
//     'country' => 'Japan',
//     'company' => 'Nintendo',
//   ),
//   array(
//     'country' => 'Japan',
//     'company' => 'Sony',
//   )
// )

```

In essence you can create your own XML settings file following these steps:

* Define in your XML a namespace pointing to ```http://github.com/superruzafa/settings-loader```
* Use the two reserved tags in that namespace for define _settings entries_: ```<abstract>``` and ```<settings>```
* The namespaceless tags will be used as key-value pairs and will build the _settings entries_.

### ```<abstract>``` vs. ```<settings>```

Both ```<abstract>``` and ```<settings>``` tags define a context (or change the previous one).
However, the ```<settings>``` takes the current context and creates a _settings entry_ in the global settings list.

Summarizing, you should use ```<abstract>``` when you want to define a global context that would be overrided by concrete context using ```<settings>```.

### Inheritance

Both ```<abstract>``` and ```<settings>``` nodes inherit the values defined by its ancestors and could be combined for create large collections of settings easily.
These tags could be nested:

``` xml

<s:settings>
    <s:abstract>
        <s:settings>
            <s:settings>
                ...
            </s:settings>
        </s:settings>
    
        <s:settings>
            <s:abstract>
                ...
            </s:abstract>
        </s:settings>
    <s:abstract>

    <s:settings>
        ...
    </s:settings>
</s:settings>
```

### Elements vs. attributes

An ```<abstract>``` or a ```<setting>``` node are allowed to define their context using both elements and attributes. These two examples would create the same settings:

``` xml
<s:settings>
  <language>PHP</language>
  <purpose>Web and more</purpose>
</s:settings>
```

``` xml
<s:settings language="PHP" purpose="Web and more" />
```

``` xml
<s:settings language="PHP">
  <language>PHP</language>
</s:settings>
```

You can use the method that better fits your needs.

### Arrays

When a key appears twice or more within the same context then the values for that key are interpreted as an array, instead of preserving the last defined value:

``` xml
<s:settings>
	<colors>red</colors>
	<colors>green</colors>
	<colors>blue</colors>
</s:settings>
```

``` php
// array(
//   array('colors' => array('red', 'green', 'blue'))
// )
```

When inheriting, the child settings overrides its parent:

Reason: otherwise settings nodes having keys that already are defined by its parent would always append its value to the one from its parent, creating an array.

``` xml
<s:settings>
	<colors>black</colors>
	<colors>white</colors>
    
	<s:settings>
		<colors>red</colors>
		<colors>green</colors>
		<colors>blue</colors>
	</s:settings>
    
	<s:settings>
		<colors>transparent</colors>
	</s:settings>
      
<s:settings>	
```

``` php
// array(
//   array('colors' => array('black', 'white')),
//   array('colors' => array('red', 'green', 'blue'))
//   array('colors' => 'transparent')
// )
```

### String interpolation

String values could be considered as templates.

When an string contains something like ```{{ username }}``` the parser looks in the current context the value associated to the key "username" and makes a replacement.

``` xml
<s:settings>
	<language>PHP</language>
	<string>I like {{ language }}</string>
</s:settings>
```

``` php
// array(
//   'language' => 'PHP',
//   'string' => 'I like PHP',
// )
```

You can chain even more complicated interpolations and hierarchies:

``` xml
<s:abstract>
	<s:settings who="I">
		<language>PHP</language>
		<string>{{ who }} {{ preference }} {{ language }} {{ how-many }}</string>
        <preference>like</preference>
	</s:settings>
    
	<how-many>so much!<how-many>
    <preference>love</preference>
    
</s:abstract>
```

``` php
// array(
//   'who' => 'I',
//   'language' => 'PHP',
//   'string' => 'I like PHP so much!',
//   'preference' => 'like',
// )
```

#### Caveats

Non-existing keys are replaced by an empty string, generating a warning.
``` xml
<s:settings>
   	<string>My name is {{ name }}</key1>
</s:setting>
```
	
``` php
// array(
//   array (
//     'string' => 'My name is ',
//   )
// )
```

Cyclic recursive resolution will end with an empty string, generating a warning:

``` xml
<s:settings>
   	<key1>Need {{ key2 }}</key1>
   	<key2>Need {{ key3 }}</key2>
   	<key3>Need {{ key1 }}</key3>
</s:setting>
```
	
``` php
// array(
//   array (
//     'key1' => 'Need Need Need ',
//     'key2' => 'Need Need ',
//     'key3' => 'Need ',
//   )
// )
```

Array interpolations are replaced by ```"<array>"``` and a warning is generated:

``` xml
<s:settings>
   	<seasons>Spring</seasons>
   	<seasons>Summer</seasons>
   	<seasons>Autumn</seasons>
   	<seasons>Winter</seasons>
    <year>A year is composed by {{ seasons }}</year>
</s:setting>
```
	
``` php
// array(
//   array (
//     'seasons' => array('Spring','Summer','Autumn','Winter'),
//     'year' => 'A year is composed by <array>',
//   )
// )
```

