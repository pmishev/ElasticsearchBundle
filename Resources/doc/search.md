# Searching

## Get a document by id

```php
$repo = $this->get('sfes.index.product')->getRepository('AppBundle:Product');
$product = $repo->getById(5); // 5 is the _id of the document in Elasticsearch
```
> The result is returned as an instance of the Product class. An optional second parameter of getByID() allows you to specify different format of the result. See [results types](#resulttypes) below for more information.

## Find documents using a search query

```php
$repo = $this->get('sfes.index.product')->getRepository('AppBundle:Product');
$searchBody = [
    'query' => [
        'match_all' => []
    ]
];
$products = $repo->find($searchBody);
```
> The result by default is a DocumentIterator object. See [results types](#resulttypes) below for the different result types.

You can specify additional options as well:

```php
$products = $repo->find($searchBody, $resultsType, $additionalRequestParams, $totalHits);
echo $totalHits; // This contains the total hits returned by the search
```

## Getting the count of matching documents

If you want to get the number of documents that match a query, but don't want to get the results themselves, you can do so like this:

```php
$productsCount = $repo->count($searchBody);
```

## Searching in multiple types and indices

It is convenient to search in a single type as shown above, but sometime you may wish to search in multiple indices and/or types. The finder service comes in play:

```php
$finder = $this->get('sfes.finder');
$searchBody = [
    'query' => [
        'match_all' => []
    ]
];
$finder->find(['AppBundle:Product', 'AppBundle:Deals'], $searchBody);
```
> You may specify the same options as when using Repository::find(), except you need to specify all entities to search in as the first parameter.


## <a name=resulttypes></a>Result types

| Argument               | Result                                                                              |
|------------------------|-------------------------------------------------------------------------------------|
| Finder::RESULTS_RAW    | Returns raw output as it comes from the elasticsearch client                        |
| Finder::RESULTS_ARRAY  | An array of results with structure that matches a document                          |
| Finder::RESULTS_OBJECT | `DocumentIterator` or an object of the wanted entity when a single result is wanted |