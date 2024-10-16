<?php
$payload = file_get_contents('output/payload.json');
$payload_decode = json_decode($payload);

$indexResponse['index_delimiter_list'] = resolveIndexdelimiter($payload_decode->indexDelimiterList);
function resolveIndexdelimiter($delimiterList){
    $delimiterArray = json_decode(json_encode($delimiterList), true);
    return $delimiterArray[0];
}
$body_content = file_get_contents('output/final_index_GroupIndexWithSorting.xml');
$delimeterList = [];
//$changexml = indexCleanUpFunction($body_content, $indexResponse['index_delimiter_list']);
//file_put_contents('idElement.xml', $changexml);
$changexml = file_get_contents('idElement.xml');
$duplicatioRemovedContent = removeDuplicationIntexElement($changexml);
file_put_contents('duplicate.xml', $duplicatioRemovedContent);


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
    if($linkEndAttributes == 'C16_I032'){
        echo "<pre>";print_r($linkEndAttributes);
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
            $elementIdforEntry = $duplicateElementvalue->getAttribute('ele-id');
            $classAttributeforEntry = $duplicateElementvalue->getAttribute('class');

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
                
                primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex, $elementIdforEntry, $classAttributeforEntry);

            }
            else{
               primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startpage, $docIndex, $xpathIndex, $elementIdforEntry, $classAttributeforEntry);
            }

        }
    }
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
            $elementIdForParent = $subLevelduplicateElementvalue->getAttribute('ele-id');
            $idElementsubEntries->setAttribute('linkend', $subLevelLinkendAttribute);
            $idElementsubEntries->setAttribute('ele-id', $elementIdForParent);
            
            
            if($existingSubEntries->length > 0){
                $idElementforSubEntries = $xpathIndex->query("./id", $existingSubEntries[0]);
                if($idElementforSubEntries->length > 0){
                    $checkIdAlreadyExist = $xpathIndex->query("./id[@linkend='".$subLevelLinkendAttribute."']", $existingSubEntries[0]);
                    $idElementforSubEntries[0]->parentNode->insertBefore($idElementsubEntries, $idElementforSubEntries[0]->nextSibling);
                    if($checkIdAlreadyExist->length > 0){
                        $checkIdAlreadyExist[0]->parentNode->removeChild($checkIdAlreadyExist[0]); 
                    }

                    $seeElementIsExistSecondlevel = $xpathIndex->query(".//see|.//tertiary|.//quaternary", $subLevelduplicateElementvalue);
                    if($seeElementIsExistSecondlevel->length > 0){
                        foreach ($seeElementIsExistSecondlevel as $key => $seeElementIsExistvalue) {
                            $seeClonedElement = $seeElementIsExistvalue->cloneNode(TRUE);
                            $seeClonedElement->setAttribute("role", "see-also");
                            $existingId = $xpathIndex->query(".//id[last()]", $existingSubEntries[0]);
                            if($existingId->length > 0){
                                $existingId[0]->parentNode->insertBefore($seeClonedElement, $existingId[0]->nextSibling);
                            }
                            if($seeElementIsExistvalue->localName == "see"){
                                $seeClonedElement->setAttribute("role", "see-also");
                                $existingId = $xpathIndex->query(".//id[last()]", $existingSubEntries[0]);
                                if($existingId->length > 0){
                                    $existingId[0]->parentNode->insertBefore($seeClonedElement, $existingId[0]->nextSibling);
                                }
                            }else{
                                $existingSubEntries[0]->appendChild($seeClonedElement);
                            }
                        }
                    }

                    $subLevelduplicateElementvalue->parentNode->removeChild($subLevelduplicateElementvalue); 
                
                }  
            }
            
        }
    }
    $outputContent  = $docIndex->saveXML();
    return $outputContent;
    
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


function primaryIdElementMovement($duplicateAttribute, $IndexparentNode, $linkendAttribute, $pageAttribute, $startPage, $docIndex, $xpathIndex, $elementIdforEntry, $classAttributeforEntry){
    $orginalIndextermNode = $xpathIndex->query("//indexterm[@store='".$duplicateAttribute."']");
    $seeElementIsExist = $xpathIndex->query(".//see|.//secondary|.//tertiary|.//quaternary", $IndexparentNode);
    if($seeElementIsExist->length == 0){ 
        if($orginalIndextermNode->length > 0){
            $idElement = $docIndex->createElement("id", (int)$pageAttribute + (int)$startPage);
            $idElementLinkend = $idElement->setAttribute('linkend', $linkendAttribute);
            $idElement->setAttribute('class', $classAttributeforEntry);
            $eleId = $idElement->setAttribute('ele-id', $elementIdforEntry);
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
    }else{
        if($orginalIndextermNode->length > 0){
            $idElement = $docIndex->createElement("id", (int)$pageAttribute + (int)$startPage);
            $idElementLinkend = $idElement->setAttribute('linkend', $linkendAttribute);
            $idElement->setAttribute('class', $classAttributeforEntry);
            $eleId = $idElement->setAttribute('ele-id', $elementIdforEntry);
            $setIsPrimaryAttributes = $idElement->setAttribute('primary','true');
            $existingIdElementPrimary = $xpathIndex->query("./primary", $orginalIndextermNode[0]);
            if($existingIdElementPrimary->length > 0){
                $existingIdElementPrimary[0]->appendChild($idElement);
            } else{
                $orginalIndextermNode[0]->parentNode->insertBefore($idElement, $orginalIndextermNode[0]->nextSibling);
            }
        
            if($seeElementIsExist->length > 0){
                foreach ($seeElementIsExist as $key => $seeElementIsExistvalue) {
                    
                    $seeClonedElement = $seeElementIsExistvalue->cloneNode(TRUE);
                    if($seeElementIsExistvalue->localName == "see"){
                        $seeClonedElement->setAttribute("role", "see-also");
                        $existingId = $xpathIndex->query(".//id[last()]", $orginalIndextermNode[0]);
                        if($existingId->length > 0){
                            $existingId[0]->parentNode->insertBefore($seeClonedElement, $existingId[0]->nextSibling);
                        }
                    }else{
                        $existingIdElementPrimary = $xpathIndex->query("./primary", $orginalIndextermNode[0]);
                        if($existingIdElementPrimary->length > 0){
                            $existingIdElementPrimary[0]->appendChild($seeClonedElement);
                        }
                    }
                    
                }
                $IndexparentNode->parentNode->removeChild($IndexparentNode); 
            }
        }
    }
}
