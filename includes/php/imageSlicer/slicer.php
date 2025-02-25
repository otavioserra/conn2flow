<?php
/*
The purpose of this class is to generate
image pieces by slicing the image
specified by coordinate x,y and width,heigth
*/


class Slicer {

	var $picture			= 	"";
	var $pic_width			= 	0;
	var	$pic_height			=	0;
	var $slice_width		= 	0;
	var	$slice_height		=	0;
	var $slice_hor			= 	0;
	var	$slice_ver			=	0;
	var	$img_pieces			=	false;

	function Slicer() {
	}

	function set_picture($pic) {

		$this->picture = $pic;

	}

	function get_width() {
		return $this->pic_width;

	}

	function set_slicing($horizontal,$vertical) {
		$this->slice_hor = $horizontal;
		$this->slice_ver = $vertical;

	}

	function set_slice_res($horizontal,$vertical) {
		$this->slice_width = $horizontal;
		$this->slice_height = $vertical;

	}

	function show_slice($type="jpg",$n) {
		/* make it not case sensitive*/
		$this->img_type = strtolower($type);


		$picture = $this->prepare_pieces($n);

		/* show the images  */			
		switch($this->img_type){
			case 'jpeg' :
			case 'jpg' 	:
				header("Content-type: image/jpeg");
				imagejpeg($picture);
				break;
			case 'gif' :
				header("Content-type: image/gif");
				imagegif($picture);
				break;
			case 'png' :
				header("Content-type: image/png");
				imagepng($picture);
				break;
			case 'wbmp' :
				header("Content-type: image/vnd.wap.wbmp");
				imagewbmp($picture);
				break;
		}

	}

	function save_slices($type="jpg",$output) {
		/* make it not case sensitive*/
		$this->img_type = strtolower($type);

		$horizontal = $this->slice_hor;
		$vertical = $this->slice_ver;
		
		$max = $horizontal*$vertical;
		
		$this->image_pieces();
		
		for($i=0;$i<count($this->img_pieces);$i++){
			$picture = $this->img_pieces[$i];
			imagejpeg($picture, $output . $i . "." . $type , 100);
		}
	}

	function save_slices_res($type="jpg",$output) {
		/* make it not case sensitive*/
		$this->img_type = strtolower($type);
		$this->image_pieces_res();
		
		for($i=0;$i<count($this->img_pieces);$i++){
			$picture = $this->img_pieces[$i];
			imagejpeg($picture, $output . $i . "." . $type , 100);
		}
	}

	function load_picture() {

		/* pick picture you want to frame  */
		if(file_exists($this->picture)) {

			$extension = $this->get_imagetype($this->picture);

			/* create image source from your image strip stock */
			switch($extension){
				case 'jpeg' :
				case 'jpg' 	:
					$img_picture 	= @imagecreatefromjpeg ($this->picture);
					break;
				case 'gif' :
					$img_picture 	= @imagecreatefromgif ($this->picture);					
					break;
				case 'png' :
					$img_picture 	= @imagecreatefrompng ($this->picture);	
					break;
			}

		} else {
						/* if fail to load image file, create it on the fly */
			$img_picture 	= $this->draw_picture();
		}

		return $img_picture;
		imagedestroy( $img_picture );
	}

	function draw_picture() {

		if($this->img_image) {
		
			return $this->img_image;
		
		} else {		
			if(!$this->pic_height)
				$this->set_size(300,200);
	
			$img_picture    = imagecreatetruecolor($this->pic_width, $this->pic_height);
			$bg_color		= imagecolorallocate ($img_picture, 200, 200, 200);
			imagefill ( $img_picture, 0, 0, $bg_color );
		}
		return $img_picture;
		imagedestroy ($img_picture);


	}

	function set_image($image) {
	
		$this->img_image = $image;
	
	}

	function get_imagetype($file) {

		$acceptable = array("jpg","jpeg","gif","png");
		/* ask the image type */
		$file_info  = pathinfo($file);
		$extension  = $file_info["extension"];
		
		if(in_array($extension,$acceptable))
			return $extension;
		else
			return null;
	}

	function image_pieces() {		

		$img_picture		= 	$this->load_picture(); 

 		$this->pic_width 	= 	imagesx($img_picture); 
		$this->pic_height 	= 	imagesy($img_picture); 

		/* slice into hor x ver pieces */
		$slice_width	= $this->pic_width/$this->slice_hor;
		$slice_height	= $this->pic_height/$this->slice_ver;

		$x 				= 0;
		$y				= 0;
		$w				= $slice_width;
		$h				= $slice_height;
		$k=1;
		for($i=0 ; $i<$this->slice_ver ; $i++){
			for($j=0 ; $j<$this->slice_hor ; $j++){
				$this->img_pieces[]	= $this->slice_image($img_picture,$x,$y,$w,$h);
				$x	= $x + $w;
				$k++;
			}
			$x = 0;
			$y = $y + $h;

 		}
	}

    function image_pieces_res() {

		$img_picture		= 	$this->load_picture(); 

 		$this->pic_width 	= 	imagesx($img_picture); 
		$this->pic_height 	= 	imagesy($img_picture); 

		$num_slices_x = floor($this->pic_height/$this->slice_height) + ( $this->pic_height % $this->slice_height == 0 ? 0 : 1);
		$num_slices_y = floor($this->pic_width/$this->slice_width) + ( $this->pic_width % $this->slice_width == 0 ? 0 : 1);
		
		$this->set_slicing($num_slices_x,$num_slices_y);
		
		$last_slice_width = $this->pic_width % $this->slice_width;
		$last_slice_height = $this->pic_height % $this->slice_height;
		
		$x 				= 0;
		$y				= 0;
		$w				= $this->slice_width;
		$h				= $this->slice_height;
		$k=1;
		
		for($i=0;$i<$num_slices_x;$i++){
			for($j=0;$j<$num_slices_y;$j++){
				if($i == $num_slices_x - 1 && $last_slice_height > 0)
					$nh = $last_slice_height;
				else
					$nh = $h;
				
				if($j == $num_slices_y - 1 && $last_slice_width > 0)
					$nw = $last_slice_width;
				else
					$nw = $w;
				
				$this->img_pieces[]	= $this->slice_image($img_picture,$x,$y,$nw,$nh);
				
				$x	= $x + $w;
			}
			$x = 0;
			$y = $y + $h;

 		}
	}

    function prepare_pieces($n) {		

		$img_picture		= 	$this->load_picture(); 

 		$this->pic_width 	= 	imagesx($img_picture); 
		$this->pic_height 	= 	imagesy($img_picture); 

		/* slice into hor x ver pieces */
		$slice_width	= $this->pic_width/$this->slice_hor;
		$slice_height	= $this->pic_height/$this->slice_ver;

		$x 				= 0;
		$y				= 0;
		$w				= $slice_width;
		$h				= $slice_height;
		$k=1;
		for($i=0 ; $i<$this->slice_ver ; $i++){
			for($j=0 ; $j<$this->slice_hor ; $j++){
				if($k==$n)
					$img_piece	= $this->slice_image($img_picture,$x,$y,$w,$h);
				$x	= $x + $w;
				$k++;
			}
			$x = 0;
			$y = $y + $h;

 		}
		return $img_piece;

	}

    function slice_image($img_src, $x, $y, $width, $height) {
   
		$img_slice = imagecreatetruecolor($width, $height);
		imagecopyresampled($img_slice, $img_src, 0, 0, $x, $y, $width, $height, $width, $height);
        return $img_slice;
		imagedestroy( $img_slice );
    }

}
?>