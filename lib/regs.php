<?

/*
//Inukami!_01.DVD(H264.AAC)[Mendoi-KAA][503C5DFC].mkv
Soul Eater/[Tadashi] SOUL EATER - 08 (720p h264-AAC).nfo
Legend of the Legendary Heroes/[Derp]_Legend_of_the_Legendary_Heroes_-_01_[BD][720p][1B2D85D4].nfo
Level E
*/

function getShowRegs( $title = null, $episode = null, $num = 1 ) {
	$quality = "480|720|1080";
	$formats = "BD|RIP|h264|x264";
	$audio   = "AAC|FLAC|MP3";

	/// Todo: Rip site + show + episode, quality, hash apart
	$showRegs =  array(
	'SHOW1' => '(?:[0-9\-]+)?[a-zA-Z \._!]+(?:2nd Season)?(?:[ \-_.]+[a-zA-Z]+-)?',
	'SHOW2' => '(?:[0-9\-]+)?[a-zA-Z \._!]+(?:2nd Season|[0-9])?(?:[ \-_.]+[a-zA-Z]+-)?',
	'EPISODE' => '(?:[ \-_.]+)?' . 
		'(?:[sS](?<season>[0-9]+))?' . 
		'(?:[eE][pP](?:isode)?)?' . 
		'(?<episode>[0-9]+\.[0-9]+|[0-9]+)'
	);

	$regs = array(
		'(?:\[(?<site>[a-zA-Z\-\.]+)\])?' . 
		'(?:[ \-_.]+)?' . 
		'(?<show>#SHOW#)' . 
		'#EPISODE#' . 
		'(?:[ \-_.]+)?' . 
		'(?:\[(?<site2>[a-zA-Z\-\.]+)\])?' . 
		//'.*\.(?<extension>(mkv|avi|mpg|mpeg|mp4|wmv|ts|rm|mov))' .
		''
		,
		'[\(\[](?<hash>[0-9A-Z]{8})[\]\)]',
		'[\(\[](?<quality>(' . $quality . ')p? ?_?(' . $formats . ')? ?_?(' . $audio . ')?+)[\]\)]',
	);

	$result = array();
	foreach ( $regs as $reg ) {
		foreach ( $showRegs as $regname => $preg ) {
			if ( $title && preg_match("/SHOW/", $regname) ) $preg = $title;
			if ( $episode && $regname == "EPISODE" ) $preg = $episode;
			if ( preg_match('/SHOW' . $num . '/', $regname) ) $regname = "SHOW";
			$reg = str_replace('#' . $regname . '#', $preg, $reg);
		}
		$result[] = $reg;
	}

	return $result;
}

