<?php
/**
 * Expanded Search plugin for Craft CMS 3.x
 *
 * An expansion of Crafts search
 *
 * @link      mustasj.no
 * @copyright Copyright (c) 2019 Mustasj
 */

namespace mustasj\expandedsearch\variables;

use mustasj\expandedsearch\ExpandedSearch;

use Craft;

/**
 * Expanded Search Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.expandedSearch }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Mustasj
 * @package   ExpandedSearch
 * @since     0.0.1
 */
class ExpandedSearchVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.expandedSearch.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.expandedSearch.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function search($term)
    {
        return ExpandedSearch::$plugin->expandedSearchService->search($term);
    }
}
