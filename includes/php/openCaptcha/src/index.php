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

// This file is meant as a general guide for how to use Open Captcha.

session_start(); //necessary for Open Captcha.

//If the form has been submitted, $_POST['isSubmitted'] will be TRUE
if ($_POST['isSubmitted'] == TRUE){
	//Check that the captcha text submitted by the user equals the captcha text stored in the session
	if ($_POST['captcha'] == $_SESSION['captchaText']){
		echo "<p>Congratulations!  You entered the correct code.</p>";
		
		//Unset the session variable, so we can use it again.
		unset ($_SESSION['captchaText']);

		/*At this point, we'd do something like this:
		header("Location: http://somedomain.com/somepage.html");
		*/
	}else{
		echo "<p>I'm sorry :(  The code you entered did not match the image. Please try again.</p>";
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>OpenCaptcha v1.2</title>
	<script type = "text/javascript">
		// This function is used in combination with a refresh button to get a new capthca image
		// in case the user cannot read the current one.  The page is not reloaded to accomplish this.
		
		function getCaptcha(){
		 	var d = new Date();
			document.getElementById('captchaImage').setAttribute('src', 'captcha.php?r='+ d.getTime());
		}
	</script>
</head>

<body>
<p>This is a sample page that demonstrates how OpenCaptcha v1.2 works.</p>

<form name = "captcha" method = "post" action = "<?php $_SERVER['php_self']; ?>">
	<input type = "hidden" name = "isSubmitted" id = "isSubmitted" value = "TRUE" />
	<img id = "captchaImage" src = "captcha.php"><br />
	<input type = "button" id = "refresh" value = "Refresh Image" onclick = "getCaptcha();" /><br /><br />
	<label>Captcha Text: </label>
	<input type = "text" name = "captcha" id = "captcha" /><br />
	<input type = "submit" value = "Submit" />
</form>

</body>
</html>