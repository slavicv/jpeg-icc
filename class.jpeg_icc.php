<?php
/**
 * Description of class
 *
 * @author risko
 */
class JPEG_ICC
{
    private $icc_profile = '';
    private $icc_size = 0;
    private $icc_junks = 0;

    /**
     * ICC header size in APP2 segment
	 *
	 * 'ICC_PROFILE' 0x00 junk_no junk_cnt
     */
    const ICC_HEADER_LEN = 14;

    /**
     * maximum data len of a JPEG marker
     */
    const MAX_BYTES_IN_MARKER = 65533;

	/**
	 * ICC header marker
	 */
	const ICC_MARKER = "ICC_PROFILE\x00";

	/**
	 * Rendering intent field (Bytes 64 to 67 in ICC profile)
	 */
	const ICC_RI_PERCEPTUAL = 0x00000000;
	const ICC_RI_RELATIVE_COLORIMETRIC = 0x00000001;
	const ICC_RI_SATURATION = 0x00000002;
	const ICC_RI_ABSOLUTE_COLORIMETRIC = 0x00000003;

    public function  __construct()
    {
    }

    public function LoadFromJPEG($fname)
    {
		$f = file_get_contents($fname);
		$len = strlen($f);
		$pos = 0;
		$counter = 0;
		$profile_junks = array(); // tu su ulozene jednotlive casti profilu

		while ($pos < $len && $counter < 1000)
		{
			$pos = strpos($f, "\xff", $pos);
			if ($pos === false) break; // dalsie 0xFF sa uz nenaslo - koniec vyhladavania

			// inak analyzujeme dalej
			$type = $this->getJPEGSegmentType($f, $pos);

			switch ($type)
			{
				case 0xe2: // APP2
					echo "APP2 ";
					$size = $this->getJPEGSegmentSize($f, $pos);
					echo "Size: $size\n";
					
					if ($this->getJPEGSegmentContainsICC($f, $pos, $size))
					{
						echo "+ ICC Profile: YES\n";
						list($junk_no, $junk_cnt) = $this->getJPEGSegmentICCJunkInfo($f, $pos);
						echo "+ ICC Profile junk number: $junk_no\n";
						echo "+ ICC Profile junks count: $junk_cnt\n";

						if ($junk_no <= $junk_cnt)
						{
							$profile_junks[$junk_no] = $this->getJPEGSegmentICCJunk($f, $pos);

							if ($junk_no == $junk_cnt) // posledny kusok
							{
								ksort($profile_junks);
								$this->SetProfile(implode('', $profile_junks));
								return true;
							}
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
					$size = $this->jpegSementSize($f, $pos);
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

		return false;
    }

    public function SaveToJPEG($fname)
    {

    }

    public function LoadFromICC($fname)
    {
		if (!file_exists($fname)) throw new Exception("File $fname doesn't exist.\n");
		if (!is_readable($fname)) throw new Exception("File $fname isn't readable.\n");

		$this->SetProfile(file_get_contents($fname));
    }

    public function SaveToICC($fname)
    {
		if ($this->icc_profile == '') throw new Exception("No profile loaded.\n");
		$dir = realpath($fname);
		if (!is_writable($dir)) throw new Exception("Directory $fname isn't writeable.\n");
		if (file_exists($fname)) throw new Exception("File $fname exists.\n");

		$ret = file_put_contents($fname, $this->icc_profile);
		if ($ret === false || $ret < $this->icc_size) throw new Exception ("Write failed.\n");
	}

    public function RemoveFromJPEG($fname)
    {

    }

    public function SetProfile($data)
    {
		$this->icc_profile = $data;
		$this->icc_size = strlen($data);
		$this->countJunks();
    }

    public function GetProfile()
    {
		return $this->icc_profile;
    }

    private function countJunks()
    {
		$this->icc_junks = ceil($this->icc_size / ((float) (self::MAX_BYTES_IN_MARKER - self::ICC_HEADER_LEN)));
    }

	private function setRenderingIntent($newRI)
	{
		if ($this->icc_size >= 68)
		{
			substr_replace($this->icc_profile, pack('N', $newRI), 64, 4);
		}
	}

	/**
	 * Get value of Rendering intent field in ICC profile
	 *
	 * @return		int
	 */
	private function getRenderingIntent()
	{
		if ($this->icc_size >= 68)
		{
			$arr = unpack('Nint', substr($this->icc_profile, 64, 4));
			return $arr['int'];
		}

		return null;
	}

	/**
	 * Size of JPEG segment
	 *
	 * @param		string		file data
	 * @param		int			start of segment
	 * @return		int
	 */
	private function getJPEGSegmentSize(&$f, $pos)
	{
		$arr = unpack('nint', substr($f, $pos + 2, 2)); // segment size has offset 2 and length 2B
		return $arr['int'];
	}

	/**
	 * Type of JPEG segment
	 *
	 * @param		string		file data
	 * @param		int			start of segment
	 * @return		int
	 */
	private function getJPEGSegmentType(&$f, $pos)
	{
		$arr = unpack('Cchar', substr($f, $pos + 1, 1)); // segment type has offset 1 and length 1B
		return $arr['char'];
	}

	/**
	 * Check if segment contains ICC profile marker
	 *
	 * @param		string		file data
	 * @param		int			position of segment data
	 * @param		int			size of segment data (without 2 bytes of size field)
	 * @return		bool
	 */
	private function getJPEGSegmentContainsICC(&$f, $pos, $size)
	{
		if ($size < self::ICC_HEADADER_LEN) return false; // ICC_PROFILE 0x00 Marker_no Marker_cnt

		return (bool) (substr($f, $pos + 4, self::ICC_HEADER_LEN - 2) == self::ICC_MARKER); // 4B offset in segment data = 2B segment marker + 2B segment size data
	}

	/**
	 * Get ICC segment junk info
	 *
	 * @param		string		file data
	 * @param		int			position of segment data
	 * @return		array		{junk_no, junk_cnt}
	 */
	private function getJPEGSegmentICCJunkInfo(&$f, $pos)
	{
		return unpack('Cjunk_no/Cjunk_count', substr($f, $pos + 16, 2)); // 16B offset to data = 2B segment marker + 2B segment size + 'ICC_PROFILE' + 0x00, 1. byte junk number, 2. byte junks count
	}

	private function getJPEGSegmentICCJunk(&$f, $pos)
	{
		$data_offset = $pos + 4 + self::ICC_HEADER_LEN; // 4B JPEG APP offset + 14B ICC header offset
		$data_size = $size - self::ICC_HEADER_LEN - 2; // 14B ICC header - 2B of size data
		return substr($f, $data_offset, $data_size);
	}

	/**
	 * Get data of given junk
	 *
	 * @param		int			junk number
	 * @return		string
	 */
	private function getJunk($junk_no)
	{
		if ($junk_no > $this->icc_junks) return '';
		
		$max_junk_size = self::MAX_BYTES_IN_MARKER - self::ICC_HEADER_LEN;
		$from = ($junk_no - 1) * $max_junk_size;
		$bytes = ($junk_no < $this->icc_junks) ? $max_junk_size : $this->icc_size % $max_junk_size;

		return substr($this->icc_profile, $from, $bytes);
	}
}
?>
