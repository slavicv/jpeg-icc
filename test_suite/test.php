<pre>
<?
error_reporting(E_ALL);
ini_set('display_errors','On');

//http://www.ibiblio.org/studioforrecording/site01182008/tom.fc8/temp/gimp-2.4.2.not.installed/plug-ins/jpeg/jpeg-icc.c

function _size(&$f, $pos)
{
	$arr = unpack('nint', substr($f, $pos + 2, 2));
	return $arr['int'];
}

function _type(&$f, $pos)
{
	$arr = unpack('Cchar', substr($f, $pos + 1, 1));
	return $arr['char'];
}

function _contains_icc(&$f, $pos, $size)
{
	if ($size < 14) return false; // ICC_PROFILE 0x00 Marker_no Marker_cnt
	
	return (bool) (substr($f, $pos, 11) == 'ICC_PROFILE' && substr($f, $pos + 11, 1) == 0x00);
}

function _save_part(&$f, $pos, $size, $fname)
{
	$data = substr($f, $pos, $size - 2);
	file_put_contents($fname, $data);
}

$f = file_get_contents('in-fogra.jpg');
$len = strlen($f);
//print_r($f);
$pos = 0;
//echo 'a';
$counter = 0;
while ($pos < $len && $counter < 1000)
{
	$pos = strpos($f, 0xff, $pos);
	if ($pos === false) break; // dalsie 0xFF sa uz nenaslo
	
	// inak analyzujeme dalej
	
	//echo "New Position: $pos \n";
	//$type = ord(substr($f, $pos + 1, 1));
	$type = _type($f, $pos);
	
	echo "Type: " . dechex(ord($type)) . PHP_EOL;
	
	switch ($type)
	{
		case 0xd8:
			echo "SOI\n";
			$pos += 2;
			break;
		case 0xc0:
			echo "SOF0 ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xc2:
			echo "SOF2 ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xc4:
			echo "DHT ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xdb:
			echo "DQT ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xdd:
			echo "DRI\n";
			$pos += 2;
			break;
		case 0xda:
			echo "SOS ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xfe:
			echo "COM ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			$pos += $size + 2;
			break;
		case 0xd9:
			echo "EOI\n";
			$pos += 2;
			break;
		case 0xd0:
		case 0xd1:
		case 0xd2:
		case 0xd3:
		case 0xd4:
		case 0xd5:
		case 0xd6:
		case 0xd7:
			$n = substr(dechex($type), 1, 1);
			echo "RST$n\n";
			$pos += 2;
			break;
			
		case 0xe0:
		case 0xe1:
		case 0xe2:
		case 0xe3:
		case 0xe4:
		case 0xe5:
		case 0xe6:
		case 0xe7:
		case 0xe8:
		case 0xe9:
		case 0xea:
		case 0xeb:
		case 0xec:
		case 0xed:
		case 0xee:
		case 0xef:
			$n = hexdec(substr(dechex($type), 1, 1));
			echo "APP$n ";
			$size = _size($f, $pos); // velkost nasledujucich dat (pri niektorych typoch neplati)
			echo "Size: $size\n";
			
			$bicc = _contains_icc($f, $pos + 4, $size); // 4B offset to data = 2B marker + 2B size
			if ($bicc) 
			{
				echo "+ ICC Profile: YES\n";
				//_save_part($f, $pos + 4, $size, 'profile-pro.icc');
			}
			$pos += $size + 2; // size + 2b size of marker
			break;
			
		default:
			$pos += 2;
			break;
	}
	
	//echo "--- Position: $pos\n\n";
	$counter++;
}
?>
</pre>