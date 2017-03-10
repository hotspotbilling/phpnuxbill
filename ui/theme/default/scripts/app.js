jQuery(function(){
	"use strict";
	var MateriaApp=function(){
		this.isMobile=null,
		this.navHorizontal=!1,
		this.fixedHeader=!0,
		this.themeActive="theme-zero",
		this.navFull=!1,
		this.navOffCanvas=!1,
		this.mainContainer=$(".main-container"),
		this.siteHead=$(".site-head"),
		this.siteSettings=$(".site-settings"),
		this.app=$(".app"),
		this.navWrap=$(".nav-wrap"),
		this.contentContainer=$(".content-container"),
		this._init()
	};
	MateriaApp.prototype._init=function(){
		this._checkMobile(),
		this.toggleSiteNav(),
		this.initDefaultSettings(),
		this.initRipple(),
		this.toggleSettingsBox(),
		this.initPerfectScrollbars(),
		this.toggleFullScreen(),
		this.toggleFloatingSidebar(),
		this.initNavAccordion()
	},
	MateriaApp.prototype.initDefaultSettings=function(){
		function onNavHorizontal(){
			this.checked?(that.navHorizontal=!0,
			that.mainContainer.addClass("nav-horizontal")):(that.navHorizontal=!1,
			that.mainContainer.removeClass("nav-horizontal")),
			setTimeout(function(){
				sQuery.navHorizontal=that.navHorizontal,
				statesQuery.put(sQuery)})
		}
		function onFixedHeader(){
			this.checked?(that.fixedHeader=!0,that.siteHead.addClass("fixedHeader"),
			that.contentContainer.addClass("fixedHeader")):(that.fixedHeader=!1,
			that.siteHead.removeClass("fixedHeader"),
			that.contentContainer.removeClass("fixedHeader")),
			setTimeout(function(){
				sQuery.fixedHeader=that.fixedHeader,
				statesQuery.put(sQuery)
			})
		}
		function onNavFull(){
			var elems=["body",".main-container",".nav-wrap",".content-container"];
			this.checked?(that.navFull=!0,elems.forEach(
			function(el){$(el).addClass("nav-expand")})):(that.navFull=!1,
			elems.forEach(function(el){$(el).removeClass("nav-expand")})),
			setTimeout(function(){sQuery.navFull=that.navFull,statesQuery.put(sQuery)})
		}
		function onThemeChange(e){
			var $t=$(this),
			$list=that.siteSettings.find("#themeColor li");
			$list.removeClass("active"),
			$t.addClass("active"),
			that.app.removeClass(that.themeActive),
			that.themeActive=$t.data("theme"),
			sQuery.themeActive=that.themeActive,
			statesQuery.put(sQuery),
			that.app.addClass(that.themeActive),
			e.preventDefault()
		}
		var that=this,
			SETTINGS_STATES="_setting-states",
			statesQuery={get:function(){return JSON.parse(localStorage.getItem(SETTINGS_STATES))},
			put:function(states){localStorage.setItem(SETTINGS_STATES,JSON.stringify(states))}},
			sQuery=statesQuery.get()||{navHorizontal:that.navHorizontal,fixedHeader:that.fixedHeader,navFull:that.navFull,themeActive:that.themeActive};
			if(sQuery&&(this.navHorizontal=sQuery.navHorizontal,
			this.fixedHeader=sQuery.fixedHeader,
			this.navFull=sQuery.navFull,
			this.themeActive=sQuery.themeActive),
			this.siteSettings.find("#navHorizontal").on("change",onNavHorizontal),
			this.siteSettings.find("#fixedHeader").on("change",onFixedHeader),
			this.siteSettings.find("#navFull").on("change",onNavFull),
			this.siteSettings.find("#themeColor li").on("click touchstart",onThemeChange),
			this.app.addClass(this.themeActive),
			this.navFull){
				this.siteSettings.find("#navFull")[0].checked=!0;
				var elems=["body",".main-container",".nav-wrap",".content-container"];
				elems.forEach(function(el){$(el).addClass("nav-expand")})
			}
			this.navHorizontal&&(this.siteSettings.find("#navHorizontal")[0].checked=!0,
			this.mainContainer.addClass("nav-horizontal")),
			this.fixedHeader&&(this.siteSettings.find("#fixedHeader")[0].checked=!0,
			this.siteHead.addClass("fixedHeader"),
			this.contentContainer.addClass("fixedHeader")),
			this.navOffCanvas&&this.navWrap.addClass("nav-offcanvas")
	},
	MateriaApp.prototype.initRipple=function(){
		Waves.attach(".btn"),
		Waves.init({duration:900,delay:300}),
		Waves.attach(".nav-wrap .site-nav .nav-list li"),
		Waves.attach(".md-button:not(.md-no-ink)")
	},
	MateriaApp.prototype._checkMobile=function(){
		var mm=window.matchMedia("(max-width: 767px)");
		this.isMobile=mm.matches?!0:!1;
		var that=this;mm.addListener(function(m){that.isMobile=m.matches?!0:!1})
	},
	MateriaApp.prototype.toggleSiteNav=function(){
		this.siteHead.find(".nav-trigger").on("click touchstart",function(e){
		var elems=["body",".main-container",".nav-wrap",".content-container"];
			elems.forEach(function(el){
				$(el).toggleClass("nav-expand"),".nav-wrap"==el&&$(el).toggleClass("nav-offcanvas")
			}),e.preventDefault()
		})
	},
	MateriaApp.prototype.toggleSettingsBox=function(){
		this.siteSettings.find(".trigger").on("click touchstart",
		function(e){$(".site-settings").toggleClass("open"),e.preventDefault()})
	},
	MateriaApp.prototype.initPerfectScrollbars=function(){
		var $el=$("[data-perfect-scrollbar]");
		$el.each(function(){
			var $t=$(this);
			$t.perfectScrollbar({suppressScrollX:!0}),
			setInterval(function(){
				$t[0].scrollHeight>=$t[0].clientHeight&&$t.perfectScrollbar("update")
			},400)
		})
	},
	MateriaApp.prototype.toggleFullScreen=function(){
		$(".site-head .fullscreen").on("click",function(e){screenfull.toggle(),e.preventDefault()})
	},
	MateriaApp.prototype.toggleFloatingSidebar=function(){
		$(".site-head .floating-sidebar > a").on("click",function(e){$(this).parent().toggleClass("open"),e.preventDefault()})
	},
	MateriaApp.prototype.initNavAccordion=function(){
		var el=$(".site-nav .nav-list"),lists=el.find("ul").parent("li"),
		a=lists.children("a"),aul=lists.find("ul a"),
		listsRest=el.children("li").not(lists),
		aRest=listsRest.children("a"),
		stopClick=0,that=this;a.on("click",function(e){
			if(!that.navHorizontal){
				if(e.timeStamp-stopClick>300){
					var self=$(this),
					parent=self.parent("li");
					lists.not(parent).removeClass("open"),
					parent.toggleClass("open"),
					stopClick=e.timeStamp
				}
				e.preventDefault()
			}
			e.stopPropagation(),
			e.stopImmediatePropagation()
		}),
		aul.on("touchend",function(e){
			that.isMobile&&that.navWrap.toggleClass("nav-offcanvas"),
			e.stopPropagation(),
			e.stopImmediatePropagation()
		}),
		aRest.on("touchend",function(){that.isMobile&&that.navWrap.toggleClass("nav-offcanvas")}),
		aRest.on("click",function(e){if(!that.navHorizontal){var parent=aRest.parent("li");lists.not(parent).removeClass("open")}e.stopPropagation(),e.stopImmediatePropagation()})
	};
	window.MateriaApp=new MateriaApp
});