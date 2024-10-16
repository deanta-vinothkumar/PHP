<?php
$chapterAllContent = file_get_contents('output/final_index_CollectAllIndex_allcontents.xml');
echo $chapterAllContent;
$secondLevelDuplication = [];
$domChapter = new \DOMDocument ();
$domChapter->loadXML($chapterAllContent);
$mainNodeX = new \DOMXpath($domChapter);
foreach ($sortList as $list) {
    $ele = $mainNodeX->query("//*[not(self::bookmark or self::see)][@firstentry='".$list['entry']."']|//*[not(self::bookmark or self::see)][@linkend='".$list['refId']."']");
    if($ele->length > 0){
        foreach ($ele as $key => $elevalue) {
            if( $elevalue->getAttribute('linkend') ==  'C16_I032'){
               
               echo "<pre>";print_r($elevalue);
               echo $elevalue->getAttribute('entry');
               echo $elevalue->getAttribute('linkend');
            }
            
            $entryValue = $elevalue->getAttribute('entry');
            $classValue = $elevalue->getAttribute('class');
            if(!empty($list['duplicate']) && $classValue != 'italic' && $classValue != 'bold' && $elevalue->localName == 'primary'){
                $isArrayExist = array_search($entryValue, array_column($indexListEntries, 'entry'));
                $elevalue->setAttribute('duplicate', $list['duplicate']);
            }

            $lowerCaseEntry = strtolower(trim($entryValue))."&&".strtolower(trim($list['entry']));
            if(!in_array($lowerCaseEntry, $secondLevelDuplication)){
                $secondLevelDuplication[$elevalue->getAttribute('store')] = $lowerCaseEntry;
            }else{
                $isArrayExist = array_search($lowerCaseEntry, array_column($indexListEntries, 'entry_node'));
                if(!empty($isArrayExist) && $classValue != 'italic' && $classValue != 'bold' ){
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
                //echo " added the Entry: ".trim($newElementNode)."</br>";
                $indexListEntries[$indexKey]['tagName'] = $elevalue->localName;
                $indexListEntries[$indexKey]['entry'] = trim($newElementNode);
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
        if($ele->length > 0){
            foreach ($ele as $key => $elevalue) {
                if($elevalue->localName == 'see'){
                    $entryValue = $elevalue->textContent;
                    $classValue = $elevalue->getAttribute('class');
                    $seeStoreId = $elevalue->getAttribute('store');
                    $isArrayExist = array_search($entryValue, array_column($indexListEntries, 'entry'));
                    if($isArrayExist == 1  && $classValue != 'italic' && $classValue != 'bold'){
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
file_put_contents("group.xml", $indexNewValue);


?>