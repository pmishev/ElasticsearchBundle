# Mapping

The Elasticsearch bundle requires document mapping definitions to create the correct index schema and be able to convert data to objects and vice versa - think Doctrine. 

## Document class annotations

Elasticsearch type mappings are defined using annotations within document entity classes that implement DocumentInterface:
```php
<?php
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


#### Document annotation

The class representing a document must be annotated as `@ES\Document`. The following properties are supported inside that annotation:

- `type` Specifies the name of the Elasticsearch type this class represents. The parameter is optional and, if not supplied, the bundle will use the lowercased class name as such. 

- `repositoryClass` Allows you to specify a specific repository class for this document. If not specified, the default repository class is used.
> EXAMPLE: `repositoryClass="AppBundle\Document\Repository\ProductRepository"`

- `parent` Allows you to specify a parent type ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-parent-field.html))
> EXAMPLE: [TODO]

- `all` Set the _all field ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-all-field.html)) 
> EXAMPLE: `all={"enabled":true}`

- `dynamicTemplates` Set dynamic_templates ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html))

- `dynamicDateFormats` Set dynamic_date_formats ([more info here](https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-field-mapping.html#date-detection))


### Properties annotations

Each field within the document is specified using the `@ES\Property` annotation. The following properties are supported inside that annotation:

- `name` Specifies the name of the field (required).

- `type` Specifies the type of the field in Elasticsearch (required).

- `multilanguage` A flag that specifies whether the field will be multilanguage. For more information, see [multilanguage support](i18n.md).
> EXAMPLE: `multilanguage=true`

- `objectName` When the field type is `object` or `nested`, this property must be specified, as it says which class defines the (nested) object. For more information, see [mapping of nested/inner objects](objects.md).
> EXAMPLE: `objectName="AppBundle:ObjAlias"`

- `multiple` Relevant only for `object` and `nested` fields. It specifies whether the field contains a single object or multiple ones.
> EXAMPLE: `multiple=true`

- `options` An array of literal options, sent to Elasticsearch as they are. The only exception is with multilanguage properties, where further processing is applied. 
> EXAMPLE: `options={"analyzer":"my_special_analyzer", "null_value":0}`

## Object class annotations

Object classes are almost the same as document classes:

```php
<?php
namespace AppBundle\Document;

use Sineflow\ElasticsearchBundle\Document\ObjectInterface;
use Sineflow\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Object
 */
class ObjAlias implements ObjectInterface
{
    /**
     * @var string
     *
     * @ES\Property(name="title", type="string")
     */
    public $title;
}
```

The difference with document classes is that the class must implement `ObjectInterface` and be annotated as `@ES\Object`. The mapping of the object properties follows the same rules as the one for the document properties.

More info about mapping is in the [elasticsearch mapping documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html)
