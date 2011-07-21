<?php

define("VIDEO_EXTENSIONS", "mkv|avi|mpg|mpeg|mp4|wmv|flv|ts|rm|mov|vob");
function getExtensions() {
	return explode("|", VIDEO_EXTENSIONS);
}
