!function(){
	"use strict";
	var isCommonjs="undefined"!=typeof module&&module.exports,
	keyboardAllowed="undefined"!=typeof Element&&"ALLOW_KEYBOARD_INPUT"in Element,
	fn=function(){
		for(
			var val,
			valLength,
			fnMap=[["requestFullscreen","exitFullscreen","fullscreenElement","fullscreenEnabled","fullscreenchange","fullscreenerror"],
			["webkitRequestFullscreen","webkitExitFullscreen","webkitFullscreenElement","webkitFullscreenEnabled","webkitfullscreenchange","webkitfullscreenerror"],
			["webkitRequestFullScreen","webkitCancelFullScreen","webkitCurrentFullScreenElement","webkitCancelFullScreen","webkitfullscreenchange","webkitfullscreenerror"],
			["mozRequestFullScreen","mozCancelFullScreen","mozFullScreenElement","mozFullScreenEnabled","mozfullscreenchange","mozfullscreenerror"],
			["msRequestFullscreen","msExitFullscreen","msFullscreenElement","msFullscreenEnabled","MSFullscreenChange","MSFullscreenError"]],
			i=0,
			l=fnMap.length,
			ret={};
			l>i;
			i++
		)
		if(val=fnMap[i],
		val&&val[1]in document){
			for(i=0,valLength=val.length;valLength>i;i++)
				ret[fnMap[0][i]]=val[i];
			return ret
		}
		return!1
	}(),
	screenfull={
		request:function(elem){
			var request=fn.requestFullscreen;
			elem=elem||document.documentElement,/5\.1[\.\d]* Safari/.test(navigator.userAgent)?elem[request]():elem[request](keyboardAllowed&&Element.ALLOW_KEYBOARD_INPUT)
		},
		exit:function(){
			document[fn.exitFullscreen]()
		},
		toggle:function(elem){this.isFullscreen?this.exit():this.request(elem)},raw:fn
	};return fn?(Object.defineProperties(screenfull,{isFullscreen:{get:function(){return!!document[fn.fullscreenElement]}},element:{enumerable:!0,get:function(){return document[fn.fullscreenElement]}},enabled:{enumerable:!0,get:function(){return!!document[fn.fullscreenEnabled]}}}),void(isCommonjs?module.exports=screenfull:window.screenfull=screenfull)):void(isCommonjs?module.exports=!1:window.screenfull=!1)}();