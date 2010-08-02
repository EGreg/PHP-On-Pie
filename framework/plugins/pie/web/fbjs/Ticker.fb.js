Pie.Ticker = function (prefix, options, pieOptions) {
	// private variables:
	var threshold = 5; // how many pixels until manual scrolling kicks in
	var isReady = false;
	
	// private methods:
	
	// parent constructors
 	Pie.Tool.apply(this, arguments);
	
	// constructor
	var me = this;
	
	if (!('vertical' in options)) {
		options['vertical'] = true;
	}	
	if (!('pause_ms_min' in options)) {
		options['pause_ms_min'] = options['pause_ms'];
	}
	if (!('element_id' in options)) {
		options['element_id'] = prefix + 'tool';
	}

	// events:
	this.onHitStart = function() { }; // override this to handle this event
	this.onHitEnd = function() { }; // override this to handle this event

	// public:	
	this.ready = function () {
		
		if (isReady) {
			return;
		}
		var ticker = document.getElementById(options['element_id']);
		if (!ticker.hasClassName('ticker')) {
			ticker.addClassName('ticker');
		}
		if (!ticker.hasClassName('vertical') && !ticker.hasClassName('horizontal')) {
			ticker.addClassName('vertical');
		}
		var ticker_children = ticker.getChildNodes();
		var container = ticker_children[0];
		var children = container.getChildNodes();

		me.calculateDimensions();
		me.setScrollbars(options['scrollbars']);

		if (options['speed'] < 0) {
			if (options['vertical']) {
				ticker.setScrollTop(10000000);
			} else {
				ticker.setScrollLeft(10000000);
			}
		}

		me.anim_ms = 100;
		if (options['anim_ms']) {
			me.anim_ms = options['anim_ms'];
		}

		setInterval(me.autoScroll, me.anim_ms);
		isReady = true;
	};
	
	this.setScrollbars = function(shouldShow) {
		var ticker = document.getElementById(options['element_id']);
		
		if (shouldShow) {
			ticker.addClassName('scrollbars');
		} else {
			ticker.removeClassName('scrollbars');
		}
	};
	
	this.pause = function()
	{
		me.scrollMode = 'paused';
		me.msSincePaused = 0;
		me.pause_ms = options['pause_ms_min'] + Math.random() * 
			(options['pause_ms'] - options['pause_ms_min']);
	};
	
	this.resume = function(curScrollPos)
	{
		me.scrollMode = 'auto';
		me.frameIndex = 0;
		me.startScrollPos = curScrollPos;
		me.newScrollPos = me.getNextScrollPos(curScrollPos);
	};
	
	this.autoScroll = function () {		

		var ticker = document.getElementById(options['element_id']);
		var curScrollPos = (options['vertical'])
			? ticker.getScrollTop()
			: ticker.getScrollLeft();
		
		// Raise scrolling events, if any
		if (! ('lastScrollPos' in me)  || me.lastScrollPos != curScrollPos) {
			me.raisedOnHitStart = false;
			me.raisedOnHitEnd = false;
		}
			
		if (curScrollPos === 0) {
			if (!me.raisedOnHitStart) {
				me.onHitStart();
			}
			me.raisedOnHitStart = true;
		} else {
			if (options['vertical']) {
				if (curScrollPos == ticker.getScrollHeight() 
				- ticker.getClientHeight()) {
					if (!me.raisedOnHitEnd) {
						me.onHitEnd();
					}
					me.raisedOnHitEnd = true;
				}
			} else {
				if (curScrollPos == ticker.getScrollWidth() 
				- ticker.getClientWidth()) {
					if (!me.raisedOnHitEnd) {
						me.onHitEnd();
					}
					me.raisedOnHitEnd = true;
				}
			}
		}
	
		// Handle the auto scrolling	
		
		if (! ('scrollMode' in me)) {
			if ('initial_scroll_mode' in options) {
				me.scrollMode = options.initial_scroll_mode;
			} else {
				me.scrollMode = 'auto';
			}
			if (me.scrollMode == 'auto') {
				me.resume(curScrollPos);
				me.newScrollPos = me.getFirstScrollPos(curScrollPos);
			} else {
				me.msSincePaused = 0;
				me.pause_ms = options['pause_ms_min'] + Math.random() * 
					(options['pause_ms'] - options['pause_ms_min']);
			}
		}

		if (speed === 0) {
			return -1;
		}
		
		if (! ('frameIndex' in me)) {
			me.frameIndex = 0;
		}
			
		if (! ('startScrollPos' in me)) {
			me.startScrollPos = 0;
		}

		if (me.scrollMode != 'manual'
		&& 'lastAutoScrollPos' in me 
		&& !isNaN(me.lastAutoScrollPos)
		&& Math.abs(me.lastAutoScrollPos - curScrollPos) > threshold) {
		
			// the scrollbar has started moving by some other means
			// stop this function from executing, start waiting
			// until it stops and options['scrollbars_pause_ms']
			// milliseconds have elapsed since then.
			me.scrollMode = 'manual';
			
			// reset # of msec passed since manual scrolling
			me.msSinceManual = 0;
			
		} else if (me.scrollMode == 'manual') {
			
			if ('lastScrollPos' in me 
			&& me.lastScrollPos != curScrollPos) {
				// the scrollbar continues to move,
				// reset # of msec to wait since manual scrolling
				me.msSinceManual = 0;
				
				// keep the scroll mode as 'manual'
			} else {
				// increment the # of msec passed since manual scrolling
				me.msSinceManual += me.anim_ms;
				
				if (options['scrollbars_pause_ms'] >= 0
				&& me.msSinceManual > options['scrollbars_pause_ms']) {
					me.resume(curScrollPos);
				}
			}
			
		} else if (me.scrollMode == 'paused') {
		
			// increment the # of msec passed since manual scrolling
			me.msSincePaused += me.anim_ms;
		
			if (me.pause_ms >= 0
			&& me.msSincePaused > me.pause_ms) {
				me.resume(curScrollPos);
			}
			
		}

		if (me.scrollMode == 'auto') {
			var speed = options['speed'];
			var fraction = me.frameIndex * (me.anim_ms / 1000 * Math.abs(speed));

			var nextScrollPos;
			if (fraction >= 1) {
				me.pause();
			}
			nextScrollPos = me.startScrollPos + 
				(me.newScrollPos - me.startScrollPos) * me.ease(fraction);
			if (options['vertical']) {
				ticker.setScrollTop(nextScrollPos);
				me.lastAutoScrollPos = ticker.getScrollTop();
			} else {
				ticker.setScrollLeft(nextScrollPos);
				me.lastAutoScrollPos = ticker.getScrollLeft();
			}
			
			++me.frameIndex;
		} else {
			me.lastScrollPos = curScrollPos;
		}
		
		// This makes sure the scrolling events don't happen twice
		if (! ('lastScrollPos' in me)  || me.lastScrollPos != curScrollPos) {
			me.lastScrollPos = curScrollPos;
		}
		
		return 0;
	};

	this.getFirstScrollPos = function(curScrollPos)
	{
		var ticker = document.getElementById(options['element_id']);
		var speed = options['speed'];
		var i;
		
		me.calculateDimensions();
		
		if (speed >= 0) {
			return 0;
		}
		
		if (options['vertical']) {
			var bottom = 0;
			var top = 0;
			for (i=0; i < me.heights.length; ++i) {
				bottom += me.heights[i];
			}
			for (i=0; i < me.heights.length; ++i) {
				top += me.heights[i];
				if (top + me.heights[i] > bottom - ticker.getClientHeight()) {
					return top;
				}
			}
			return top;
		} else {
			var right = 0;
			var left = 0;
			for (i=0; i < me.widths.length; ++i) {
				right += me.widths[i];
			}
			for (i=0; i < me.widths.length; ++i) {
				left += me.widths[i];
				if (left + me.widths[i] > right - ticker.getClientWidth()) {
					return left;
				}
			}
			return left;
		}
		return -2;
	};
	
	this.getNextScrollPos = function(curScrollPos)
	{
		var speed = options['speed'];
		var i;
		
		me.calculateDimensions();

		if (speed === 0) {
			return -1;
		}
			
		if (options['vertical']) {
			var top = 0;
			for (i=0; i < me.heights.length; ++i) {
				if (speed > 0) {
					if (top + me.heights[i] > curScrollPos)
						return top + me.heights[i];
				} else {
					if (top + me.heights[i] >= curScrollPos - 1)
						return top;
				}
				top += me.heights[i];
			}
		} else {
			var left = 0;
			for (i=0; i < me.widths.length; ++i) {
				if (speed > 0) {
					if (left + me.widths[i] > curScrollPos) {
						return left + me.widths[i];
					}
				} else {
					if (left + me.widths[i] >= curScrollPos - 1) {
						return left;
					}
				}
				left += me.widths[i];
			}
		}
		return -2;
	};
	
	if (! ('ease' in Pie)) {
		Pie.ease = {};
	}
	
	Pie.ease.bounce = function(fraction) {
		return Math.sin(Math.PI * 1.2 * (fraction - 0.5)) / 1.7 + 0.5;
	};
	
	Pie.ease.smooth = function(fraction) {
		return Math.sin(Math.PI * (fraction - 0.5)) / 2 + 0.5;
	};
	
	Pie.ease.linear = function(fraction) {
		return fraction;
	};
	
	this.ease = ('ease' in options)
		? Pie.ease[options['ease']]
		: Pie.ease.smooth;
		
	this.calculateDimensions = function () {
		var ticker = document.getElementById(options['element_id']);
		var ticker_children = ticker.getChildNodes();
		var container = ticker_children[0]; // the first node
		var children = container.getChildNodes();
		
		me.widths = [];
		me.heights = [];
		
		for (i=0; i<children.length; ++i) {
			me.widths.push(children[i].getOffsetWidth());
			me.heights.push(children[i].getOffsetHeight());
		}
	};
};
