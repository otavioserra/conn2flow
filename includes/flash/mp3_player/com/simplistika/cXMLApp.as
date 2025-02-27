//----------------------------------------------------------------------------------------------------
//	cXMLApp.as
//----------------------------------------------------------------------------------------------------
package com.simplistika
{
import flash.display.Sprite;
import flash.net.URLLoader;
import flash.net.URLRequest;
import flash.events.Event;
import flash.xml.XMLDocument;

//----------------------------------------------------------------------------------------------------
//	class definition
//----------------------------------------------------------------------------------------------------
public class cXMLApp extends Sprite
{
	
//----------------------------------------------------------------------------------------------------
//	member data
//----------------------------------------------------------------------------------------------------
private var mURLLoader : URLLoader;
protected var mData : XML;
protected var mItems : uint;
protected var mState : String;

//----------------------------------------------------------------------------------------------------
//	constructor
//----------------------------------------------------------------------------------------------------
public function 
cXMLApp(
	vFile : String
) : void
{
	trace(this + " " + "cXMLApp.constructor");
	
	var urlRequest : URLRequest = new URLRequest(vFile);
	mURLLoader = new URLLoader();
	mURLLoader.load(urlRequest);
	mURLLoader.addEventListener(Event.COMPLETE, fLoaded);

}

//----------------------------------------------------------------------------------------------------
//	fLoaded
//----------------------------------------------------------------------------------------------------
private function 
fLoaded(
	e :Event
):void 
{
	trace(this + " " + "cXMLApp.fLoaded()");
		
	var vDoc : XMLDocument = new XMLDocument();	
	vDoc.ignoreWhite = true;
	mData = XML(mURLLoader.data);
	vDoc.parseXML(mData.toXMLString());	
	mItems = vDoc.firstChild.childNodes.length;	
	dispatchEvent(new Event("XMLLoaded"));

}
//----------------------------------------------------------------------------------------------------
}	// class XMLHelper
//----------------------------------------------------------------------------------------------------
}	// package