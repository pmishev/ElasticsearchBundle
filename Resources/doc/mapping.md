# Mapping

The Elasticsearch bundle requires document mapping definitions to create the correct index schema and be able to convert data to objects and vice versa - think Doctrine. 

### Document class annotations

Elasticsearch type mappings are defined using annotations within document entity classes:
```php
<?php
// 
namespace AppBundle\Document;

use Sineflow\ElasticsearchBundle\Document\AbstractDocument;
use Sineflow\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Document(type="product")
 */
class Product extends AbstractDocument
{
    /**
     * @var string
     *
     * @ES\Property(name="title", type="string")
     */
    public $title;
}
```

> Make sure your document classes directly implement DocumentInterface or extend AbstractDocument.


#### Document annotation configuration

- `type` Specifies the name of the Elasticsearch type this class represents. The parameter is optional and, if not supplied, the bundle will use the lowercased class name as such. 

- `repositoryClass` Allows you to specify a specific repository class for this document. If not specified, the default repository class is used.
> EXAMPLE: `repositoryClass="AppBundle\Document\Repository\ProductRepository"`

- `parent` Allows you to specify a parent type ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-parent-field.html))
> EXAMPLE: [TODO]

- `all` Allows enabling/disabling of the _all field ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-all-field.html)) 
> EXAMPLE: `all={"enabled":true}`

- `dynamicTemplates` Set dynamic field templates ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html))

- `dynamicDateFormats`

- `timestamp`

##### Abstract document
``AbstractDocument`` implements ``DocumentInterface`` and gives support with all special fields in the elasticsearch document such as `_id`, `_source`, `_ttl`, `_parent`, `_fields` handling. `AbstractDocument` has all parameters and setters already defined for you.


### Document properties annotations

To define type properties there is `@ES\Property` annotation. You can define different class property name as an elasticsearch type's property name and it will be handled automatically by bundle. Property also supports the type where it needs to define what kind of information will be indexed. Analyzers names is the same that was defined in `config.yml` `analysis` section [before](#Mapping configuration).

To add custom settings to property like analyzer it has to be included in `options`. Here's an example how to add it:

```php

<?php
//AcmeDemoBundle:Content
use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractContentDocument;

/**
 * @ES\Document(type="content")
 */
class Content extends AbstractContentDocument
{
    /**
     * @ES\Property(
        type="string",
        name="title",
        options={"index_analyzer":"incrementalAnalyzer"}
      )
     */
    public $title;
}

```

> `options` container accepts any parameters. We leave mapping validation to elasticsearch and elasticsearch-php client, if there will be a mistake index won't be created due exception.


It is a little different to define nested and object types. For this user will need to create a separate class with object annotation. Lets assume we have a Content type with object field.

```php

<?php
//AcmeDemoBundle:Content
use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractContentDocument;

/**
 * @ES\Document(type="content")
 */
class Content extends AbstractContentDocument
{
    /**
     * @ES\Property(type="string", name="title")
     */
    public $title;

    /**
     * @var ContentMetaObject
     *
     * @ES\Property(name="meta", type="object", objectName="AcmeDemoBundle:ContentMetaObject")
     */
    public $metaObject;
}

```

And the content object will look like:

```php

<?php
//AcmeDemoBundle:ContentMetaObject

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Object
 */
class ContentMetaObject
{
    /**
     * @ES\Property(type="string")
     */
    public $key;

    /**
     * @ES\Property(type="string")
     */
    public $value;
}

```

##### Multiple objects
As shown in the example, by default only a single object will be saved in the document. If there is necessary to store a multiple objects (array), add `multiple=true`. While initiating a document with multiple items you can simply set an array or any kind of traversable.

```php

//....
/**
 * @var ContentMetaObject
 *
 * @ES\Property(name="meta", type="object", multiple="true", objectName="AcmeDemoBundle:ContentMetaObject")
 */
public $metaObject;
//....

```

Insert action will look like this:
```php

<?php
$content = new Content();
$content->properties = [new ContentMetaObject(), new ContentMetaObject()];

$manager->persist($content);
$manager->commit();

```
To define object or nested fields the same `@ES\Property` annotations could be used. In the objects there is possibility to define other objects also.

> Nested types can be defined the same way as objects, except ``@ES\Nested`` annotation must be used.

More info about mapping is in the [elasticsearch mapping documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html)
