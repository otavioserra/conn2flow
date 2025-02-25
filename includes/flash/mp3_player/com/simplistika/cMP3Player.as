package com.simplistika 
{
import flash.display.*;
import flash.text.*;
import flash.events.*;
import flash.media.*;
import flash.net.*;
import flash.geom.*;
import flash.filters.GlowFilter;

//----------------------------------------------------------------------------------------------------
//	class definition
//----------------------------------------------------------------------------------------------------
public class cMP3Player extends cXMLApp 
{
//----------------------------------------------------------------------------------------------------
//	member data
//----------------------------------------------------------------------------------------------------
private var mChannel : SoundChannel;
private var mSound : Sound;
private var mPosition : int;
private var mCurrentPosition : int;
private var mVolume : int;
private var mTrack : int = 0;
private var mLoading : Boolean;
private var mVisual : Spectrum;
private var mBoundt : Rectangle; 	//for track scrubber
private var mBoundv : Rectangle; 	//for volume scrubber
private var mBoundp : Rectangle; 	//for panning
private var glowOn : GlowFilter = new GlowFilter(0xFFFFFF,1,5,5,2,2,false,false);
private var glowOff : GlowFilter = new GlowFilter(0xFFFFFF,0,5,5,2,2,false,false);
private var mScrubber : String;
private var mTotalSec : String;

//----------------------------------------------------------------------------------------------------
//	constructor
//----------------------------------------------------------------------------------------------------
public function cMP3Player() : void
{
	var paramObj:Object = LoaderInfo(this.root.loaderInfo).parameters;
	
	super("mp3_player.php?xml=1&mp3_player=1&mp3_id="+paramObj['mp3_id']);	// load in xmlfile
	super.addEventListener("XMLLoaded", fStart);						// once its completed loading, goto init()			
}

public function stopPlayer():void{
	trace("STOP");
	mState = "STOP";
	mChannel.stop();				
	tBar.tBarKnob.x = 0;
	mCurrentPosition = 0;
}

public function playPlayer(param:Array):void{
	var numMp3:int;
	trace("PLAY");
	if(param['mp3_num'])	numMp3 = param['mp3_num']; else numMp3 = mCurrentPosition;
	mChannel = mSound.play(numMp3);
	mState = "PLAY";
}

private function fStart(e : Event) : void
{
	trace(this + " " + "cMP3Player.fStart()");
	
	var i : int;	
	
	stage.addEventListener(Event.ENTER_FRAME, fRefresh, false, 0, true);
	stage.addEventListener(MouseEvent.MOUSE_DOWN, fOnMouseDown, false, 0, true);
	stage.addEventListener(MouseEvent.MOUSE_UP, fOnMouseUp, false, 0, true);
	stage.addEventListener(MouseEvent.CLICK, fOnClick, false, 0, true);

	// visualization settings
	mVisual = new Spectrum();
	mVisual.x = mBg.x + 2;
	mVisual.y = mBg.y + 45;
	addChild(mVisual);
	setChildIndex(mVisual, 1);

	// scrubber settings
	mBoundt = new Rectangle(0,tBar.tBarKnob.y,tBar.tBarBg.width,0);	// boundary so scrubber will stay within the tBarBg	
	mBoundv = new Rectangle(0,vBar.vBarKnob.y,vBar.vBarBg.width,0);	// boundary so volume will stay within the vBarBg
	mBoundp = new Rectangle(0,pBar.pBarKnob.y,pBar.pBarBg.width,0);	// boundary so panning will stay within the pBarBg
	vBar.vBarKnob.x = vBar.vBarBg.width;
	pBar.pBarKnob.x = pBar.pBarBg.width / 2;
	
	mLoading = true;												// Starts playing on run
	mSound = new Sound(new URLRequest(mData.mp3[0].url));	
	//mChannel = mSound.play();
	//mState = "PLAY";

	// to stop playing when it starts just uncomment these 3 lines
	// mState = "STOP";
	// pbStop.addEventListener("dontPlay", fOnClick);
 	// pbStop.dispatchEvent(new MouseEvent("dontPlay"));
	
}

private function fOnComplete(e : Event) : void
{
	if (txtRepeat.text == "REPEAT ON")
		mChannel.stop();
	else
	{
		mTrack++;
		mChannel.stop();
		if (mTrack > mItems - 1)
			mTrack = 0;
	}
	fLoad(mTrack,0);
}

protected function 
fOnMouseDown(e : MouseEvent) : void
{
	if (e.target.name.substring(1, 4) == "Bar" && e.target.name.substring(1, 5) == "Knob")
	{
		this[e.target.parent.name][e.target.name].filters = [glowOn];
		this[e.target.parent.name][e.target.name].startDrag(true, this["mBound" + e.target.name.substring(0, 1)]);
		mScrubber = e.target.parent.name;
	}
	else if (e.target.name.substring(1, 4) == "Bar" && e.target.name.substring(1, 5) != "Knob")
	{
		this[e.target.parent.name][e.target.parent.name.substring(0, 1) + "BarKnob"].filters = [glowOn];
		this[e.target.parent.name][e.target.parent.name.substring(0, 1) + "BarKnob"].startDrag(true, this["mBound" + e.target.name.substring(0, 1)]);
		mScrubber = e.target.parent.name;
	}
}

private function fOnMouseUp(e : MouseEvent) : void
{
	if (mScrubber != null)
	{		
		this[mScrubber][mScrubber + "Knob"].stopDrag();
		this[mScrubber][mScrubber + "Knob"].filters = [glowOff];
		
		switch (mScrubber)
		{
		case "tBar":
			switch (mState)
			{
			case "PLAY":
				mChannel.stop();
				mChannel = mSound.play(mSound.length / 100 * Math.floor(tBar.tBarKnob.x/(tBar.tBarBg.width)*100));
				break;
				
			case "STOP":
				mCurrentPosition = mSound.length / 100 * Math.floor(tBar.tBarKnob.x/(tBar.tBarBg.width)*100);
				break;
			}
			break;
		}				
		mScrubber = null;
	}
}

private function fOnClick(e : MouseEvent) : void
{
	switch (e.target.name)
	{
		case "pbForward":
			mTrack++;
			if (mTrack > mItems - 1)
				mTrack = 0;			
			break;
			
		case "pbBack":			
			mTrack--;
			if (mTrack < 0)
				mTrack = mItems - 1;
			break;
			
		case "pbPlayPause":
			if (mState == "PLAY") 
			{
				mCurrentPosition = mPosition;
				mChannel.stop();
				mState = "STOP";
			}
			else
			{
				mChannel = mSound.play(mCurrentPosition);
				mState = "PLAY";
			}
			return;
		
		case "pbStop":
			mState = "STOP";
			mChannel.stop();				
			tBar.tBarKnob.x = 0;
			mCurrentPosition = 0;
			return;
			
		case "hsConfig_Repeat":
			txtRepeat.text == "REPEAT OFF" ? txtRepeat.text = "REPEAT ON" : txtRepeat.text = "REPEAT OFF";
			return;
		
		default:
			return;
			
	}
	if (mState == "STOP")
		mState = "PLAY";
	mChannel.stop();
	fLoad(mTrack, 0);	
}

private function fLoad(vTrack : int, vPos : int) : void
{
	if (mLoading == true)
	{
		mSound.close();
		mLoading = false;
	}
	mChannel.stop();
	mSound = new Sound(new URLRequest(mData.mp3[vTrack].url));
	mChannel = mSound.play(vPos);
}

private function fRefresh(e : Event) : void
{
	mChannel.soundTransform = new SoundTransform(vBar.vBarKnob.x / vBar.vBarBg.width, - 1 + 2 * (pBar.pBarKnob.x / pBar.pBarBg.width));
	mChannel.addEventListener(Event.SOUND_COMPLETE, fOnComplete, false, 0, true);
	
	if (mSound.bytesTotal > 0)										// if mp3 is successfully loaded
	{
		if (mSound.bytesLoaded < mSound.bytesTotal) 				// show loading %
		{
			txtLoaded.text = Math.floor(mSound.bytesLoaded / mSound.bytesTotal * 100).toString() + "%";
			mLoading = true;
		}
		else
		{
			txtLoaded.text = "";
			mLoading = false;
		}

		tBar.tScrubBarBg.width = 184 * mSound.bytesLoaded / mSound.bytesTotal;
		tBar.tBarBg.width = 172 * mSound.bytesLoaded / mSound.bytesTotal;
		mPosition = mChannel.position;								// current position off track in miliseconds
		txtDisplay.text = (String(mData.mp3[mTrack].artist)?String(mData.mp3[mTrack].artist) + " - ":"") + String(mData.mp3[mTrack].title);
		if (mScrubber != null)
		{
			mPosition = mSound.length * tBar.tBarKnob.x / tBar.tBarBg.width;
			mCurrentPosition = mSound.length * tBar.tBarKnob.x / tBar.tBarBg.width;
		}
		else
		{
			if (mState == "PLAY")
				tBar.tBarKnob.x = tBar.tBarBg.width / 100 * Math.floor(mPosition / mSound.length * 100);
		}
		fUpdateTime();
		mVisual.fUpdate();
	}
	else
	{
		mLoading = true;
		txtTime.text = "Please wait..";
		txtDisplay.text = "Buffering.. ";
	}
}

private function fUpdateTime() : void
{
	var vMin : Number;
	var vSec : Number;

	switch (mState)
	{
		case "PLAY":			
			vMin = Math.floor(mPosition / 1000) / 60 >> 0;
			vSec = Math.floor(mPosition / 1000) % 60 >> 0;

			break;
		case "STOP":
			vMin = Math.floor(mCurrentPosition / 1000) / 60 >> 0;
			vSec = Math.floor(mCurrentPosition / 1000) % 60 >> 0;
			break;
	}
	
	if (vSec >= 0 && vSec < 10)
		txtTime.text = String(vMin) + ":0" + String(vSec);
	else
		txtTime.text = String(vMin) + ":" + String(vSec);
		
}
//----------------------------------------------------------------------------------------------------
} // class cMP3Player
//----------------------------------------------------------------------------------------------------
} // package