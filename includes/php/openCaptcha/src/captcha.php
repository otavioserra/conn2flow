<?php
/*
OpenCaptcha v1.2 - Feb. 1, 2007
Copyright (C) 2007 Christopher Craig (chris@chriscraig.net)
http://chriscraig.net/blog/scripts

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//*******************************************************************************************
//Configuration
//*******************************************************************************************
$minStringLength = 4;						//Minimum length of the captcha string.
$maxStringLength = 7;						//Maximum length of the captcha string.
$fontPath = "captcha_fonts/";				//Must only include .ttf fonts.  Include trailing slash.
$backgroundPath = "captcha_backgrounds/";	//Must only include .png images.  Include trailing slash.
$angle = 20;								//The maximum offset for the text angle from 90 degrees.
											//Any charachters you want to show up in the captch string.
$captchaCharacters = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&";
$caseSensitive = false;						//Make the captcha string case-sensitive.

$minFontSize = 12;							//The minimum font size in points.
$maxFontSize = 24;							//The maximum font size in points.
$minLines = 2;								//Minimum number of lines to draw in the background.
$maxLines = 5;								//Maximum number of lines to draw in the background.

$shadow = true;								//Add Drop shadow (true/false)
$shadowOffsetX = 1;							//The x axis shadow offset (only used if $shadow is true)
$shadowOffsetY = 1;							//The y axis shadow offset (only used if $shadow is true)

/*******************************************************************************************/
//DO NOT EDIT BELOW THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING!!
/*******************************************************************************************/

session_start();								

//*******************************************************************************************
//Choose the background image from the $backgroundPath directory.
//*******************************************************************************************

//Find the number of png file in the $backgroundPath.
$backgroundCount = 0;
if ($handle = opendir($backgroundPath)){
	while (false !== ($file = readdir($handle))){
	 	
		 //make sure the file is a .png image file.
		if (substr($file, (strlen($file)-4), 4) == ".png"){
			$backgrounds[$backgroundCount++] = $file;
		}
	}
	closedir($handle);
}

//Stop if no background .png files were found.
if($backgroundCount == 0) die("No background images were found. Can't make captcha image.");

//Pick a random background.
$randomBackground = $backgroundPath . $backgrounds[rand(0,$backgroundCount -1)];

//Find the size of the background for later use in placing the text.
list($bgWidth,$bgHeight,$bgType,$bgAttr) = getimagesize($randomBackground);

//Create a new .png image from $randomBackground.
$captcha = imagecreatefrompng($randomBackground);

//*****************************************************************************************
//Create the text for the captcha image and add it to the image.
//*****************************************************************************************

//Set string length.
$stringLength = rand($minStringLength,$maxStringLength);

//String length can't be 0
if ($stringLength < 1) die("String length can't be less than 1. Can't make captcha image.");

//Make a random string using $captchaCharacters and $stringLength
srand(date('s'));
$string = "";
while(strlen($string) < $stringLength)
	$string .= substr($captchaCharacters, rand()%(strlen($captchaCharacters)),1);

//If $caseSensitive is true, make the captcha string all uppercase.
if (true == $caseSensitive) $string = strtoupper($string);

//Pick random text colors.
$red = rand(0,255);
$blue = rand(0,255);
$green = rand(0,255);
$textColor = imagecolorallocate($captcha, $red, $green, $blue);

//Set the random text angle using $angle.
$angle = rand( (abs($angle) * -1), $angle );

//If $angle is greater than 90 then the text would be upside down.
if (abs($angle) > 90) die("The angle offset must be between 0 and 90 degrees.  Can't make captcha image.");

//Set the text font.
//Find the number of ttf file in the $fontPath.
$fontCount = 0;
if ($handle = opendir($fontPath)){
	while (false !== ($file = readdir($handle))){
	 	//make sure the file is a .ttf font file.
		if (strtoupper(substr($file, (strlen($file)-4), 4)) == ".TTF"){
			$fonts[$fontCount++] = $file;
		}
	}
	closedir($handle);
}

//A font is necessary.
if ($fontCount == 0) die("There are no fonts in the font directory.  Can't create captcha image.");

//Pick a random font.
$randomFont = $fontPath . $fonts[rand(0,$fontCount - 1)];


//Make sure the $minFontSize is smaller or equal to the $maxFontSize
if ($minFontSize < 10 || ($minFontSize > $maxFontSize)) die("The min font size must be greater than 9 and less than or equal to the max font size. Can't make captcha image.");

//Set the font size.
$fontSize = rand($minFontSize,$maxFontSize);

//*****************************************************************************************
//Position the text in the background.
//*****************************************************************************************
//Find the size of the text box.
$textSize = imagettfbbox($fontSize,$angle,$randomFont,$string);

//Set the position of the text.
if ($angle > 0){
	$textBoxWidth = abs($textSize[6]) + abs($textSize[2]);
	$textBoxHeight = abs($textSize[1]) + abs($textSize[5]);
	$textYPos = rand($textBoxHeight, $bgHeight);
}else{
	$textBoxWidth = abs($textSize[0]) + abs($textSize[4]);
	$textBoxHeight = abs($textSize[3]) + abs($textSize[7]);
	$textYPos = rand($textBoxHeight,$bgHeight - $textSize[3]);
}
$textXPos = rand(0,$bgWidth - $textBoxWidth);

//If $shadow is on, write the shadow text first.
if($shadow){
 	$shadowColor = imagecolorallocate($captcha,200,200,200);
	imagettftext($captcha,$fontSize,$angle,$textXPos + $shadowOffsetX,$textYPos + $shadowOffsetY,$shadowColor,$randomFont,$string);	
}

//Write the text to the image.
imagettftext($captcha,$fontSize,$angle,$textXPos,$textYPos,$textColor,$randomFont,$string);

//*****************************************************************************************
//Draw random lines on the background.
//*****************************************************************************************
$lineColor = imagecolorallocate($captcha,0,0,0);

//$minLines must be less than or equal to $maxLines
if ($minLines < 0 || ($minLines > $maxLines)) die("min lines must be 0 or greater, but not larger than max lines. Can't make captcha image.");

//Set the number of lines to draw.
$numLines = rand($minLines,$maxLines);

//Generate lines to cross the image in random places.
for ($i = 0;$i < $numLines; $i++){
	//Pick a random color.
	$red = rand(0,255);
	$blue = rand(0,255);
	$green = rand(0,255);

	//Set the line color.
	$lineColor = imagecolorallocate($captcha,$red,$green,$blue);
	
	//Pick random coordinants.
	$lineStartXPos = rand(0,$bgWidth);
	$lineEndXPos = rand(0,$bgWidth);
	
	//Draw the line from top to bottom.
	imageline($captcha,$lineStartXPos,0,$lineEndXPos,$bgHeight,$lineColor);
}

//Write the captcha code to the session
$_SESSION['captchaText'] = $string;

//Generate filename for image.
$imageFilename = date("YmdHis") . ".png";
//die($_SESSION['captchaText']);
//Output the captcha image
header("Content-type: image/png");
imagepng($captcha);
?>