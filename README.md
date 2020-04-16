# Expanded Search plugin for Craft CMS 3.x

Ported https://github.com/composedcreative/craft-expandedsearch from Craft 2 to Craft 3.
Is is an expansion of Crafts search, which gives you a context for search hits.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.1.0 or later.

## Installation

To install the plugin, follow these instructions.

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require mustasj/expanded-search

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for Expanded Search.

## Using Expanded Search

The first parameter is the search term. Which will be salted automatically: `*{term}*`
The second is settings.

| Setting   | Type         | Purpose                                   | Default             |
| --------- | ------------ | ----------------------------------------- | ------------------- |
| length    | int          | Cuts off the search value at given length | 300                 |
| section   | array/string | section names to search in                | null (all sections) |
| sectionId | array/int    | id of sections to search in               | null (all sections) |
| limit     | int          | how many results to return (pagination)   | 0 (all)             |
| offset    | int          | how many results to skip (pagination)     | 0                   |
| subLeft   | bool         | to use fuzzy search left                  | true                |
| subRight  | bool         | to use fuzzy search right                 | true                |

In your search results template

```
{% set expandedResults = craft.expandedSearch.search(query) %}
{% set expandedResults = craft.expandedSearch.search(query, { sections: ['news'], length: 150 }) %}
{% for result in expandedResults %}
    <strong data-field="{{result.matchedField}}">{{result.entry.title}}</strong><br>
    <p>{{result.matchedValue}}</p>
    <a href="{{result.entry.url}}">{{result.entry.url}}</a>
{% else %}
    <p>Sorry, no results for {{query}}.</p>
{% endfor %}
```

## Expanded Search from Element API

To use the plugin from ElementAPI. Do a normal search and then for each result, you can fetch the `ExpandedSearchModel` from the service

```
'transformer' => function(Entry $entry) {
    $searchResults = ExpandedSearch::$plugin->expandedSearchService->expandSearchResults($entry, $query, $length);
    return [
        'id' => $entry->title,
        'title' => $entry->title,
        'matchedValue' => $searchResult->matchedValue,
        'matchedField' => $searchResult->matchedField
    ];
},
```

## Expanded Search Roadmap

Some things to do, and ideas for potential features:

-   [x] ~~Release it~~
-   [x] ~~Add proper pagination within the plugin~~
-   [ ] Add handling for more fields
