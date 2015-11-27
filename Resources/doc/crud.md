# CRUD Actions

> To proceed with steps bellow it is necessary to read the [mapping](mapping.md) topic and have defined documents in the bundle.

For all steps below we assume that there is an `AppBundle` with the `Product` document and that you have an index manager defined to manage that document.

```php

<?php
//AcmeDemoBundle:Content
use Sineflow\ElasticsearchBundle\Annotation as ES;
use Sineflow\ElasticsearchBundle\Document\AbstractDocument;

/**
 * @ES\Document(type="content")
 */
class Content extends AbstractDocument
{
    /**
     * @ES\Property(type="string", name="title")
     */
    public $title;
}

```

```
# config.yml
sineflow_elasticsearch:
    indices:
        ...
        products:
            extends: @base
            name: acme_products
            types:
                - AppBundle:Product
```

## Index manager

In order to work with an index, you will need the respective index manager service.
Once you define managers in your `config.yml` file, you can use them in controllers and grab them from DI container via `sfes.index.<name>`. With the above example:

```php
$im = $this->get('sfes.index.product');
```

## Repositories

When you need to work with a specific type, you can do so through the Repository class of an entity. You can get the repository through the index manager that manages it:

```php
$repo = $im->getRepository('AppBundle:Product');
```

## Create a document

```php
$product = new Product();
$product->id = 5; // If not set, elasticsearch will set a random unique id.
$product->title = 'Acme title';
$repo->persist($product);
```

or

```php
$product = [
    '_id' => 5,
    'title' => 'Acme title'
];
$repo->persistRaw($product);
```

> **id** is a special field that comes from `AbstractDocument` and translates to **\_id** in Elasticsearch, just like **parent**, which translates to **\_parent**.

## Update a document

```php
$product = $repo->getById(5);
$product->title = 'changed Acme title';
$repo->persist($product);
```

## Delete a document

```php
$repo->delete(5);
```

## Reindex a document

You can refresh the content of a document from its registered data provider.
```php
$repo->reindex(5);
```
For more information about that, see [data providers](dataproviders.md).

## Bulk operations

It is important to note that all of the above examples for create, update, delete or reindex will only work, if the connection's autocommit mode is **on**, as it is by default.

Sometimes, however, you may want to execute several operations at once to reduce round trips to the Elasticsearch cluster. In that case you may turn off the autocommit mode:
```php
$im->getConnection()->setAutocommit(false);
```
When you do that, all of the above operations will just add an item to a bulk request, which you must then execute manually like that:
```php
$im->getConnection()->commit();
```
> Note that turning on the autocommit mode of the connection will also commit any pending operations.