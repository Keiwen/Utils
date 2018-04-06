# Utils
A bench of utils functions

## ArrayAnalyser and ArrayMutator
* Check if array is sequential (numerical keys ordered)
* Check if array has homogeneous elements in it (with inheritance support)
* Flatten array (nested field brought up to first level, concatenating keys)
* Sort by field

## JsonObject
* Generate objects from json, with each json field converted to attribute
* JsonObject could include another one (or a list).
* JsonObject could be generated from a parent with additional Json data.
* Functions to sort or search in a list of objects.

## SimpleCurl
* Generate simple curl request, with configurable headers, cookies, curl options

## HtmlParsing
* Isolate target HTML non-empty element by id or class
(if only this class for this element)
* Retrieve list of similar first child tags from HTML source
(typically all `<li>` from `<ul>`)
* Get tag content if not nesting a similar tag.
* Get attribute from isolated html tag

## UnitConverter
Convert some data from a unit to another
(typically from fahrenheit degree to celsius degree, using kelvin International System unit)

## StringSanitizer
Filter incoming string
