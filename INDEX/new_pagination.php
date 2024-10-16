<?php
$indexContent = file_get_contents('page_input.xml');
$jsonContent = file_get_contents("9781023404442_bookpdf_data.json");
$paginationList = json_decode($jsonContent, true);
$paginationNewList = [];
$keyIndex = 0;
if(count($paginationList) > 0){
    foreach ($paginationList as $key => $paginationListvalue) {
        if(count($paginationListvalue['elements']) > 0){
            foreach ($paginationListvalue['elements'] as $key => $listvalue) {
                $paginationNewList[$keyIndex]['ele_id'] = $listvalue['ele_id'];
                $paginationNewList[$keyIndex]['is_continue'] = $listvalue['is_continue'];
                $paginationNewList[$keyIndex]['content'] = $listvalue['content'];
                $paginationNewList[$keyIndex]['parent'] = $listvalue['parent'];
                $paginationNewList[$keyIndex]['page'] = $listvalue['page'];
                $keyIndex++;
            }
        }
    }
}

$domPage = new \DOMDocument ();
$domPage->loadXML($indexContent);
$xpathPagenation = new \DOMXpath($domPage);

$idElementwithoutPagination = $xpathPagenation->query("//id");
if($idElementwithoutPagination->length > 0){
    foreach ($idElementwithoutPagination as $key => $idElementwithoutPaginationvalue) {
        $parentElement = $idElementwithoutPaginationvalue->parentNode;
        $entryValue = $parentElement->getAttribute('entry');
        $elementIdForPage = $idElementwithoutPaginationvalue->getAttribute('ele-id');
        $pageCount = getPageByEleId($paginationNewList, $elementIdForPage, $entryValue);
        $idElementwithoutPaginationvalue->nodeValue = $pageCount;
        echo "<h5>ElementId : ".$elementIdForPage." ---- pagenumber: ".$pageCount."</h5>";
    }
}


function getPageByEleId($array, $ele_id, $entryValue) {
    foreach ($array as $item) {
        if ($item['ele_id'] === $ele_id) {
            if($item['is_continue'] == false){
                return $item['page'];
            }else{
                if (strpos($item['content'],$entryValue) !== false) {
                    return $item['page'];
                }
            }
        }
    }
    return null; // Return null if the ele_id is not found
}


$indexNewValue = $domPage->saveXML();
file_put_contents("page_output.xml", $indexNewValue);


?>