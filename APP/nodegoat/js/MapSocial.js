
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapSocial(elm_draw, PARENT, options) {

	var elm = $(elm_draw),
	elm_host = PARENT.elm_paint_host,
	SELF = this;
	
	const DISPLAY_VECTOR = 1;
	const DISPLAY_PIXEL = 2;
	
	var has_init = false,
	arr_labels = {},
	stage_ns = 'http://www.w3.org/2000/svg',
	elm_svg = false,
	elm_canvas = false,
	svg = false,
	svg_group = false,
	display = DISPLAY_VECTOR,
	elm_selected_node_container = false,
	elm_search_container = false,
	elm_analyse_container = false,
	elm_layout = false,
	elm_layout_statistics = false,
	elm_layout_select = false,
	elm_layout_status = false,
	elm_plot_lines = false,
	elm_plot_dots = false,
	elm_plot_info = false,
	func_tooltip_stop = null,
	arr_data = false,
	arr_loop_object_subs = [],
	include_location_nodes = false,
	show_disconnected_node = false,
	in_predraw = true,
	in_first_run = true,
	do_redraw = false,
	do_draw = false,
	count_loop = 0,
	num_node_weight_min = false,
	num_node_weight_max = false,
	num_link_weight_min = 1,
	num_link_weight_max = false,
	arr_nodes = {},
	arr_links = {},
	arr_object_subs_children = {},
	arr_loop_nodes = [],
    arr_loop_links = [],
    arr_active_nodes = [],
    arr_active_links = [],
	key_move = false,
	simulation = null,
	use_simulation_native = false,
	is_dragging_node = false,
	is_dragging = false,
	hovering = false,
	focus_object_id = false,
	cur_node_id = false,
	arr_highlighted_nodes = [],
	arr_remove_nodes = [],
	arr_size_initialise = {},
	size_renderer = {},
	renderer = false,
	renderer_2 = false,
	stage = false,
	stage_2 = false,
	drawer = false,
	drawer_2 = false,
	drawer_defs = false,
	num_scale = 1,
	static_layout = false,
	static_layout_interval = 0,
	static_layout_timer = window.performance.now(),
	font_family = null,
	size_text = 12,
	size_text_min = 8,
	size_text_max = 30,
	num_text_scaled = null,
	color_text = '#000000',
	opacity_text = 1,
	do_text_scale = true,
	do_text_pixel = false,
	length_text_max = 80,
	color_highlight_node = '#d92b2b',
	color_highlight_node_connect = '#ff7070',
	color_highlight_link = 'rgba(255,0,0,0.4)',
	
	size_node_max = null,
	size_node_min = null,
	size_node_start = null,
	size_node_stop = null,
	color_node = null,
	opacity_node = null,
	color_node_stroke = null,
	opacity_node_stroke = null,
	width_node_stroke = null,
	
	width_line_min = 1.5,
	width_line_max = 1.5,
	color_line = 'rgb(100,100,100)',
	opacity_line = 0.2,
	arr_color_line = false,
	do_line_color_weight = false,
	do_node_icons_weight = false,
	use_best_quality = null,
	use_beta_mode = false,
	
	pos_hover_poll = false,
	pos_move = {x: 0, y: 0},
	pos_translation = {x: 0, y: 0},
	pos_stage = {x: 0, y: 0},
	
	use_metrics = false,
	metrics_process = false,
		
	geometry_shader = null,
	geometry_shader_uniforms = null,
	buffer_geometry_lines_position = false,
	buffer_geometry_lines_index = false,
	buffer_geometry_lines_normal = false,
	buffer_geometry_lines_color = false,
	do_update_geometry_lines_index = false,
	do_update_geometry_lines_color = false,
	count_links = 0,
	count_nodes = 0,
	arr_assets_texture_icons = {},
	
	is_weighted = false,
	force_options = {},
	forceatlas2_options = {},
	show_line = true,
	show_arrowhead = false,
	label_threshold = 0.1,
	label_condition = false,
	show_icon_as_node = false,
	num_size_dot_icons = 15,
	num_offset_dot_icons = 4,
	num_spacer_dot_icons = 2,
	num_offset_dot_text = 3,

	key_animate = false,
	
	arr_elm_particles = [],
	arr_assets_texture_line_dots = [],
	size_max_elm_container = 15000,
	
	SocialUtilities = new MapUtilities(PARENT.obj_map);
	
	this.init = function() {
		
		const parseBool = function(value, loose) {
			if (value === true || value === 'true') {
				return true;
			} else if (value === false || value === 'false') {
				return false;
			} else if (loose) {
				return value;
			} else {
				return false;
			}
		};
		
		const arr_setting_advanced = options.arr_visual.social.settings.social_advanced;
		
		display = options.arr_visual.social.settings.display;
		static_layout = options.arr_visual.social.settings.static_layout;
		static_layout_interval = options.arr_visual.social.settings.static_layout_interval;
		
		SocialUtilities.parseColor = (display == DISPLAY_PIXEL ? SocialUtilities.parseColorToHex : SocialUtilities.parseColorToString);
		SocialUtilities.parseColorLink = (display == DISPLAY_PIXEL ? SocialUtilities.parseColorToArray : function(str) { return str; });
		
		let use_capture = (options.arr_visual.capture.enable ? true : false);
		if (typeof arr_setting_advanced.best_quality != 'undefined') {
			use_best_quality = parseBool(arr_setting_advanced.best_quality);
		}
		use_best_quality = (use_best_quality != null ? use_best_quality : (use_capture ? true : false));
		if (typeof arr_setting_advanced.beta_mode != 'undefined') {
			use_beta_mode = parseBool(arr_setting_advanced.beta_mode);
		}
				
		size_node_max = parseFloat(options.arr_visual.social.dot.size.max);
		size_node_min = parseFloat(options.arr_visual.social.dot.size.min);
		size_node_start = parseFloat(options.arr_visual.social.dot.size.start);
		size_node_stop = parseFloat(options.arr_visual.social.dot.size.stop);
		color_node = options.arr_visual.social.dot.color;
		opacity_node = parseFloat(options.arr_visual.social.dot.opacity);
		color_node_stroke = options.arr_visual.social.dot.stroke_color;
		opacity_node_stroke = parseFloat(options.arr_visual.social.dot.stroke_opacity);
		width_node_stroke = options.arr_visual.social.dot.stroke_width;

		if (!options.arr_visual.social.label.show) {
			label_threshold = 2.0; // Set above 1, the maximum
		} else {
			label_threshold = parseFloat(options.arr_visual.social.label.threshold);
			label_condition = options.arr_visual.social.label.condition;
			label_condition = (label_condition ? label_condition : false);
			color_text = options.arr_visual.social.label.color;
			opacity_text = parseFloat(options.arr_visual.social.label.opacity);
			size_text = parseFloat(options.arr_visual.social.label.size);
			if (size_text < size_text_min) {
				size_text_min = size_text;
			}
			if (size_text > size_text_max) {
				size_text_max = size_text;
			}
		}
		
		width_line_max = parseFloat(options.arr_visual.social.line.width.max);
		width_line_min = parseFloat(options.arr_visual.social.line.width.min);
		
		color_line = options.arr_visual.social.line.color;
		opacity_line = parseFloat(options.arr_visual.social.line.opacity);
		
		arr_color_line = parseCSSColor(color_line);
		
		force_options = {
			friction: parseFloat(options.arr_visual.social.force.friction),
			charge: parseInt(options.arr_visual.social.force.charge),
			gravity: parseFloat(options.arr_visual.social.force.gravity),
			theta: parseFloat(options.arr_visual.social.force.theta)
		};
		
		forceatlas2_options = {
			lin_log_mode: options.arr_visual.social.forceatlas2.lin_log_mode,
			outbound_attraction_distribution: options.arr_visual.social.forceatlas2.outbound_attraction_distribution,
			adjust_sizes: options.arr_visual.social.forceatlas2.adjust_sizes,
			edge_weight_influence: options.arr_visual.social.forceatlas2.edge_weight_influence,
			scaling_ratio: options.arr_visual.social.forceatlas2.scaling_ratio,
			strong_gravity_mode: options.arr_visual.social.forceatlas2.strong_gravity_mode,
			gravity: options.arr_visual.social.forceatlas2.gravity,
			optimize_theta: options.arr_visual.social.forceatlas2.optimize_theta
		};

		if (typeof arr_setting_advanced.force_friction != 'undefined') {
			force_options.friction = parseFloat(arr_setting_advanced.force_friction);
		}
		if (typeof arr_setting_advanced.force_charge != 'undefined') {
			force_options.charge = parseInt(arr_setting_advanced.force_charge);
		}
		if (typeof arr_setting_advanced.force_gravity != 'undefined') {
			force_options.gravity = parseFloat(arr_setting_advanced.force_gravity);
		}
		if (typeof arr_setting_advanced.force_theta != 'undefined') {
			force_options.theta = parseFloat(arr_setting_advanced.force_theta);
		}
		
		if (typeof arr_setting_advanced.highlight_node_color != 'undefined') {
			color_highlight_node = arr_setting_advanced.highlight_node_color;
		}
		if (typeof arr_setting_advanced.highlight_node_connect_color != 'undefined') {
			color_highlight_node_connect = arr_setting_advanced.highlight_node_connect_color;
		}
		if (typeof arr_setting_advanced.highlight_link_color != 'undefined') {
			color_highlight_link = arr_setting_advanced.highlight_link_color;
		}
		
		if (typeof arr_setting_advanced.label_threshold != 'undefined') {
			label_threshold = parseFloat(arr_setting_advanced.label_threshold);
		}
		if (typeof arr_setting_advanced.label_font != 'undefined') {
			font_family = arr_setting_advanced.label_font;
		}
		if (typeof arr_setting_advanced.label_scale != 'undefined') {
			do_text_scale = parseBool(arr_setting_advanced.label_scale);
		}
		if (typeof arr_setting_advanced.label_size_min != 'undefined') {
			size_text_min = parseFloat(arr_setting_advanced.label_size_min);
		}
		if (typeof arr_setting_advanced.label_size_max != 'undefined') {
			size_text_max = parseFloat(arr_setting_advanced.label_size_max);
		}
		if (typeof arr_setting_advanced.metrics != 'undefined') {
			//use_metrics = parseBool(arr_setting_advanced.metrics, false);
		}
		if (typeof arr_setting_advanced.node_icons_size != 'undefined') {
			if (arr_setting_advanced.node_icons_size === 'weight') {
				do_node_icons_weight = true;
			} else {
				num_size_dot_icons = parseInt(arr_setting_advanced.node_icons_size);
			}
		}
		if (typeof arr_setting_advanced.node_icons_offset != 'undefined') {
			num_offset_dot_icons = parseInt(arr_setting_advanced.node_icons_offset);
		}
		if (typeof arr_setting_advanced.node_icons_show_as_node != 'undefined') {
			show_icon_as_node = parseBool(arr_setting_advanced.node_icons_show_as_node, true);
		}
		show_line = options.arr_visual.social.line.show;
		show_arrowhead = (show_line ? options.arr_visual.social.line.arrowhead_show : false);			
		if (options.arr_visual.social.settings.disconnected_dot_show) {
			show_disconnected_node = true;
		}
		if (options.arr_visual.social.settings.include_location_references) {
			include_location_nodes = true;
		}

		let arr_scripts = ['/js/support/d3-force.pack.js'];
		if (display == DISPLAY_PIXEL) {
			arr_scripts.push('/CMS/js/support/pixi8.min.js');
		}
		
		arr_labels = ['lbl_visualise_layout_complete', 'lbl_visualise_layout_iterations', 'lbl_nodes', 'lbl_links', 'lbl_links_out', 'lbl_links_in', 'lbl_reference', 'lbl_references', 'lbl_conditions', 'msg_no_results'];

		ASSETS.fetch(elm_host, {
			script: arr_scripts, font: ['pixel'], labels: arr_labels
		}, async function() {
			
			has_init = true;

			arr_data = PARENT.getData();
			
			if (arr_data.focus && arr_data.focus.object_id) {
				focus_object_id = arr_data.focus.object_id;
			}
			
			ASSETS.getLabels(elm_host, arr_labels, (data) => {arr_labels = data});

			var count_start = 0;
			
			count_start++; // Main loading

			var func_start = function() {
				
				if (count_start > 0) {
					return;
				}
			
				SELF.drawData = drawData;

				if (display == DISPLAY_PIXEL) {
					
					key_move = PARENT.obj_map.move(rePosition, null, false);
					PARENT.obj_map.setZoom(PARENT.obj_map.getZoom());

					PARENT.doDraw(); // First draw using possible scaling
				} else {
					
					PARENT.doDraw(); // First draw native before possible scaling
					
					key_move = PARENT.obj_map.move(rePosition, null, false);
					PARENT.obj_map.setZoom(PARENT.obj_map.getZoom());
				}
			};
			
			if (arr_data.legend.conditions) {
				
				for (const str_identifier_condition in arr_data.legend.conditions) {
					
					const arr_condition = arr_data.legend.conditions[str_identifier_condition];

					if (arr_condition.weight && arr_condition.weight > 0) {
						is_weighted = true;
					}
				}
				
				const arr_media = PARENT.obj_data.getDataMedia();

				if (arr_media.length) {
					
					count_start++ // Media loading
					
					ASSETS.fetch(elm_host, {media: arr_media}, function() {
						
						if (display == DISPLAY_PIXEL) {
							
							for (let i = 0, len = arr_media.length; i < len; i++) {
							
								const str_resource = arr_media[i];
								const arr_medium = ASSETS.getMedia(str_resource);
								const elm_image = arr_medium.image.cloneNode(false);
								
								const source = new PIXI.ImageSource({resource: elm_image, width: arr_medium.width, height: arr_medium.height});
								const texture = new PIXI.Texture(source);
								//texture = await PIXI.Assets.load(str_resource);
								
								arr_assets_texture_icons[str_resource] = {texture: texture, width: arr_medium.width, height: arr_medium.height};
							}
						}
						
						count_start--; // Media loaded
						
						func_start();
					});
				}
			}

			if (display == DISPLAY_PIXEL) {
				
				const pos_map = PARENT.obj_map.getPosition();
				const num_zoom_initialise = pos_map.level;
				
				arr_size_initialise = {level: num_zoom_initialise}; // Rendering target
									
				if (num_zoom_initialise < 0) {
					num_scale = num_scale * Math.pow(0.7, Math.abs(num_zoom_initialise));
				} else if (num_zoom_initialise > 0) {
					num_scale = num_scale * Math.pow(1.4286, num_zoom_initialise);
				}
				
				size_renderer = {width: pos_map.size.width, height: pos_map.size.height, resolution: pos_map.render.resolution};
				
				elm_canvas = document.createElement('canvas');
				elm_canvas.width = size_renderer.width;
				elm_canvas.height = size_renderer.height;	
				elm[0].appendChild(elm_canvas);

				PIXI.GraphicsContextSystem.defaultOptions.bezierSmoothness = 0.5;
				if (use_capture || use_best_quality) {
					PIXI.GraphicsContextSystem.defaultOptions.bezierSmoothness = 1;
				}

				const func_renderer_settings = function(renderer_check) {
					
					renderer_check.events.autoPreventDefault = false;
					renderer_check.canvas.style.removeProperty('touch-action');
					
					return renderer_check;
				};
				
				const func_stage_settings = function(stage_check) {
					
					stage_check.isRenderGroup = true; // Default for render stage
					stage_check.cullableChildren = false;
					stage_check.interactiveChildren = false;
					stage_check.eventMode = 'none';
					
					return stage_check;
				};
				
				const arr_renderer_settings = {width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: size_renderer.resolution, autoDensity: true, premultipliedAlpha: true, preference: (use_beta_mode ? 'webgpu' : 'webgl')};
				
				renderer = func_renderer_settings(await PIXI.autoDetectRenderer(arr_renderer_settings));
				renderer_2 = func_renderer_settings(await PIXI.autoDetectRenderer(arr_renderer_settings));

				const vertex_shader = `
					precision mediump float;
					attribute vec2 a_position;
					//attribute vec2 a_normal;
					attribute vec4 a_color;
					varying vec4 v_color;
					uniform vec2 u_bounds;
					uniform vec2 u_translation;
					uniform vec2 u_stagetranslation;
					uniform vec2 u_scale;
					//uniform float u_width_line;
					void main(void) {
						//vec2 delta = a_normal * u_width_line;
					 	//vec2 pos = ((((a_position.xy + delta.xy + u_translation.xy) * u_scale.xy) + u_stagetranslation.xy) / u_bounds.xy) * 2.0 - 1.0;
					 	vec2 pos = ((((a_position.xy + u_translation.xy) * u_scale.xy) + u_stagetranslation.xy) / u_bounds.xy) * 2.0 - 1.0;
					 	gl_Position = vec4(pos * vec2(1, -1), 0, 1.0);
					 	v_color = a_color;
					}
				`;
					
				const fragment_shader = `
					precision mediump float;
					varying vec4 v_color;
					void main(void) {
						// Multiply RGB by Alpha before outputting
						gl_FragColor = vec4(v_color.rgb * v_color.a, v_color.a);
					}
				`;
				
				const line_wgsl = `
					struct GlobalUniforms {
						u_bounds: vec2<f32>,
						u_translation: vec2<f32>,
						u_stagetranslation: vec2<f32>,
						u_scale: vec2<f32>,
					};

					@group(0) @binding(0) var<uniform> my_uniforms: GlobalUniforms;
					
					struct VertexOutput {
						@builtin(position) position: vec4<f32>,
						@location(0) v_color: vec4<f32>,
					};

					@vertex
					fn mainVertex(
						@location(0) a_position: vec2<f32>,
						@location(1) a_color: vec4<f32>,
					) -> VertexOutput {
						var output: VertexOutput;
						
						let shifted_pos = (a_position + my_uniforms.u_translation) * my_uniforms.u_scale;
						let final_pos = (shifted_pos + my_uniforms.u_stagetranslation) / my_uniforms.u_bounds;
						
						// Map to NDC and flip Y
						let ndc_pos = final_pos * 2.0 - 1.0;
						output.position = vec4<f32>(ndc_pos.x, ndc_pos.y * -1.0, 0.0, 1.0);
						
						output.v_color = a_color;
						return output;
					}

					@fragment
					fn mainFragment(
						@location(0) v_color: vec4<f32>
					) -> @location(0) vec4<f32> {
						// Multiply RGB by Alpha before outputting
						return vec4<f32>(v_color.rgb * v_color.a, v_color.a);
					}
				`;

				const gl_program = PIXI.GlProgram.from({
					vertex: vertex_shader,
					fragment: fragment_shader
				});
				const gpu_program = PIXI.GpuProgram.from({
					vertex: {source: line_wgsl, entryPoint: 'mainVertex'},
					fragment: {source: line_wgsl, entryPoint: 'mainFragment'}
				});				
				
				geometry_shader_uniforms = new PIXI.UniformGroup({
					u_bounds: {value: [size_renderer.width, size_renderer.height], type: 'vec2<f32>'},
					u_translation: {value: [0, 0], type: 'vec2<f32>'},
					u_stagetranslation: {value: [0, 0], type: 'vec2<f32>'},
					u_scale: {value: [1.0, 1.0], type: 'vec2<f32>'},
					//u_width_line: {value: width_line_min, type: 'f32'},
				});

				geometry_shader = new PIXI.Shader({
					glProgram: gl_program,
					gpuProgram: gpu_program,
					resources: {
						my_uniforms: geometry_shader_uniforms
					}
				});
				
				stage = func_stage_settings(new PIXI.Container());
				stage_2 = func_stage_settings(new PIXI.Container());
				
				elm_plot_lines = new PIXI.Container();
				elm_plot_dots = new PIXI.Container();
				elm_plot_info = new PIXI.Container();
				
				stage.addChild(elm_plot_lines);
				stage.addChild(elm_plot_dots);
				stage_2.addChild(elm_plot_info);
				
				drawer = renderer.canvas;
				elm[0].appendChild(drawer);
				drawer_2 = renderer_2.canvas;
				elm[0].appendChild(drawer_2);

				font_family = (font_family ? font_family : 'pixel');
				do_text_pixel = (font_family == 'pixel' ? true : false);

				num_text_scaled = size_text;
				if (do_text_scale) {
					num_text_scaled = Math.floor(num_scale * size_text);
					if (num_text_scaled < size_text_min) {
						num_text_scaled = size_text_min;
					} else if (num_text_scaled > size_text_max) {
						num_text_scaled = size_text_max;
					}
				}
				if (do_text_pixel) { // Resize text in blocks using remainder
					num_text_scaled = (num_text_scaled - (num_text_scaled % size_text_min));
				}
			} else {
				
				arr_size_initialise = {level: 0, width: 100000, height: 50000}; // Native rendering origin

				size_renderer = {width: arr_size_initialise.width, height: arr_size_initialise.height};
				
				renderer = document.createElementNS(stage_ns, 'svg');
				renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
				
				drawer = renderer;
				drawer.style.width = size_renderer.width+'px';
				drawer.style.height = size_renderer.height+'px';
				
				elm[0].appendChild(drawer);
				
				stage = renderer.ownerDocument;
				
				drawer_defs = stage.createElementNS(stage_ns, 'defs');
				drawer.appendChild(drawer_defs);
				
				svg_group = stage.createElementNS(stage_ns, 'g');		
				drawer.appendChild(svg_group);
				
				const drawer_style_body = stage.createElementNS(stage_ns, 'style');
				drawer.appendChild(drawer_style_body);
				
				const node_style = document.createTextNode(`
					*[data-visible="0"] { display: none; }
					circle { paint-order: stroke; }
				`);
				drawer_style_body.appendChild(node_style);
							
				if (use_capture) {
					
					count_start++; // Font loading
										
					font_family = (font_family ? font_family : 'pixel');
					size_text = (size_text - (size_text % 8));
					
					ASSETS.getFiles(elm_host, ['Unibody8Pro-Regular'], function(arr_files) {
						
						for (const str_identifier in arr_files) {
						
							const reader = new FileReader();
							reader.onload = function(e) {
								
								const str_url = e.target.result;

								const node_style = document.createTextNode(`
									@font-face { 
										font-family: 'pixel';
										src: url('`+str_url+`') format('woff');
										font-style: normal;
										font-weight: normal;
									}
								`);
								drawer_style_body.appendChild(node_style);
								
							};
							reader.readAsDataURL(arr_files[str_identifier]);
						}
						
						count_start--; // Font loaded
						
						func_start();
					}, {}, 'blob', '/css/fonts/', '.woff');
				}
				
				font_family = (font_family ? font_family : 'var(--font-site)');
				do_text_pixel = (font_family == 'pixel' ? true : false);
								
				if (!do_text_scale) { // Reversed scaling as whole svg already scales
					
					num_text_scaled = size_text / num_scale;
				} else {

					let num_text_calculate = num_scale * size_text;
					if (do_text_pixel) {
						num_text_calculate = (num_text_calculate - (num_text_calculate % size_text_min));
					}
					
					if (num_text_calculate < size_text_min) {
						num_text_scaled = size_text_min / num_scale;
					} else if (num_text_calculate > size_text_max) {
						num_text_scaled = size_text_max / num_scale;
					} else {
						num_text_scaled = num_text_calculate / num_scale;
					}
				}
			
				if (show_arrowhead) {
					
					var defs = stage.createElementNS(stage_ns, 'defs');
					var marker = stage.createElementNS(stage_ns, 'marker');
					var marker_path = stage.createElementNS(stage_ns, 'path');
					var marker_selected = stage.createElementNS(stage_ns, 'marker');
					var marker_selected_path = stage.createElementNS(stage_ns, 'path');
					
					svg_group.appendChild(defs);
					defs.appendChild(marker);
					defs.appendChild(marker_selected);
					
					marker.setAttribute('id', 'end');
					marker.setAttribute('class', 'marker-end');
					marker.setAttribute('viewBox', '0 -5 10 10');
					marker.setAttribute('refX', 15);
					marker.setAttribute('refY', -1.5);
					marker.setAttribute('fill', color_node_stroke);
					marker.setAttribute('markerWidth', 6);
					marker.setAttribute('markerHeight', 6);
					marker.setAttribute('orient', 'auto');
					
					marker_selected.setAttribute('id', 'end-selected');
					marker_selected.setAttribute('class', 'marker-end');
					marker_selected.setAttribute('viewBox', '0 -5 10 10');
					marker_selected.setAttribute('refX', 15);
					marker_selected.setAttribute('refY', -1.5);
					marker_selected.setAttribute('fill', '#ff9999');
					marker_selected.setAttribute('markerWidth', 6);
					marker_selected.setAttribute('markerHeight', 6);
					marker_selected.setAttribute('orient', 'auto');
					
					marker_path.setAttribute('d', 'M0,-5L10,0L0,5');
					marker_selected_path.setAttribute('d', 'M0,-5L10,0L0,5');
					
					marker.appendChild(marker_path);
					marker_selected.appendChild(marker_selected_path);
				}
			}

			addListeners();
			
			count_start--; // Main loaded
				
			func_start();
		});
	};
	
	this.close = function() {
		
		if (!has_init) { // Nothing loaded yet
			return;
		}

		elm_selected_node_container.remove();
		elm_search_container.remove();
		elm_layout.remove();
		
		in_first_run = false; // Abort doTick();
		ANIMATOR.animate(null, key_animate);
		PARENT.obj_map.move(null, key_move);
		if (simulation) {
			simulation.close();
		}
		
		if (display == DISPLAY_PIXEL) { // Destroy WEBGL memory
			
			stage.destroy(true);
			stage_2.destroy(true);
			renderer.destroy();
			renderer_2.destroy();
			
			for (const resource in arr_assets_texture_icons) {
				
				if (arr_assets_texture_icons[resource].texture) {
					arr_assets_texture_icons[resource].texture.destroy(true);
				}
			}
		}
	};
	
	var drawData = function(dateint_range_new, dateint_range_bounds, settings_timeline_new) {

		in_predraw = false;

		if (count_loop == 0) {
			
			in_predraw = true;
			
			parseData();

			var dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_bounds.min), max: DATEPARSER.dateInt2Absolute(dateint_range_bounds.max)};
			
			setCheckObjectSubs(dateinta_range);
			checkNodes();
			setNodesLinksValues();
			createLinkElements();
			
			for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
				
				const arr_node = arr_loop_nodes[i];
				
				if (!arr_node.is_active) {
					continue;
				}
				
				const num_index = arr_active_nodes.push(arr_node);
				
				arr_node.index = num_index - 1;
				
				drawNodeElement(arr_node);
			}
			
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}

				arr_active_links.push(arr_link);
			}
			
			simulation = new HandleSimulation();
			simulation.init();
			
			in_predraw = false;
		}

		var dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_new.min), max: DATEPARSER.dateInt2Absolute(dateint_range_new.max)};
					
		setCheckObjectSubs(dateinta_range);
		checkNodes();
		
		arr_active_nodes.splice(0, arr_active_nodes.length); // Clear live array
		arr_active_links.splice(0, arr_active_links.length); // Clear live array
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
		
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
			
			const num_index = arr_active_nodes.push(arr_node);
			
			arr_node.index = num_index - 1;
		}
					
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
			const arr_link = arr_loop_links[i];
			
			if (!arr_link.is_active) {
				continue;
			}
			
			arr_active_links.push(arr_link);
			
			setLinkColor(arr_link);
		}
		
		elm_layout_statistics[0].innerHTML = '<p>'+arr_labels.lbl_nodes+': '+arr_active_nodes.length+' '+arr_labels.lbl_links+': '+arr_active_links.length+'</p>';
		
		count_loop++; // New static data ready, increment loop to indicate new state for asynchronous processes
		
		simulation.start();
		
		if (count_loop == 1) {
			
			doTick();
		} else {
			
			do_draw = true;

			if (metrics_process) {
				metrics_process.update(dateinta_range);
			}
		}
		
		if (!key_animate) {
			
			key_animate = ANIMATOR.animate(function() {
				
				if (!do_draw) {
					
					if (!is_dragging_node && !in_first_run) {
						interact();
					}
					
					return true;
				}

				if (static_layout) {

					var interval = window.performance.now() - static_layout_timer;
					
					if (interval > static_layout_interval * 1000) {
						drawTick();
						static_layout_timer = window.performance.now();
					}
				} else {
					
					drawTick();
				}
				
				in_first_run = false;
				
				if (!is_dragging_node) {
					interact();
				}
				
				if (simulation.stopDraw()) {
					
					do_draw = false;
					
					if (static_layout) {
						drawTick();
					}
					
					simulation.stop();
				}
				
				return true;
			}, key_animate);
		}
	};
	
	var doTick = function() {
		
		if (!in_first_run) {
			return;
		}
		
		if (simulation.stopDraw()) {
			
			do_draw = false;
			in_first_run = false;
			
			drawTick();
			
			simulation.stop();
			
			return;
		} else {

			setTimeout(function () {
				doTick();
			}, 0);
		}
	};
	
	var HandleSimulation = function() {
		
		const SELF = this;
		
		this.draw = null;
		this.layout = null;
		
		var num_threshold = 1;
		const num_threshold_stop = 0.01;
		var str_layout = '';
				
		this.setSpeed = function(num_state) {
			
			num_threshold = num_state;
		};
		this.getSpeed = function() {
			
			return num_threshold;
		};
		this.getSpeedThreshold = function() {
			
			return num_threshold_stop;
		};
		this.stopDraw = function() {
			
			SELF.step();

			if (num_threshold < num_threshold_stop) { // Stop drawing if force is under threshold
				return true;
			} else {
				return false;
			}
		};
					
		this.setRunning = function(is_running_new) {
			
			const is_running = (is_running_new === false ? false : true);
			
			if (!is_running) {
				elm_layout[0].classList.remove('running');
			} else {
				elm_layout[0].classList.add('running');
			}
		};
		
		this.setRunningLayout = function(str_layout_new) {
			
			var str_layout_new = (str_layout_new ? str_layout_new : '');
			
			if (str_layout === str_layout_new) {
				return false;
			}
			
			str_layout = str_layout_new;
			elm_layout_select[0].value = str_layout;
			
			return true;
		};
		this.isRunningLayout = function(str_layout_check) {

			if (str_layout === (str_layout_check ? str_layout_check : '')) {
				return true;
			}
			
			return false;
		};
		
		this.setRunningStatistics = function(str_html) {
			
			elm_layout_status[0].innerHTML = str_html;
		};
		
		this.draw = (use_simulation_native ? new HandleSimulationDrawNative(SELF) : new HandleSimulationDrawWorker(SELF)); // Default drawing native/fallback
		this.layout = new HandleSimulationLayout(SELF); // Extendable layout algorithms
	};
	
	var HandleSimulationDrawNative = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
			
		var simulate = false;
		var simulate_force_links = false;
		
		PARENT.init = function() {
			
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 0%</p>');
			
			simulate_force_links = d3.forceLink();
			
			simulate = d3.forceSimulation()
				.nodes(arr_active_nodes)
				.force('charge', d3.forceManyBody()
					.strength(force_options.charge)
					.theta(force_options.theta)
				)
				.force('link', simulate_force_links
					.links(arr_active_links)
					.distance(function(d) {
						return ((1 - (d.weight / num_link_weight_max))*80) + size_node_max;
					}))
				.force("x", d3.forceX(size_renderer.width / 2)
					.strength(force_options.gravity)
				)
				.force("y", d3.forceY(size_renderer.height / 2)
					.strength(force_options.gravity)
				)
				//.force('center', d3.forceCenter(size_renderer.width / 2, size_renderer.height / 2)
				//	.strength(force_options.gravity)
				//)
				.velocityDecay(force_options.friction)
			;
			simulate.stop();
		};
		PARENT.start = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout();
			PARENT.setSpeed(1);
			
			simulate.nodes(arr_active_nodes);
			simulate_force_links.links(arr_active_links);
			
			simulate.alpha(1);
		};
		PARENT.step = function() {
			
			if (!PARENT.isRunningLayout()) { // Could be running an other layout
				return;
			}
			
			simulate.tick();
			
			PARENT.setSpeed(simulate.alpha());
			
			const has_stopped = (PARENT.getSpeed() < PARENT.getSpeedThreshold());
			
			if (!has_stopped) {
				
				const num_perc = 100*((1 - PARENT.getSpeed()) / (1 - PARENT.getSpeedThreshold()));
				
				PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': '+num_perc.toFixed(2)+'%</p>');
				
			} else {

				PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 100%</p>');
			}
		};
		PARENT.resume = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout();
			PARENT.setSpeed(0.1);
			
			simulate.alpha(0.1);
		};		
		PARENT.stop = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning(false);
			PARENT.setSpeed(0);
			
			simulate.alpha(0);
		};
		PARENT.close = function() {
			
			PARENT.stop();
		};
		PARENT.resize = function() {
			
			simulate.force("x", d3.forceX(size_renderer.width / 2)
					.strength(force_options.gravity)
				)
				.force("y", d3.forceY(size_renderer.height / 2)
					.strength(force_options.gravity)
				)
			;
		};
	};
	
	var HandleSimulationDrawWorker = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
		
		let worker = null;
		let is_running = false;
		let is_running_waiting = false;
		let identifier_running = null;
		
		let arr_matrix_nodes = null;
		let arr_matrix_edges = null;
		const num_properties_nodes = 5;
		const num_properties_edges = 3;
				
		PARENT.init = function() {
			
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 0%</p>');
			
			setMatrix();
			
			worker = createForceWorker();

			worker.addEventListener('message', function(event) {
				
				const has_identifier = (event.data.identifier !== null);
				const is_actual = (is_running && has_identifier && event.data.identifier == identifier_running); // Is the running iteration still relevant?
				
				arr_matrix_nodes = new Float32Array(event.data.nodes);
				
				const arr_nodes_matrix_index = [];
				
				for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
					const num_index = arr_matrix_nodes[i + 4];
					const arr_node = arr_loop_nodes[num_index];
										
					if (is_actual && (arr_node.fixed || arr_node.fixed != arr_matrix_nodes[i + 3])) {
						
						if (arr_node.fixed) {
						
							arr_matrix_nodes[i] = arr_node.x;
							arr_matrix_nodes[i + 1] = arr_node.y;
						}
						
						arr_matrix_nodes[i + 3] = arr_node.fixed;
							
						arr_nodes_matrix_index.push(i);
					} else {
						
						arr_node.x = arr_matrix_nodes[i];
						arr_node.y = arr_matrix_nodes[i + 1];
					}
				}
				
				if (!is_running || !has_identifier) {
					return;
				}

				if (is_actual) { // Continue loop
					
					PARENT.setSpeed(event.data.alpha);
					const has_stopped = (PARENT.getSpeed() < PARENT.getSpeedThreshold());
					
					if (!has_stopped) {
						
						worker.postMessage({
								action: 'loop',
								nodes: arr_matrix_nodes.buffer,
								nodes_state: (arr_nodes_matrix_index.length ? arr_nodes_matrix_index : false),
								identifier: identifier_running,
								iterations: 1
							},
							[arr_matrix_nodes.buffer]
						);
						
						const num_perc = 100*((1 - PARENT.getSpeed()) / (1 - PARENT.getSpeedThreshold()));
						
						PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': '+num_perc.toFixed(2)+'%</p>');
					} else {

						identifier_running = null; // Loop has finished
						
						PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 100%</p>');
					}
				} else { // Loop end, check for new data

					is_running_waiting = true;
					PARENT.start();
				}
			});
			
			worker.postMessage({
					action: 'init',
					nodes: arr_matrix_nodes.buffer,
					settings: {
						friction: force_options.friction,
						charge: force_options.charge,
						gravity: force_options.gravity,
						charge: force_options.charge,
						theta: force_options.theta,
						link_weight_max: num_link_weight_max,
						node_size_max: size_node_max,
						width: size_renderer.width,
						height: size_renderer.height
					}
				}
			);
		};
		PARENT.start = function() {
			
			if (identifier_running !== null) { // Loop is running
				
				if (!is_running_waiting) { // Invalidate data and rerun start() from inside loop
					
					identifier_running = count_loop;
					return;
				}
				
				is_running_waiting = false;
			} else if (is_running_waiting) {
				
				is_running_waiting = false;
				return;
			}
			
			// (Re)start: new data
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			const has_changed = PARENT.setRunningLayout();
			
			setMatrix();
			
			is_running = true;
			identifier_running = count_loop;
			PARENT.setSpeed(1);
			
			worker.postMessage({
					action: 'start',
					nodes: arr_matrix_nodes.buffer,
					nodes_state: (has_changed ? 'update' : false),
					alpha: 1,
					edges: arr_matrix_edges.buffer,
					identifier: identifier_running,
					iterations: 1
				},
				[arr_matrix_nodes.buffer, arr_matrix_edges.buffer]
			);
		};
		PARENT.step = function() {};
		PARENT.resume = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			const has_changed = PARENT.setRunningLayout();

			if (!is_running || identifier_running === null) { // Hard resume: continue, no new data
				
				is_running = true;
				identifier_running = count_loop;
				PARENT.setSpeed(0.1);
				
				if (has_changed) {
					updateMatrixNodes();
				}

				worker.postMessage({
						action: 'resume',
						nodes: arr_matrix_nodes.buffer,
						nodes_state: (has_changed ? 'update' : 'pass'),
						alpha: 0.1,
						identifier: identifier_running,
						iterations: 1
					},
					[arr_matrix_nodes.buffer]
				);
			} else { // Soft resume: interact (i.e. drag) with data still at the worker
				
				is_running = true;
				PARENT.setSpeed(0.1);
				
				worker.postMessage({
						action: 'resume',
						alpha: 0.1,
						identifier: identifier_running,
						iterations: 1
					}
				);
			}
		};
		PARENT.stop = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning(false);
			PARENT.setSpeed(0);
			is_running = false;
			is_running_waiting = false;
			identifier_running = null;
			
			worker.postMessage({
					action: 'stop'
				}
			);
		};
		PARENT.close = function() {
			
			PARENT.stop();
			
			if (!worker) {
				return;
			}
			
			worker.terminate();
			worker = null;
		};
		PARENT.resize = function() {
		
			worker.postMessage({
					action: 'settings',
					settings: {
						width: size_renderer.width,
						height: size_renderer.height,
						gravity: force_options.gravity
					}
				}
			);
		};
		
		var setMatrix = function() {
			
			// Allocating Byte arrays
			let len_matrix = arr_active_nodes.length * num_properties_nodes;
			arr_matrix_nodes = new Float32Array(len_matrix);
			len_matrix = arr_active_links.length * num_properties_edges;
			arr_matrix_edges = new Float32Array(len_matrix);
						
			// Iterate through nodes
			for (let i = 0, j = 0, len = arr_active_nodes.length; i < len; i++) {
				
				const arr_node = arr_active_nodes[i];
				
				// Populating byte array
				arr_matrix_nodes[j] = arr_node.x;
				arr_matrix_nodes[j + 1] = arr_node.y;
				arr_matrix_nodes[j + 2] = arr_node.weight;
				arr_matrix_nodes[j + 3] = arr_node.fixed;
				arr_matrix_nodes[j + 4] = arr_node.count;
				
				j += num_properties_nodes;
			}
			
			// Iterate through edges
			for (let i = 0, j = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_link = arr_active_links[i];
				
				arr_matrix_edges[j] = arr_link.source.index;
				arr_matrix_edges[j + 1] = arr_link.target.index;
				arr_matrix_edges[j + 2] = arr_link.weight;
				
				j += num_properties_edges;
			}
		};
		
		var updateMatrixNodes = function() {
			
			for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
				
				const num_index = arr_matrix_nodes[i + 4];
				const arr_node = arr_loop_nodes[num_index];
				
				arr_matrix_nodes[i] = arr_node.x;
				arr_matrix_nodes[i + 1] = arr_node.y;
				arr_matrix_nodes[i + 3] = arr_node.fixed;
			}
		};
				
		var createForceWorker = function() {
			
			var func_worker = function() {
				
				let simulate = false;
				let simulate_force_links = false;
				
				let arr_nodes = [];
				let arr_active_edges = [];
				let arr_active_nodes = [];
				
				let arr_matrix_nodes = false;
				let arr_matrix_edges = false;
				
				const num_properties_nodes = 5;
				const num_properties_edges = 3;
				
				function init(arr_settings) {
					
					simulate_force_links = d3.forceLink();
					
					simulate = d3.forceSimulation()
						.nodes(arr_active_nodes)
						.force('charge', d3.forceManyBody()
							.strength(arr_settings.charge)
							.theta(arr_settings.theta)
						)
						.force('link', simulate_force_links
							.links(arr_active_edges)
							.distance(function(d) {
								return ((1 - (d.weight / arr_settings.link_weight_max))*80) + arr_settings.node_size_max;
							}))
						.force("x", d3.forceX(arr_settings.width / 2)
							.strength(arr_settings.gravity)
						)
						.force("y", d3.forceY(arr_settings.height / 2)
							.strength(arr_settings.gravity)
						)
						//.force('center', d3.forceCenter(arr_settings.width / 2, arr_settings.height / 2)
						//	.strength(arr_settings.gravity)
						//)
						.velocityDecay(arr_settings.friction)
					;
					simulate.stop();
				}
				
				function initNodes(arr_matrix_nodes_buffer) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
					
					for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {

						var arr_node = {
							x: arr_matrix_nodes[i],
							y: arr_matrix_nodes[i + 1],
							weight: arr_matrix_nodes[i + 2],
							fixed: arr_matrix_nodes[i + 3],
							count: arr_matrix_nodes[i + 4]
						};
						
						if (arr_node.fixed) {
							arr_node.fx = arr_node.x;
							arr_node.fy = arr_node.y;
						}
						
						arr_nodes[arr_node.count] = arr_node;
						arr_active_nodes.push(arr_node);
					}		
				}
				
				function passNodes(arr_matrix_nodes_buffer) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
				}
				
				function setNodes(arr_matrix_nodes_buffer, do_position) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
					
					arr_active_nodes = [];
					
					for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
						
						const num_count = arr_matrix_nodes[i + 4];

						var arr_node = arr_nodes[num_count];
						
						arr_node.weight = arr_matrix_nodes[i + 2];
						
						if (do_position) {
							
							arr_node.x = arr_matrix_nodes[i];
							arr_node.y = arr_matrix_nodes[i + 1];
							
							arr_node.fixed = arr_matrix_nodes[i + 3];
							if (arr_node.fixed) {
								arr_node.fx = arr_node.x;
								arr_node.fy = arr_node.y;
							} else {
								arr_node.fx = null;
								arr_node.fy = null;
							}
						}

						arr_active_nodes.push(arr_node);
					}
					
					simulate.nodes(arr_active_nodes);
				}
				
				function updateNodesByMatrixIndex(arr_node_indices) {
										
					for (let i = 0, len = arr_node_indices.length; i < len; i++) {
						
						const num_index_matrix = arr_node_indices[i];										
						const num_count = arr_matrix_nodes[num_index_matrix + 4];

						var arr_node = arr_nodes[num_count];
						
						arr_node.x = arr_matrix_nodes[num_index_matrix];
						arr_node.y = arr_matrix_nodes[num_index_matrix + 1];
						
						arr_node.fixed = arr_matrix_nodes[num_index_matrix + 3];
						if (arr_node.fixed) {
							arr_node.fx = arr_node.x;
							arr_node.fy = arr_node.y;
						} else {
							arr_node.fx = null;
							arr_node.fy = null;
						}
					}		
				}
				
				function getNodes() {
					
					for (let i = 0, j = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
						const arr_node = arr_active_nodes[j];
						
						arr_matrix_nodes[i] = arr_node.x;
						arr_matrix_nodes[i + 1] = arr_node.y;
						arr_matrix_nodes[i + 4] = arr_node.count;
						
						j++;
					}
					
					return arr_matrix_nodes;
				}
				
				function setEdges(arr_matrix_edges_buffer) {
					
					arr_matrix_edges = arr_matrix_edges_buffer;
					
					arr_active_edges = [];
					
					for (let i = 0, len = arr_matrix_edges.length; i < len; i += num_properties_edges) {
				
						const arr_edge = {
							source: arr_active_nodes[arr_matrix_edges[i]],
							target: arr_active_nodes[arr_matrix_edges[i + 1]],
							weight: arr_matrix_edges[i + 2]
						};
						
						arr_active_edges.push(arr_edge);
					}
					
					simulate_force_links.links(arr_active_edges);
				}

				function setConfiguration(arr_settings) {
					
					simulate.force("x", d3.forceX(arr_settings.width / 2)
							.strength(arr_settings.gravity)
						)
						.force("y", d3.forceY(arr_settings.height / 2)
							.strength(arr_settings.gravity)
						)
					;
				}
				
				function ready() {
					
					const arr_matrix_nodes_buffer = getNodes();
					
					self.postMessage(
						{
							nodes: arr_matrix_nodes_buffer.buffer,
							alpha: simulate.alpha(),
							identifier: null
						},
						[arr_matrix_nodes_buffer.buffer]
					);
				}
						
				function run(n, identifier) {
					
					for (let i = 0; i < n; i++) {
						simulate.tick();
					}
					
					const arr_matrix_nodes_buffer = getNodes();
					
					self.postMessage(
						{
							nodes: arr_matrix_nodes_buffer.buffer,
							alpha: simulate.alpha(),
							identifier: identifier
						},
						[arr_matrix_nodes_buffer.buffer]
					);
				}

				var func_listener = function(e) {
					
					const nodes_state = (e.data.nodes_state != null ? e.data.nodes_state : false);
					
					switch (e.data.action) {
						case 'init':
						
							initNodes(new Float32Array(e.data.nodes));
													
							init(e.data.settings);
							
							ready();
							break;
							
						case 'settings':

							setConfiguration(e.data.settings);
							break;
							
						case 'start':

							setNodes(new Float32Array(e.data.nodes), (nodes_state == 'update' ? true : false));
							setEdges(new Float32Array(e.data.edges));
							
							simulate.alpha(e.data.alpha);
							
							run(e.data.iterations, e.data.identifier);
							break;
							
						case 'resume':
														
							if (nodes_state == 'pass') {
								passNodes(new Float32Array(e.data.nodes));
							} else if (nodes_state == 'update') {
								setNodes(new Float32Array(e.data.nodes), true);
							}
							
							simulate.alpha(e.data.alpha);
							
							if (nodes_state) {
								run(e.data.iterations, e.data.identifier);
							}
							break;
							
						case 'loop':
						
							passNodes(new Float32Array(e.data.nodes));
							
							if (e.data.nodes_state) {
								updateNodesByMatrixIndex(e.data.nodes_state);
							}
							
							run(e.data.iterations, e.data.identifier);
							break;
								
						case 'stop':
						
							simulate.alpha(0);
							break;

						default:
					}
				};

				self.addEventListener('message', func_listener);
			};

			return ASSETS.createWorker(func_worker, ['/js/support/d3-force.pack.js']);
		};
	};
	
	var HandleSimulationLayout = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
		
		let worker = null;

		this.startLayoutForceAtlas2 = function() {
			
			PARENT.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout('forceatlas2');
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_iterations+': 0</p>');
			
			PARENT.setSpeed(1); // Continuous
			
			const num_properties_nodes = 9;
			const num_properties_edges = 3;
			
			// Allocating Byte arrays
			let len_matrix = arr_active_nodes.length * num_properties_nodes;
			arr_matrix_nodes = new Float32Array(len_matrix);
			len_matrix = arr_active_links.length * num_properties_edges;
			arr_matrix_edges = new Float32Array(len_matrix);
			
			let count_iteration = 0;
			let num_graph_x = (size_renderer.width / 2); // Adjust algorithm center (0,0) to our center
			let num_graph_y = (size_renderer.height / 2);
			
			// Iterate through nodes
			for (let i = 0, j = 0, len = arr_active_nodes.length; i < len; i++) {
				
				const arr_node = arr_active_nodes[i];

				// Populating byte array
				arr_matrix_nodes[j] = arr_node.x - num_graph_x;
				arr_matrix_nodes[j + 1] = arr_node.y - num_graph_y;
				arr_matrix_nodes[j + 2] = 0;
				arr_matrix_nodes[j + 3] = 0;
				arr_matrix_nodes[j + 4] = 0;
				arr_matrix_nodes[j + 5] = 0;
				arr_matrix_nodes[j + 6] = 1 + arr_node.weight; // Base needs 1, never 0
				arr_matrix_nodes[j + 7] = arr_node.radius; // Providing radius as 'size' yields best results
				arr_matrix_nodes[j + 8] = arr_node.fixed;
				
				j += num_properties_nodes;
			}
			
			// Iterate through edges
			for (let i = 0, j = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_link = arr_active_links[i];
				
				arr_matrix_edges[j] = arr_link.source.index * num_properties_nodes;
				arr_matrix_edges[j + 1] = arr_link.target.index * num_properties_nodes;
				arr_matrix_edges[j + 2] = arr_link.weight;
				
				j += num_properties_edges;
			}
			
			worker = createForceAtlas2Worker();
			
			worker.postMessage({
					action: 'start',
					nodes: arr_matrix_nodes.buffer,
					edges: arr_matrix_edges.buffer,
					iterations: 1,
					auto: true, // Do auto-configure
					metrics: false,
					settings: {
						linLogMode: forceatlas2_options.lin_log_mode,
						adjustSizes: forceatlas2_options.adjust_sizes,
						edgeWeightInfluence: forceatlas2_options.edge_weight_influence,
						strongGravityMode: forceatlas2_options.strong_gravity_mode,
						gravity: forceatlas2_options.gravity, // Auto
						scalingRatio: forceatlas2_options.scaling_ratio, // Auto
						outboundAttractionDistribution: forceatlas2_options.outbound_attraction_distribution, // Auto
						barnesHutOptimize: (forceatlas2_options.optimize_theta !== null ? (forceatlas2_options.optimize_theta > 0) : null), // Auto
						barnesHutTheta: forceatlas2_options.optimize_theta, // Auto
						jitterTolerance: null // Auto
					}
				},
				[arr_matrix_nodes.buffer, arr_matrix_edges.buffer]
			);

			worker.addEventListener('message', function(event) {
				
				if (event.data.action === 'configured') { // Optional, use reported auto configuration
					
					//console.log(event.data.settings);
					return;
				}
				
				arr_matrix_nodes = new Float32Array(event.data.nodes);
				
				for (let i = 0, j = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
					const arr_node = arr_active_nodes[j];
					
					arr_node.x = arr_matrix_nodes[i] + num_graph_x;
					arr_node.y = arr_matrix_nodes[i + 1] + num_graph_y;
					
					j++;
				}
				
				if (event.data.metrics !== null) { // Optional, live metrics
				
				}
				
				if (worker) {
					
					count_iteration++;
					PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_iterations+': '+count_iteration+'</p>');
					
					worker.postMessage({
							action: 'loop',
							nodes: arr_matrix_nodes.buffer,
							iterations: 1,
							metrics: false
						},
						[arr_matrix_nodes.buffer]
					);
				}
			});
		};

		var createForceAtlas2Worker = function() {
			
			var func_worker = function() {
		
				var forceatlas2 = new LayoutForceAtlas2();
				
				const arr_payload = {nodes: null, metrics: null};
				
				function run(n, do_metrics) {

					for (let i = 0; i < n; i++) {
						forceatlas2.pass();
					}
					
					arr_payload.metrics = null;
					if (do_metrics === true) {
						arr_payload.metrics = forceatlas2.getMetrics();
					}

					arr_matrix_nodes = forceatlas2.getNodes();
					arr_payload.nodes = arr_matrix_nodes.buffer;

					self.postMessage(arr_payload, [arr_matrix_nodes.buffer]);
				}

				var func_listener = function(e) {
					
					switch (e.data.action) {
						case 'start':
						
							forceatlas2.init(
								new Float32Array(e.data.nodes),
								new Float32Array(e.data.edges),
								e.data.settings
							);

							if (e.data.auto) { // Optional, auto-configure.
								
								self.postMessage({
									action: 'configured',
									settings: forceatlas2.autoConfigure(e.data.settings)
								});
							}

							run(e.data.iterations, e.data.metrics);
							break;

						case 'loop':
						
							forceatlas2.setNodes(new Float32Array(e.data.nodes));
							
							run(e.data.iterations, e.data.metrics);
							break;

						case 'settings':

							forceatlas2.setConfiguration(e.data.settings);
							break;

						default:
					}
				};

				self.addEventListener('message', func_listener);
			};

			return ASSETS.createWorker(func_worker, ['/js/LayoutForceAtlas2.js']);
		};
		
		this.stop = function() {
			
			if (!worker) {
				return false;
			}
			
			worker.terminate();
			
			worker = null;
			arr_matrix_nodes = null;
			arr_matrix_edges = null;
			
			return true;
		};
	}
		
	var interact = function() {
		
		const pos_hover = PARENT.obj_map.getMousePosition();
			
		if (!pos_hover) {
			
			if (pos_hover_poll) {
				
				pos_hover_poll = false;
				hoverNode(false, false);
				cur_node_id = false;
				elm_host.classList.remove('hovering');
			}
		} else {
						
			const x_point = pos_hover.x;
			const y_point = pos_hover.y;

			if (!pos_hover_poll || (Math.abs(x_point-pos_hover_poll.x) > 0 || Math.abs(y_point-pos_hover_poll.y) > 0)) {
			
				pos_hover_poll = pos_hover;
				let is_hovering = false;

				for (let i = 0, len = arr_active_nodes.length; i < len; i++) {
					
					const arr_node = arr_active_nodes[i];
					const num_dx = (((arr_node.x + pos_translation.x) * num_scale) + pos_stage.x) - pos_hover_poll.x;
					const num_dy = (((arr_node.y + pos_translation.y) * num_scale) + pos_stage.y) - pos_hover_poll.y;
					
					const num_distance_squared = num_dx * num_dx + num_dy * num_dy;
					const num_radius_squared = ((arr_node.radius * num_scale) + 2) * ((arr_node.radius * num_scale) + 2);

					if (num_distance_squared < num_radius_squared) {
						
						is_hovering = true;
						
						if (cur_node_id !== arr_node.id) {
								
							hoverNode(arr_node, true);
							cur_node_id = arr_node.id;
							elm_host.classList.add('hovering');
						}
					}
				}
				
				if (cur_node_id !== false && !is_hovering) {
					
					hoverNode(false, false);
					cur_node_id = false;
					elm_host.classList.remove('hovering');
				}
			}
		}	
    }

	var rePosition = function(move, pos, zoom, calc_zoom) {

		const num_width = pos.size.width;
		const num_height = pos.size.height;

		if (calc_zoom) { // Zoomed related
			
			if (display == DISPLAY_PIXEL) {
				
				if (typeof calc_zoom == 'string') { // Zoom in/out
					
					const num_zoom = parseInt(calc_zoom);
					
					if (num_zoom < 0) {
						num_scale = num_scale * Math.pow(0.7, Math.abs(num_zoom));
					} else if (num_zoom > 0) {
						num_scale = num_scale * Math.pow(1.4286, num_zoom);
					}
					
					if (do_text_scale) {
						
						num_text_scaled = Math.floor(num_scale * size_text);
						if (num_text_scaled < size_text_min) {
							num_text_scaled = size_text_min;
						} else if (num_text_scaled > size_text_max) {
							num_text_scaled = size_text_max;
						}
						if (do_text_pixel) {
							num_text_scaled = (num_text_scaled - (num_text_scaled % size_text_min));
						}
					}
				}
			} else {
				
				num_scale = num_width / arr_size_initialise.width;
				
				if (!do_text_scale) { // Reversed scaling as whole svg already scales
					
					num_text_scaled = size_text / num_scale;
				} else {
					
					let num_text_calculate = num_scale * size_text;
					if (do_text_pixel) {
						num_text_calculate = (num_text_calculate - (num_text_calculate % size_text_min));
					}
					
					if (num_text_calculate < size_text_min) {
						num_text_scaled = size_text_min / num_scale;
					} else if (num_text_calculate > size_text_max) {
						num_text_scaled = size_text_max / num_scale;
					} else {
						num_text_scaled = num_text_calculate / num_scale;
					}
				}
			}

			do_redraw = true;
		}
		
		if (display == DISPLAY_PIXEL) {
			
			if (num_width != size_renderer.width || num_height != size_renderer.height) {
				
				do_redraw = true;
				do_draw = true;
					
				size_renderer.resolution = pos.render.resolution;
				
				renderer.resize(num_width, num_height);
				renderer.resolution = size_renderer.resolution;
				renderer_2.resize(num_width, num_height);
				renderer_2.resolution = size_renderer.resolution;
				
				elm_canvas.width = num_width;
				elm_canvas.height = num_height;	
				
				geometry_shader_uniforms.uniforms.u_bounds[0] = num_width;
				geometry_shader_uniforms.uniforms.u_bounds[1] = num_height;
				
				if (simulation) {
					simulation.resize();
					simulation.resume();
				}
			}
			
			if (move === true) { // Move Starts
				
				pos_move.x = pos.x;
				pos_move.y = pos.y;
				is_dragging = true;
			}
			if (move !== false && is_dragging && !is_dragging_node) { // Moving...
				
				pos_translation.x = pos_translation.x - ((pos_move.x - pos.x) / num_scale);
				pos_translation.y = pos_translation.y - ((pos_move.y - pos.y) / num_scale);
				
				geometry_shader_uniforms.uniforms.u_translation[0] = pos_translation.x;
				geometry_shader_uniforms.uniforms.u_translation[1] = pos_translation.y;

				pos_move.x = pos.x;
				pos_move.y = pos.y;
			}
			if (move === false && is_dragging) { // Move Ends
				is_dragging = false;
			}
			
			if (typeof calc_zoom == 'string') { // Zoom in/out
				
				pos_stage.x = (num_width - (num_width * num_scale)) / 2;
				pos_stage.y = (num_height - (num_height * num_scale)) / 2;
				
				stage.position.x = pos_stage.x;
				stage_2.position.x = pos_stage.x;
				
				stage.position.y = pos_stage.y;
				stage_2.position.y = pos_stage.y;

				geometry_shader_uniforms.uniforms.u_stagetranslation[0] = pos_stage.x;
				geometry_shader_uniforms.uniforms.u_stagetranslation[1] = pos_stage.y;
				geometry_shader_uniforms.uniforms.u_scale[0] = num_scale;
				geometry_shader_uniforms.uniforms.u_scale[1] = num_scale;
			}
			
			geometry_shader_uniforms.update();
		} else {
				
			drawer.style.width = num_width+'px';
			drawer.style.height = num_height+'px';
			
			svg_group.setAttribute('transform', 'scale('+num_scale+')');
		}
		
		size_renderer.width = num_width;
		size_renderer.height = num_height;

		if (!in_first_run) {	
			drawTick();
		}
	};
	
	var createLinkElements = function() {	
		
		if (!show_line) {
			return;
		}
		
		if (display == DISPLAY_PIXEL) {
			
			buffer_geometry_lines_index = new PIXI.Buffer({data: new Uint32Array(arr_loop_links.length * 6), usage: PIXI.BufferUsage.INDEX | PIXI.BufferUsage.COPY_DST});
			buffer_geometry_lines_position = new PIXI.Buffer({data: new Float32Array(arr_loop_links.length * 4 * 2), usage: PIXI.BufferUsage.VERTEX | PIXI.BufferUsage.COPY_DST});
			buffer_geometry_lines_color = new PIXI.Buffer({data: new Float32Array(arr_loop_links.length * 4 * 4), usage: PIXI.BufferUsage.VERTEX | PIXI.BufferUsage.COPY_DST});
			//buffer_geometry_lines_normal = new PIXI.Buffer({data: new Float32Array(arr_loop_links.length * 4 * 2), usage: PIXI.BufferUsage.VERTEX});

			const geometry_lines = new PIXI.Geometry({
				attributes: {
					a_position: {buffer: buffer_geometry_lines_position, size: 2, format: 'float32x2', instance: false},
					a_color: {buffer: buffer_geometry_lines_color, size: 4, format: 'float32x4', instance: false},
					//a_normal: {buffer: buffer_geometry_lines_normal, size: 2},
				},
				indexBuffer: buffer_geometry_lines_index
			});

			const geometry_mesh = new PIXI.Mesh({geometry: geometry_lines, shader: geometry_shader});
			geometry_mesh.blendMode = 'normal';
			
			elm_plot_lines.addChild(geometry_mesh);
			
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {
				
				const arr_link = arr_loop_links[i];
				
				const num_buffer_offset = arr_link.count * 6; // 6 indices per link that refer to one of the available 4 vertex positions
				const num_index_offset = arr_link.count * 4; // The index references the 4 available vertices
				
				const arr_indices = buffer_geometry_lines_index.data;
				
				arr_indices[num_buffer_offset + 0] = num_index_offset + 0;
				arr_indices[num_buffer_offset + 1] = num_index_offset + 1;
				arr_indices[num_buffer_offset + 2] = num_index_offset + 2;
				
				arr_indices[num_buffer_offset + 3] = num_index_offset + 1;
				arr_indices[num_buffer_offset + 4] = num_index_offset + 3;
				arr_indices[num_buffer_offset + 5] = num_index_offset + 2;
			}
			
			buffer_geometry_lines_index.update();
		} else {
					
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {
				
				const arr_link = arr_loop_links[i];
				
				arr_link.elm = stage.createElementNS(stage_ns, 'path');
				svg_group.appendChild(arr_loop_links[i].elm);
				
				arr_link.elm.setAttribute('fill', 'none');
				arr_link.elm.setAttribute('stroke', arr_loop_links[i].color);
				arr_link.elm.setAttribute('stroke-width', width_line_min+'px');
				
				if (show_arrowhead) {
					arr_link.elm.setAttribute('marker-end', 'url(#end)');
				}
			}
		}
	};
	
	var drawNodeElement = function(arr_node) {
		
		if (arr_node.redraw_node === false && do_redraw && display == DISPLAY_VECTOR) { // Quick update
			
			if (arr_node.elm_text !== null) {
				arr_node.elm_text.style.fontSize = num_text_scaled+'px';
			}
			
			return;
		}
			
		let elm = arr_node.elm;
		let do_highlight = false;
		let str_identifier = '';
			
		// Set the primary color of the node
		if (!arr_node.color) {
			
			if (arr_node.style && arr_node.style.color) {
				
				// Color set by Object
				if (typeof arr_node.style.color == 'object') { // Select last color color contains multiple values
					arr_node.color = arr_node.style.color[arr_node.style.color.length-1];
				} else {
					arr_node.color = arr_node.style.color;
				}
			} else if (arr_data.legend.types && arr_data.legend.types[arr_node.type_id] && arr_data.legend.types[arr_node.type_id].color) {
				
				// Color set by Type
				arr_node.color = arr_data.legend.types[arr_node.type_id].color;
			} else {
				
				// Color set by Visualisation 
				arr_node.color = color_node;
			}
		}
		
		let str_color = arr_node.color;
		
		if (arr_node.highlight_color) {
			
			str_color = arr_node.highlight_color;
			
			do_highlight = true;
			arr_node.highlight_color = false;
		}
		
		str_identifier += str_color;
	
		if (!do_highlight && arr_node.has_conditions) {
			
			// update conditioned colors and weight
			handleConditions(arr_node);
			
			str_identifier += arr_node.identifier_condition;
		}
		
		const num_count_links = (arr_node.count_in + arr_node.count_out);
		
		if (is_weighted) {

			let num_weight_conditions = arr_node.weight_conditions;
			
			if (size_node_start && num_weight_conditions < size_node_start) {
				num_weight_conditions = size_node_start;
			}
			
			if (size_node_stop && num_weight_conditions > size_node_stop) {
				num_weight_conditions = size_node_stop;
			}
			
			arr_node.weight = num_weight_conditions;
		} else {
			
			arr_node.weight = num_count_links;
		}
		
		let num_size = 0;
		
		if (num_count_links == 0 && !show_disconnected_node) {
			
			num_size = 0;
		} else if (!arr_node.is_alive) { // Set Radius to 0 when node has no position (has been removed when it fell out of the selection)
			
			num_size = (show_disconnected_node ? size_node_min : 0);
		} else {
			
			if (arr_node.weight == 0) {
				
				if (show_disconnected_node) {
					num_size = size_node_min;
				}
			} else {
				
				if (num_node_weight_max == num_node_weight_min) {
					num_size = size_node_max;
				} else {
				
					const num_weight_ratio = ((arr_node.weight - num_node_weight_min) / (num_node_weight_max - num_node_weight_min));
					num_size = (size_node_min + (num_weight_ratio * (size_node_max - size_node_min)));
				}
			}
		}
		
		const num_size_radius = (num_size / 2); // Diameter to radius
		
		str_identifier += num_size_radius+'-'+num_scale;
				
		if (str_identifier == arr_node.identifier) {

			if (display == DISPLAY_PIXEL) {
				
				if (elm !== null) { // arr_node.elm could be missing for DISPLAY_PIXEL when num_size is 0. arr_node.elm always exists for DISPLAY_VECTOR as it's used for grouping
					
					elm.visible = true;
					if (arr_node.show_text) {
						arr_node.elm_text.visible = true;
					}
				}
			} else {
				
				elm.dataset.visible = 1;
			}
		} else {

			arr_node.radius = Math.round(num_size_radius + width_node_stroke);

			if (display == DISPLAY_PIXEL) {
				
				pos_elm = null;
							
				if (elm === null) {

					if (arr_node.show_text) {
						
						const elm_text = new PIXI.Text(arr_node.name_text, {fontSize: num_text_scaled, fontFamily: font_family, fill: color_text});
						elm_text.alpha = opacity_text;
						
						elm_text.anchor.set(0, 0.5);
						
						elm_plot_info.addChild(elm_text);

						arr_node.elm_text = elm_text;
					}
				} else {

					elm.visible = true;
					
					if (arr_node.elm_text !== null && do_text_scale) {
						arr_node.elm_text.style.fontSize = num_text_scaled;
					}
					
					pos_elm = elm.position;
				}
				
				if (arr_node.elm_text !== null) {
					arr_node.elm_text.visible = (arr_node.show_text && num_size_radius);
				}
				
				if (num_size_radius) {
					
					const num_size_radius_scaled = num_size_radius * num_scale;
					const num_width_stroke_scaled = width_node_stroke * num_scale;
					
					if (arr_node.has_conditions) {
						
						if (elm === null) {
							
							elm = new PIXI.Container();
							elm_plot_dots.addChild(elm);
							
							arr_node.elm = elm;
						}
						
						let num_index = 0;

						if (!show_icon_as_node) {
							
							let arr_colors = arr_node.colors;
							
							if (arr_colors !== null && arr_colors.length == 1 && !do_highlight) {
								
								str_color = arr_colors[0].color;
								arr_colors = null;
							}
							
							let elm_stroke = elm.children[0];
							elm_stroke = (elm_stroke === undefined ? null : elm_stroke);
							let elm_new = null;

							if (arr_colors === null || do_highlight) {
								elm_new = getGraphicsElementNode(elm_stroke, num_size_radius_scaled, SocialUtilities.parseColor(str_color, opacity_node), num_width_stroke_scaled, (num_width_stroke_scaled ? SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke) : null));
							} else {
								elm_new = getGraphicsElementNodePie(elm_stroke, num_size_radius_scaled, arr_colors, opacity_node, num_width_stroke_scaled, (num_width_stroke_scaled ? SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke) : null));
							}

							if (elm_new !== null) {
								
								if (elm_stroke !== null) {
									elm.replaceChild(elm_stroke, elm_new);
								} else {
									elm.addChild(elm_new);
								}
							}
							
							num_index++;
						}
						
						if (elm.children[num_index] !== undefined) { // Remove old icons
							elm.removeChildAt(num_index);
						}
		
						if (arr_node.icons !== false && arr_node.icons.length) {
							
							let elms_icon = null;
							let num_height_max = 0;
							let num_width_sum = 0;
							const num_size_icon_scaled = (show_icon_as_node ? num_size_radius_scaled * 2 : num_size_dot_icons * num_scale);
							
							for (let i = 0, len = arr_node.icons.length; i < len; i++) {
								
								const resource = arr_node.icons[i].resource;
								const arr_resource = arr_assets_texture_icons[resource];

								const elm_icon = new PIXI.Sprite(arr_resource.texture);
								const num_scale_icon = (arr_resource.width / arr_resource.height);
								
								const num_height_icon = (do_node_icons_weight ? ((num_size_radius_scaled * 2) * (arr_node.icons[i].weight / arr_node.weight_conditions)) : num_size_icon_scaled);
								
								const num_width_icon = (num_height_icon * num_scale_icon);
								elm_icon.height = num_height_icon;
								elm_icon.width = num_width_icon;
								
								if (i == 0) { // First icon is largest
									num_height_max = num_height_icon;
								}
								if (i > 0) {
									num_width_sum += num_spacer_dot_icons;
								}
								elm_icon.position.set(num_width_sum, ((num_height_max - num_height_icon) / 2));
								num_width_sum += num_width_icon;

								if (i == 0) {
									
									if (len > 1) {
										
										elms_icon = new PIXI.Container();
										elms_icon.addChild(elm_icon);
									} else {
										
										elms_icon = elm_icon;
									}
								} else {
									
									elms_icon.addChild(elm_icon);
								}
							}
							
							let num_offset = 0;
							
							if (show_icon_as_node) {
								
								num_offset = -(num_height_max / 2);
							} else {
								
								if (num_offset_dot_icons == 0) {
									num_offset = -(num_height_max / 2);
								} else if (num_offset_dot_icons < 0) {
									num_offset = (-(num_size_radius_scaled + num_width_stroke_scaled) + (num_offset_dot_icons * num_scale) - num_height_max);
								} else {
									num_offset = (num_size_radius_scaled + num_width_stroke_scaled + (num_offset_dot_icons * num_scale));
								}
							}
							
							elms_icon.position.set(Math.floor(-(num_width_sum / 2)), Math.floor(num_offset));
							
							elm.addChild(elms_icon);
						}
					} else {
						
						const elm_new = getGraphicsElementNode(elm, num_size_radius_scaled, SocialUtilities.parseColor(str_color, opacity_node), num_width_stroke_scaled, (num_width_stroke_scaled ? SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke) : null));
					
						if (elm_new !== null) {

							if (elm !== null) {
								elm_plot_dots.replaceChild(elm, elm_new);
							} else {
								elm_plot_dots.addChild(elm_new);
							}
							
							elm = elm_new;
							arr_node.elm = elm_new;
						}
					}

					if (pos_elm) {
						elm.position = pos_elm;
					} else {
						elm.position.set(0, 0);
					}
				}
			} else {
				
				let elm_circle = null;
				
				if (elm === null) {
					
					elm = stage.createElementNS(stage_ns, 'g');
					svg_group.appendChild(elm);

					elm_circle = stage.createElementNS(stage_ns, 'circle');
					elm.appendChild(elm_circle);
					
					if (arr_node.show_text) {
						
						const elm_text = stage.createElementNS(stage_ns, 'text');
						elm_text.style.fontSize = num_text_scaled+'px';
						elm_text.style.fontFamily = font_family;
						elm_text.style.fill = color_text;
						elm_text.style.fillOpacity = opacity_text;
						elm_text.style.dominantBaseline = 'central';
						const node_text = stage.createTextNode(arr_node.name_text);
						elm_text.appendChild(node_text);
						elm.appendChild(elm_text);
						
						arr_node.elm_text = elm_text;
					}
					
					arr_node.elm = elm;
				} else {
					
					elm_circle = elm.firstChild;
					
					elm.dataset.visible = 1;
					
					if (arr_node.elm_text !== null) {
						arr_node.elm_text.style.fontSize = num_text_scaled+'px';
					}
				}
				
				if (arr_node.elm_text !== null) {
					
					arr_node.elm_text.dataset.visible = (arr_node.show_text && num_size_radius ? 1 : 0);
					arr_node.elm_text.setAttribute('dx', (num_size_radius + width_node_stroke) + num_offset_dot_text);
				}
				
				elm.dataset.node_id = arr_node.id;
				
				if (arr_node.has_conditions) {
				
					for (let i = elm.children.length-1; i >= 0; i--) { // Remove possible previous pie and icons
						
						if (elm.children[i].tagName !== 'g') {
							continue;
						}
						elm.children[i].remove();
					}

					if (arr_node.colors !== null && !do_highlight) {
						
						if (arr_node.colors.length == 1) {
							
							str_color = arr_node.colors[0].color;
						} else {
							
							str_color = 'none';
							
							if (!show_icon_as_node) {

								let num_current_portion = 0; 
								const num_x = 0;
								const num_y = 0;
								const elms_pie = stage.createElementNS(stage_ns, 'g');

								for (let i = 0; i < arr_node.colors.length; i++) {

									const num_start = num_current_portion * 2 * Math.PI;
									num_current_portion = num_current_portion + arr_node.colors[i].portion;
									const num_end = num_current_portion * 2 * Math.PI;

									const elm_path = stage.createElementNS(stage_ns, 'path');
									
									elm_path.setAttribute('d','M '+Math.floor(num_x)+','+Math.floor(num_y)+' L '+(Math.floor(num_x) + num_size_radius * Math.cos(num_start))+','+(Math.floor(num_y) + num_size_radius * Math.sin(num_start))+' A '+num_size_radius+','+num_size_radius+' 0 '+(num_end - num_start < Math.PI ? 0 : 1)+',1 '+(Math.floor(num_x) + num_size_radius * Math.cos(num_end))+','+(Math.floor(num_y) + num_size_radius * Math.sin(num_end))+' z');
									elm_path.style.fill = SocialUtilities.parseColor(arr_node.colors[i].color, opacity_node);
									
									elms_pie.appendChild(elm_path);
								}
								
								elm.appendChild(elms_pie);
							}
						}
					}
						
					if (arr_node.icons !== false && arr_node.icons.length) {
						
						const elms_icon = stage.createElementNS(stage_ns, 'g');
						let num_height_max = 0; // First icon is largest
						let num_width_sum = 0;
						const num_size_icon = (show_icon_as_node ? num_size_radius * 2 : num_size_dot_icons);
						
						for (let i = 0, len = arr_node.icons.length; i < len; i++) {
						
							const resource = arr_node.icons[i].resource;
							const arr_resource = ASSETS.getMedia(resource);

							const elm_icon = stage.createElementNS(stage_ns, 'image');
							elm_icon.setAttribute('href', arr_resource.resource);
							const num_scale_icon = (arr_resource.width / arr_resource.height);
							
							const num_height_icon = (do_node_icons_weight ? ((num_size_radius * 2) * (arr_node.icons[i].weight / arr_node.weight_conditions)) : num_size_icon);
							
							const num_width_icon = num_height_icon * num_scale_icon;
							elm_icon.setAttribute('height', num_height_icon);
							elm_icon.setAttribute('width', num_width_icon);
							
							if (i == 0) { // First icon is largest
								num_height_max = num_height_icon;
							}
							if (i > 0) {
								num_width_sum += num_spacer_dot_icons;
							}
							elm_icon.setAttribute('x', num_width_sum);
							elm_icon.setAttribute('y', ((num_height_max - num_height_icon) / 2));
							num_width_sum += num_width_icon;

							elms_icon.appendChild(elm_icon);
						}
						
						let num_offset = 0;
						
						if (show_icon_as_node) {
							
							num_offset = -(num_height_max / 2);
						} else {
							
							if (num_offset_dot_icons == 0) {
								num_offset = -(num_height_max / 2);
							} else if (num_offset_dot_icons < 0) {
								num_offset = (-(num_size_radius + width_node_stroke) + num_offset_dot_icons - num_height_max);
							} else {
								num_offset = (num_size_radius + width_node_stroke + num_offset_dot_icons);
							}
						}
						
						elms_icon.setAttribute('transform', 'translate('+(-(num_width_sum / 2))+' '+(num_offset)+')');
						
						elm.appendChild(elms_icon);
					}
				}
				
				if (arr_node.icons !== false && arr_node.icons.length && show_icon_as_node) {
					
					elm_circle.setAttribute('r', 0);
				} else {
					
					let elm_circle_stoke = null;
					
					if (use_best_quality) {
						
						elm_circle_stoke = elm.children[1];
						if (elm_circle_stoke === undefined || elm_circle_stoke.tagName !== 'circle') {
							elm_circle_stoke = null;
						}
					}

					if (str_color === 'none') { // No need to correct for SVG's inability to add an outer stroke.
						
						elm_circle.setAttribute('r', (num_size_radius ? (num_size_radius + (width_node_stroke / 2)) : 0)); // Circle is empty and can be used for possible stroke, just make it a bit bigger
						elm_circle.style.fill = str_color;
						
						if (width_node_stroke) {
							
							elm_circle.style.stroke = SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke);
							elm_circle.style.strokeWidth = width_node_stroke;
							
							if (elm_circle_stoke !== null) {
								elm_circle_stoke.remove();
							}
						}
					} else if (use_best_quality) { // Correct for SVG's inability to add an outer stroke. create new element sized just for the stroke
						
						elm_circle.setAttribute('r', (num_size_radius ? num_size_radius : 0));
						elm_circle.style.fill = SocialUtilities.parseColor(str_color, opacity_node);
						
						if (width_node_stroke) {
							
							elm_circle.style.strokeWidth = 0;
							
							if (elm_circle_stoke === null) {
								
								elm_circle_stoke = stage.createElementNS(stage_ns, 'circle');
								elm_circle.after(elm_circle_stoke); // Insert as second child
								elm_circle_stoke.style.fill = 'none';
							}
							
							elm_circle_stoke.setAttribute('r', (num_size_radius ? (num_size_radius + (width_node_stroke / 2)) : 0));
							elm_circle_stoke.style.stroke = SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke);
							elm_circle_stoke.style.strokeWidth = width_node_stroke;
						}
					} else { // Correct for SVG's inability to add an outer stroke. double the stroke width, as half will be hidden behind the fill (using paint-order: stroke;)
						
						elm_circle.setAttribute('r', (num_size_radius ? num_size_radius : 0));
						elm_circle.style.fill = SocialUtilities.parseColor(str_color, opacity_node);
						
						if (width_node_stroke) {
							
							elm_circle.style.stroke = SocialUtilities.parseColor(color_node_stroke, opacity_node_stroke);
							elm_circle.style.strokeWidth = (width_node_stroke * 2);
						}
					}
				}
			}
		}
		
		arr_node.color = false;
		arr_node.redraw_node = false;
		arr_node.identifier = str_identifier;
	}
	
	var handleConditions = function(arr_node) {

		// Node size is based on amount of links
		// One link can set multiple colours
		// One part is relative to total amount of parts (i.e. links)
		// One part may contain multiple colours
		// Grouped later by colour
		
		let has_part_condition_object = 0;
		let num_parts_total = 0;
		
		if (arr_node.is_alive) {
			
			// Do we need one part for conditions generated by the object itself? Based on own object or sub-object conditions
			
			// Conditions based on object			
			if (arr_node.conditions.object.length) {
					
				has_part_condition_object = 1;
			} else {
				
				// Conditions based on object subs
				for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub[i];
				
					if (!arr_object_subs_children[arr_condition.source_id].is_active) {
						continue;
					}
					
					has_part_condition_object = 1;
					break;
				}
			}
			
			// Total number of parts is based on all incoming relations (that can generate cross-referenced conditions) and one part for object conditions
			num_parts_total = has_part_condition_object + arr_node.count_in;
		}
		
		// If no parts are there, return.
		if (!num_parts_total) {
			
			arr_node.weight_conditions = 1;
			arr_node.colors = null;
			arr_node.icons = false;
			arr_node.show_text = arr_node.has_text_threshold;
			arr_node.identifier_condition = '';
			
			return;
		}
		
		let str_identifier = '';
		let arr_condition_colors = [];
		let arr_condition_icons = [];
		let arr_parts = {};
		let arr_colors_group = {};
		let arr_icons_group = [];
		let num_parts_condition_referenced = 0;
		let num_parts_self = 0;
		let num_weight_conditions = 0;
		let do_show_text = null; 
		
		// Cross referenced conditions based on referenced object definitions
		
		for (let i = 0, len = arr_node.conditions.object_parent.length; i < len; i++) {

			const arr_condition = arr_node.conditions.object_parent[i];

			if (arr_node.object_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			if (label_condition !== false) {
				do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
			}
			
			if (arr_condition.weight === 0) {
				continue;
			}
			
			const arr_object_definition = arr_data.objects[arr_condition.source_id].object_definitions[arr_condition.source_definition_id];
						
			if (arr_parts['o_'+arr_condition.source_id] === undefined) {
				arr_parts['o_'+arr_condition.source_id] = {colors: []};
				num_parts_condition_referenced++;
			}
			
			if (arr_condition.color !== null) {
				arr_parts['o_'+arr_condition.source_id].colors.push([arr_condition.color, arr_condition.weight]);
			}
			
			if (arr_object_definition.style.weight !== null) {
				num_weight_conditions += arr_condition.weight;
			}
			
			if (arr_condition.icon !== null) {
				arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
			}
		}
		
		// Cross referenced conditions based on referenced sub object definitions
			
		for (let i = 0, len = arr_node.conditions.object_sub_parent.length; i < len; i++) {
			
			const arr_condition = arr_node.conditions.object_sub_parent[i];
			
			if (arr_node.object_sub_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			if (label_condition !== false) {
				do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
			}
			
			if (arr_condition.weight === 0) {
				continue;
			}
			
			const arr_object_sub = arr_data.object_subs[arr_condition.source_id];
			const arr_object_sub_definition = arr_object_sub.object_sub_definitions[arr_condition.source_definition_id]
			const object_id = arr_object_sub.object_id; // Need object_id as identifier for the part as sub object references can be multiple
			
			if (arr_parts['s_'+object_id] === undefined) {
				arr_parts['s_'+object_id] = {colors: []};
				num_parts_condition_referenced++;
			}
			
			if (arr_condition.color !== null) {
				arr_parts['s_'+object_id].colors.push([arr_condition.color, arr_condition.weight]);
			}
			
			if (arr_object_sub_definition.style.weight !== null) {
				num_weight_conditions += arr_condition.weight;
			}
			
			if (arr_condition.icon !== null) {
				arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
			}
		}

		if (has_part_condition_object) {
			
			arr_parts.object = {colors: []};
				
			// Conditions based on object
			for (let i = 0, len = arr_node.conditions.object.length; i < len; i++) {
				
				const arr_condition = arr_node.conditions.object[i];
				
				if (label_condition !== false) {
					do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
				}
				
				if (arr_condition.weight === 0) {
					continue;
				}
				
				const arr_object = arr_data.objects[arr_node.id];
								
				if (arr_condition.color !== null) {
					arr_parts.object.colors.push([arr_condition.color, arr_condition.weight]);
				}
				
				if (arr_object.style.weight !== null) {
					num_weight_conditions += arr_condition.weight;
				}
				
				if (arr_condition.icon !== null) {
					arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
				}
			}

			// Conditions based on object subs
			
			for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
				
				const arr_condition = arr_node.conditions.object_sub[i];
				
				if (!arr_object_subs_children[arr_condition.source_id].is_active) {
					continue;
				}
				
				if (label_condition !== false) {
					do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
				}
				
				if (arr_condition.weight === 0) {
					continue;
				}
				
				const arr_object_sub = arr_data.object_subs[arr_condition.source_id];
				
				if (arr_condition.color !== null) {
					arr_parts.object.colors.push([arr_condition.color, arr_condition.weight]);
				}
				
				if (arr_object_sub.style.weight !== null) {
					num_weight_conditions += arr_condition.weight;
				}
				
				if (arr_condition.icon !== null) {
					arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
				}
			}
			
			if (!arr_parts.object.colors.length) {
				
				has_part_condition_object = 0;
				delete arr_parts.object;
			}
		}
		
		num_parts_self = (num_parts_total - num_parts_condition_referenced);
		
		for (const part_id in arr_parts) {
			
			const arr_part = arr_parts[part_id];
			const num_part_portion_total = arr_part.colors.reduce(function(num_sum, arr) { return num_sum + arr[1]; }, 0); // arr[1] = weight
			
			let num_part_percentage = 0;
			if (part_id == 'object') {
				num_part_percentage = (num_parts_self / num_parts_total);
			} else {
				num_part_percentage = (1 / num_parts_total);
			}
			const num_part_portion_percentage = (num_part_percentage / num_part_portion_total);
			
			for (let i = 0, len = arr_part.colors.length; i < len; i++) {
				
				const arr_part_color = arr_part.colors[i];
				const str_color = arr_part_color[0];
				
				if (arr_colors_group[str_color] === undefined) {
					
					arr_colors_group[str_color] = {color: str_color, portion: 0};
					arr_condition_colors.push(arr_colors_group[str_color]);
				}
				
				arr_colors_group[str_color].portion += (arr_part_color[1] * num_part_portion_percentage);
			}
		}	

		if (!has_part_condition_object && num_parts_total > num_parts_condition_referenced) {

			if (arr_colors_group[arr_node.color] === undefined) {
				
				arr_colors_group[arr_node.color] = {color: arr_node.color, portion: 0};
				arr_condition_colors.push(arr_colors_group[arr_node.color]);
			}
			
			arr_colors_group[arr_node.color].portion += (num_parts_self / num_parts_total);	
		}

		if (!arr_condition_colors.length) {
			
			arr_condition_colors.push({color: arr_node.color, portion: 1});
			
			str_identifier += arr_node.color;
		} else {
			
			for (let i = 0, len = arr_condition_colors.length; i < len; i++) {
				str_identifier += arr_condition_colors[i].color+arr_condition_colors[i].portion;
			}
		}
		
		if (arr_icons_group.length) {
			
			arr_condition_icons = {};
			
			for (let i = 0, len = arr_icons_group.length; i < len; i++) {
				
				const str_icon_value = arr_icons_group[i][0];
				
				let arr_icon_group = arr_condition_icons[str_icon_value];
				
				if (arr_icon_group === undefined) {
					
					arr_condition_icons[str_icon_value] = {weight: 0, resource: str_icon_value};
					arr_icon_group = arr_condition_icons[str_icon_value];
				}
				
				arr_icon_group.weight += arr_icons_group[i][1];
				
				str_identifier += str_icon_value;
			}
			
			arr_condition_icons = Object.values(arr_condition_icons);
			
			arr_condition_icons.sort(function(a, b) { return b.weight - a.weight }); // Order by size, descending
		}

		arr_node.weight_conditions = 1 + num_weight_conditions;
		arr_node.colors = arr_condition_colors;
		arr_node.icons = arr_condition_icons;
		arr_node.show_text = (do_show_text !== null ? do_show_text : arr_node.has_text_threshold);
		arr_node.identifier_condition = str_identifier;
	}
	
	var drawTick = function() {
		
		if (do_redraw) { // New draw
			
			// Prepare asset tracking
			pos_hover_poll = false;
		}

		// Redraw Links
		if (show_line) {
			
			const do_size_line = (width_line_max != width_line_min && num_link_weight_max != num_link_weight_min);
			
			for (let i = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_active_link = arr_active_links[i];
				
				let num_line_width = width_line_min;
				
				if (do_size_line) {
					
					const num_weight_ratio = ((arr_active_link.weight - num_link_weight_min) / (num_link_weight_max - num_link_weight_min)); // Correct the range with the minimum weight to get a 0-1 ratio
					num_line_width = (width_line_min + (num_weight_ratio * (width_line_max - width_line_min)));
				}
				
				if (display == DISPLAY_PIXEL) {
							
					const num_buffer_offset = arr_active_link.count * 4 * 2; // 4 xy vertices (quad) per link to be truely updated/positioned, the actual 6 vertices are further handled by the index (buffer_geometry_lines_index)
					
					const node_source = arr_active_link.source;
					const node_target = arr_active_link.target;
					
					const dx = node_target.x - node_source.x;
					const dy = node_target.y - node_source.y;
					const dl = Math.sqrt(dx * dx + dy * dy);
					const dx_normalised = (dx / dl);
					const dy_normalised = (dy / dl);
					
					const num_width = (num_line_width / 2);
					const num_offset = (arr_active_link.has_reverse ? num_width : 0);
					const num_source_x = node_source.x + ((dy / dl) * num_offset);
					const num_source_y = node_source.y + ((-dx / dl) * num_offset);
					const num_target_x = node_target.x + ((dy / dl) * num_offset);
					const num_target_y = node_target.y + ((-dx / dl) * num_offset);
					
					const arr_positions = buffer_geometry_lines_position.data;

					arr_positions[num_buffer_offset + 0] = num_source_x + (-dy_normalised * num_width);
					arr_positions[num_buffer_offset + 1] = num_source_y + (dx_normalised * num_width);
					arr_positions[num_buffer_offset + 2] = num_source_x + (dy_normalised * num_width);
					arr_positions[num_buffer_offset + 3] = num_source_y + (-dx_normalised * num_width);
					
					arr_positions[num_buffer_offset + 4] = num_target_x + (-dy_normalised * num_width);
					arr_positions[num_buffer_offset + 5] = num_target_y + (dx_normalised * num_width);
					arr_positions[num_buffer_offset + 6] = num_target_x + (dy_normalised * num_width);
					arr_positions[num_buffer_offset + 7] = num_target_y + (-dx_normalised * num_width);
				} else {
					
					if (arr_active_link.elm) {
						
						const num_offset_radius = 2.5; // Lower means more arc
						const dx = arr_active_link.target.x - arr_active_link.source.x;
						const dy = arr_active_link.target.y - arr_active_link.source.y;
						const dr = Math.sqrt(dx * dx + dy * dy) * num_offset_radius;
						
						arr_active_link.elm.setAttribute('d', 'M' + arr_active_link.source.x + ',' + arr_active_link.source.y + 'A' + dr + ',' + dr + ' 1,0,0 ' + arr_active_link.target.x + ',' + arr_active_link.target.y);
						
						if (do_size_line) {
							arr_active_link.elm.setAttribute('stroke-width', num_line_width+'px');
						}
						
						// Show previously hidden link after it has received its new position
						if (arr_active_link.action == 'show') {
							arr_active_link.elm.dataset.visible = 1;
							arr_active_link.action = false;
						}
					}
				}
			}
		}
		
		// Redraw Nodes
		for (let i = 0, len = arr_active_nodes.length; i < len; i++) {
			
			const arr_node = arr_active_nodes[i];			

			if (arr_node.redraw_node === true || do_redraw) {
				drawNodeElement(arr_node);
			}
			
			if (display == DISPLAY_PIXEL) {
				
				if (arr_node.elm !== null) {
					
					const num_x_calc = (arr_node.x + pos_translation.x) * num_scale;
					const num_y_calc = (arr_node.y + pos_translation.y) * num_scale;

					arr_node.elm.position.set(num_x_calc, num_y_calc);
					
					if (arr_node.elm_text !== null) {
						arr_node.elm_text.position.set(num_x_calc + ((arr_node.radius + num_offset_dot_text) * num_scale), num_y_calc);
					}
				}
			} else {
				
				arr_node.elm.setAttribute('transform', 'translate('+arr_node.x+','+arr_node.y+')');
			}
		}
		
		if (display == DISPLAY_PIXEL) {
			
			if (show_line) {
			
				buffer_geometry_lines_position.update();
				//buffer_geometry_lines_normal.update();
				
				if (do_update_geometry_lines_index) {
					
					buffer_geometry_lines_index.update();
					do_update_geometry_lines_index = false;
				}
				
				if (do_update_geometry_lines_color) {
					
					buffer_geometry_lines_color.update();
					do_update_geometry_lines_color = false;
				}
			}
			
			renderer.render(stage);
			renderer_2.render(stage_2);
		}
		
		do_redraw = false;
	};
		
	var addListeners = function () {
				
		const elm_legends = PARENT.elm_controls.find('.legends');
		
		elm_layout = $('<figure class="run-layout"></figure>');
		elm_search_container = $('<figure class="search-nodes" />');
		elm_selected_node_container = $('<figure class="selected-node hide" />');
		
		elm_layout.appendTo(elm_legends);
		elm_search_container.appendTo(elm_legends);
		elm_selected_node_container.appendTo(elm_legends);
		
		const elm_search_input = $('<input type="search" class="autocomplete" />').appendTo(elm_search_container);
		
		const func_search_request = function(str_input, callback) {
			
			const str_input_normalised = str_input.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
			const max_results = 10;
			const arr_results = [];
			
			for (let i = 0; i < arr_active_nodes.length; i++) {
				
				const arr_node = arr_active_nodes[i];
				
				if (!arr_node.name) {
					continue;
				}
				
				if (arr_node.name_normalised === undefined) {
					arr_node.name_normalised = arr_node.name.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
				}
				
				if (arr_node.name_normalised.indexOf(str_input_normalised) === -1) {
					continue;
				}
				
				const is_exact = (arr_node.name.indexOf(str_input) !== -1);
					
				const num_total = (arr_node.count_object_sub_parents + arr_node.count_object_parents);
				arr_results.push({id: arr_node.id, label: arr_node.name, total: num_total, exact: is_exact});
			}
			
			const arr_data = []
			
			if (arr_results.length > 0) {
				
				arr_results.sort((a, b) => b.exact - a.exact || b.total - a.total); // Sort on exact DESC first, totals DESC second
				
				for (let i = 0; i < (arr_results.length > max_results ? max_results : arr_results.length); i++) {
					
					arr_data.push(arr_results[i]);
				}
			} else {
				
				arr_data.push({id: null, label: arr_labels.msg_no_results});
			}
			
			callback(arr_data);
		};
		
		const autocompleter = new AutoCompleter(elm_search_input, {
			input_clear: false,
			call_request: func_search_request,
			call_active: function(elm_label, arr_value) {
				
				if (!arr_value.id) {
					
					hoverNode(false, false);
					return;
				}
				
				hoverNode(arr_nodes[arr_value.id], true, true);
			},
			call_select: function(elm_label, arr_value) {
				
				return arr_value;
			},
			call_inactive: function() {
				
				hoverNode(false, false);
			}
		});
		
		const elm_search_popout = autocompleter.getPopout();
		elm_search_popout.classList.add('search-nodes');
		
		elm_layout_statistics = $('<div></div>').appendTo(elm_layout);
		elm_layout_status = $('<div></div>').appendTo(elm_layout);
		elm_layout_select = $('<select><option value="">D3 Force</option><option value="forceatlas2">ForceAtlas2</option></select>').appendTo(elm_layout);
		const elm_layout_run = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_layout);
		const elm_layout_stop = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_layout);
				
		ASSETS.getIcons(elm_host, ['play', 'stop'], function(data) {
			elm_layout_run[0].children[0].innerHTML = data.play;
			elm_layout_stop[0].children[0].innerHTML = data.stop;
		});
			
		elm_layout_run[0].addEventListener('click', function(e) {
			
			if (!simulation) {
				return;
			}
			
			const str_algorithm = elm_layout_select.val();
			
			switch (str_algorithm) {
				case 'forceatlas2':
					simulation.layout.startLayoutForceAtlas2();
					do_draw = true;
					break;
				case '':
				default:
					simulation.resume();
					do_draw = true;
					break;
			}
		});
		elm_layout_stop[0].addEventListener('click', function(e) {
			
			simulation.stop();
			do_draw = true;
		});
				
		elm_selected_node_container[0].addEventListener('mouseover', function(e) {
			
			const elm_target = (e.target.dataset.node_id ? e.target : e.target.closest('[data-node_id], figure'));
			
			if (!elm_target.dataset.node_id) {
				return;
			}
			
			hoverNode(arr_nodes[elm_target.dataset.node_id], false, true);
		});
		
		elm_selected_node_container[0].addEventListener('mouseout', function(e) {
			
			const elm_target = (e.target.dataset.node_id ? e.target : e.target.closest('[data-node_id], figure'));
			
			if (!elm_target.dataset.node_id) {
				return;
			}
			
			hoverNode(false, false);
		});
		
		elm_selected_node_container[0].addEventListener('click', function(e) {
			
			const elm_target = (e.target.dataset.node_id ? e.target : e.target.closest('[data-node_id], figure'));
			
			if (!elm_target.dataset.node_id) {
				return;
			}
			
			SCRIPTER.triggerEvent(elm_host, 'review');
		});
				
		const func_mouse_down = function(e) {
						
			if (cur_node_id === false) {
				
				elm_selected_node_container.addClass('hide');
				
				elm_host.arr_link = false;
				elm_host.arr_info_box = false;
				
				return;
			}
			
			arr_nodes[cur_node_id].fixed = (arr_nodes[cur_node_id].fixed ? 2 : 1);
			
			PARENT.obj_map.onInteractDown = function() {};
			
			PARENT.obj_map.onInteractMove = function() {
				
				is_dragging_node = true;
				
				const pos_hover = PARENT.obj_map.getMousePosition(false);
				const arr_node = arr_nodes[cur_node_id];
				
				arr_node.x = ((pos_hover.x - pos_stage.x) / num_scale) - pos_translation.x;
				arr_node.y = ((pos_hover.y - pos_stage.y) / num_scale) - pos_translation.y;
				
				arr_node.fx = arr_node.x;
				arr_node.fy = arr_node.y;
				
				simulation.resume();
				do_draw = true;
			};
			
			PARENT.obj_map.onInteractUp = function() {
				
				pos_hover_poll = false;
				
				if (is_dragging_node) {
					
					const arr_node = arr_nodes[cur_node_id];
					
					is_dragging_node = false;
					
					if (arr_node.fixed == 2) {
						
						arr_node.fixed = 0;
						arr_node.fx = null;
						arr_node.fy = null;
					}

					simulation.resume();
					do_draw = true;
				} else {

					if (cur_node_id !== false && arr_nodes[cur_node_id].fixed == 1) {
						arr_nodes[cur_node_id].fixed = 0;
					}
				}
				
				PARENT.obj_map.onInteractDown = null;
				PARENT.obj_map.onInteractMove = null;
				PARENT.obj_map.onInteractUp = null;
			};
		};
			
		elm[0].addEventListener('touchstart', function(e) {
			
			if (in_first_run) {
				return;
			}
			
			interact();
			
			func_mouse_down(e);
		});		
		elm[0].addEventListener('mousedown',  function(e) {
						
			if (POSITION.isTouch()) {
				return;
			}
			
			func_mouse_down(e);
		});
		
		if (use_metrics) {
			metrics_process = new MapNetworkMetrics(elm_legends, PARENT);
		}
	};
	
	var hoverNode = function(arr_node, do_show_box, is_interact_static, do_highlight) {
		
		elm_host.removeAttribute('title');
		
		if (func_tooltip_stop !== null) {
			func_tooltip_stop();
			func_tooltip_stop = null;
		}
		
		if (do_highlight !== false) {
			
			while (arr_highlighted_nodes.length) {
				
				const node_id = arr_highlighted_nodes.pop();
				drawNodeElement(arr_nodes[node_id]);
			}
			
			for (let i = 0; i < arr_loop_links.length; i++) {
				
				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}
				
				setLinkColor(arr_link, false);
				
				if (show_arrowhead && display == DISPLAY_VECTOR) {
					arr_link.elm.setAttribute('marker-end', 'url(#end)');
				}
			}
		}
		
		if (arr_node === false) {
			
			elm_host.arr_link = false;
			elm_host.arr_info_box = false;
		} else {
			
			let arr_connection_object_parents = [];
			let arr_connection_object_sub_parents = [];

			if (do_highlight !== false) {

				arr_node.highlight_color = color_highlight_node;
				drawNodeElement(arr_node);
				
				if (arr_highlighted_nodes.indexOf(arr_node.id) === -1) {
					arr_highlighted_nodes.push(arr_node.id);
				}
				
				const arr_node_details = getNodeDetails(arr_node);
				
				let str_title = '<ul>\
					<li>\
						<label></label>\
						<span>'+arr_node.name+'</span>\
					</li>';
				
				if (arr_node_details.conditions) {
					
					let has_conditions = false;
					
					let str_tooltip_conditions = '<hr />\
					<li>\
						<label>'+arr_labels.lbl_conditions+'</label>\
						<ul>';
						
						const arr_sort = [];
						for (const str_identifier in arr_node_details.conditions) {
							arr_sort.push([str_identifier, arr_node_details.conditions[str_identifier]]);
						}
						arr_sort.sort(function(a, b) {
							return b[1] - a[1];
						});
					
						for (let i = 0, len = arr_sort.length; i < len; i++) {
							
							const arr_condition = arr_data.legend.conditions[arr_sort[i][0]];
							
							if (arr_condition === undefined || !arr_condition.label) {
								continue;
							}
							
							has_conditions = true;
								
							const num_amount = arr_sort[i][1];
							
							str_tooltip_conditions += '<li>\
								<label>'+arr_condition.label+'</label>\
								<ul>';
								
									str_tooltip_conditions += '<li>'+num_amount+'</li>';
							
								str_tooltip_conditions += '</ul>\
							</li>';
						}
						
						str_tooltip_conditions += '</ul>\
					</li>';
					
					if (has_conditions) {
						str_title += str_tooltip_conditions;
					}
				}
				
				str_title += '</ul>';
				
				if (is_interact_static === true) {
					
					const elm_tooltip_static = $('<div class="tooltip label">'+str_title+'</div>').appendTo(elm);
					
					const num_radius_distance = Math.sqrt(2 * ((arr_node.radius + num_offset_dot_text) * (arr_node.radius + num_offset_dot_text))) / 2;
					const num_left = (((arr_node.x + num_radius_distance + pos_translation.x) * num_scale) + pos_stage.x);
					const num_top = (((arr_node.y + num_radius_distance + pos_translation.y) * num_scale) + pos_stage.y);
					
					elm_tooltip_static[0].style.left = num_left+'px';
					elm_tooltip_static[0].style.top = num_top+'px';
					
					func_tooltip_stop = function() {
						
						elm_tooltip_static[0].remove();
					};
				} else {
					
					elm_host.title = str_title;
				}
			}
			
			const arr_connect_objects = [];
			
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {

				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}
				
				let arr_node_connected = null;
				
				if (arr_link.target.id === arr_node.id) {
					arr_node_connected = arr_link.source;
				} else if (arr_link.source.id === arr_node.id) {
					arr_node_connected = arr_link.target;
				}
				
				if (arr_node_connected === null) {
					continue;
				}
				
				const connected_object_id = arr_node_connected.id;
				
				arr_connect_objects.push({object_id: connected_object_id, type_id: arr_node_connected.type_id});
				
				const arr_object_parents = [arr_link.source_object_id, arr_link.target_object_id];
				
				if (do_highlight !== false) {
					
					if (show_arrowhead && display == DISPLAY_VECTOR) {
						arr_link.elm.setAttribute('marker-end', 'url(#end-selected)');
					}
					
					setLinkColor(arr_link, color_highlight_link);
					
					arr_node_connected.highlight_color = color_highlight_node_connect;
					drawNodeElement(arr_node_connected);
					
					if (arr_highlighted_nodes.indexOf(connected_object_id) === -1) {
						arr_highlighted_nodes.push(connected_object_id);
					}
				}
				
				arr_connection_object_parents = arr_connection_object_parents.concat(arr_object_parents);
				
				for (const object_sub_id in arr_link.object_sub_parents) {
					
					if (arr_link.object_sub_parents[object_sub_id] !== true) { // Not active
						continue;
					}
					
					arr_connection_object_sub_parents.push(object_sub_id);
				}
			}
			
			elm_host.arr_link = {object_id: parseInt(arr_node.id), type_id: parseInt(arr_node.type_id), object_sub_ids: arr_node.object_sub_parents, connect_object_ids: arr_connect_objects};
			elm_host.arr_info_box = {name: arr_node.name};

			if (do_show_box) {
				
				const elm_heading = $('<h2><span class="a" data-node_id="'+arr_node.id+'">'+arr_node.name+'</span></h2>');
				elm_selected_node_container.removeClass('hide').html(elm_heading);
				
				const arr_data_details = getDataDetails(arr_node.id, arr_connection_object_parents, arr_connection_object_sub_parents);
				
				const func_object_group = function(arr_object_ids) {
					
					let arr_list = {};
					
					for (const object_id of arr_object_ids) {
						
						if (arr_list[object_id] === undefined) {
							arr_list[object_id] = 0;
						}
						arr_list[object_id]++;
					}
					
					arr_list = Object.entries(arr_list).sort((a, b) => b[1] - a[1]); // Will be an array with key[0]-value[1] pairs
					
					return arr_list;
				};
				
				const func_object_list = function(arr_list, num_limit) {
					
					let str_return = '<ul>';
					
					for (let i = 0; i < arr_list.length && i < num_limit; i++) {
						
						const object_id = arr_list[i][0];
						
						str_return += '<li><span data-node_id="'+object_id+'" title="'+arr_list[i][1]+' '+(arr_list[i][1] > 1 ? arr_labels.lbl_references : arr_labels.lbl_reference)+'" class="a">'+arr_nodes[object_id].name+'</span></li>';
					}
					
					if (arr_list.length > num_limit) {
						str_return += '<li>... '+(arr_list.length-num_limit)+'x</li>'
					}
					
					str_return += '</ul>';
					
					return str_return;
				};
				
				const arr_collect_statements = {out: [{}], in: [{}]};
				let num_references = 0;
				let num_statements = 0;

				for (const object_description_id in arr_data_details.source.object_definitions) {
					
					const arr_object_ids = arr_data_details.source.object_definitions[object_description_id];
					num_references += Object.keys(arr_object_ids).length;
					
					const arr_list = func_object_group(arr_object_ids);
					num_statements += arr_list.length;
					
					arr_collect_statements.out.push({label: arr_data.info.object_descriptions[object_description_id].object_description_name, list: arr_list});
				}
							
				for (const type_id in arr_data_details.source.object_subs) {
								
					for (const object_sub_details_id in arr_data_details.source.object_subs[type_id]) {	
								
						for (const object_sub_description_id in arr_data_details.source.object_subs[type_id][object_sub_details_id]) {
							
							let str_object_sub_description = '';
							
							if (object_sub_details_id === 'collapse') { // Could be collapsed and not exist
								str_object_sub_description = '[~]';
							} else {
								str_object_sub_description = '['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']';
							}
							
							str_object_sub_description += ' '+arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name;
							
							const arr_object_ids = arr_data_details.source.object_subs[type_id][object_sub_details_id][object_sub_description_id];
							num_references += Object.keys(arr_object_ids).length;
							
							const arr_list = func_object_group(arr_object_ids);
							num_statements += arr_list.length;
							
							arr_collect_statements.out.push({label: str_object_sub_description, list: arr_list});
						}
					}
				}
								
				let str_value = arr_node.count_out;
				let str_title = arr_node.count_out+' '+arr_labels.lbl_links+' ('+num_references+' '+arr_labels.lbl_references+')';
				
				arr_collect_statements.out[0] = {label: '<strong>'+arr_labels.lbl_links_out+'</strong>', elm: '<strong title="'+str_title+'">'+str_value+'</strong>'};
				
				num_references = 0;
				
				for (const type_id in arr_data_details.target.object_definitions) {
					
					for (const object_description_id in arr_data_details.target.object_definitions[type_id]) {
						
						const arr_object_ids = arr_data_details.target.object_definitions[type_id][object_description_id];
						num_references += Object.keys(arr_object_ids).length;
						
						const arr_list = func_object_group(arr_object_ids);
						num_statements += arr_list.length;
					
						arr_collect_statements.in.push({label: arr_data.info.object_descriptions[object_description_id].object_description_name, list: arr_list});
					}
				}	
				
				for (const type_id in arr_data_details.target.object_subs) {	
							
					for (const object_sub_details_id in arr_data_details.target.object_subs[type_id]) {
								
						for (const object_sub_description_id in arr_data_details.target.object_subs[type_id][object_sub_details_id]) {
							
							let str_object_sub_description = '';
							
							if (object_sub_details_id === 'collapse') { // Could be collapsed and not exist
								str_object_sub_description = '[~]';
							} else {
								str_object_sub_description = '['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']';
							}
							
							str_object_sub_description += ' '+arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name;
							
							const arr_object_ids = arr_data_details.target.object_subs[type_id][object_sub_details_id][object_sub_description_id];
							num_references += Object.keys(arr_object_ids).length;
							
							const arr_list = func_object_group(arr_object_ids);
							num_statements += arr_list.length;
					
							arr_collect_statements.in.push({label: str_object_sub_description, list: arr_list});
						}
					}
				}
				
				str_value = arr_node.count_in;
				str_title = arr_node.count_in+' '+arr_labels.lbl_links+' ('+num_references+' '+arr_labels.lbl_references+')';
				
				arr_collect_statements.in[0] = {label: '<strong>'+arr_labels.lbl_links_in+'</strong>', elm: '<strong title="'+str_title+'">'+str_value+'</strong>'};
				
				let num_limit_list = 20;
				
				if (num_statements > num_limit_list) {
					
					num_limit_list = Math.ceil(num_limit_list / (arr_collect_statements.out.length-1 + arr_collect_statements.in.length-1));
					
					if (num_limit_list < 3) {
						num_limit_list = 3;
					}
				}

				const elm_list = $('<dl />').appendTo(elm_selected_node_container);
				
				for (const key_in_out in arr_collect_statements) {
					for (const arr_statement of arr_collect_statements[key_in_out]) {
						
						if (arr_statement.list !== undefined) {
							arr_statement.elm = func_object_list(arr_statement.list, num_limit_list);
						}
						
						const elm_li = $('<div />').appendTo(elm_list);
						const elm_dt = $('<dt />').html(arr_statement.label).appendTo(elm_li);
						const elm_dd = $('<dd />').html(arr_statement.elm).appendTo(elm_li);
					}
				}
			}
		}
		
		TOOLTIP.update();
		
		if (display == DISPLAY_PIXEL && !in_first_run && !do_draw) { // Rerender stage to show/hide highlight colours
			
			if (do_update_geometry_lines_color) {
				
				buffer_geometry_lines_color.update();
				do_update_geometry_lines_color = false;
			}
				
			renderer.render(stage);
		}
	};
	
	var setNodesLinksValues = function() {
		
		num_node_weight_min = null;
		num_node_weight_max = 1;
		num_link_weight_max = 1;

		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
			
			if (is_weighted) {
				
				let num_weight_conditions = arr_node.weight_conditions;
				
				if (num_weight_conditions == 0) {
					continue;
				}
				
				if (size_node_stop && num_weight_conditions > size_node_stop) {
					num_weight_conditions = size_node_stop;
				}

				if (num_weight_conditions > num_node_weight_max) {
					num_node_weight_max = num_weight_conditions;
				}
				if (num_weight_conditions < num_node_weight_min || num_node_weight_min === null) {
					num_node_weight_min = num_weight_conditions;
				}
			} else {
				
				const num_weight = (arr_node.count_out + arr_node.count_in);
				
				if (num_weight == 0) {
					continue;
				}

				if (num_weight > num_node_weight_max) {
					num_node_weight_max = num_weight;
				}
				
				// num_node_weight_min = 1 (one connection)
			}
		}
		
		if (num_node_weight_min === null) {
			num_node_weight_min = 1;
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
						
			if (is_weighted) {
				
				let num_weight_conditions = arr_node.weight_conditions;

				if (size_node_stop && num_weight_conditions > size_node_stop) {
					num_weight_conditions = size_node_stop;
				}
				
				const num_weight_normalised = (num_node_weight_max != num_node_weight_min ? ((num_weight_conditions - num_node_weight_min) / (num_node_weight_max - num_node_weight_min)) : 1);
				
				if (num_weight_normalised >= label_threshold) {
					arr_node.has_text_threshold = true;
					arr_node.show_text = true;
				}
							
			} else {
				
				if ((arr_node.count_out + arr_node.count_in) / num_node_weight_max >= label_threshold) {
					arr_node.has_text_threshold = true;
					arr_node.show_text = true;
				}
			}
		}
		
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
			const arr_link = arr_loop_links[i];
			
			if (!arr_link.is_active) {
				continue;
			}
			
			if (arr_link.weight > num_link_weight_max) {
				num_link_weight_max = arr_link.weight;
			}
		}		
	};
	
	var checkObjectSubInRange = function(arr_object_sub) {

		return checkNodeInRange(arr_nodes[arr_object_sub.object_id]);
	};
	
	var setCheckObjectSubs = function(dateinta_range) {

		// Single date sub objects
		for (let i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			const date = arr_data.date.arr_loop[i];
			const dateinta = DATEPARSER.dateInt2Absolute(date);
			const in_range_date = (dateinta >= dateinta_range.min && dateinta <= dateinta_range.max);
			const arr_object_subs = arr_data.date[date];
		
			for (let j = 0; j < arr_object_subs.length; j++) {
				
				const object_sub_id = String(arr_object_subs[j]);
				let in_range = in_range_date;
				
				if (in_range) {
					
					const arr_object_sub = arr_data.object_subs[object_sub_id];
					
					in_range = checkObjectSubInRange(arr_object_sub);
				}
				
				checkObjectSub(object_sub_id, !in_range);
			}
		}
		
		// Sub objects with a date range
		for (let i = 0, len = arr_data.range.length; i < len; i++) {
			
			const object_sub_id = String(arr_data.range[i]);
			const arr_object_sub = arr_data.object_subs[object_sub_id];
			
			const dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
			const dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
			
			let in_range = ((dateinta_start >= dateinta_range.min && dateinta_start <= dateinta_range.max) || (dateinta_end >= dateinta_range.min && dateinta_end <= dateinta_range.max) || (dateinta_start < dateinta_range.min && dateinta_end > dateinta_range.max));

			if (in_range) {
				in_range = checkObjectSubInRange(arr_object_sub);
			}
			
			checkObjectSub(object_sub_id, !in_range);
		}
	};
	
	var checkObjectSub = function(object_sub_id, do_remove) {

		const arr_object_sub_children = arr_object_subs_children[object_sub_id];
		let count_nodes = arr_object_sub_children.child_nodes.length;
		let count_links = arr_object_sub_children.child_links.length;
		
		arr_object_sub_children.is_active = !do_remove;
		
		// Nodes and Links are added, removed or updated based on sub-object and object parents
		// They set whether a node/link may or may not exist
		// The size of the node/link is based on the number of links
			
		while (count_nodes--) {

			const object_id = arr_object_sub_children.child_nodes[count_nodes];
			const arr_node = arr_nodes[object_id];
			
			const has_parent_id = (arr_node.object_sub_parents[object_sub_id] === true);
			
			if (do_remove) {
				
				if (has_parent_id) {
					
					arr_node.object_sub_parents[object_sub_id] = false;
					arr_node.count_object_sub_parents--;
				}
			} else {

				if (!has_parent_id) { // add node
					
					arr_node.object_sub_parents[object_sub_id] = true;
					arr_node.count_object_sub_parents++;
				} 
			}
		}
		
		while (count_links--) {
			
			const link_id = arr_object_sub_children.child_links[count_links];
			const arr_link = arr_links[link_id];
			
			const has_parent_id = (arr_link.object_sub_parents[object_sub_id] === true);

			if (do_remove) {
				
				if (has_parent_id) {
					
					arr_link.object_sub_parents[object_sub_id] = false;
					arr_link.count_object_sub_parents--;
					
					arr_link.weight -= arr_link.weight_object_sub_parents[object_sub_id];
				} 
			} else {
				
				if (!has_parent_id) {
					
					arr_link.object_sub_parents[object_sub_id] = true;
					arr_link.count_object_sub_parents++;
					
					arr_link.weight += arr_link.weight_object_sub_parents[object_sub_id];
				}
			}
		}
	};
	
	var checkNodes = function() {
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
				
			const arr_node = arr_loop_nodes[i];
			
			arr_node.is_enabled = checkNodeInRange(arr_node);
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) { // Setting all nodes' static object links to removed, when applicable. Point of depature, clean slate.
			
			const arr_node = arr_loop_nodes[i];
			const do_add = (arr_node.is_enabled && arr_node.count_object_sub_parents);
			
			if (!do_add) {
				checkRemoveNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) { // Adding all nodes' static object links, when applicable.
			
			const arr_node = arr_loop_nodes[i];
			const do_add = (arr_node.is_enabled && arr_node.count_object_sub_parents);
			
			if (do_add) {
				checkAddNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (arr_node.is_enabled && (arr_node.count_object_parents || arr_node.count_object_sub_parents)) {
				addNode(arr_node);
			} else {
				removeNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
				
			const arr_link = arr_loop_links[i];
			
			if (in_predraw) {
				arr_link.source = arr_nodes[arr_link.source_object_id];
				arr_link.target = arr_nodes[arr_link.target_object_id];
			}
			
			if (!arr_link.source.is_alive || !arr_link.target.is_alive) {
				removeLink(arr_link);
			} else if (!arr_link.has_object_parent && arr_link.has_object_sub_parents && !arr_link.count_object_sub_parents) {
				removeLink(arr_link);
			} else {
				addLink(arr_link);
			}
		}
		
		for (let i = 0; i < arr_remove_nodes.length; i++) {
			
			const object_id = arr_remove_nodes[i];

			drawNodeElement(arr_nodes[object_id]);
		}
		
		arr_remove_nodes = [];
	};
	
	var checkRemoveNode = function(arr_node) {
		
		if (!arr_node.child_nodes.length) {
			return;
		}
		
		if (arr_node.is_enabled && arr_node.count_object_sub_parents) {
			return;
		}
		
		arr_node.is_checked = false; // This additional switch helps to identify possible mutual static links that negate a natural flow (cancel each other) in the next checkAddNode-phase
		
		for (let i = 0, len = arr_node.child_nodes.length; i < len; i++) {
			
			const arr_node_child = arr_nodes[arr_node.child_nodes[i]];
			
			if (arr_node_child.object_parents[arr_node.id]) {
				
				arr_node_child.object_parents[arr_node.id] = false;
				arr_node_child.count_object_parents--;
			}

			if (arr_node_child.is_checked) {
				checkRemoveNode(arr_node_child);
			}
		}
	};
	
	var checkAddNode = function(arr_node) {
		
		if (!arr_node.child_nodes.length) {
			return;
		}
		
		if (!arr_node.is_enabled) {
			return;
		}
		
		arr_node.is_checked = true;
		
		for (let i = 0, len = arr_node.child_nodes.length; i < len; i++) {
			
			const arr_node_child = arr_nodes[arr_node.child_nodes[i]];
			
			if (!arr_node_child.object_parents[arr_node.id]) {
				
				arr_node_child.object_parents[arr_node.id] = true;
				arr_node_child.count_object_parents++;
			}
			
			if (!arr_node_child.is_checked) {
				checkAddNode(arr_node_child);
			}
		}
	};
	
	var checkNodeInRange = function(arr_node) {
		
		if (in_predraw) {
			return true;
		}
				
		if (PARENT.obj_data.arr_inactive_types[arr_node.type_id]) {
			return false;
		}
				
		if (PARENT.obj_data.arr_loop_inactive_conditions.length) {
			
			for (let i = 0, len = PARENT.obj_data.arr_loop_inactive_conditions.length; i < len; i++) {
				
				const has_inactive_condition = hasCondition(arr_node, PARENT.obj_data.arr_loop_inactive_conditions[i]);
				
				if (has_inactive_condition) {
					return false;
				}
			}
		}

		return true;
	}
	
	var addNode = function(arr_node) {
	
		if (arr_node.weight_total === 0) {
			return;
		}
		
		if (arr_node.is_alive) {
			
			if (arr_node.conditions.object_sub.length || arr_node.conditions.object_sub_parent.length) { // Check if node has changed internally (node has sub-object conditions).
				
				let str_identifier = '';
				
				for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub[i];
					
					if (!arr_object_subs_children[arr_condition.source_id].is_active) {
						continue;
					}
						
					str_identifier += arr_condition.source_id;
				}
				
				for (let i = 0, len = arr_node.conditions.object_sub_parent.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub_parent[i];
					
					if (arr_node.object_sub_parents[arr_condition.source_id] === false) {
						continue;
					}
						
					str_identifier += arr_condition.source_id;
				}
				
				if (str_identifier != arr_node.identifier_condition_self) {

					arr_node.identifier_condition_self = str_identifier;
					arr_node.redraw_node = true;
					
					return;
				}
			} else {
				
				return;
			}
		}
		
		arr_node.is_alive = true;
		arr_node.is_active = true;
		arr_node.redraw_node = true;
	}
		
	var removeNode = function(arr_node) {
			
		if (!arr_node.is_alive) {
			return;
		}
		
		arr_node.is_alive = false;
		arr_node.redraw_node = true;

		if (show_disconnected_node == false) {
			
			if (arr_node.is_active) {
					
				arr_node.is_active = false;
				
				if (arr_node.elm !== null) {
					
					if (display == DISPLAY_PIXEL) {
						
						arr_node.elm.visible = false;
						if (arr_node.show_text) {
							arr_node.elm_text.visible = false;
						}
					} else {
						
						arr_node.elm.dataset.visible = 0;
					}
				}
			}
		} else {
			
			if (arr_node.elm !== null) {
				arr_remove_nodes.push(arr_node.id);
			}
		}
	}
	
	var addLink = function(arr_link) {

		if (arr_link.is_active) {
			return;
		}

		var arr_source_node = arr_link.source;
		var arr_target_node = arr_link.target;
		
		var has_source_node = (arr_source_node.out[arr_link.id] === true);
		if (!has_source_node) {
			arr_source_node.out[arr_link.id] = true;
			arr_source_node.count_out++;
			arr_source_node.redraw_node = true;
		}
		
		var has_target_object = (arr_target_node.in[arr_link.id] === true);
		if (!has_target_object) {
			arr_target_node.in[arr_link.id] = true;
			arr_target_node.count_in++;
			arr_target_node.redraw_node = true;
		}
		
		arr_link.is_active = true;
		
		if (show_line && !in_predraw) {
			
			if (display == DISPLAY_VECTOR) {
				
				arr_link.action = 'show'; 
			} else if (display == DISPLAY_PIXEL) {
				
				const num_buffer_offset = arr_link.count * 6;
				const num_index_offset = arr_link.count * 4;
				
				const arr_indices = buffer_geometry_lines_index.data;
				
				arr_indices[num_buffer_offset + 0] = num_index_offset + 0;
				arr_indices[num_buffer_offset + 1] = num_index_offset + 1;
				arr_indices[num_buffer_offset + 2] = num_index_offset + 2;
				
				arr_indices[num_buffer_offset + 3] = num_index_offset + 1;
				arr_indices[num_buffer_offset + 4] = num_index_offset + 3;
				arr_indices[num_buffer_offset + 5] = num_index_offset + 2;
				
				do_update_geometry_lines_index = true;
			}
		}
	};
	
	var removeLink = function(arr_link) {
		
		if (!arr_link.is_active) {
			return;
		}

		const arr_source_node = arr_link.source;
		const arr_target_node = arr_link.target;
						
		const has_source_node = (arr_source_node.out[arr_link.id] === true);
		if (has_source_node) {
			arr_source_node.out[arr_link.id] = false;
			arr_source_node.count_out--;
			arr_source_node.redraw_node = true;
		}
		
		const has_target_object = (arr_target_node.in[arr_link.id] === true);
		if (has_target_object) {
			arr_target_node.in[arr_link.id] = false;
			arr_target_node.count_in--;
			arr_target_node.redraw_node = true;
		}

		if (show_line && !in_predraw) {
			
			if (display == DISPLAY_VECTOR) {
				
				arr_link.elm.dataset.visible = 0;
			} else {
				
				const num_buffer_offset = arr_link.count * 6;
				const arr_indices = buffer_geometry_lines_index.data;
				
				arr_indices[num_buffer_offset + 0] = 0;
				arr_indices[num_buffer_offset + 1] = 0;
				arr_indices[num_buffer_offset + 2] = 0;
				arr_indices[num_buffer_offset + 3] = 0;
				arr_indices[num_buffer_offset + 4] = 0;
				arr_indices[num_buffer_offset + 5] = 0;
				
				do_update_geometry_lines_index = true;
			}
		}
		
		arr_link.is_active = false;
	};
	
	var setLinkColor = function(arr_link, str_color) {
		
		if (!show_line) {
			return;
		}

		if (str_color) {
			
			arr_link.color = SocialUtilities.parseColorLink(str_color);
		} else {
			
			let num_line_ratio = 1;
			
			if (do_line_color_weight && num_link_weight_max != num_link_weight_min) {
			
				const num_weight_ratio = 1 - ((arr_link.weight - num_link_weight_min) / (num_link_weight_max - num_link_weight_min)); // Correct the range with the minimum weight to get a 0-1 ratio
				const arr_color = SocialUtilities.colorToBrightColor(color_line, (num_weight_ratio * 40));
				
				arr_link.color = SocialUtilities.parseColorLink('rgba('+arr_color.r+','+arr_color.g+','+arr_color.b+','+opacity_line+')');
			} else {
				
				arr_link.color = SocialUtilities.parseColorLink('rgba('+arr_color_line.r+','+arr_color_line.g+','+arr_color_line.b+','+opacity_line+')');
			}
		}
		
		if (show_line) {
			
			if (display == DISPLAY_VECTOR) {
				
				arr_link.elm.setAttribute('stroke', arr_link.color);
			} else {
				
				const num_buffer_offset = arr_link.count * 4 * 4; // 4 vertices (quad) per link to be truely updated/coloured, having 4 colour statemens (rgba)
				const num_r = (arr_link.color.r / 255);
				const num_g = (arr_link.color.g / 255);
				const num_b = (arr_link.color.b / 255);
				const num_a = arr_link.color.a;
				
				const arr_colors = buffer_geometry_lines_color.data;
				
				arr_colors[num_buffer_offset + 0] = num_r;
				arr_colors[num_buffer_offset + 1] = num_g;
				arr_colors[num_buffer_offset + 2] = num_b;
				arr_colors[num_buffer_offset + 3] = num_a;
				arr_colors[num_buffer_offset + 4] = num_r;
				arr_colors[num_buffer_offset + 5] = num_g;
				arr_colors[num_buffer_offset + 6] = num_b;
				arr_colors[num_buffer_offset + 7] = num_a;
				
				arr_colors[num_buffer_offset + 8] = num_r;
				arr_colors[num_buffer_offset + 9] = num_g;
				arr_colors[num_buffer_offset + 10] = num_b;
				arr_colors[num_buffer_offset + 11] = num_a;
				arr_colors[num_buffer_offset + 12] = num_r;
				arr_colors[num_buffer_offset + 13] = num_g;
				arr_colors[num_buffer_offset + 14] = num_b;
				arr_colors[num_buffer_offset + 15] = num_a;
				
				do_update_geometry_lines_color = true;
			}
		}
	}

	var getDataDetails = function(object_id, arr_object_parents, arr_object_sub_parents) {
		
		let arr_object = null;
		
		if (arr_data.objects[object_id]) {
			arr_object = arr_data.objects[object_id];
		} else {
			arr_object = {object_definitions: {}};
		}
		
		var arr_object_parents = arrUnique(arr_object_parents); // Could have doubles (bidirectional)
		var arr_object_sub_parents = arrUnique(arr_object_sub_parents); // Could have doubles (bidirectional)
		const arr_details = {source: {object_definitions: {}, object_subs: {}}, target: {object_definitions: {}, object_subs: {}}};
		
		// Count all relations REFERENCING/OUT context object
		
		for (const object_definition_id in arr_object.object_definitions) {
			
			const arr_object_definition = arr_object.object_definitions[object_definition_id];
			const object_description_id = arr_object_definition.description_id;
			
			if (!arr_object_definition || !arr_object_definition.ref_object_id.length) {
				continue;
			}
			
			for (let j = 0; j < arr_object_definition.ref_object_id.length; j++) {
				
				const referencing_object_id = arr_object_definition.ref_object_id[j]+''; // IDs are generally collected as strings
				
				if (referencing_object_id === object_id) {
					continue;
				}
				
				if (!arr_object_parents.includes(referencing_object_id)) {
					continue;
				}
				
				if (arr_details.source.object_definitions[object_description_id] === undefined) {
					arr_details.source.object_definitions[object_description_id] = [];
				}
				
				arr_details.source.object_definitions[object_description_id].push(referencing_object_id);
			}
		}
		
		// Count all relations REFERENCING/OUT context object sub objects based on object_sub_parent
		
		for (let i = 0; i < arr_object_sub_parents.length; i++) {
			
			if (arr_data.object_subs[arr_object_sub_parents[i]].object_id != object_id) {
				continue;
			}
			
			const arr_object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
			
			if (!arr_object_sub.object_sub_definitions) {
				continue;
			}
			
			for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
				
				const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];
				const object_sub_description_id = arr_object_sub_definition.description_id;
				
				if (!arr_object_sub_definition || !arr_object_sub_definition.ref_object_id.length) {
					continue;
				}
					
				for (let j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
					
					const referencing_object_id = arr_object_sub_definition.ref_object_id[j]+''; // IDs are generally collected as strings
					
					if (referencing_object_id === object_id) {
						continue;
					}
					
					const type_id = arr_data.objects[arr_object_sub.object_id].type_id;
					
					if (arr_details.source.object_subs[type_id] === undefined) {
						arr_details.source.object_subs[type_id] = {};
					}
					
					const object_sub_details_id = arr_object_sub.object_sub_details_id; // Could be collapsed
					
					if (arr_details.source.object_subs[type_id][object_sub_details_id] === undefined) {
						arr_details.source.object_subs[type_id][object_sub_details_id] = {};
					}
					
					if (arr_details.source.object_subs[type_id][object_sub_details_id][object_sub_description_id] === undefined) {
						arr_details.source.object_subs[type_id][object_sub_details_id][object_sub_description_id] = [];
					}
					
					arr_details.source.object_subs[type_id][object_sub_details_id][object_sub_description_id].push(referencing_object_id);
				}
			}
		}
								
		// Count all relations REFERENCED/IN context object based on object_parents
		
		for (let i = 0; i < arr_object_parents.length; i++) {
			
			const referenced_object_id = arr_object_parents[i];
			
			if (referenced_object_id === object_id) {
				continue;
			}
			
			let arr_referenced_object = null;
			
			if (arr_data.objects[referenced_object_id]) {
				arr_referenced_object = arr_data.objects[referenced_object_id];
			} else {
				arr_referenced_object = {object_definitions: {}};
			}
			
			for (const object_definition_id in arr_referenced_object.object_definitions) {
				
				const arr_object_definition = arr_referenced_object.object_definitions[object_definition_id];
				const object_description_id = arr_object_definition.description_id;
				
				if (!arr_object_definition || !arr_object_definition.ref_object_id.length) {
					continue;
				}
					
				for (let j = 0; j < arr_object_definition.ref_object_id.length; j++) {
				
					if (arr_object_definition.ref_object_id[j] != object_id) {
						continue;
					}
					
					if (arr_details.target.object_definitions[arr_referenced_object.type_id] === undefined) {
						arr_details.target.object_definitions[arr_referenced_object.type_id] = {};
					}
					
					if (arr_details.target.object_definitions[arr_referenced_object.type_id][object_description_id] === undefined) {
						arr_details.target.object_definitions[arr_referenced_object.type_id][object_description_id] = [];
					}
					
					arr_details.target.object_definitions[arr_referenced_object.type_id][object_description_id].push(referenced_object_id);
				}
			}
		}
		
		// Count all relations REFERENCED/IN context object based on object_sub_parents
		
		for (let i = 0; i < arr_object_sub_parents.length; i++) {
			
			const referenced_object_id = arr_data.object_subs[arr_object_sub_parents[i]].object_id+'';// IDs are generally collected as strings
			
			if (referenced_object_id === object_id) {
				continue;
			}
				
			const arr_object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
			
			if (!arr_object_sub.object_sub_definitions) {
				continue;
			}
				
			for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
				
				const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];
				const object_sub_description_id = arr_object_sub_definition.description_id;
				
				if (!arr_object_sub_definition || !arr_object_sub_definition.ref_object_id.length) {
					continue;
				}
				
				for (let j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
					
					if (arr_object_sub_definition.ref_object_id[j] != object_id) {
						continue;
					}

					const type_id = arr_data.objects[referenced_object_id].type_id;
					
					if (arr_details.target.object_subs[type_id] === undefined) {
						arr_details.target.object_subs[type_id] = {};
					}
					
					const object_sub_details_id = arr_object_sub.object_sub_details_id;
					
					if (arr_details.target.object_subs[type_id][object_sub_details_id] === undefined) {
						arr_details.target.object_subs[type_id][object_sub_details_id] = {};
					}
					
					if (arr_details.target.object_subs[type_id][object_sub_details_id][object_sub_description_id] === undefined) {
						arr_details.target.object_subs[type_id][object_sub_details_id][object_sub_description_id] = [];
					}
					
					arr_details.target.object_subs[type_id][object_sub_details_id][object_sub_description_id].push(referenced_object_id);
				}
			}
		}
	
		return arr_details;
	}
		
	var parseData = function() {
		
		arr_nodes = {};
		arr_links = {};
		arr_object_subs_children = {};
		
		const arr_object_subs = arr_data.object_subs;
		const arr_loop_object_subs = Object.keys(arr_object_subs);
		let count_arr_object_subs = arr_loop_object_subs.length;
		
		const makeLink = function(source_object_id, target_object_id) {
			
			const link_id = source_object_id+'_'+target_object_id;
			
			if (arr_links[link_id] === undefined) {
				
				const arr_link = {id: link_id, count: count_links, source: false, target: false, source_object_id: source_object_id, target_object_id: target_object_id,
					count_object_parent: 0, has_object_parent: false,
					object_sub_parents: {}, count_object_sub_parents: 0, has_object_sub_parents: false,
					weight: 0, weight_object_parent: 0, weight_object_sub_parents: {}, has_reverse: false, elm: false,
					is_active: false
				};
				
				arr_links[link_id] = arr_link;
				arr_loop_links[count_links] = arr_link;
				
				count_links++;
				
				if (arr_links[target_object_id+'_'+source_object_id] !== undefined) {
					
					arr_link.has_reverse = true;
					arr_links[target_object_id+'_'+source_object_id].has_reverse = true;
				}
			}

			return link_id;
		}
		
		const arr_objects = arr_data.objects;
		
		for (const object_id in arr_objects) {
			
			const arr_object = arr_objects[object_id];
			
			setNodeProperties(false, object_id, false);
			
			const arr_node = arr_nodes[object_id];
			
			for (const object_definition_id in arr_object.object_definitions) {
				
				const arr_object_definition = arr_object.object_definitions[object_definition_id];
				const object_description_id = arr_object_definition.description_id;
				
				if (!arr_object_definition || !arr_object_definition.ref_object_id.length) {
					continue;
				}

				let has_conditions = false;
				
				const num_weight = arr_object_definition.style.weight;
				const has_weight_zero = (num_weight === 0);
				
				for (let i = 0, len = arr_object_definition.ref_object_id.length; i < len; i++) {
					
					const target_object_id = setNodeProperties(arr_data.info.object_descriptions[object_description_id].object_description_ref_type_id, arr_object_definition.ref_object_id[i], arr_object_definition.value[i]);
					
					if (object_id == target_object_id) {
						continue;
					}
						
					const arr_target_node = arr_nodes[target_object_id];
					
					arr_node.child_nodes.push(target_object_id);
					
					if (!has_weight_zero) {

						const link_id = makeLink(object_id, target_object_id);
						const arr_link = arr_links[link_id];
						
						arr_link.has_object_parent = true;
						arr_link.count_object_parent++;
						
						let num_link_weight = (num_weight !== null ? num_weight : 0) + (arr_object_definition.style.link != null ? arr_object_definition.style.link.weight : 0);
						num_link_weight = (num_link_weight !== 0 ? num_link_weight : 1); // A weighted connection (collected in a link) always has a minimum of 1, as with nodes
						
						arr_link.weight_object_parent += num_link_weight;
						arr_link.weight += num_link_weight;
						
						arr_node.child_links.push(link_id);	
					}
					
					// Weight and other conditions of object applied to target node
					
					if (num_weight !== null) {

						arr_target_node.weight_conditions += num_weight;
						arr_target_node.weight_total += num_weight;
					}
					
					const arr_conditions = setCondition(arr_object_definition.style, object_id, object_definition_id);
					
					if (arr_conditions) {
						
						arr_target_node.has_conditions = true;
						arr_target_node.conditions.object_parent = arr_target_node.conditions.object_parent.concat(arr_conditions);
						
						has_conditions = true;
					}
				}
				
				if (has_conditions) {
					
					for (const str_identifier_condition in arr_object.style.conditions) {
						arr_node.conditions.object_definition.push({identifier: str_identifier_condition, source_id: object_id});
					}
				}
			}			
		}
		
		while (count_arr_object_subs--) {
			
			const object_sub_id = arr_loop_object_subs[count_arr_object_subs];
			const arr_object_sub = arr_object_subs[object_sub_id];
			const object_id = arr_object_sub.object_id+'';
			
			const arr_object_sub_children = {child_nodes: [object_id], child_links: [], is_active: false};
			
			arr_object_subs_children[object_sub_id] = arr_object_sub_children;
			
			const arr_node = arr_nodes[object_id];
			
			// Weight and other conditions of sub-object applied to current node
			
			if (arr_object_sub.style.weight !== null) {
	
				arr_node.weight_conditions += arr_object_sub.style.weight;
				arr_node.weight_total += arr_object_sub.style.weight;
			}
			
			const arr_conditions = setCondition(arr_object_sub.style, object_sub_id);
			
			if (arr_conditions) {
				
				arr_node.has_conditions = true;
				arr_node.conditions.object_sub = arr_node.conditions.object_sub.concat(arr_conditions);
			}
			
			let has_sub_weight_zero = (arr_object_sub.style.weight === 0);
			
			if (include_location_nodes && arr_object_sub.location_object_id) {
				
				const target_object_id = setNodeProperties(arr_object_sub.location_type_id, arr_object_sub.location_object_id, arr_object_sub.location_name);
				
				if (object_id != target_object_id) {
							
					arr_object_sub_children.child_nodes.push(target_object_id);
					
					if (!has_sub_weight_zero) {
						
						const link_id = makeLink(object_id, target_object_id);
						const arr_link = arr_links[link_id];
						
						arr_link.has_object_sub_parents = true;
						
						arr_link.weight_object_sub_parents[object_sub_id] = 1;
						
						arr_object_sub_children.child_links.push(link_id);
						
					}
				}
			}

			if (!arr_object_sub.object_sub_definitions) {
				continue;
			}
				
			for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
				
				const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];
				const object_sub_description_id = arr_object_sub_definition.description_id;
				
				if (!arr_object_sub_definition || !arr_object_sub_definition.ref_object_id.length) {
					continue;
				}
				
				let has_conditions = false;
				
				const num_weight = arr_object_sub_definition.style.weight;
				const has_weight_zero = (num_weight === 0);
				
				for (let i = 0, len = arr_object_sub_definition.ref_object_id.length; i < len; i++) {
					
					const target_object_id = setNodeProperties(arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_ref_type_id, arr_object_sub_definition.ref_object_id[i], arr_object_sub_definition.value[i]);	
					
					if (object_id == target_object_id) {
						continue;
					}
					
					const arr_target_node = arr_nodes[target_object_id];
					
					arr_object_sub_children.child_nodes.push(target_object_id);
					
					if (!has_sub_weight_zero && !has_weight_zero) {
						
						const link_id = makeLink(object_id, target_object_id);
						const arr_link = arr_links[link_id];
						
						arr_link.has_object_sub_parents = true;
						
						const num_link_weight = (num_weight !== null ? num_weight : 0) + (arr_object_sub_definition.style.link != null ? arr_object_sub_definition.style.link.weight : 0);
						arr_link.weight_object_sub_parents[object_sub_id] = (num_link_weight !== 0 ? num_link_weight : 1); // A weighted connection (collected in a link) always has a minimum of 1, as with nodes

						arr_object_sub_children.child_links.push(link_id);
					}

					// Cross Referencing weight and other conditions added to target node
					
					if (num_weight !== null) {

						arr_target_node.weight_conditions += num_weight;
						arr_target_node.weight_total += num_weight;
					}
					
					const arr_conditions = setCondition(arr_object_sub_definition.style, object_sub_id, object_sub_definition_id);
					
					if (arr_conditions) {
						
						arr_target_node.has_conditions = true;
						arr_target_node.conditions.object_sub_parent = arr_target_node.conditions.object_sub_parent.concat(arr_conditions);
						
						has_conditions = true;
					}
				}
				
				if (has_conditions) {
					
					for (const str_identifier_condition in arr_object_sub.style.conditions) {
						arr_node.conditions.object_sub_definition.push({identifier: str_identifier_condition, source_id: object_sub_id});
					}
				}
			}
		}
	}
	
	var setCondition = function(arr_style, source_id, source_definition_id) {
		
		if (!arr_style || (!arr_style.color && !arr_style.icon && arr_style.weight == null)) {
			return false;
		}

		const arr_conditions = [];
		
		const is_array_color = (typeof arr_style.color == 'object');
		const is_array_icon = (typeof arr_style.icon == 'object');
		const num_weight_style = (arr_style.weight !== null ? arr_style.weight : null);
		let num_weight_conditions = 0;
		
		const arr_legend_conditions = arr_data.legend.conditions;
		
		for (const str_identifier_condition in arr_style.conditions) {
			
			const arr_legend_condition = arr_legend_conditions[str_identifier_condition];
			
			const arr_condition_value = arr_style.conditions[str_identifier_condition];
			
			if (arr_condition_value === null) {
				continue;
			}
			
			const num_weight = arr_condition_value.weight;

			num_weight_conditions += num_weight;
			let str_color_style = null;
			let str_icon_style = null;

			if (arr_legend_condition.color) {
				
				if (is_array_color) {
					
					for (let i = 0, len = arr_style.color.length; i < len; i++) {
						
						if (arr_legend_condition.color !== arr_style.color[i]) {
							continue;
						}
							
						str_color_style = arr_style.color[i];
						break;
					}
				} else if (arr_legend_condition.color === arr_style.color) {
					
					str_color_style = arr_style.color;
				}
			}

			if (arr_legend_condition.icon) {
				
				if (is_array_icon) {
					
					for (let i = 0, len = arr_style.icon.length; i < len; i++) {
						
						if (arr_legend_condition.icon !== arr_style.icon[i]) {
							continue;
						}
						
						str_icon_style = arr_style.icon[i];
						break;
					}
				} else if (arr_legend_condition.icon === arr_style.icon) {
					
					str_icon_style = arr_style.icon;
				}
			}
			
			if (str_color_style === null && str_icon_style === null && num_weight_style === null) {
				continue;
			}
			
			const str_color = (str_color_style !== null && arr_condition_value.color != null ? arr_condition_value.color : str_color_style); // Calculated color only when style color is applied
			const str_icon = (str_icon_style !== null && arr_condition_value.icon != null ? arr_condition_value.icon : str_icon_style); // Calculated icon (via condition function) only when icon source is applied
			
			arr_conditions.push({identifier: str_identifier_condition, source_id: source_id, source_definition_id: source_definition_id, weight: num_weight, color: str_color, icon: str_icon});
		}
		
		// Adjust the individual condition weights to scale within the overall calculated weight
		
		if (num_weight_style !== null && num_weight_conditions != num_weight_style) {
			
			const num_scale = (num_weight_style / num_weight_conditions);
			
			for (let i = 0, len = arr_conditions.length; i < len; i++) {
				
				const arr_condition = arr_conditions[i];
				
				if (arr_condition.weight == 0) {
					continue;
				}
				
				if (num_scale < 1) {
					arr_condition.weight = Math.ceil(arr_condition.weight * num_scale); // Make sure to keep integer
				} else {
					arr_condition.weight = Math.round(arr_condition.weight * num_scale); // Make sure to keep integer
				}
			}
		}
		
		return arr_conditions;
	}
	 
	var hasCondition = function(arr_node, condition_label) {
		
		const arr_conditions = arr_node.conditions;
		
		for (let i = 0, len = arr_conditions.object.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_sub.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_sub[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_definition.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_definition[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_sub_definition.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_sub_definition[i].identifier) {
				return true;
			}
		}
		
		return false;
	}
	
	var getNodeDetails = function(arr_node) {
		
		const arr_details = {conditions: null};
		const arr_conditions = arr_node.conditions;
		
		const arr_details_conditions = {};
		let has_conditions = false;
		
		for (let i = 0, len = arr_conditions.object.length; i < len; i++) {

			const arr_condition = arr_conditions.object[i];
			
			if (arr_details_conditions[arr_condition.identifier] === undefined) {
				arr_details_conditions[arr_condition.identifier] = 0;
			}
			
			arr_details_conditions[arr_condition.identifier] += arr_condition.weight;
			has_conditions = true;
		}
		
		for (let i = 0, len = arr_conditions.object_sub.length; i < len; i++) {
			
			const arr_condition = arr_conditions.object_sub[i];
			
			if (!arr_object_subs_children[arr_condition.source_id].is_active) {
				continue;
			}
			
			if (arr_details_conditions[arr_condition.identifier] === undefined) {
				arr_details_conditions[arr_condition.identifier] = 0;
			}
			
			arr_details_conditions[arr_condition.identifier] += arr_condition.weight;
			has_conditions = true;
		}
		
		for (let i = 0, len = arr_conditions.object_parent.length; i < len; i++) {
			
			const arr_condition = arr_conditions.object_parent[i];
			
			if (arr_node.object_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			if (arr_details_conditions[arr_condition.identifier] === undefined) {
				arr_details_conditions[arr_condition.identifier] = 0;
			}
			
			arr_details_conditions[arr_condition.identifier] += arr_condition.weight;
			has_conditions = true;
		}
		
		for (let i = 0, len = arr_conditions.object_sub_parent.length; i < len; i++) {
			
			const arr_condition = arr_conditions.object_sub_parent[i];
			
			if (arr_node.object_sub_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			if (arr_details_conditions[arr_condition.identifier] === undefined) {
				arr_details_conditions[arr_condition.identifier] = 0;
			}
			
			arr_details_conditions[arr_condition.identifier] += arr_condition.weight;
			has_conditions = true;
		}
		
		if (has_conditions) {
			arr_details.conditions = arr_details_conditions;
		}
		
		return arr_details;
	}
	
	var setNodeProperties = function(type_id, object_id, str_name) {
		
		var object_id = object_id+''; // Make string so all IDs have same format
	
		if (!arr_nodes[object_id]) {
			
			const arr_node = {};
			
			arr_node.id = object_id;
			arr_node.count = count_nodes;
			arr_node.index = null;
			//arr_node.x = (size_renderer.width / 2);
			//arr_node.y = (size_renderer.height / 2);
			arr_node.x = (Math.random() * 100) - (100 / 2) + (size_renderer.width / 2);
			arr_node.y = (Math.random() * 100) - (100 / 2) + (size_renderer.height / 2);
			arr_node.radius = 1;
			arr_node.weight = 1;
			arr_node.fixed = 0;
			
			arr_node.has_conditions = false;
			arr_node.conditions = {object: [], object_sub: [], object_parent: [], object_sub_parent: [], object_definition: [], object_sub_definition: []};
			arr_node.identifier_condition = '';
			arr_node.identifier_condition_self = '';
			arr_node.colors = null;
			arr_node.icons = false;
			arr_node.weight_conditions = 1;
			arr_node.weight_total = null;
			
			if (arr_data.objects[object_id]) { // Object is present in objects in arr_data
				
				const arr_object = arr_data.objects[object_id];
				var str_name = arr_object.name;
				var type_id = arr_object.type_id;
				
				if (arr_object.style.weight !== null) {
					
					arr_node.weight_conditions += arr_object.style.weight;
					arr_node.weight_total += arr_object.style.weight;
				} else {
					
					arr_node.weight_total = 1;
				}
				
				const arr_conditions = setCondition(arr_object.style, false);
				
				if (arr_conditions) {
					
					arr_node.has_conditions = true;
					arr_node.conditions.object = arr_node.conditions.object.concat(arr_conditions);
				}
			}
			
			arr_node.type_id = type_id;
			
			arr_node.name = str_name;
			var str_name = stripHTMLTags(str_name);
			if (str_name.length > length_text_max) {
				str_name = str_name.substr(0, length_text_max)+'...';
			}
			arr_node.name_text = str_name;
							
			arr_node.child_links = [];
			arr_node.child_nodes = [];
			arr_node.object_parents = {};
			arr_node.object_sub_parents = {};
			arr_node.count_object_parents = 0;
			arr_node.count_object_sub_parents = 0;
			
			arr_node.in = {};
			arr_node.out = {};
			arr_node.count_in = 0;
			arr_node.count_out = 0;
			arr_node.is_enabled = false;
			arr_node.is_alive = false;
			arr_node.is_active = false;
			arr_node.is_checked = false;
			arr_node.identifier = '';
			arr_node.elm = null;
			arr_node.elm_text = null;
			arr_node.color = false;
			arr_node.redraw_node = false;
			arr_node.show_text = false;
			arr_node.has_text_threshold = false;
			
			if (focus_object_id == object_id) {
				
				arr_node.x = (size_renderer.width / 2);
				arr_node.y = (size_renderer.height / 2);
				
				arr_node.fixed = 1;
				arr_node.fx = arr_node.x;
				arr_node.fy = arr_node.y;
			}
			
			arr_nodes[object_id] = arr_node;
			arr_loop_nodes[arr_node.count] = arr_node;
				
			count_nodes++;
		}
	
		return object_id;
	}
	
	const arr_cache_graphics_nodes = new Map();
	const arr_cache_graphics_nodes_pie = new Map();

	var getGraphicsElementNode = function(elm, num_radius, color, num_stroke, color_stroke) {
		
		var num_radius = (num_radius < 0.5 ? 0.5 : Math.round(num_radius * 2) / 2); // 0.5 rounding
		if (color_stroke !== null) {
			var num_stroke = (num_stroke < 0.5 ? 0 : Math.round(num_stroke * 2) / 2); // 0.5 rounding
		}
		const str_identifier = num_radius+'|'+num_stroke+'|'+color_stroke+'|'+color;
		
		let texture = arr_cache_graphics_nodes.get(str_identifier);
		
		if (texture === undefined) {
			
			const elm_graphics = new PIXI.Graphics();
			elm_graphics.circle(0, 0, num_radius);
			if (color_stroke !== null) {
				elm_graphics.stroke({width: num_stroke, color: color_stroke, alignment: 0});
			}
			elm_graphics.fill(color);
			
			texture = renderer.generateTexture({target: elm_graphics, resolution: renderer.resolution, antialias: true, textureSourceOptions: {scaleMode: 'linear'}});
			
			arr_cache_graphics_nodes.set(str_identifier, texture);
		}

		if (elm === null || !(elm instanceof PIXI.Sprite)) {

			const elm_new = new PIXI.Sprite(texture);
			elm_new.anchor = 0.5;
			
			return elm_new;
		}

		elm.texture = texture;
		elm.anchor = 0.5;
		
		return null;
	};
	
	var getGraphicsElementNodePie = function(elm, num_radius, arr_colors, num_opacity, num_stroke, color_stroke) {
		
		var num_radius = (num_radius < 0.5 ? 0.5 : Math.round(num_radius * 2) / 2);  // 0.5 rounding
		if (color_stroke !== null) {
			var num_stroke = (num_stroke < 0.5 ? 0 : Math.round(num_stroke * 2) / 2); // 0.5 rounding
		}
		let str_identifier = num_radius+'|'+num_stroke+'|'+color_stroke+'|'+num_opacity+'|';
		
		for (let i = 0; i < arr_colors.length; i++) {
			
			const num_portion = Math.round(arr_colors[i].portion * 100) / 100;
			
			str_identifier += arr_colors[i].color+'-'+num_portion+',';
		}
		
		let context = arr_cache_graphics_nodes_pie.get(str_identifier);
		
		if (context === undefined) {
			
			context = new PIXI.GraphicsContext();
			context.circle(0, 0, num_radius);
			if (color_stroke !== null) {
				context.stroke({width: num_stroke, color: color_stroke, alignment: 0});
			}
			
			let num_current_portion = 0; 
			
			for (let i = 0; i < arr_colors.length; i++) {
				
				const num_portion = Math.round(arr_colors[i].portion * 100) / 100;
				
				const num_start = num_current_portion * 2 * Math.PI;
				num_current_portion = num_current_portion + num_portion;
				const num_end = num_current_portion * 2 * Math.PI;
				
				context.moveTo(0, 0)
					.lineTo(num_radius * Math.cos(num_start), num_radius * Math.sin(num_start))
					.arc(0, 0, num_radius, num_start, num_end, false)
					.lineTo(0, 0);
				context.fill(SocialUtilities.parseColor(arr_colors[i].color, num_opacity));
			}
			
			arr_cache_graphics_nodes_pie.set(str_identifier, context);
		}

		if (elm === null || !(elm instanceof PIXI.Graphics)) {
			
			const elm_new = new PIXI.Graphics(context);
			
			return elm_new;
		}
		
		elm.context = context;
		
		return null;
	};
};
