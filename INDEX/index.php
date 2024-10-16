<?php
ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
ini_set('max_execution_time', '0'); // for infinite time of execution 
echo "The time is " . date("h:i:sa");
$payload = file_get_contents('254032/payload.json');
$payload_decode = json_decode($payload);

$indexResponse['index_delimiter_list'] = resolveIndexdelimiter($payload_decode->indexDelimiterList);
function resolveIndexdelimiter($delimiterList){
    $delimiterArray = json_decode(json_encode($delimiterList), true);
    return $delimiterArray[0];
}
$directoryFilePath = '254032/finaloutput.xml';
$indexResponse['main_xml_cleaned_content'] = file_get_contents($directoryFilePath);
$indexResponse['project_id'] = 254032;
$indexResponse['sortingType'] = 2;
$indexResponse['project_priority'] = 5;
$indexResponse['primary_capitalization'] = 1;
$indexResponse['sublevel_capitalization'] = 1;
$indexResponse['company_id'] = 18;
//echo"<pre>";print_r($indexResponse);
$generateFileList = [];
$indexFilePath = '254032/output/final_index.xml';
$generateFileList['collect_allindex'] = str_replace(".xml","_CollectAllIndex.xml",$indexFilePath);
$generateFileList['grouping_index'] = str_replace(".xml","_GroupIndex.xml",$indexFilePath);
$generateFileList['crossReferenceIndex'] = str_replace(".xml","_crossReferenceIndex.xml",$indexFilePath);
$generateFileList['IdElement_index'] = str_replace(".xml","_IdElementIndex.xml",$indexFilePath);
$generateFileList['duplicate_removed_index'] = str_replace(".xml","_duplicateRemovedIndex.xml",$indexFilePath);
$generateFileList['sorted_index'] = str_replace(".xml","_sortedIndex.xml",$indexFilePath);
$generateFileList['final_cleanup_index'] = str_replace(".xml","_finalCleanupIndex.xml",$indexFilePath);
$generateFileList['final_storeid_index'] = str_replace(".xml","_StoreIdCleanup.xml",$indexFilePath);
$generateFileList['page_number_formatting'] = str_replace(".xml","_pageNumberFormattingIndex.xml",$indexFilePath);
$generateFileList['final_index'] = str_replace(".xml","_finalIndex.xml",$indexFilePath);
$generateFileList['final_index_cross'] = str_replace(".xml","_finalIndexCross.xml",$indexFilePath);
$generateFileList['bookmark_index'] = str_replace(".xml","_bookmarkinfo.xml",$indexFilePath);
$generateFileList['bookmark_final'] = str_replace(".xml","_bookmarkfinal.xml",$indexFilePath);
$generateFileList['pagination_index'] = str_replace(".xml","_paginationCompleted.xml",$indexFilePath);
$generateFileList['index_filepath'] = $indexFilePath;
$generateFileList['status'] = false;
$generateFileList['status'] = false;
$generateFileList['message'] = '';




$sortList = collectPrimaryIndex($indexResponse);
//echo"<pre>";print_r($sortList);
// die();

if($indexResponse['sortingType'] == 2){
    $columns1 = array_column($sortList, 'sortTextValue');
    array_multisort($columns1, SORT_ASC, SORT_NATURAL, $sortList);
} else{
    $columns = array_column($sortList, 'sortTextValue');
    array_multisort($columns, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $sortList);
}

$indextermWithBookmarks = getAllIndexElementBlob($sortList, $generateFileList['collect_allindex'], $indexResponse, $generateFileList['bookmark_index']);
//echo"<pre>";print_r($sortList);
die();

$body_content = groupingSubLevelElement($indextermWithBookmarks, $generateFileList['grouping_index'], $indexResponse, $indexResponse['sortingType']);
if($body_content !== ''){
    $changexml = indexCleanUpFunction($body_content, $indexResponse['index_delimiter_list']);
    if(!empty($changexml)){
        storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $generateFileList['IdElement_index'], $changexml);
    } else{
        $generateFileList['message'] = 'Index generation failed due to indexCleanUpFunction method.';
        print_r($generateFileList);
    }

    $duplicatioRemovedContent = removeDuplicationIntexElement($changexml);
    if(!empty($duplicatioRemovedContent)){
        storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $generateFileList['duplicate_removed_index'], $duplicatioRemovedContent);
    } else{
        $generateFileList['message'] = 'Index generation failed due to removeDuplicationIntexElement method.';
        print_r($generateFileList);
    }


    $sortedOutputXMLContent = sortElementByAttribute($duplicatioRemovedContent);
    if(!empty($sortedOutputXMLContent)){
        storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $generateFileList['sorted_index'], $sortedOutputXMLContent);
    } else{
        $generateFileList['message'] = 'Index generation failed due to sortElementByAttribute method.';
        print_r($generateFileList);
    }


    //Page Number formatting changes
    $pageNumberFormattingOutput = pageNumberFormattingChanges($sortedOutputXMLContent);
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $generateFileList['page_number_formatting'], $pageNumberFormattingOutput);
    //Page Number formatting changes
   
   $finalCleanupOutput = finalCleanupIndex($pageNumberFormattingOutput);
   if(!empty($finalCleanupOutput)){
       storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $generateFileList['final_cleanup_index'], $finalCleanupOutput);
   } else{
       $generateFileList['message'] = 'Index generation failed due to finalCleanupIndex method.';
       return $generateFileList;
   }
   print_r($generateFileList);

}else{
    $generateFileList['status'] = false;
    $generateFileList['message'] = 'Index generation failed due to groupingSubLevelElement method.';
    print_r($generateFileList);
}
echo "The time is " . date("h:i:sa");

function finalCleanupIndex($finalOuput){
    //$changexml = preg_replace("/(<\/id>)(<id[^>]+>)/i", "$1<punc><!--,&nbsp;--></punc>$2", $finalOuput);
    // $changexml = str_replace("</primary><id", "</primary><punc><!-- &nbsp;--></punc><id", $changexml);
    // $changexml = str_replace("</secondary><id", "</secondary><punc><!-- &nbsp;--></punc><id", $changexml);
    // $changexml = str_replace("</tertiary><id", "</tertiary><punc><!-- &nbsp;--></punc><id", $changexml);
    // $changexml = str_replace("</quaternary><id", "</quaternary><punc><!-- &nbsp;--></punc><id", $changexml);
    $body_content = str_replace("<intexnew>","<chapter><index>",$finalOuput);
    $body_content = str_replace("</intexnew>","</index></chapter>",$body_content);
    $body_content = preg_replace('(<chapter>)','<chapter><!--<LRH>Index</LRH>--><!--<RRH>Index</RRH>--><title1>Index</title1>',$body_content);	
    $body_content = str_replace('<?xml version="1.0"?>','', $body_content);
    $body_content = str_replace("&lt;", "<", $body_content);
    $body_content = str_replace("&gt;", ">", $body_content);
    $body_content = str_replace("\n", "", $body_content);
    $body_content = str_replace("\r", "", $body_content);
    $body_content = str_replace("<intexnew>", "", $body_content);
    $body_content = str_replace("</intexnew>", "", $body_content);
    $body_content = preg_replace('(<chapter>)','<?xml version="1.0" encoding="utf-8"?><!DOCTYPE chapter SYSTEM "docbook.dtd"><book><chapter>',$body_content);
    $body_content = str_replace("</chapter>","</chapter></book>",$body_content);
    return $body_content;
}


function pageNumberFormattingChanges($finalCleanUpContent){
    $domformatting = new \DOMDocument();
    $domformatting->loadXML($finalCleanUpContent);
    $xpath = new \DOMXPath($domformatting);
    $subEntriesList = $xpath->query('//primary|//secondary|//tertiary|//quaternary');
    if($subEntriesList->length > 0){
        foreach ($subEntriesList as $key => $subEntriesvalue) {
            $linkend = $subEntriesvalue->getAttribute("linkend");
            $classAttributes = $subEntriesvalue->getAttribute("class");
            $idElement = $xpath->query('//id[@linkend="'.$linkend.'"]');
            if($idElement->length > 0){
                foreach ($idElement as $key => $idvalue) {
                    $classList = explode("_", $classAttributes);
                    $pageClass = end($classList);
                    $idNodeValue = $idvalue->nodeValue;
                    if($pageClass === 'bold'){
                        $newIdNodeValue = '<b>'.$idNodeValue."</b>";
                        $idvalue->nodeValue = $newIdNodeValue;        
                    }elseif($pageClass === 'italic'){
                        $newIdNodeValue = '<i>'.$idNodeValue."</i>";
                        $idvalue->nodeValue = $newIdNodeValue;        
                    }
                                    
                }
            }
        }
    }

    $outputContent  = $domformatting->saveXML();
    $outputContent = str_replace("&lt;", "<", $outputContent);
    $outputContent = str_replace("&gt;", ">", $outputContent);
    return $outputContent;
}



function sortElementByAttribute($indexXMLContent){
    $domSort = new \DOMDocument();
    $domSort->loadXML($indexXMLContent);
    $xpath = new \DOMXPath($domSort);
    $indexElements = $xpath->query('//indexterm');
    if($indexElements->length > 0 ){
        foreach ($indexElements as $key => $indexElementValue) {
            $elements = $xpath->query('./id[@primary="true"]', $indexElementValue);
            $secondaryElement = $xpath->query('./secondary', $indexElementValue);
            if($elements->length > 1){
                $sortedElements = iterator_to_array($elements);
                usort($sortedElements, function ($a, $b) {
                    return strcmp($a->getAttribute('linkend'), $b->getAttribute('linkend'));
                });

                foreach ($sortedElements as $element) {
                    if($secondaryElement->length > 0){
                        $secondaryElement[0]->parentNode->insertBefore($element, $secondaryElement[0]);	
                    } else{
                        $indexElementValue->appendChild($element);
                    }	
                }	
            }
        }
    }
    
    $outputContent  = $domSort->saveXML();
    return $outputContent;
}


function removeDuplicationIntexElement($xmlContent){
    $docIndex = new \DOMDocument ();
    $docIndex->loadXML($xmlContent);    
    $xpathIndex = new \DOMXpath($docIndex);
    $duplicateElement = $xpathIndex->query("//indexterm/primary[@duplicate]");
    $textList = [];
    if($duplicateElement->length > 0){
        foreach ($duplicateElement as $key => $duplicateElementvalue) {
            $duplicateAttribute = $duplicateElementvalue->getAttribute('duplicate');
            $linkendAttribute = $duplicateElementvalue->getAttribute('linkend');

            $IndexparentNode = $duplicateElementvalue->parentNode;
            $pageAttribute = $IndexparentNode->getAttribute('page');
            $startpage = $IndexparentNode->getAttribute('startpage');
            $secondaryNode = $xpathIndex->query("./secondary",$IndexparentNode);
            $quaternaryNode = $xpathIndex->query("./quaternary",$IndexparentNode);

            if($secondaryNode->length > 0){
                foreach ($secondaryNode as $key => $secondaryNodevalue) {
                   
                    appendDuplicationElement($secondaryNodevalue, $duplicateAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex);
                }

                $teriorityNode = $xpathIndex->query("./tertiary",$IndexparentNode);
                if($teriorityNode->length > 0){
                    foreach ($teriorityNode as $key => $teriorityNodevalue) {
                        appendDuplicationElement($teriorityNodevalue, $duplicateAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex);
                    }
                }

                if($quaternaryNode->length > 0){
                    foreach ($quaternaryNode as $key => $quaternaryNodevalue) {
                        appendDuplicationElement($quaternaryNodevalue, $duplicateAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex);
                    }
                }
                
                primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex);

            }
            else{
                primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex);
            }

        }
    }
    $saveXMLContent = $docIndex->saveXML();
    file_put_contents('step1.xml', $saveXMLContent);
    //Remove the duplication for sublevel element and add the line
    $subLevelduplicateElement = $xpathIndex->query("//indexterm/primary//*[@duplicate]");
    if($subLevelduplicateElement->length){
        foreach ($subLevelduplicateElement as $key => $subLevelduplicateElementvalue) {
            $storeAtrribute = $subLevelduplicateElementvalue->getAttribute('duplicate');
            $subEntrieparentNode = $subLevelduplicateElementvalue->parentNode;
            $tagName = $subLevelduplicateElementvalue->localName;
            $existingSubEntries = $xpathIndex->query("./".$tagName."[@store='".$storeAtrribute."']", $subEntrieparentNode);
            $idElementsubEntries = $docIndex->createElement("id", "0");
            $subLevelLinkendAttribute = $subLevelduplicateElementvalue->getAttribute('linkend');
            $idElementsubEntries->setAttribute('linkend', $subLevelLinkendAttribute);
            if($existingSubEntries->length > 0){
                $idElementforSubEntries = $xpathIndex->query("./id", $existingSubEntries[0]);
                if($idElementforSubEntries->length > 0){
                    $checkIdAlreadyExist = $xpathIndex->query("./id[@linkend='".$subLevelLinkendAttribute."']", $existingSubEntries[0]);
                    $idElementforSubEntries[0]->parentNode->insertBefore($idElementsubEntries, $idElementforSubEntries[0]->nextSibling);
                    if($checkIdAlreadyExist->length > 0){
                        $checkIdAlreadyExist[0]->parentNode->removeChild($checkIdAlreadyExist[0]); 
                    }
                    $subLevelduplicateElementvalue->parentNode->removeChild($subLevelduplicateElementvalue); 
                   
                }
                
            }
        }
    }
    $outputContent  = $docIndex->saveXML();
    return $outputContent;
    
} 


function primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startPage, $docIndex, $xpathIndex){
    $orginalIndextermNode = $xpathIndex->query("//indexterm[@store='".$duplicateAttribute."']");
    if($orginalIndextermNode->length > 0){
        $idElement = $docIndex->createElement("id", (int)$pageAttribute + (int)$startPage);
        $idElementLinkend = $idElement->setAttribute('linkend', $linkendAttribute);
        $setIsPrimaryAttributes = $idElement->setAttribute('primary','true');
        $existingIdElementPrimary = $xpathIndex->query("./primary", $orginalIndextermNode[0]);
        if($existingIdElementPrimary->length > 0){
            //$existingIdElementPrimary[0]->parentNode->insertBefore($idElement, $existingIdElementPrimary[0]->nextSibling);
            $existingIdElementPrimary[0]->appendChild($idElement);
        } else{
            $orginalIndextermNode[0]->parentNode->insertBefore($idElement, $orginalIndextermNode[0]->nextSibling);
        }
    }
    $IndexparentNode->parentNode->removeChild($IndexparentNode); 
}


function appendDuplicationElement($elementNode, $duplicateAttribute, $pageAttribute, $startPage, $docIndex, $xpathIndex){
    $secondaryAttribute = $elementNode->getAttribute('linkend');
    $elementPage = (int)$pageAttribute + (int)$startPage;
    $cloneNodevalue = $elementNode->cloneNode(TRUE);
    $orginalPrimaryNode = $xpathIndex->query("//indexterm[@store='".$duplicateAttribute."']");
    $orginalPrimaryNode[0]->appendChild($cloneNodevalue);

    $orginalIndextermNode = $xpathIndex->query("//indexterm[@store='".$duplicateAttribute."']");
    if($orginalIndextermNode->length > 0){
        $idElement = $docIndex->createElement("id", $elementPage);
        $idElementLinkend = $idElement->setAttribute('linkend', $secondaryAttribute);
        $idElement->appendChild($idElementLinkend);
        $orginalIndextermNode[0]->appendChild($idElement);
    }
    $elementNode->parentNode->removeChild($elementNode); 
}







function indexCleanUpFunction($data, $delimiterList){
    $docIndex = new \DOMDocument ();
    $docIndex->loadXML($data);    
    $xpathIndex = new \DOMXpath($docIndex);
    $indexElement = $xpathIndex->query("//indexterm");
    if($indexElement->length > 0){
        foreach ($indexElement as $key => $indexElementvalue) {

            $primaryElement = $xpathIndex->query("./primary",$indexElementvalue);
            $secondaryElement = $xpathIndex->query("./primary/secondary",$indexElementvalue);
            $teriorityElement = $xpathIndex->query("./primary/secondary/tertiary",$indexElementvalue);
            $quaternaryElement = $xpathIndex->query("./primary/secondary/tertiary/quaternary",$indexElementvalue);
            if($primaryElement->length > 0){
               $parentXpath = '';
                createIdElementForAllEntries($primaryElement, $indexElementvalue, $docIndex, $xpathIndex, 1, $parentXpath, $delimiterList);
            }
            if($secondaryElement->length > 0){
                $parentXpath = "//primary";
                createIdElementForAllEntries($secondaryElement, $indexElementvalue, $docIndex, $xpathIndex, 0, $parentXpath, $delimiterList);
            }
            if($teriorityElement->length > 0){
                $parentXpath = "//primary/secondary";
                createIdElementForAllEntries($teriorityElement, $indexElementvalue, $docIndex, $xpathIndex, 0, $parentXpath, $delimiterList);
            }
            if($quaternaryElement->length > 0){
                $parentXpath = "//primary/secondary/tertiary";
                createIdElementForAllEntries($quaternaryElement, $indexElementvalue, $docIndex, $xpathIndex, 0, $parentXpath, $delimiterList);
            }
        }
    }
    $outputContent  = $docIndex->saveXML();
    //$outputContent = preg_replace('/(<id[^>]+>((?:(?!<\/id>).)*)<\/id>)(\w+)/','$3$1',$outputContent);
    return $outputContent;
}

function createIdElementForAllEntries($elementNode, $indexElementNode, $docIndex, $xpathIndex, $IsPrimary, $parentXpath, $delimiterList){
    if($elementNode->length > 0){
        foreach ($elementNode as $key => $elementNodevalue) {
            $elementTagName = $elementNodevalue->localName;
            createIdElementWithAttributes($elementNodevalue, $indexElementNode, $docIndex, $xpathIndex, $IsPrimary, $elementTagName, $parentXpath, $delimiterList);
        }
    }
}


function createIdElementWithAttributes($firstElementNode, $indexElementNode, $docIndex, $xpathIndex, $IsPrimary, $elementTagName, $parentXpath, $delimiterList){
    $linkEndAttributes = $firstElementNode->getAttribute('linkend');
    if($linkEndAttributes == 'C10_I336'){
        //echo "<pre>";print_r($linkEndAttributes);
    }
    $parentNodePages = $indexElementNode->getAttribute('page');
    $startPageOfIndex = $indexElementNode->getAttribute('startpage');
    $idElement = $docIndex->createElement("id", (int)$linkEndAttributes + (int)$startPageOfIndex);
    $idElement->setAttribute('linkend', $linkEndAttributes);
    if($firstElementNode->hasAttribute('bookmark-startpage')){
        $idElement->setAttribute('index-position','start');
        $idElement->setAttribute('linkend', $firstElementNode->getAttribute('bookmark-startpage'));
    }
    if($firstElementNode->hasAttribute('bookmark-endpage')){
        $bookMarkidElement = $docIndex->createElement("id","0");
        $bookMarkidElement->setAttribute('index-position','end');
        $bookMarkidElement->setAttribute('linkend', $firstElementNode->getAttribute('bookmark-endpage'));
    }else{
        $bookMarkidElement = 0;
    }

    if($IsPrimary == 1){
        $existingIdElement = $xpathIndex->query("./primary", $indexElementNode);
        $setIsPrimaryAttributes = $idElement->setAttribute('primary','true');
        $existingChild = $existingIdElement[0]->firstChild;
        if(is_object($bookMarkidElement)){
            $existingChild->parentNode->insertBefore($bookMarkidElement, $existingChild->nextSibling); 
        }
        $existingChild->parentNode->insertBefore($idElement, $existingChild->nextSibling); 

    } else{
        $xpathSubEntry = $parentXpath.'/'.$elementTagName.'[@linkend="'.$linkEndAttributes.'"]';
        $existingSubElement = $xpathIndex->query($xpathSubEntry, $indexElementNode);
        if($existingSubElement->length > 0){
            $existingSubValueChild = $existingSubElement[0]->firstChild;
            if(is_object($bookMarkidElement)){
                $existingSubValueChild->parentNode->insertBefore($bookMarkidElement, $existingSubValueChild->nextSibling); 
            }
            $existingSubValueChild->parentNode->insertBefore($idElement, $existingSubValueChild->nextSibling); 
        
        } else{
            $existingSubChild = $existingSubElement[0]->firstChild;
            if(is_object($bookMarkidElement)){
                $existingSubChild->parentNode->insertBefore($bookMarkidElement, $existingSubChild->nextSibling); 
            }
            $existingSubChild->parentNode->insertBefore($idElement, $existingSubChild->nextSibling); 
            
        }
    }
}

function getAllIndexElementBlob($sortList, $filePath, $indexResponse, $bookmarkfilePath){       
    $chapterAllContent = '';
    $indexContent = '';
    $indexListEntries = [];
    $formattingEntries = [];
    $indexKey = 0;
    $domx = new \DOMDocument ();
    $domx->loadXML($indexResponse['main_xml_cleaned_content']);
    $domx->xinclude();
    $xmlDate = $domx->getElementsByTagName( "include");
    if($xmlDate->length > 0){
        foreach( $xmlDate as $searchNode1 ){
            $valueID1 = $searchNode1->getAttribute('href');
            $chapterPath = '254032/'.$valueID1;
            //$chapterContent = $this->getProeditorFileContent($chapterPath);
            $chapterContent = file_get_contents($chapterPath);

            $domChapterXML = new \DOMDocument ();
            $domChapterXML->loadXML($chapterContent);
            $xpathchapter = new \DOMXpath($domChapterXML);
            $bookElement = $xpathchapter->query("//book");
            if($bookElement->length > 0){
                foreach ($bookElement as $key => $bookElementvalue) {
                    $chapterAllContent.= $bookElementvalue->ownerDocument->saveXML($bookElementvalue);
                }
            }
        }
    }
    $chapterAllContent = str_replace("</book><book>","", $chapterAllContent);
    $chapterAllContent = preg_replace("/<\/book><book[^>]+>/","", $chapterAllContent);
    $chapterAllContent = str_replace('xlink:href','xlinkhref', $chapterAllContent);
    $chapterAllContent = str_replace('&amp;','[[[AMP]]]', $chapterAllContent);
    $newFilePath = str_replace(".xml","_allcontents.xml",$filePath);
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $newFilePath, $chapterAllContent);

    $secondLevelDuplication = [];
    $domChapter = new \DOMDocument ();
    $domChapter->loadXML($chapterAllContent);
    $mainNodeX = new \DOMXpath($domChapter);
    $mainNodeX->registerNodeNamespaces = false;
    //$mainNodeX->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
    foreach ($sortList as $list) {
        // $ele = $mainNodeX->query("//*[@firstentry='".$list['entry']."']|//*[@linkend='".$list['refId']."']");
        $ele = $mainNodeX->query("//*[not(self::bookmark or self::see)][@firstentry='".$list['entry']."']|//*[not(self::bookmark or self::see)][@linkend='".$list['refId']."']");
        //echo '<pre>'; print_r($ele);
        //echo "//*[not(self::bookmark or self::see)][@firstentry='".$list['entry']."']|//*[not(self::bookmark or self::see)][@linkend='".$list['refId']."']";
        
        if($ele !== false && $ele->length > 0){
            foreach ($ele as $key => $elevalue) {
                //adding Element Id
                $elementId = getElementIdofGrandParentNode($elevalue);
                $elevalue->setAttribute("ele-id", $elementId);
                $entryValue = $elevalue->getAttribute('entry');
                $classValue = $elevalue->getAttribute('class');
                if(!empty($list['duplicate']) && $classValue != 'italic' && $classValue != 'bold' && $classValue != 'formatting_italic' && $classValue != 'formatting_bold' && $elevalue->localName == 'primary'){
                    $isArrayExist = array_search($entryValue, array_column($indexListEntries, 'entry'));
                    $elevalue->setAttribute('duplicate', $list['duplicate']);
                }

                $lowerCaseEntry = strtolower(trim($entryValue))."&&".strtolower(trim($list['entry']));
                if(!in_array($lowerCaseEntry, $secondLevelDuplication)){
                    $secondLevelDuplication[$elevalue->getAttribute('store')] = $lowerCaseEntry;
                }else{
                    $isArrayExist = array_search($lowerCaseEntry, array_column($indexListEntries, 'entry_node'));
                    $linkendId = $elevalue->getAttribute('linkend');
                    $linkIdisArrayExist = array_search($linkendId, array_column($indexListEntries, 'linkend'));
                    if(!empty($linkIdisArrayExist)){
                        continue;
                    }
                    //echo " Array Exist: ".$isArrayExist."</br>";
                    if(!empty($isArrayExist) && $classValue != 'italic' && $classValue != 'bold' && $classValue != 'formatting_italic' && $classValue != 'formatting_bold' ){
                        $elevalue->setAttribute("duplicate", $indexListEntries[$isArrayExist]['store']);
                        if (strpos($classValue,'duplicate-indicate-secondary') !== false) {
                            $classValue = str_replace("duplicate-indicate-secondary", "", $classValue);
                        }
                        $newClass = $classValue." duplicate-indicate-secondary";
                        $elevalue->removeAttribute('class');
                        $elevalue->setAttribute('class', $newClass);
                    }
                }
                $indecTermEle = $elevalue->parentNode;
                
                $classAttribute = $elevalue->getAttribute('class');
                
                $newElementNode = addFormattingTagForEntries($elevalue, $classAttribute, $indexResponse['primary_capitalization']);
                $elevalue->nodeValue = $newElementNode;
                if($elevalue->localName != 'primary'){
                    $indexListEntries[$indexKey]['tagName'] = $elevalue->localName;
                    $indexListEntries[$indexKey]['entry'] = $newElementNode;
                    $indexListEntries[$indexKey]['entry_node'] = $lowerCaseEntry;
                    $indexListEntries[$indexKey]['store'] =  $elevalue->getAttribute('store');
                    $indexListEntries[$indexKey]['linkend'] = $elevalue->getAttribute('linkend');
                    $indexKey++;
                }
                

                $bookmarkforSubElements = $mainNodeX->query("//bookmark[@role='".$entryValue."']");
                if($bookmarkforSubElements->length > 0){
                    $bookInformation = getAllBookMarkElementForSubLevel($bookmarkforSubElements, $mainNodeX);
                    if(count($bookInformation) > 0){
                        $elevalue->setAttribute('bookmark-startpage', $bookInformation['start_id']);
                        $elevalue->setAttribute('bookmark-endpage', $bookInformation['end_id']);
                    }
                }

                if(!is_null($list['pageStart']) && !empty($list['pageStart'])){
                    $pageAttribute = $indecTermEle->setAttribute('startpage', $list['pageStart']);
                    $indecTermEle->appendChild($pageAttribute);
                }
                if(is_object($indecTermEle)){
                    $indexContent .= $indecTermEle->ownerDocument->saveXML( $indecTermEle );
                }
            }
        } else{
            $ele = $mainNodeX->query("//see[contains(.,'".$list['entry']."')]");
            if($ele !== false && $ele->length > 0){
                foreach ($ele as $key => $elevalue) {
                    if($elevalue->localName == 'see'){
                        $entryValue = $elevalue->textContent;
                        $classValue = $elevalue->getAttribute('class');
                        $seeStoreId = $elevalue->getAttribute('store');
                        $isArrayExist = array_search($entryValue, array_column($indexListEntries, 'entry'));
                        if($isArrayExist == 1  && $classValue != 'italic' && $classValue != 'bold' && $classValue != 'formatting_bold' && $classValue != 'formatting_italic'){
                            $elevalue->setAttribute("duplicate", $elevalue->getAttribute('store'));
                            $newClass = $classValue." duplicate-indicate";
                            $elevalue->removeAttribute('class');
                            $elevalue->setAttribute('class', $newClass);
                        }
                        $crossElementValue = addCrossReferenceWithStructuralEntries($elevalue);
                        $seeAlsoElement =  $mainNodeX->query("//*[not(self::bookmark or self::see)][contains(@entry,'".$entryValue."')]");
                        if($seeAlsoElement->length > 0){
                            $italicElement = $domChapter->createElement('i','see also');
                        }else{
                            $italicElement = $domChapter->createElement('i','see');
                        }
                        $elevalue->appendChild($italicElement);
                        $crossRefTextNode = $domChapter->createTextNode($crossElementValue);
                        $elevalue->appendChild($crossRefTextNode);
                        $indexterm = $domChapter->createElement('indexterm');
                        $indexterm->setAttribute('store', $seeStoreId);
                        $indexterm->setAttribute('contenteditable', 'true');
                        $indexterm->appendChild($elevalue);
                        $indexContent .= $indexterm->ownerDocument->saveXML( $indexterm );
                    }
                }
            }
        }
    }

    $indexNewValue = "<intexnew>".$indexContent."</intexnew>";
    $indexNewValue = str_replace("&nbsp;", " ", $indexNewValue);
    $indexNewValue = str_replace("&lt;", "<", $indexNewValue);
    $indexNewValue = str_replace("&gt;", ">", $indexNewValue);
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $filePath, $indexNewValue);
    //echo '<pre>'; print_r($indexListEntries);
    

    //Bookmarks
    $bookmarkIdList = [];
    $docElement = new \DOMDocument ();
    $docElement->loadXML($indexNewValue); 
    $indexTerm = new \DOMXpath($docElement);
    foreach ($sortList as $list) {
        $bookmarkFinalList = [];
        if($list['entry'] == 'innovation'){
            echo '<pre>'; print_r($list);
            
        }
        $bookmarkElementStart = $mainNodeX->query("//bookmark[@entry='".$list['entry']."' and @index-position='start']|//bookmark[@entry='".$list['entry']."']");
        if($bookmarkElementStart !== false && $bookmarkElementStart->length > 0){
            foreach ($bookmarkElementStart as $key => $bookmarkNodevalue) {
                $bookmarkFinalList['bookmark_start_id'] = getElementIdofGrandParentNode($bookmarkNodevalue);
                $bookmarkFinalList['start_id'] = $bookmarkNodevalue->getAttribute("linkend");
            }
        }
        echo "//bookmark[@entry='".$list['entry']."' and @index-position='start']<br><br>";
        $bookmarkElementEtart = $mainNodeX->query("//bookmark[@entry='".$list['entry']."' and @index-position='end']");
        if($bookmarkElementEtart !== false && $bookmarkElementEtart->length > 0){
            foreach ($bookmarkElementEtart as $key => $bookmarkNodevalue) {
                $bookmarkFinalList['end_id'] = $bookmarkNodevalue->getAttribute("linkend");
                $bookmarkFinalList['bookmark_end_id'] = getElementIdofGrandParentNode($bookmarkNodevalue);
            }
        }

        if(count($bookmarkFinalList) > 0){
            echo '<pre>'; print_r($bookmarkFinalList);
            $primaryElementForBookMarks = $indexTerm->query("//primary[@entry='".$list['entry']."']");
            if($primaryElementForBookMarks->length > 0){
                foreach ($primaryElementForBookMarks as $key => $primaryElementvalue) {
                    $primaryElementvalue->setAttribute('bookmark-startpage', $bookmarkFinalList['start_id']);
                    $primaryElementvalue->setAttribute('bookmark-endpage', $bookmarkFinalList['end_id']);
                    $primaryElementvalue->setAttribute('bookmark-start-ele-id', $bookmarkFinalList['bookmark_start_id']);
                    $primaryElementvalue->setAttribute('bookmark-end-ele-id', $bookmarkFinalList['bookmark_end_id']);
                }
            }
        }
    }
    $indexterm_value = $docElement->saveXML();
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $bookmarkfilePath, $indexterm_value);
    return $indexterm_value;
}

function getElementIdofGrandParentNode($elementNode){
    $eleId = ''; 
    $parentNode = $elementNode->parentNode;
    // Get the grandparent node, which is 'para'
    while ($parentNode !== null && $parentNode->nodeName !== 'para') {
        $parentNode = $parentNode->parentNode;
    }
    // Check if the found parent node is indeed a <para> element
    if ($parentNode instanceof \DOMElement && $parentNode->nodeName === 'para') {
        // Access parent <para> attributes
        if ($parentNode->hasAttribute('ele-id')) {
            $eleId = $parentNode->getAttribute('ele-id');
        }
    }
    return $eleId;
}


function groupingSubLevelElement($indexXMLContent, $filePath, $indexResponse, $sortingType){
    $docElement = new \DOMDocument ();
    $docElement->loadXML($indexXMLContent);    
    $xpathElement = new \DOMXpath($docElement);
    $subelementLists = $xpathElement->query("//secondary|//tertiary|//quaternary");
    if($subelementLists->length > 0){
        foreach ($subelementLists as $key => $subelementvalue) {
            $refId = $subelementvalue->getAttribute('firstentry');
            $primaryElement = $xpathElement->query("//primary[@entry='".$refId."']");
            if($primaryElement->length > 0){
                $primaryParentNode = $primaryElement[0]->parentNode;
                $primaryElement[0]->appendChild($subelementvalue);
                $subElementParentNode = $subelementvalue->parentNode;
            }
        }
    }
    $indexTermElement = $xpathElement->query("//indexterm");
    if($indexTermElement->length > 0){
        foreach ($indexTermElement as $key => $indexTermElementValue) {
            if (!$indexTermElementValue->hasChildNodes()) {
                $indexTermElementValue->parentNode->removeChild($indexTermElementValue);
            }
        }
    }

    $groupIndexElement = $docElement->saveXML();
    $groupIndexElement = str_replace("&nbsp;", " ", $groupIndexElement);
    $groupIndexElement = str_replace("&amp;nbsp;", " ", $groupIndexElement);
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $filePath, $groupIndexElement);
    
    
    $docSortSubEntries = new \DOMDocument ();
    $docSortSubEntries->loadXML($groupIndexElement);    
    $xpathElementSubentries = new \DOMXpath($docSortSubEntries);
    $indexElement = $xpathElementSubentries->query("//indexterm");
    if($indexElement->length > 0){
        foreach ($indexElement as $key => $indexElementvalue) {
            $subentriesList = $xpathElementSubentries->query('.//secondary', $indexElementvalue);
            if($subentriesList->length > 0){
                $secondayAttributeList = [];
                foreach ($subentriesList as $key => $subentriesList) {
                    $secondayAttributeList[$key]['htmlContent'] = addFormattingTagForSubEntries($subentriesList, $indexResponse['sublevel_capitalization']);
                    $subentriesValue = cleanUpSpecialCharacter($subentriesList->getAttribute('entry'));
                    $secondayAttributeList[$key]['compare_entry'] = compareEntryList($subentriesValue);
                    $secondayAttributeList[$key]['entry'] = $subentriesList->getAttribute('entry');
                    $secondayAttributeList[$key]['firstentry'] = $subentriesList->getAttribute('firstentry');
                    $secondayAttributeList[$key]['linkend'] = $subentriesList->getAttribute('linkend');
                }
                //echo "<pre>";print_r($secondayAttributeList);
                if(count($secondayAttributeList) > 1){
                    $secondaryAttributeList = sortingSubentries($secondayAttributeList, $sortingType, 1, 'compare_entry');
                    foreach ($secondaryAttributeList as $key => $secondayAttributevalue) {
                        $selectSecondElement = $xpathElementSubentries->query(".//secondary[@linkend='".$secondayAttributevalue['linkend']."']", $indexElementvalue);
                        $primaryParentElement = $xpathElementSubentries->query(".//primary[@entry='".$secondayAttributevalue['firstentry']."']", $indexElementvalue);
                        $fragment = $docSortSubEntries->createDocumentFragment();
                        $fragment->appendXml($secondayAttributevalue['htmlContent']);  
                        //$indexElementvalue->appendChild($fragment);
                        if($primaryParentElement->length > 0 && $selectSecondElement->length > 0){
                            //echo $secondayAttributevalue['htmlContent']."<br>";
                            $primaryParentElement[0]->appendChild($fragment);
                            $selectSecondElement[0]->parentNode->removeChild($selectSecondElement[0]);
                        }
                    }
                }
            }

            $thirdLevelElementList = $xpathElementSubentries->query('.//tertiary', $indexElementvalue);
            if($thirdLevelElementList->length > 0){
                $thirdAttributeList = [];
                foreach ($thirdLevelElementList as $key => $thirdLevelElementValue) {
                    //$thirdAttributeList[$key]['htmlContent'] = $thirdLevelElementValue->ownerDocument->saveHTML($thirdLevelElementValue);
                    $thirdAttributeList[$key]['htmlContent'] = addFormattingTagForSubEntries($thirdLevelElementValue, $indexResponse['sublevel_capitalization']);
                    $thirdsubentriesValue = cleanUpSpecialCharacter($thirdLevelElementValue->getAttribute('entry'));
                    $thirdAttributeList[$key]['third_compare_entry'] = compareEntryList($thirdsubentriesValue);
                    $thirdAttributeList[$key]['entry'] = $thirdLevelElementValue->getAttribute('entry');
                    $thirdAttributeList[$key]['secondentry'] = $thirdLevelElementValue->getAttribute('secondentry');
                    $thirdAttributeList[$key]['linkend'] = $thirdLevelElementValue->getAttribute('linkend');
                }
                if(count($thirdAttributeList) > 0){
                    $thirdAttributeList = sortingSubentries($thirdAttributeList, $sortingType, 2, 'third_compare_entry');
                    foreach ($thirdAttributeList as $key => $thirdAttributeListvalue) {
                        $thirdLevelElement = $xpathElementSubentries->query(".//tertiary[@linkend='".$thirdAttributeListvalue['linkend']."']", $indexElementvalue);
                        $secondParentElement = $xpathElementSubentries->query(".//secondary[@entry='".$thirdAttributeListvalue['secondentry']."']", $indexElementvalue);
                        $fragment = $docSortSubEntries->createDocumentFragment();
                        $fragment->appendXml($thirdAttributeListvalue['htmlContent']);
                
                        if($secondParentElement->length > 0 && $thirdLevelElement->length > 0){ 
                            $secondParentElement[0]->appendChild($fragment);
                            //$secondParentElement[0]->parentNode->insertBefore($fragment, $secondParentElement[0]->nextSibling);
                            $thirdLevelElement[0]->parentNode->removeChild($thirdLevelElement[0]);
                        }
                    }
                }
            }


            $fourthLevelElementList = $xpathElementSubentries->query('.//quaternary', $indexElementvalue);
            if($fourthLevelElementList->length > 0){
                $fouthAttributeList = [];
                foreach ($fourthLevelElementList as $key => $fourthLevelElementValue) {
                    //$fouthAttributeList[$key]['htmlContent'] = $fourthLevelElementValue->ownerDocument->saveHTML($fourthLevelElementValue);
                    $fouthAttributeList[$key]['htmlContent'] = addFormattingTagForSubEntries($fourthLevelElementValue, $indexResponse['sublevel_capitalization']);
                    $fourthsubentriesValue = cleanUpSpecialCharacter($fourthLevelElementValue->getAttribute('entry'));
                    $fouthAttributeList[$key]['fourth_compare_entry'] = compareEntryList($fourthsubentriesValue);
                    $fouthAttributeList[$key]['entry'] = $fourthLevelElementValue->getAttribute('entry');
                    $fouthAttributeList[$key]['thirdentry'] = $fourthLevelElementValue->getAttribute('thirdentry');
                    $fouthAttributeList[$key]['linkend'] = $fourthLevelElementValue->getAttribute('linkend');
                }
                if(count($fouthAttributeList) > 0){
                    $fouthAttributeList = sortingSubentries($fouthAttributeList, $sortingType, 2, 'fourth_compare_entry');
                    foreach ($fouthAttributeList as $key => $fouthAttributeListValue) {
                        $fourthLevelElement = $xpathElementSubentries->query(".//quaternary[@linkend='".$fouthAttributeListValue['linkend']."']", $indexElementvalue);
                        $thirdParentElement = $xpathElementSubentries->query(".//tertiary[@entry='".$fouthAttributeListValue['thirdentry']."']", $indexElementvalue);

                        $fragment = $docSortSubEntries->createDocumentFragment();
                        $fragment->appendXml($fouthAttributeListValue['htmlContent']);
                        if($thirdParentElement->length > 0 && $fourthLevelElement->length > 0){ 
                            //$thirdParentElement[0]->parentNode->insertBefore($fragment, $thirdParentElement[0]->nextSibling);
                            $thirdParentElement[0]->appendChild($fragment);
                            $fourthLevelElement[0]->parentNode->removeChild($fourthLevelElement[0]);
                        }
                    }
                }
            }

        }
    }

    $newgroupContent = $docSortSubEntries->saveXML();
    $newFilePath = str_replace("GroupIndex","GroupIndexWithSorting", $filePath);
    storeXMLResponseInBlob($indexResponse['project_priority'], $indexResponse['project_id'], $indexResponse['company_id'], $newFilePath, $newgroupContent);
    return $newgroupContent;
}


function storeXMLResponseInBlob($projectPriority, $projectId, $companyId, $BlobStoragePath, $xmlContent){
    file_put_contents($BlobStoragePath, $xmlContent);
}

function collectPrimaryIndex($indexResponse){
    libxml_use_internal_errors(true);
    $indexterm_value = NULL;
    $sortList = [];
    $duplicateElement = 0;
    $textList = [];
    $domx = new \DOMDocument ();
    $domx->loadXML($indexResponse['main_xml_cleaned_content']);
    $domx->xinclude();
    $xmlDate = $domx->getElementsByTagName( "include");
    if($xmlDate->length > 0){
        foreach( $xmlDate as $searchNode1 ){
            $valueDate = $searchNode1->nodeValue; 
            $valueID1 = $searchNode1->getAttribute('href');
            $chapterPath = '254032/'.$valueID1;
            //$chapterContent = $this->getProeditorFileContent($chapterPath);
            $chapterContent = file_get_contents($chapterPath);
            $dom = new \DOMDocument ();
            $dom->loadXML($chapterContent);
            $chapter = $dom->getElementsByTagName('chapter');
            if($chapter->length > 0){
                foreach ($chapter as $chkey => $chapterchild){
                    $pageStart = '';
                    // if(isset($vxechapterList[$chkey])){
                    //     $chapterName = $vxechapterList[$chkey];
                    //     $toc = $this->repository["tb_projects"]->getTocByChaptername($indexResponse['project_id'],$chapterName);
                    //     if(isset($toc['start_pageno'])){
                    //         $pageStart = $toc['start_pageno'];
                    //     }
                    // }
                    $pageStart = 0;
                    $elements = $chapterchild->getElementsByTagName('indexterm');
                    if($elements->length > 0){
                        foreach ($elements as $node) {
                            if($node->getElementsByTagName('primary')->length > 0 && !$node->hasAttribute('index-level')){
                                $primaryNode = $node->getElementsByTagName('primary')->item(0);
                                if ($node->hasAttribute('xmlns:xlink')){
                                    continue;
                                }
                                
                                //$sortedValue = preg_replace('/[^a-zA-Z0-9\s]/', "", trim($primaryNode->textContent));
                                //adding Element Id
                                $parentNodeOfEntry = $primaryNode->parentNode;
                                while ($parentNodeOfEntry !== null && $parentNodeOfEntry->nodeName !== 'para' && $parentNodeOfEntry->nodeName !== 'title1' && $parentNodeOfEntry->nodeName !== 'ul') {
                                    $parentNodeOfEntry = $parentNodeOfEntry->parentNode;
                                }                                      
                                $parentNodeEleId = $parentNodeOfEntry->getAttribute('ele-id');
                                $primaryNode->setAttribute("ele-id", $parentNodeEleId);

                                $sortedValue = cleanUpSpecialCharacter($primaryNode->getAttribute('entry'));
                                $sortedValue = compareEntryList($sortedValue);
                                if($indexResponse['sortingType'] == 2){
                                    $sortedValue = ucfirst($sortedValue);
                                }
                                $entryValue = (!empty($primaryNode->hasAttribute('entry')) ? trim($primaryNode->getAttribute('entry')) : $primaryNode->textContent);
                                
                                if( !in_array( $entryValue ,$textList ) ){
                                    $textList[$node->getAttribute('store')] = trim($entryValue);
                                    $duplicateElement = 0;
                                }else{
                                    $key = array_search($entryValue, array_column($sortList, 'textValue'));
                                    $primaryNode->setAttribute("duplicate", $sortList[$key]['store']);
                                    $duplicateElement = $sortList[$key]['store'];
                                }

                                $sortList[] = [
                                    'textValue' => $entryValue,
                                    'textContent'  => $primaryNode->textContent,
                                    'sortTextValue' => $sortedValue,
                                    'store' => $node->getAttribute('store'),
                                    'refId' => $primaryNode->getAttribute('linkend'),
                                    'linkend' => $primaryNode->getAttribute('linkend'),
                                    'entry' => $entryValue,
                                    'pageStart' => $pageStart,
                                    'duplicate' => $duplicateElement
                                ];

                                //Default:indexterm escape
                                // $sortList[] = [
                                //     'textValue' => $primaryNode->textContent,
                                //     'store' => $node->getAttribute('store'),
                                //     'refId' => $primaryNode->getAttribute('linkend'),
                                //     'linkend' => $primaryNode->getAttribute('linkend'),
                                //     'pageStart' => $pageStart,
                                // ];
                                // if( !in_array( $primaryNode->textContent ,$textList ) ){
                                //     $textList[$node->getAttribute('store')] = $primaryNode->textContent;
                                // }else{
                                //     $key = array_search($primaryNode->textContent, array_column($sortList, 'textValue'));
                                //     $primaryNode->setAttribute("duplicate", $sortList[$key]['store']);
                                // }
                            }
                            $innerHTML = $node->ownerDocument->saveXML( $node );
                            $indexterm_value = $indexterm_value.$innerHTML;
                        }
                    }
                }
            }
        }
    }
    return $sortList;
}

function cleanUpSpecialCharacter($string){
    if(!empty($string)){
        $string = str_replace("&nbsp;","", trim($string));
        $string = preg_replace('/[^a-zA-Z0-9\s]/',"", $string);
        return trim($string);
    } else{
        return trim($string);
    }
}

function compareEntryList($compareEntry){
    $excludeList = array( 'a', 'an', 'the', 'and', 'in', 'of','or', 'to', 'at', 'on', 'by', ' ', 'against','before', 'between','during','for', 'from', 'into', 'under', 'versus', 'vs.', 'with', 'within');
    foreach ($excludeList as $key => $excludeValue) {
        if(preg_match('/^'.$excludeValue.'\s/i', $compareEntry)){
            $compareEntry = preg_replace('/^'.$excludeValue.'\s/i',"", $compareEntry);
        }
    }
    return strtolower($compareEntry);
}

function addFormattingTagForEntries($elementNode, $class, $initalCaps){
    if($elementNode->childNodes->length > 1){
        $nodeValue = $elementNode->getAttribute('entry');
        $nodeValue = ucfirst($nodeValue); 
        $seeElement = $elementNode->getElementsByTagName("see");   
        if($seeElement->length > 0){
            $seehtmlContent = $seeElement[0]->ownerDocument->saveHTML($seeElement[0]);
            return $nodeValue.$seehtmlContent;
        } 
        return $nodeValue;
    }
    $nodeValue = $elementNode->getAttribute('entry');
    if($initalCaps == 1){
        $nodeValue = ucfirst($nodeValue); 
     }
    if(strtoupper($class) == 'BOLD ITALIC' || strtoupper($class) == 'ITALIC BOLD' ){
        $nodeValue = "<b><i>".$nodeValue."</i></b>";
    } elseif(strtoupper($class) == 'ITALIC'){
        $nodeValue = "<i>".$nodeValue."</i>";
    } elseif(strtoupper($class) == 'BOLD'){
        $nodeValue = "<b>".$nodeValue."</b>";
    } else{
        $nodeValue = $nodeValue; 
    }
    return $nodeValue;
}

function getAllBookMarkElementForSubLevel($bookmarkElement, $xpathElement){
    $bookmarkIdList = [];
    $bookmarkFinalList = [];
    foreach ($bookmarkElement as $key => $bookmarkNodevalue) {
        $entryOfPrimary = $bookmarkNodevalue->getAttribute("entry");
        $bookMarkscomments = $xpathElement->query("./comment()", $bookmarkNodevalue);
        if($bookMarkscomments->length > 0){
            foreach ($bookMarkscomments as $key => $bookmarkCommentValue) {
                $bookmarkValue = $bookmarkCommentValue->nodeValue;
                if(preg_match('/linkend="((.*?)S)"/', $bookmarkValue, $startmatches) && count($startmatches) > 0){
                    if(!in_array($startmatches[1], $bookmarkIdList)){
                        $bookmarkFinalList['linkend'] = $bookmarkNodevalue->getAttribute('linkend');
                        $startIdForBookmarks = $startmatches[1];
                        $bookmarkIdList[] = $startIdForBookmarks;
                        $bookmarkFinalList['start_id'] = $startIdForBookmarks;
                    }
                } elseif(preg_match('/linkend="((.*?)E)"/', $bookmarkValue, $endmatches)){
                    if(!in_array($endmatches[1], $bookmarkIdList)){
                        $endIdForBookmarks = $endmatches[1];
                        $bookmarkIdList[] = $endIdForBookmarks;
                        $bookmarkFinalList['end_id'] = $endIdForBookmarks;
                    }
                }
            }
        }
        
    }
   return $bookmarkFinalList;
}

function addCrossReferenceWithStructuralEntries($seeElementListvalue){
    $crossElementValue = '';
    if($seeElementListvalue->hasAttribute('fouthentry')){
        $crossElementValue .= $seeElementListvalue->getAttribute('firstentry');
        $crossElementValue .= ", ".$seeElementListvalue->getAttribute('secondentry');
        $crossElementValue .= ", ".$seeElementListvalue->getAttribute('thirdentry').", ".$seeElementListvalue->getAttribute('fouthentry');
    }elseif($seeElementListvalue->hasAttribute('thirdentry')){
        $crossElementValue .= $seeElementListvalue->getAttribute('firstentry');
        $crossElementValue .= ", ".$seeElementListvalue->getAttribute('secondentry').", ".$seeElementListvalue->getAttribute('thirdentry');
    }elseif($seeElementListvalue->hasAttribute('secondentry')){
        $crossElementValue .= $seeElementListvalue->getAttribute('firstentry').", ".$seeElementListvalue->getAttribute('secondentry');
    }else{
        $crossElementValue .= $seeElementListvalue->getAttribute('firstentry');
    }
    return $crossElementValue;
}

function addFormattingTagForSubEntries($elementNode, $subleveCaptialization){
    $classAttribute = $elementNode->getAttribute('class');
    $newElementNode = addFormattingTagForEntries($elementNode, $classAttribute, $subleveCaptialization);
    $elementNode->nodeValue = $newElementNode;
    return $elementNode->ownerDocument->saveHTML($elementNode);
}

function sortingSubentries($sortList, $sortingType, $sortingOrder, $sortingText){
    if($sortingType == 2){
        $columns1 = array_column($sortList, $sortingText);
        if($sortingOrder == 1){
            array_multisort($columns1, SORT_ASC, SORT_NATURAL, $sortList);
        } else{
            array_multisort($columns1, SORT_DESC, SORT_NATURAL, $sortList);
        }
        
    } else{
        $columns = array_column($sortList, $sortingText);
        if($sortingOrder == 1){
            array_multisort($columns, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $sortList);
        } else{
            array_multisort($columns, SORT_DESC, SORT_NATURAL|SORT_FLAG_CASE, $sortList);
        }
    }
    return $sortList;
}

?>