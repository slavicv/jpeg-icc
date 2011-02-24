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

	return (bool) (substr($f, $pos, 12) == "ICC_PROFILE\x00");
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

$profile_junks = array(); // tu su ulozene jednotlive casti profilu

while ($pos < $len && $counter < 1000)
{
	$pos = strpos($f, "\xff", $pos);
	if ($pos === false) break; // dalsie 0xFF sa uz nenaslo

	// inak analyzujeme dalej

	echo "New Position: $pos \n";
	$type = _type($f, $pos);

	//echo "Type: " . dechex(ord($type)) . PHP_EOL;

	switch ($type)
	{
		case 0xe2:
			echo "APP2 ";
			$size = _size($f, $pos);
			echo "Size: $size\n";

			$bicc = _contains_icc($f, $pos + 4, $size); // 4B offset to data = 2B marker + 2B size
			if ($bicc)
			{
				echo "+ ICC Profile: YES\n";
				$junk_no = ord(substr($f, $pos + 4 + 12, 1)); // 16B offset to data = 2B marker + 2B size + 'ICC_PROFILE' + 0x00
				$junk_cnt = ord(substr($f, $pos + 4 + 13, 1)); // 17B offset to data = 2B marker + 2B size + 'ICC_PROFILE' + 0x00 + junk_no
				echo "+ ICC Profile junk number: $junk_no\n";
				echo "+ ICC Profile junks count: $junk_cnt\n";
				if ($junk_no <= $junk_cnt)
				{
					$data_offset = $pos + 4 + 14; // 4B JPEG APP offset + 14B ICC header offset
					$data_size = $size - 14 - 2; // 14B ICC header - 2B of size data
					$profile_junks[$junk_no] = substr($f, $data_offset, $data_size);
				}

				if ($junk_no >= $junk_cnt) // end of our search...
				{
					$pos = $len;
					break;
				}
			}
			$pos += $size + 2; // size of segment data + 2B size of segment marker
			break;

		case 0xe0: // APP0
		case 0xe1: // APP1
		case 0xe3: // APP3
		case 0xe4: // APP4
		case 0xe5: // APP5
		case 0xe6: // APP6
		case 0xe7: // APP7
		case 0xe8: // APP8
		case 0xe9: // APP9
		case 0xea: // APP10
		case 0xeb: // APP11
		case 0xec: // APP12
		case 0xed: // APP13
		case 0xee: // APP14
		case 0xef: // APP15
		case 0xc0: // SOF0
		case 0xc2: // SOF2
		case 0xc4: // DHT
		case 0xdb: // DQT
		case 0xda: // SOS
		case 0xfe: // COM
			$size = _size($f, $pos);
			$pos += $size + 2; // size of segment data + 2B size of segment marker
			break;

		case 0xd8: // SOI
		case 0xdd: // DRI
		case 0xd9: // EOI
		case 0xd0: // RST0
		case 0xd1: // RST1
		case 0xd2: // RST2
		case 0xd3: // RST3
		case 0xd4: // RST4
		case 0xd5: // RST5
		case 0xd6: // RST6
		case 0xd7: // RST7
		default:
			$pos += 2;
			break;
		
	}

	$counter++;
}


if (count($profile_junks))
{
	ksort($profile_junks);
	file_put_contents('out.icc', implode('', $profile_junks));


}

?>
</pre>