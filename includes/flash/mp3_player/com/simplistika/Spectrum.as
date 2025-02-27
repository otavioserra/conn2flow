//----------------------------------------------------------------------------------------------------
//	Spectrum.as
//----------------------------------------------------------------------------------------------------
package com.simplistika 
{
import flash.display.Bitmap;
import flash.display.BitmapData;
import flash.display.Sprite;
import flash.geom.Rectangle;
import flash.media.SoundMixer;
import flash.utils.ByteArray;

//----------------------------------------------------------------------------------------------------
//	class definition
//----------------------------------------------------------------------------------------------------
public class Spectrum extends Sprite 
{

//----------------------------------------------------------------------------------------------------
//	memeber data
//----------------------------------------------------------------------------------------------------
protected var mBMP:BitmapData;
protected var mWidth:Number = 295;
protected var mSpectrum:ByteArray;

//----------------------------------------------------------------------------------------------------
//	constructor
//----------------------------------------------------------------------------------------------------
public function 
Spectrum(
) : void 
{
	var vBitmap : Bitmap;
	
	mBMP = new BitmapData(mWidth, 50, true, 0x000000); 					//width, height, transp, fill
	vBitmap = new Bitmap(mBMP);
	mSpectrum = new ByteArray();
	addChild(vBitmap);
	
}

//----------------------------------------------------------------------------------------------------
//	fUpdate
//----------------------------------------------------------------------------------------------------
public function 
fUpdate(
) : void 
{	
	SoundMixer.computeSpectrum(mSpectrum);
	mBMP.fillRect(mBMP.rect, 0x000000);
	for (var i:int=0; i<mWidth; i++)
		mBMP.setPixel32(i, 10 + mSpectrum.readFloat() * 20, 0xAAAAAAAA);//mSpectrum color
		
}
//----------------------------------------------------------------------------------------------------
} // class
//----------------------------------------------------------------------------------------------------
} // package