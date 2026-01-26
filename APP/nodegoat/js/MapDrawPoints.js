
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapDrawPoints(element, PARENT, options) {

	var elm = $(element),
	SELF = this,
	settings = $.extend({
		arr_visual: false
	}, options || {});

	var	arr_data = {},
	stage = false,
	stage_ns = 'http://www.w3.org/2000/svg',
	renderer = false,
	drawer = false,
	elm_plot = false,
	key_move = false,
	
	pos_offset_x = 0,
	pos_offset_y = 0,
	pos_offset_extra_x = 0,
	pos_offset_extra_y = 0;
	
	this.init = function() {
					
		renderer = document.createElementNS(stage_ns, 'svg');
		renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
		
		drawer = renderer;
		elm[0].appendChild(drawer);
		
		stage = renderer.ownerDocument;
				
		key_move = PARENT.obj_map.move(rePosition);
	};
	
	this.close = function() {
		
		PARENT.obj_map.move(null, key_move);
	};
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (move === false || calc_zoom === false || calc_zoom) { // Move stop, resize, or zoomed
	
			// Reposition drawer
			const num_width = pos.size.width;
			const num_height = pos.size.height;
			
			drawer.style.width = num_width+'px';
			drawer.style.height = num_height+'px';
			
			let do_redraw = (calc_zoom ? true : false);
			
			const num_x = -pos.x - pos.offset.x - (num_width/2);
			const num_y = -pos.y - pos.offset.y - (num_height/2);

			if (do_redraw || (num_x - pos_offset_extra_x) + (pos.view.width/2) > (num_width/2) || (num_x - pos_offset_extra_x) - (pos.view.width/2) < -(num_width/2) || (num_y - pos_offset_extra_y) + (pos.view.height/2) > (num_height/2) || (num_y - pos_offset_extra_y) - (pos.view.height/2) < -(num_height/2)) {
		
				pos_offset_extra_x = num_x;
				pos_offset_extra_y = num_y;

				const str = 'translate('+num_x+'px, '+num_y+'px)';
				drawer.style.transform = drawer.style.webkitTransform = str;
				
				do_redraw = true;
			}

			pos_offset_x = pos.offset.x + pos_offset_extra_x;
			pos_offset_y = pos.offset.y + pos_offset_extra_y;
			
			if (do_redraw) {
				PARENT.doDraw();
			}
		}
	};
	
	this.prepareData = function(arr_data_source) {
		
		arr_data = arr_data_source;
	};
	
	this.drawData = function() {
		
		if (elm_plot) {
			drawer.removeChild(elm_plot);
		}
		
		elm_plot = stage.createElementNS(stage_ns, 'g');
		elm_plot.setAttribute('class', 'plot');
		drawer.appendChild(elm_plot);
		
		if (!options.arr_visual) {
			return;
		}
		
		for (const key in arr_data.points) {
			
			const arr_point = arr_data.points[key];
			
			const arr_xy = PARENT.obj_map.plotPoint(arr_point.latitude, arr_point.longitude);
						
			const elm_dot = addDot(arr_xy, options.arr_visual.dot.color);
		}
	};
	
	var addDot = function(arr_xy, str_color) {
		
		const num_r = options.arr_visual.dot.size.min;
	
		const num_x = arr_xy.x - (num_r/2) - pos_offset_x;
		const num_y = arr_xy.y - (num_r/2) - pos_offset_y;
		
		const elm = stage.createElementNS(stage_ns, 'circle');
		elm.setAttribute('cx', num_x);
		elm.setAttribute('cy', num_y);
		elm.setAttribute('r', num_r);
		elm.style.fill = str_color;
		elm.style.stroke = options.arr_visual.dot.stroke_color;
		elm.style.strokeWidth = options.arr_visual.dot.stroke_width;
		elm_plot.appendChild(elm);
		
		return elm;
	};
};
