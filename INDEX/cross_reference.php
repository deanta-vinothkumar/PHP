<?php
echo "<h3>Index Cross Reference</h3>";
$finalCleanupOutput = file_get_contents('final_storeid_index.xml');
$finalCrossReferenceOutput = addCrossReferenceLinkForEntries($finalCleanupOutput);
file_put_contents('final_output.xml', $finalCrossReferenceOutput);

function addCrossReferenceLinkForEntries($storeIdOutput){
    $domCross = new \DOMDocument();
    $domCross->loadXML($storeIdOutput);
    $xpath = new \DOMXPath($domCross);
    $seeElementList = $xpath->query('//see');
    if($seeElementList->length > 0){    
        foreach ($seeElementList as $key => $seeElementListvalue) {
            $crossElementValue  = '';
            $seeNodeValue = $seeElementListvalue->nodeValue;
            $linkendId = $seeElementListvalue->getAttribute('linkend');
            if($seeElementListvalue->hasAttribute('crossref-fouthentry')){
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-firstentry');
                $crossElementValue .= ", ".$seeElementListvalue->getAttribute('crossref-secondentry');
                $crossElementValue .= ", ".$seeElementListvalue->getAttribute('crossref-thirdentry').", ";
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-fouthentry');
                appendCrossLinkEntries($seeElementListvalue, 'fouthentry', 'quaternary', $xpath, $domCross, $seeNodeValue, $crossElementValue, $linkendId);
            }elseif($seeElementListvalue->hasAttribute('crossref-thirdentry')){
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-firstentry');
                $crossElementValue .= ", ".$seeElementListvalue->getAttribute('crossref-secondentry').", ";
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-thirdentry');
                appendCrossLinkEntries($seeElementListvalue, 'thirdentry', 'tertiary', $xpath, $domCross, $seeNodeValue, $crossElementValue, $linkendId);
            }elseif($seeElementListvalue->hasAttribute('crossref-secondentry')){
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-firstentry').", ";
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-secondentry');
                appendCrossLinkEntries($seeElementListvalue, 'secondentry', 'secondary', $xpath, $domCross, $seeNodeValue, $crossElementValue, $linkendId);
            }else{
                $crossElementValue .= $seeElementListvalue->getAttribute('crossref-firstentry');
                appendCrossLinkEntries($seeElementListvalue, 'firstentry', 'primary', $xpath, $domCross, $seeNodeValue, $crossElementValue, $linkendId);
            }
        }
    }
    //removing the unwanted class
    $pagenumberClassElement = $xpath->query('//primary[contains(@class, "pagenumber_bold") or contains(@class, "pagenumber_italic")]|//secondary[contains(@class, "pagenumber_bold") or contains(@class, "pagenumber_italic")]|//tertiary[contains(@class, "pagenumber_bold") or contains(@class, "pagenumber_italic")]|//quaternary[contains(@class, "pagenumber_bold") or contains(@class, "pagenumber_italic")]');
    if($pagenumberClassElement->length > 0){    
        foreach ($pagenumberClassElement as $key => $pagenumberClassElementvalue) {
            $pagenumberClassElementvalue->removeAttribute('class');
        }
    }

    $idUnwantedClassElement = $xpath->query('//id[contains(@class, "formatting_bold") or contains(@class, "formatting_italic")]');
    if($idUnwantedClassElement->length > 0){    
        foreach ($idUnwantedClassElement as $key => $idUnwantedClassElementValue) {
            $idUnwantedClassElementValue->removeAttribute('class');
        }
    }

    //Removeing Primary id pagenumber is false
    $primaryEntryWithoutPNo = $xpath->query('//primary[@pagenumber="false"]/id');
    if($primaryEntryWithoutPNo->length > 0){    
        foreach ($primaryEntryWithoutPNo as $key => $primaryEntryValue) {
            $primaryEntryValue->parentNode->removeChild($primaryEntryValue);
        }
    }

    $elements = $xpath->query('//*[@*]'); 
    if($elements->length > 0){
        foreach ($elements as $element) {
            foreach ($element->attributes as $attr) {
                if(strpos($attr->value, '&lt;') !== false || strpos($attr->value, '&gt;') !== false){
                    $newValue = str_replace(['&lt;', '&gt;'], ['[[[LT]]]', '[[[GT]]]'], $attr->value);
                    $element->setAttribute($attr->name, $newValue);
                }
            }
        }
    }

    $outputContent  = $domCross->saveXML();
    $outputContent = str_replace("&amp;", "&", $outputContent);
    $outputContent = str_replace("&lt;", "<", $outputContent);
    $outputContent = str_replace("&gt;", ">", $outputContent);
    return $outputContent;
}



function appendCrossLinkEntries($elementNode, $attributesname, $tagName, $xpath, $dom, $seeNodeValue, $crossElementValue, $linkendId){
  
    $entryValue = $elementNode->getAttribute("crossref-firstentry");
    echo $entryValue."<br>";
    $checkCrossRefasPrimary = $xpath->query('//primary[@entry="'.$entryValue.'"]');
    if($checkCrossRefasPrimary->length > 0){
        echo '<pre>'; print_r($checkCrossRefasPrimary);
        
    }


    $seeAttributesName = $elementNode->getAttribute($attributesname);
    $seeAlso = false;
    $seeTextNode = 'see';

    if ($elementNode->hasAttribute('role') == 'see-also' || $elementNode->hasAttribute('crossref-pagenumber')) {
        $seeAlso = true;
        $seeTextNode = 'see also';
    }

    $italicElement = $dom->createElement('i',$seeTextNode);
    $elementNode->appendChild($italicElement);
    $crossRefTextNode = $dom->createTextNode($crossElementValue);
    $elementNode->appendChild($crossRefTextNode);
    $entryName = $elementNode->getAttribute($attributesname);
    $previousSilbing = $elementNode->parentNode;

    if($seeAlso != true){
        $idElementforSubEntries = $xpath->query("./id", $previousSilbing);
        if($idElementforSubEntries->length > 0){
            $entriesClonedSee = $elementNode->cloneNode(TRUE);
            $previousSilbing->appendChild($entriesClonedSee);
            $elementNode->parentNode->removeChild($elementNode);
            $idElementforSubEntries[0]->parentNode->removeChild($idElementforSubEntries[0]);
        }
    }
}


?>