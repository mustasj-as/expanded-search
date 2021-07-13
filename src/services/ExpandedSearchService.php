<?php
/**
* Expanded Search plugin for Craft CMS 3.x
*
* An expansion of Crafts search
*
* @link      mustasj.no
* @copyright Copyright (c) 2019 Mustasj
*/

namespace mustasj\expandedsearch\services;

use mustasj\expandedsearch\ExpandedSearch;
use mustasj\expandedsearch\models\ExpandedSearchModel;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\elements\Entry;

/**
* ExpandedSearchService Service
*
* All of your plugin’s business logic should go in services, including saving data,
* retrieving data, etc. They provide APIs that your controllers, template variables,
* and other plugins can interact with.
*
* https://craftcms.com/docs/plugins/services
*
* @author    Mustasj
* @package   ExpandedSearch
* @since     0.0.1
*/
class ExpandedSearchService extends Component
{
	// Public Methods
	// =========================================================================

	/**
	* This function can literally be anything you want, and you can have as many service
	* functions as you want
	*
	* From any other plugin file, call it like this:
	*
	*     ExpandedSearch::$plugin->expandedSearchService->exampleService()
	*
	* @return mixed
	*/
	public function exampleService()
	{
		$result = 'something';

		return $result;
	}

	/**
	* Searches entries for the given term
	*
	* @param string $term
	* @return array the search results
	*/
	public function search($term, $settings)
	{
		$default = [
			'sections' => null,
			'sectionId' => null,
			'length' => 300,
			'limit' => 0,
			'offset' => 0,
			'subLeft' => true,
			'subRight' => true
		];
		$settings = (object)array_merge($default, $settings);
		
		$query = $term;
		if ($settings->subLeft) {
			$query = '*' . $query;
		}
		if ($settings->subRight) {
			$query = $query . '*';
		}
		
		$entries = Entry::find()
			->search($query)
			->orderBy('score');

		if ($settings->sections) {
            $entries->section($settings->sections);
        }
		if ($settings->sectionId) {
            $entries->sectionId($settings->sectionId);
        }

		if ($settings->offset > 0) {
			$entries = $entries->offset($settings->offset);
		}
		if ($settings->limit > 0) {
			$entries = $entries->limit($settings->limit);
		}
		$results = [];
		foreach ($entries->all() as $entry) {
			$results[] = $this->expandSearchResults($entry, $term, $settings->length);
		}
		return $results;
	}

	/**
	* Sets up a array of ExpandedSearchModels with highlights
	*
	* @param array $entries
	* @param string $term
	* @param object $settings
	* @return array the ExpandedSearchModel object
	*/
	public function expandSearchResults($entry, $term, $length = 300)
	{
		//dump($entry->title);
		$result = new ExpandedSearchModel();
		$result->entry = $entry;
		list ($result->matchedField, $result->matchedValue, $result->relatedValues) = $this->findMatchesInFieldSet($entry, $term, $length);
		return $result;
	}

	/**
	* Converts an Element into a kvp array of its fields
	*
	* @param Craft\BaseElementModel $element
	* @return array
	*/
	protected function getFieldSetValues(Element $element)
	{
		$values = [];
		foreach ($element->getFieldLayout()->getFields() as $fieldLayoutField) {
			$fieldHandle = Craft::$app->getFields()->getFieldById($fieldLayoutField->id)->handle;
			$fieldContents = $element->getFieldValue($fieldHandle);
			$values[$fieldHandle] = $fieldContents;
		}
		return $values;
	}

	/**
	* Gets a normalized representation of the given value
	*
	* @param mixed $value
	* @return scalar
	*/
	protected function getNormalizedValue($value)
	{
		if (is_object($value) && $value instanceof \Craft\ElementCriteriaModel && $value->getElementType() instanceof \Craft\AssetElementType) {
			$assetUrls = [];
			foreach ($value as $asset) {
				$assetUrls[] = $asset->getUrl();
			}
			return 1 == count($assetUrls) ? array_shift($assetUrls) : $assetUrls;
		} elseif (is_object($value)) {
			return get_class($value);
		}
		return $value;
	}

	/**
	* Cleans up the value and adds bold tag to search term
	* Also shortens the value if needed
	*
	* @param mixed $value
	* @return scalar
	*/
	protected function contextualizeHit($content, $term, $length)
	{
		$content = strip_tags($content);
		$pattern = '/('. $term . ')/im';
		$midway = round($length / 2);
		if (strlen($content) > $length) {
			// if the hit is after the middle, we need to shorten the text on both sides
			$strpos = stripos($content, $term);
			if ($strpos > $midway) {
				$content = '...' . mb_substr($content, $strpos - $midway);
			}
			if (strlen($content) > $length) {
				$content = mb_substr($content, 0, $length) . '...';
			}
		}
		return preg_replace($pattern, '<b>${1}</b>', $content);
	}

	/**
	* Finds matches in an element's field values
	*
	* @param Craft\BaseElementModel $element
	* @param string $term the search term
	* @return array indexed array consisting of
	*                - The field handle
	*                - The matched value
	*                - Associative array of related values (handle => value)
	*/
	protected function findMatchesInFieldSet(Element $element, $term, $length)
	{
		foreach ($this->getFieldSetValues($element) as $fieldHandle => $fieldContents) {
			//dump($fieldContents);
			if (is_scalar($fieldContents))
			{
				if (stripos($fieldContents, (string)$term) !== false) {
					return [$fieldHandle, $this->contextualizeHit($fieldContents, $term, $length), []];
				}
			}
			elseif (is_object($fieldContents) && $fieldContents instanceof \verbb\supertable\elements\db\SuperTableBlockQuery)
			{
				$relatedValues = [];
				$matchedValue = '';
				foreach ($fieldContents->all() as $stBlock) {
					$stMatches = $this->findMatchesInFieldSet($stBlock, $term, $length);
					if (!is_null($stMatches)) {
						$matchedValue = $stMatches[1];
					}
				}
				// TODO: Should we append the matched sub-field handle to the higher-level handle?
				return [$fieldHandle, $matchedValue, $relatedValues];
			}
			elseif (is_object($fieldContents) && $fieldContents instanceof \craft\elements\db\MatrixBlockQuery)
			{
				$relatedValues = [];
				$matchedValue = '';
				foreach ($fieldContents->all() as $matrixBlock) {
					$matrixMatches = $this->findMatchesInFieldSet($matrixBlock, $term, $length);
					if (!is_null($matrixMatches)) {
						$matchedValue = $matrixMatches[1];
						foreach ($this->getFieldSetValues($matrixBlock) as $k => $v) {
							$relatedValues[$k] = $this->getNormalizedValue($v);
						}
					}
				}
				// TODO: Should we append the matched sub-field handle to the higher-level handle?
				return [$fieldHandle, $matchedValue, $relatedValues];
			}
			elseif (is_object($fieldContents) && $fieldContents instanceof \craft\redactor\FieldData)
			{
				if (stripos($fieldContents->getParsedContent(), (string)$term)) {
					return [$fieldHandle, $this->contextualizeHit($fieldContents->getParsedContent(), $term, $length), []];
				}
			}
			else
			{
				// TODO: handle more data types
			}
		}
		return null;
	}
}
