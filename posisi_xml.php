<?php

include "koneksi.php";

try{
	$time = isset($_GET['t'])?$_GET['t'] : '07:00';
	$comp = isset($_GET['c'])?$_GET['c'] : '0';
	$cc = substr($time,0,1);
	$tz = ($cc == "-") ? $time :  "+".$time ; 
	
	$q_wkt = "select max(convert_tz(from_unixtime(d1.epochtime),'+07:00','$tz')) wkt 
				from data d1 
					join titik_ukur tu1 on tu1.id_titik_ukur = d1.id_titik_ukur 
					join ship s on s.id_ship = tu1.id_ship
				where s.status = 1
				group by tu1.id_ship;";
	
	$stm = $conn->prepare($q_wkt);
	$stm->execute();
	$hsl = $stm->fetchAll(PDO::FETCH_OBJ);

	$a = "'";
	foreach ($hsl as $s){
		$a .= $s->wkt."','";
	}
	
	$b = substr($a,0,-2);
	
	$c = "select tu.id_ship id, s.name ves, convert_tz(from_unixtime(d.epochtime),'+07:00','$tz') wkt,
			max(case when tu.id_data_type = 1 then round(d.value,2) end) lat, 
			max(case when tu.id_data_type = 2 then round(d.value,2) end) lng 
		from data d 
			join titik_ukur tu on tu.id_titik_ukur = d.id_titik_ukur
			join ship s on s.id_ship = tu.id_ship
		where convert_tz(from_unixtime(d.epochtime),'+07:00','$tz') in ($b)
		group by tu.id_ship;";
	
	$stm = $conn->prepare($c);
	$stm->execute();
	$posisi = $stm->fetchAll(PDO::FETCH_OBJ);

	
	//================================================================================
	
	$dom = new DOMDocument('1.0', 'UTF-8');
	$node = $dom->createElementNS("http://www.opengis.net/kml/2.2", 'kml');
	$ParentNode = $dom->appendChild($node);

		$documentNode = $dom->createElement('Document');
		$doc = $ParentNode->appendChild($documentNode);

		$docnameNode = $dom->createElement('name','Vessel Tracking');
		$doc->appendChild($docnameNode);
		
		$docdescNode = $dom->createElement('description','Tracking for Marine Vessel');
		$doc->appendChild($docdescNode);
		
		$folderNode = $dom->createElement('Folder');
		$parFolder = $doc->appendChild($folderNode);
			$foldnameNode = $dom->createElement('name','Position');
			$parFolder->appendChild($foldnameNode);
			
			foreach($posisi as $pos){
				$placemark = $dom->createElement('Placemark');
				$placenode = $parFolder->appendChild($placemark);
					$placenama = $dom->createElement('name',$pos->ves);
					$placenode->appendChild($placenama);
					
					$wkt = $dom->createElement('time', $pos->wkt);
					$placenode->appendChild($wkt);
					
					$point = $dom->createElement('Point');
					$pointNode = $placenode->appendChild($point);
					
					$coord = $pos->lng.",".$pos->lat;
					$koordinat = $dom->createElement('coordinates',$coord);
					$pointNode->appendChild($koordinat);
			}
			
		
		
		$folderNode = $dom->createElement('Folder');
		$parFolder = $doc->appendChild($folderNode);
			$foldnameNode = $dom->createElement('name','Tracking 24H');
			$parFolder->appendChild($foldnameNode);
		

		$folderNode = $dom->createElement('Folder');
		$parFolder = $doc->appendChild($folderNode);
			$foldnameNode = $dom->createElement('name','Tracking Today');
			$parFolder->appendChild($foldnameNode);		
				

	$kmlOutput = $dom->saveXML();
		// header('Content-type: application/vnd.google-earth.kml+xml');
	header('Content-type: application/xml');
	echo $kmlOutput;
	
	
	//================================================================================
	
}catch(Exception $e){
	echo $e->getMessage();
}

?>