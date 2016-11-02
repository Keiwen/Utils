<?php
namespace Keiwen\Utils\Parsing;

class HtmlParsing {


    /**
     * Parse html source to isolate target element, by id or class
     * Must be a single id or class
     * Work only with container tag (with separated open and close tags)
     * @param string $content
     * @param string $idOrClass
     * @param bool $contentOnly return content inside the target tag
     * @param int $classIteration if specified, look for class instead of id. Parse the nth element
     * @return string
     */
    public static function parseHtmlElmt(string $content,
                                         string $idOrClass,
                                         bool $contentOnly = false,
                                         int $classIteration = 0)
    {
        $htmlId = 'id="'.$idOrClass.'"';
        $elmtLimit = 0;
        if(!empty($classIteration)) {
            $htmlId = 'class="'.$idOrClass.'"';
            $elmtLimit = $classIteration - 1;
        }
        $contents = explode(' ' . $htmlId, $content, $elmtLimit + 2);
        if(empty($contents[$elmtLimit + 1])) {
            //id not found
            return '';
        }
        //keep space at the end to be sure to separate from tag
        $contentBefore = $contents[$elmtLimit] . ' ';
        $contentAfter = $contents[$elmtLimit + 1];
        $indexId = strlen($contentBefore);
        $htmlChar = '';
        $indexTag = $indexId - 1;
        //look for previous open tag
        while($htmlChar != '<' && $indexTag >= 0) {
            $indexTag--;
            $htmlChar = substr($contentBefore, $indexTag, 1);
        }
        //remove everything before tag
        $contentBefore = substr($contentBefore, $indexTag);
        //looking for first space after tag to isolate tag name
        $indexChar = strpos($contentBefore, ' ');
        //isolate tag name (after '<' and up to the separating space found)
        $htmlTag = substr($contentBefore, 1, $indexChar - 1);
        //look for first ending
        $nextClose = strpos($contentAfter, '</'.$htmlTag);
        //look for last openning before this close
        $nextOpen = strrpos($contentAfter, '<'.$htmlTag, $nextClose - strlen($contentAfter) - 2);
        if(!empty($nextOpen) && $nextOpen < $nextClose) {
            //similar tag opened before our end, loop to find other occurence
            //add a limit to avoid infinite loop
            $limit = 100;
            while(!empty($nextOpen) && $nextOpen < $nextClose && $limit > 0) {
                $limit--;
                //look for first close after previous
                $nextClose = strpos($contentAfter, '</'.$htmlTag, $nextClose + 1);
                //look for last open before previous
                $nextOpen = strrpos($contentAfter, '<'.$htmlTag, $nextOpen - strlen($contentAfter) - 1);
            }
            if($limit == 0) return '';
        }

        //look for our element end
        $ending = strpos($contentAfter, '>', $nextClose);
        $contentAfter = substr($contentAfter, 0, $ending + 1);


        if($contentOnly) {
            //get opening tag of our elmt
            $openTagIndex = strpos($contentAfter, '>');
//            $contentBefore .= $htmlId . substr($contentAfter, 0, $openTagIndex + 1);
            //get closing tag of our elmt
            $closeTagIndex = strrpos($contentAfter, '<');
            //grab html in between
            $contentInside = trim(substr($contentAfter, $openTagIndex + 1, $closeTagIndex - $openTagIndex - 1));
//            $contentAfter = substr($contentAfter, $closeTagIndex);
            return $contentInside;
        }
        return $contentBefore . $htmlId . $contentAfter;

    }


    /**
     * Parse html source containing list of targeted tag
     * Typically list all <li> of a <ul>
     * List only first level tags, not similar tags nested in it
     * Work only with container tag (with separated open and close tags)
     * @param string $content
     * @param string $tag (tags to list)
     * @param bool $contentOnly return content inside the target tag
     * @return array
     */
    public static function parseTagList(string $content,
                                        string $tag,
                                        bool $contentOnly = false)
    {
        $list = array();
        $openTag = "<$tag";
        $closeTag = "</$tag>";
        $contents = explode($closeTag, $content);
        //remove everything after last tag, we dont care
        unset($contents[count($contents) - 1]);
        $content = implode($closeTag, $contents) . $closeTag;
        $contents = explode($openTag, $content);
        //operation above does not care about nested element, so rebuilt
        //beware that explode remove opening tags from content, may have to add it back
        //remove everything before first tag, we dont care
        unset($contents[0]);
        $partsCount = count($contents);
        foreach($contents as $index => $part) {
            if($contentOnly) {
                //remove first tag
                $posAfterTag = strpos($part, '>');
                $part = substr($part, $posAfterTag+1);
            } else {
                //add back opening tag
                $part = $openTag . $part;
            }
            $closeSearch = 1;
            $checkIndex = $index;
            //look for closing tags
            $nbClose = count(explode($closeTag, $part)) - 1;

            while ($nbClose != $closeSearch && $checkIndex < $partsCount) {
                //closing tag not found, means that following part is nested into current one
                //looking for one more closing tags, minus the number we already found
                $closeSearch += 1 - $nbClose;
                $checkIndex++;
                $nextPart = $contents[$checkIndex];
                //add back opening tag
                $nextPart = $openTag . $nextPart;
                //add to current part and remove from the loop
                $part .= $nextPart;
                unset($contents[$checkIndex]);

                $nbClose = count(explode($closeTag, $nextPart)) - 1;
            }
            if($contentOnly) {
                //removing trailing closing tag
                $posBeforeTag = strrpos($part, $closeTag);
                $part = substr($part, 0, $posBeforeTag);
            }
            $list[] = $part;
        }

        return $list;
    }


    /**
     * Parse html source to isolate target tag
     * Must not nest similar tag
     * Work only with container tag (with separated open and close tags)
     * @param string $content
     * @param string $tag
     * @param bool $contentOnly
     * @param int $tagIteration
     * @return string
     */
    public static function parseTag(string $content,
                                    string $tag,
                                    bool $contentOnly = false,
                                    int $tagIteration = 1)
    {
        if(empty($tagIteration)) $tagIteration = 1;
        $openTag = "<$tag";
        $closeTag = "</$tag>";
        $contents = explode($openTag, $content);
        if(empty($contents[$tagIteration])) return '';
        $content = $contents[$tagIteration];
        $posCloseTag = strpos($content, $closeTag);
        $start = 0;
        $end = $posCloseTag + strlen($closeTag);
        if($contentOnly) {
            $start = strpos($content, '>') + 1;
            $end = $posCloseTag;
        }
        $content = substr($content, $start, $end - $start);
        if(!$contentOnly) $content = $openTag . $content;

        return $content;
    }



    /**
     * Parse html source to isolate target tag's attribute
     * Content should contains isolated tag html code
     * @param string $content
     * @param string $attr
     * @return string
     */
    public static function parseTagAttribute(string $content,
                                             string $attr)
    {
        $start = $attr . '="';
        $end = '"';
        $posStart = strpos($content, $start) + strlen($start);
        $posEnd = strpos($content, $end, $posStart);
        if($posStart === false) return '';
        $value = substr($content, $posStart, $posEnd - $posStart);
        return $value;
    }

}