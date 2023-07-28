<?php
namespace Keiwen\Utils\Parsing;

class XmlParser {

    protected $content;
    protected $arrayContent;

    const DEFAULT_ATTRIBUTES_FIELDNAME = '_attributes';

    /**
     * @param string $xmlContent
     */
    public function __construct(string $xmlContent)
    {
        $this->content = trim($xmlContent);
        $this->arrayContent = $this->convertToArray();
    }

    /**
     * @return array
     */
    protected function convertToArray()
    {
        if (!function_exists('xml_parser_create')) {
            return array();
        }
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $this->content, $xmlValues);
        xml_parser_free($parser);
        if (!$xmlValues) return array();

        $xmlArray = array();
        $parents = array();
        $current = &$xmlArray;
        $repeatedTagIndex = array();
        foreach ($xmlValues as $data) {
            $tag = $data['tag'] ?? '';
            $level = $data['level'] ?? 0;
            $attributes = $data['attributes'] ?? array();
            $value = $data['value'] ?? null;
            $result = array();
            if ($value !== null) {
                $result['value'] = $value;
            }
            if (!empty($attributes)) {
                foreach ($attributes as $attr => $val) {
                    //Set all the attributes in a separated array
                    $result[static::DEFAULT_ATTRIBUTES_FIELDNAME][$attr] = $val;
                }
            }
            if ($data['type'] == "open") {
                $parents[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    $repeatedTagIndex[$tag . '_' . $level] = 1;
                    $current = &$current[$tag];
                } else {
                    if (isset ($current[$tag][0])) {
                        $current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;
                        $repeatedTagIndex[$tag . '_' . $level]++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeatedTagIndex[$tag . '_' . $level] = 2;
                    }
                    $lastItemIndex = $repeatedTagIndex[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$lastItemIndex];
                }
            } elseif ($data['type'] == "complete") {
                if (!isset ($current[$tag])) {
                    $current[$tag] = $result;
                    $repeatedTagIndex[$tag . '_' . $level] = 1;
                } else {
                    if (isset ($current[$tag][0]) and is_array($current[$tag])) {
                        $current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;
                        $repeatedTagIndex[$tag . '_' . $level]++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeatedTagIndex[$tag . '_' . $level] = 1;
                        $repeatedTagIndex[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            } elseif ($data['type'] == 'close') {
                $current = &$parents[$level - 1];
            }
        }
        return $xmlArray;
    }

    /**
     * @param string|null $attributesField XML tag attributes will be grouped in field of the specified name. If not provided, attributes will not be retrieved
     * @return array
     */
    public function toArray($attributesField = null)
    {
        $data = $this->arrayContent;
        $this->renameAttributesField($data, $attributesField);
        return $data;
    }

    /**
     * RECURSIVE rename field in current element, then deep in child element to continue
     * @param array|mixed $data
     * @param string|null $attributesField
     * @return void
     */
    protected function renameAttributesField(&$data, $attributesField = null)
    {
        if (!is_array($data)) return;
        // if not specified, use default fieldname. If empty, we will remove attributes
        if ($attributesField === null) $attributesField = static::DEFAULT_ATTRIBUTES_FIELDNAME;
        // loop on all key/value of array
        foreach($data as $key => &$childData) {
            if ($key === '_attributes') {
                // this is the attributes data
                if ($attributesField) {
                    // if we have a different attribute field name, change for it
                    if ($attributesField !== '_attributes') {
                        $data[$attributesField] = $data['_attributes'];
                        unset($data['_attributes']);
                    }
                } else {
                    // remove attributes as they are not required
                    unset($data['_attributes']);
                }
            } else {
                // if we are not on attributes data, apply this method again for recursive
                $this->renameAttributesField($childData, $attributesField);
            }
        }

    }

    /**
     * @param string $tag
     * @param string|null $attributesField XML tag attributes will be grouped in field of the specified name. If not provided, attributes will not be retrieved
     * @return mixed|null
     */
    public function getXMLTag(string $tag, $attributesField = null)
    {
        $tagData = $this->searchForTag($tag, $this->arrayContent);
        $this->renameAttributesField($tagData, $attributesField);
        return $tagData;
    }

    /**
     * RECURSIVE search for tag in current element or deep in child element
     * @param string $tag
     * @param $data
     * @return mixed|null
     */
    protected function searchForTag(string $tag, $data)
    {
        if (!is_array($data)) return null;
        foreach($data as $key => $childData) {
            if ($key === $tag) {
                return $childData;
            } else {
                $childreturn = $this->searchForTag($tag, $childData);
                if ($childreturn !== null) return $childreturn;
            }
        }
        return null;
    }

    /**
     * @param string $tag
     * @return array
     */
    public function getXMLTagAttribute(string $tag)
    {
        $tagData = $this->getXMLTag($tag);
        return $tagData[static::DEFAULT_ATTRIBUTES_FIELDNAME] ?? array();
    }


}
