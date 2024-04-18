# Utils
A bench of utils functions.

All classes here should be designed to be extendable the most as possible. 

*Full doc is planned like hemmmm you know... Later... Sorry!*

## Analyser and Mutator
* Check if array is sequential (numerical keys ordered)
* Check if array has homogeneous elements in it (with inheritance support)
* Flatten array (nested field brought up to first level, concatenating keys)
* Sort array by (sub)field
* Get classes and subdir from namespace

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
* Convert some data from a unit to another
(typically from fahrenheit degree to celsius degree, using kelvin International System unit)

## StringSanitizer
* Filter incoming string

## Grid
* Defines a frame to build a grid composed of box.
Moving from a box in some direction will get the neighbor box.
* Includes tetragon (rectangular) format, with diagonal option
* Includes hexagon format, vertical or horizontal

## ELO
* Setup ELO system and configuration
* Manage duels
* Extends to multiplayer: races and brawls

## Competition
* Setup players in various types of games within a competition
* Includes games type: duel, race, brawl, performance
* Includes classic competition type (round robin, race championship, tournament, swiss round, ...)
* and more exotic ones (gauntlet tournamenet, bubble championship, ...)
* Support teams rankings and ELO
