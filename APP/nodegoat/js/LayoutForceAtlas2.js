
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2026 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */
 
/**
 * ForceAtlas2 Algorithm Author:
 *   Jacomy M, Venturini T, Heymann S, Bastian M (2014) ForceAtlas2, a Continuous Graph Layout Algorithm for Handy Network Visualization Designed for the Gephi Software.
 *   PLoS ONE 9(6): e98679. https://doi.org/10.1371/journal.pone.0098679
 * 
 * Gephi Implementation:
 *   https://github.com/gephi/gephi/blob/master/modules/LayoutPlugin/src/main/java/org/gephi/layout/plugin/forceAtlas2/ForceAtlas2.java
 *   https://github.com/gephi/gephi/blob/master/modules/LayoutPlugin/src/main/java/org/gephi/layout/plugin/forceAtlas2/ForceFactory.java
 * 
 * Javascript Implementation:
 *   Guillaume Plique (Yomguithereal)
 *   Sigma (https://github.com/jacomyal/sigma.js/blob/master/plugins/sigma.layout.forceAtlas2/worker.js)
 *   Graphology (https://github.com/graphology/graphology-layout-forceatlas2/blob/master/iterate.js)
 */

function LayoutForceAtlas2() {
	
	const SELF = this;

	var options = {
		linLogMode: false,
		outboundAttractionDistribution: false,
		adjustSizes: false,
		edgeWeightInfluence: 0,
		scalingRatio: 1,
		strongGravityMode: false,
		gravity: 1,
		jitterTolerance: 1,
		barnesHutOptimize: false,
		barnesHutTheta: 0.5
	};

	// Global adaptive speed, persisted across pass() calls (see section 5)
	var speed = 1,
		speedEfficiency = 1;

	this.setConfiguration = function(arr_settings) {
		
		for (const key in arr_settings) {
			
			if (arr_settings[key] == null) {
				continue;
			}
			
			options[key] = arr_settings[key];
		}
	};
	
	var NodeMatrix;
	var EdgeMatrix;
		
	this.init = function(arr_nodes, arr_edges, arr_settings) {
		
		SELF.setNodes(arr_nodes);
		SELF.setEdges(arr_edges)

		SELF.setConfiguration(arr_settings || {});

		// Reset the global adaptive speed
		speed = 1;
		speedEfficiency = 1;
	};
	
	this.setNodes = function(arr_nodes) {
		
		NodeMatrix = arr_nodes;
	};

	this.getNodes = function() {
		
		return NodeMatrix;
	};
	
	this.setEdges = function(arr_edges) {
		
		EdgeMatrix = arr_edges;
	};

	const NODE_X = 0,
		NODE_Y = 1,
		NODE_DX = 2,
		NODE_DY = 3,
		NODE_OLD_DX = 4,
		NODE_OLD_DY = 5,
		NODE_MASS = 6,
		NODE_SIZE = 7,
		NODE_FIXED = 8;

	const EDGE_SOURCE = 0,
		EDGE_TARGET = 1,
		EDGE_WEIGHT = 2;

	const REGION_NODE = 0,
		REGION_CENTER_X = 1,
		REGION_CENTER_Y = 2,
		REGION_SIZE = 3,
		REGION_NEXT_SIBLING = 4,
		REGION_FIRST_CHILD = 5,
		REGION_MASS = 6,
		REGION_MASS_CENTER_X = 7,
		REGION_MASS_CENTER_Y = 8;

	const SUBDIVISION_ATTEMPTS = 3;

	const PPN = 9,
		PPE = 3,
		PPR = 9;

	const MAX_FORCE = 10;
	
	this.pass = function() {

		// Initializing variables
		var l, r, n, n1, n2, rn, e, w, g, s;

		var order = NodeMatrix.length,
			size = EdgeMatrix.length;

		var adjustSizes = options.adjustSizes;

		var thetaSquared = options.barnesHutTheta * options.barnesHutTheta;

		var outboundAttCompensation,
			coefficient,
			xDist,
			yDist,
			ewc,
			distance,
			factor;

		var RegionMatrix = [];

		// 1) Initializing layout data
		//-----------------------------

		// Resetting positions & computing max values
		for (n = 0; n < order; n += PPN) {
			NodeMatrix[n + NODE_OLD_DX] = NodeMatrix[n + NODE_DX];
			NodeMatrix[n + NODE_OLD_DY] = NodeMatrix[n + NODE_DY];
			NodeMatrix[n + NODE_DX] = 0;
			NodeMatrix[n + NODE_DY] = 0;
		}

		// If outbound attraction distribution, compensate
		if (options.outboundAttractionDistribution) {
			outboundAttCompensation = 0;
			for (n = 0; n < order; n += PPN) {
				outboundAttCompensation += NodeMatrix[n + NODE_MASS];
			}

			outboundAttCompensation /= order / PPN;
		}


		// 1.bis) Barnes-Hut computation
		//------------------------------

		if (options.barnesHutOptimize) {

			// Setting up
			var minX = Infinity,
				maxX = -Infinity,
				minY = Infinity,
				maxY = -Infinity,
				q, q2, subdivisionAttempts;

			// Computing min and max values
			for (n = 0; n < order; n += PPN) {
				minX = Math.min(minX, NodeMatrix[n + NODE_X]);
				maxX = Math.max(maxX, NodeMatrix[n + NODE_X]);
				minY = Math.min(minY, NodeMatrix[n + NODE_Y]);
				maxY = Math.max(maxY, NodeMatrix[n + NODE_Y]);
			}

			// squarify bounds, it's a quadtree
			var dx = maxX - minX,
				dy = maxY - minY;
			if (dx > dy) {
				minY -= (dx - dy) / 2;
				maxY = minY + dx;
			} else {
				minX -= (dy - dx) / 2;
				maxX = minX + dy;
			}

			// Build the Barnes Hut root region
			RegionMatrix[0 + REGION_NODE] = -1;
			RegionMatrix[0 + REGION_CENTER_X] = (minX + maxX) / 2;
			RegionMatrix[0 + REGION_CENTER_Y] = (minY + maxY) / 2;
			RegionMatrix[0 + REGION_SIZE] = Math.max(maxX - minX, maxY - minY);
			RegionMatrix[0 + REGION_NEXT_SIBLING] = -1;
			RegionMatrix[0 + REGION_FIRST_CHILD] = -1;
			RegionMatrix[0 + REGION_MASS] = 0;
			RegionMatrix[0 + REGION_MASS_CENTER_X] = 0;
			RegionMatrix[0 + REGION_MASS_CENTER_Y] = 0;

			// Add each node in the tree
			l = 1;
			for (n = 0; n < order; n += PPN) {

				// Current region, starting with root
				r = 0;
				subdivisionAttempts = SUBDIVISION_ATTEMPTS;

				while (true) {
					// Are there sub-regions?

					// We look at first child index
					if (RegionMatrix[r + REGION_FIRST_CHILD] >= 0) {

						// There are sub-regions

						// We just iterate to find a "leaf" of the tree
						// that is an empty region or a region with a single node
						// (see next case)

						// Find the quadrant of n
						if (NodeMatrix[n + NODE_X] < RegionMatrix[r + REGION_CENTER_X]) {

							if (NodeMatrix[n + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

								// Top Left quarter
								q = RegionMatrix[r + REGION_FIRST_CHILD];
							} else {

								// Bottom Left quarter
								q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR;
							}
						} else {
							if (NodeMatrix[n + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

								// Top Right quarter
								q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 2;
							} else {

								// Bottom Right quarter
								q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 3;
							}
						}

						// Update center of mass and mass (we only do it for non-leave regions)
						RegionMatrix[r + REGION_MASS_CENTER_X] = (
							RegionMatrix[r + REGION_MASS_CENTER_X] *
							RegionMatrix[r + REGION_MASS] +
							NodeMatrix[n + NODE_X] * NodeMatrix[n + NODE_MASS]
						) / (RegionMatrix[r + REGION_MASS] + NodeMatrix[n + NODE_MASS]);

						RegionMatrix[r + REGION_MASS_CENTER_Y] = (
							RegionMatrix[r + REGION_MASS_CENTER_Y] *
							RegionMatrix[r + REGION_MASS] +
							NodeMatrix[n + NODE_Y] * NodeMatrix[n + NODE_MASS]
						) / (RegionMatrix[r + REGION_MASS] + NodeMatrix[n + NODE_MASS]);

						RegionMatrix[r + REGION_MASS] += NodeMatrix[n + NODE_MASS];

						// Iterate on the right quadrant
						r = q;
						continue;
					} else {

						// There are no sub-regions: we are in a "leaf"

						// Is there a node in this leave?
						if (RegionMatrix[r + REGION_NODE] < 0) {

							// There is no node in region:
							// we record node n and go on
							RegionMatrix[r + REGION_NODE] = n;
							break;
						} else {

							// There is a node in this region

							// We will need to create sub-regions, stick the two
							// nodes (the old one r[0] and the new one n) in two
							// subregions. If they fall in the same quadrant,
							// we will iterate.

							// Create sub-regions
							RegionMatrix[r + REGION_FIRST_CHILD] = l * PPR;
							w = RegionMatrix[r + REGION_SIZE] / 2; // new size (half)

							// NOTE: we use screen coordinates
							// from Top Left to Bottom Right

							// Top Left sub-region
							g = RegionMatrix[r + REGION_FIRST_CHILD];

							RegionMatrix[g + REGION_NODE] = -1;
							RegionMatrix[g + REGION_CENTER_X] = RegionMatrix[r + REGION_CENTER_X] - w;
							RegionMatrix[g + REGION_CENTER_Y] = RegionMatrix[r + REGION_CENTER_Y] - w;
							RegionMatrix[g + REGION_SIZE] = w;
							RegionMatrix[g + REGION_NEXT_SIBLING] = g + PPR;
							RegionMatrix[g + REGION_FIRST_CHILD] = -1;
							RegionMatrix[g + REGION_MASS] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_X] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_Y] = 0;

							// Bottom Left sub-region
							g += PPR;
							RegionMatrix[g + REGION_NODE] = -1;
							RegionMatrix[g + REGION_CENTER_X] = RegionMatrix[r + REGION_CENTER_X] - w;
							RegionMatrix[g + REGION_CENTER_Y] = RegionMatrix[r + REGION_CENTER_Y] + w;
							RegionMatrix[g + REGION_SIZE] = w;
							RegionMatrix[g + REGION_NEXT_SIBLING] = g + PPR;
							RegionMatrix[g + REGION_FIRST_CHILD] = -1;
							RegionMatrix[g + REGION_MASS] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_X] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_Y] = 0;

							// Top Right sub-region
							g += PPR;
							RegionMatrix[g + REGION_NODE] = -1;
							RegionMatrix[g + REGION_CENTER_X] = RegionMatrix[r + REGION_CENTER_X] + w;
							RegionMatrix[g + REGION_CENTER_Y] = RegionMatrix[r + REGION_CENTER_Y] - w;
							RegionMatrix[g + REGION_SIZE] = w;
							RegionMatrix[g + REGION_NEXT_SIBLING] = g + PPR;
							RegionMatrix[g + REGION_FIRST_CHILD] = -1;
							RegionMatrix[g + REGION_MASS] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_X] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_Y] = 0;

							// Bottom Right sub-region
							g += PPR;
							RegionMatrix[g + REGION_NODE] = -1;
							RegionMatrix[g + REGION_CENTER_X] = RegionMatrix[r + REGION_CENTER_X] + w;
							RegionMatrix[g + REGION_CENTER_Y] = RegionMatrix[r + REGION_CENTER_Y] + w;
							RegionMatrix[g + REGION_SIZE] = w;
							RegionMatrix[g + REGION_NEXT_SIBLING] = RegionMatrix[r + REGION_NEXT_SIBLING];
							RegionMatrix[g + REGION_FIRST_CHILD] = -1;
							RegionMatrix[g + REGION_MASS] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_X] = 0;
							RegionMatrix[g + REGION_MASS_CENTER_Y] = 0;

							l += 4;

							// Now the goal is to find two different sub-regions
							// for the two nodes: the one previously recorded (r[0])
							// and the one we want to add (n)

							// Find the quadrant of the old node
							if (NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_X] < RegionMatrix[r + REGION_CENTER_X]) {
								if (NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

									// Top Left quarter
									q = RegionMatrix[r + REGION_FIRST_CHILD];
								} else {

									// Bottom Left quarter
									q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR;
								}
							} else {
								if (NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

									// Top Right quarter
									q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 2;
								} else {

									// Bottom Right quarter
									q = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 3;
								}
							}

							// We remove r[0] from the region r, add its mass to r and record it in q
							RegionMatrix[r + REGION_MASS] = NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_MASS];
							RegionMatrix[r + REGION_MASS_CENTER_X] = NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_X];
							RegionMatrix[r + REGION_MASS_CENTER_Y] = NodeMatrix[RegionMatrix[r + REGION_NODE] + NODE_Y];

							RegionMatrix[q + REGION_NODE] = RegionMatrix[r + REGION_NODE];
							RegionMatrix[r + REGION_NODE] = -1;

							// Find the quadrant of n
							if (NodeMatrix[n + NODE_X] < RegionMatrix[r + REGION_CENTER_X]) {
								if (NodeMatrix[n + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

									// Top Left quarter
									q2 = RegionMatrix[r + REGION_FIRST_CHILD];
								} else {
									// Bottom Left quarter
									q2 = RegionMatrix[r + REGION_FIRST_CHILD] + PPR;
								}
							} else {
								if (NodeMatrix[n + NODE_Y] < RegionMatrix[r + REGION_CENTER_Y]) {

									// Top Right quarter
									q2 = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 2;
								} else {

									// Bottom Right quarter
									q2 = RegionMatrix[r + REGION_FIRST_CHILD] + PPR * 3;
								}
							}

							if (q === q2) {

								// If both nodes are in the same quadrant,
								// we have to try it again on this quadrant
								if (subdivisionAttempts--) {
									r = q;
									continue; // while
								} else {
									// we are out of precision here, and we cannot subdivide anymore
									// but we have to break the loop anyway
									subdivisionAttempts = SUBDIVISION_ATTEMPTS;
									break; // while
								}

							}

							// If both quadrants are different, we record n
							// in its quadrant
							RegionMatrix[q2 + REGION_NODE] = n;
							break;
						}
					}
				}
			}
		}


		// 2) Repulsion
		//--------------
		// NOTE: adjustSizes = antiCollision & scalingRatio = coefficient

		if (options.barnesHutOptimize) {
			coefficient = options.scalingRatio;

			// Applying repulsion through regions
			for (n = 0; n < order; n += PPN) {

				// Computing leaf quad nodes iteration

				r = 0; // Starting with root region
				while (true) {

					if (RegionMatrix[r + REGION_FIRST_CHILD] >= 0) {

						// The region has sub-regions

						// We run the Barnes Hut test to see if we are at the right distance
						distance = (
							Math.pow(NodeMatrix[n + NODE_X] - RegionMatrix[r + REGION_MASS_CENTER_X], 2) +
							Math.pow(NodeMatrix[n + NODE_Y] - RegionMatrix[r + REGION_MASS_CENTER_Y], 2)
						);

						s = RegionMatrix[r + REGION_SIZE];

						if ((4 * s * s) / distance < thetaSquared) {

							// We treat the region as a single body, and we repulse

							xDist = NodeMatrix[n + NODE_X] - RegionMatrix[r + REGION_MASS_CENTER_X];
							yDist = NodeMatrix[n + NODE_Y] - RegionMatrix[r + REGION_MASS_CENTER_Y];

							if (adjustSizes === true) {

								//-- Linear Anti-collision Repulsion
								if (distance > 0) {
									factor = (coefficient * NodeMatrix[n + NODE_MASS] * RegionMatrix[r + REGION_MASS]) / distance;

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								} else if (distance < 0) {
									factor = (-coefficient * NodeMatrix[n + NODE_MASS] * RegionMatrix[r + REGION_MASS]) / Math.sqrt(distance);

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								}
							} else {

								//-- Linear Repulsion
								if (distance > 0) {
									factor = (coefficient * NodeMatrix[n + NODE_MASS] * RegionMatrix[r + REGION_MASS]) / distance;

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								}
							}

							// When this is done, we iterate. We have to look at the next sibling.
							r = RegionMatrix[r + REGION_NEXT_SIBLING];
							if (r < 0) {
								break; // No next sibling: we have finished the tree
							}

							continue;
						} else {

							// The region is too close and we have to look at sub-regions
							r = RegionMatrix[r + REGION_FIRST_CHILD];
							continue;
						}
					} else {

						// The region has no sub-region
						// If there is a node r[0] and it is not n, then repulse
						rn = RegionMatrix[r + REGION_NODE];

						if (rn >= 0 && rn !== n) {
							xDist = NodeMatrix[n + NODE_X] - NodeMatrix[rn + NODE_X];
							yDist = NodeMatrix[n + NODE_Y] - NodeMatrix[rn + NODE_Y];

							distance = xDist * xDist + yDist * yDist;

							if (adjustSizes === true) {

								//-- Linear Anti-collision Repulsion
								distance = Math.sqrt(distance) - NodeMatrix[n + NODE_SIZE] - NodeMatrix[rn + NODE_SIZE];

								if (distance > 0) {
									factor = (coefficient * NodeMatrix[n + NODE_MASS] * NodeMatrix[rn + NODE_MASS]) / distance / distance;

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								} else if (distance < 0) {
									factor = 100 * coefficient * NodeMatrix[n + NODE_MASS] * NodeMatrix[rn + NODE_MASS];

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								}
							} else {

								//-- Linear Repulsion
								if (distance > 0) {
									factor = (coefficient * NodeMatrix[n + NODE_MASS] * NodeMatrix[rn + NODE_MASS]) / distance;

									NodeMatrix[n + NODE_DX] += xDist * factor;
									NodeMatrix[n + NODE_DY] += yDist * factor;
								}
							}

						}

						// When this is done, we iterate. We have to look at the next sibling.
						r = RegionMatrix[r + REGION_NEXT_SIBLING];

						if (r < 0) {
							break; // No next sibling: we have finished the tree
						}

						continue;
					}
				}
			}
		} else {
			coefficient = options.scalingRatio;

			// Square iteration
			for (n1 = 0; n1 < order; n1 += PPN) {
				for (n2 = 0; n2 < n1; n2 += PPN) {

					// Common to both methods
					xDist = NodeMatrix[n1 + NODE_X] - NodeMatrix[n2 + NODE_X];
					yDist = NodeMatrix[n1 + NODE_Y] - NodeMatrix[n2 + NODE_Y];
					
					distance = Math.sqrt(xDist * xDist + yDist * yDist);
					
					if (adjustSizes === true) {

						//-- Anticollision Linear Repulsion
						distance = distance - NodeMatrix[n1 + NODE_SIZE] - NodeMatrix[n2 + NODE_SIZE];

						if (distance > 0) {
							factor = (coefficient * NodeMatrix[n1 + NODE_MASS] * NodeMatrix[n2 + NODE_MASS]) / distance / distance;

							// Updating nodes' dx and dy
							NodeMatrix[n1 + NODE_DX] += xDist * factor;
							NodeMatrix[n1 + NODE_DY] += yDist * factor;

							NodeMatrix[n2 + NODE_DX] -= xDist * factor;
							NodeMatrix[n2 + NODE_DY] -= yDist * factor;
						} else if (distance < 0) {
							factor = 100 * coefficient * NodeMatrix[n1 + NODE_MASS] * NodeMatrix[n2 + NODE_MASS];

							// Updating nodes' dx and dy
							NodeMatrix[n1 + NODE_DX] += xDist * factor;
							NodeMatrix[n1 + NODE_DY] += yDist * factor;

							NodeMatrix[n2 + NODE_DX] -= xDist * factor;
							NodeMatrix[n2 + NODE_DY] -= yDist * factor;
						}
					} else {

						//-- Linear Repulsion
						if (distance > 0) {
							factor = (coefficient * NodeMatrix[n1 + NODE_MASS] * NodeMatrix[n2 + NODE_MASS]) / distance / distance;

							// Updating nodes' dx and dy
							NodeMatrix[n1 + NODE_DX] += xDist * factor;
							NodeMatrix[n1 + NODE_DY] += yDist * factor;

							NodeMatrix[n2 + NODE_DX] -= xDist * factor;
							NodeMatrix[n2 + NODE_DY] -= yDist * factor;
						}
					}
				}
			}
		}


		// 3) Gravity
		//------------
		
		g = options.gravity / options.scalingRatio;
		coefficient = options.scalingRatio;
		
		for (n = 0; n < order; n += PPN) {
			
			factor = 0;

			// Common to both methods
			xDist = NodeMatrix[n + NODE_X];
			yDist = NodeMatrix[n + NODE_Y];
			distance = Math.sqrt(xDist * xDist + yDist * yDist);

			if (options.strongGravityMode) {

				//-- Strong gravity
				if (distance > 0) {
					factor = coefficient * NodeMatrix[n + NODE_MASS] * g;
				}
			} else {

				//-- Linear Anti-collision Repulsion n
				if (distance > 0) {
					factor = (coefficient * NodeMatrix[n + NODE_MASS] * g) / distance;
				}
			}

			// Updating node's dx and dy
			NodeMatrix[n + NODE_DX] -= xDist * factor;
			NodeMatrix[n + NODE_DY] -= yDist * factor;
		}

		// 4) Attraction
		//---------------
		coefficient = 1 * (options.outboundAttractionDistribution ? outboundAttCompensation : 1);

		// TODO: simplify distance
		// TODO: coefficient is always used as -c --> optimize?
		for (e = 0; e < size; e += PPE) {
			
			n1 = EdgeMatrix[e + EDGE_SOURCE];
			n2 = EdgeMatrix[e + EDGE_TARGET];
			w = EdgeMatrix[e + EDGE_WEIGHT];

			// Edge weight influence
			ewc = Math.pow(w, options.edgeWeightInfluence);

			// Common measures
			xDist = NodeMatrix[n1 + NODE_X] - NodeMatrix[n2 + NODE_X];
			yDist = NodeMatrix[n1 + NODE_Y] - NodeMatrix[n2 + NODE_Y];

			// Applying attraction to nodes
			if (adjustSizes === true) {

				distance = Math.sqrt(xDist * xDist + yDist * yDist) - NodeMatrix[n1 + NODE_SIZE] - NodeMatrix[n2 + NODE_SIZE];

				if (options.linLogMode) {
					if (options.outboundAttractionDistribution) {

						//-- LinLog Degree Distributed Anti-collision Attraction
						if (distance > 0) {
							factor = (-coefficient * ewc * Math.log(1 + distance)) / distance / NodeMatrix[n1 + NODE_MASS];
						}
					} else {

						//-- LinLog Anti-collision Attraction
						if (distance > 0) {
							factor = (-coefficient * ewc * Math.log(1 + distance)) / distance;
						}
					}
				} else {
					if (options.outboundAttractionDistribution) {

						//-- Linear Degree Distributed Anti-collision Attraction
						if (distance > 0) {
							factor = (-coefficient * ewc) / NodeMatrix[n1 + NODE_MASS];
						}
					} else {

						//-- Linear Anti-collision Attraction
						if (distance > 0) {
							factor = -coefficient * ewc;
						}
					}
				}
			} else {

				distance = Math.sqrt(xDist * xDist + yDist * yDist);

				if (options.linLogMode) {
					if (options.outboundAttractionDistribution) {

						//-- LinLog Degree Distributed Attraction
						if (distance > 0) {
							factor = (-coefficient * ewc * Math.log(1 + distance)) / distance / NodeMatrix[n1 + NODE_MASS];
						}
					} else {

						//-- LinLog Attraction
						if (distance > 0) {
							factor = (-coefficient * ewc * Math.log(1 + distance)) / distance;
						}
					}
				} else {
					if (options.outboundAttractionDistribution) {

						//-- Linear Attraction Mass Distributed
						// NOTE: Distance is set to 1 to override next condition
						distance = 1;
						factor = (-coefficient * ewc) / NodeMatrix[n1 + NODE_MASS];
					} else {

						//-- Linear Attraction
						// NOTE: Distance is set to 1 to override next condition
						distance = 1;
						factor = -coefficient * ewc;
					}
				}
			}

			// Updating nodes' dx and dy
			// TODO: if condition or factor = 1?
			if (distance > 0) {

				// Updating nodes' dx and dy
				NodeMatrix[n1 + NODE_DX] += xDist * factor;
				NodeMatrix[n1 + NODE_DY] += yDist * factor;

				NodeMatrix[n2 + NODE_DX] -= xDist * factor;
				NodeMatrix[n2 + NODE_DY] -= yDist * factor;
			}
		}


		// 5) Apply Forces
		//-----------------
		var swinging,
			df,
			newX,
			newY;

		// Auto adjust speed: one global step from total swinging vs. traction,
		// so nodes share a governed step instead of each jumping on its own
		var nodesCount = order / PPN;

		var totalSwinging = 0, // How much irregular movement
			totalEffectiveTraction = 0; // How much useful movement

		for (n = 0; n < order; n += PPN) {
			if (NodeMatrix[n + NODE_FIXED] !== 1) {

				swinging = Math.sqrt(
					(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) *
					(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) +
					(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY]) *
					(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY])
				);

				// If the node has a burst change of direction, it's not converging
				totalSwinging += NodeMatrix[n + NODE_MASS] * swinging;

				totalEffectiveTraction += NodeMatrix[n + NODE_MASS] * 0.5 * Math.sqrt(
					(NodeMatrix[n + NODE_OLD_DX] + NodeMatrix[n + NODE_DX]) *
					(NodeMatrix[n + NODE_OLD_DX] + NodeMatrix[n + NODE_DX]) +
					(NodeMatrix[n + NODE_OLD_DY] + NodeMatrix[n + NODE_DY]) *
					(NodeMatrix[n + NODE_OLD_DY] + NodeMatrix[n + NODE_DY])
				);
			}
		}

		// Optimize jitter tolerance: bigger nets need more, denser need less (empiric)
		var estimatedOptimalJitterTolerance = 0.05 * Math.sqrt(nodesCount),
			minJT = Math.sqrt(estimatedOptimalJitterTolerance),
			maxJT = 10,
			jt = options.jitterTolerance * Math.max(minJT, Math.min(maxJT,
				(estimatedOptimalJitterTolerance * totalEffectiveTraction) / (nodesCount * nodesCount)));

		var minSpeedEfficiency = 0.05;

		// Protection against erratic behavior
		if (totalSwinging / totalEffectiveTraction > 2.0) {
			if (speedEfficiency > minSpeedEfficiency) {
				speedEfficiency *= 0.5;
			}
			jt = Math.max(jt, options.jitterTolerance);
		}

		var targetSpeed = (jt * speedEfficiency * totalEffectiveTraction) / totalSwinging;

		// Speed efficiency tracks the swinging vs. convergence tradeoff, adjust it slowly
		if (totalSwinging > jt * totalEffectiveTraction) {
			if (speedEfficiency > minSpeedEfficiency) {
				speedEfficiency *= 0.7;
			}
		} else if (speed < 1000) {
			speedEfficiency *= 1.3;
		}

		// But the speed shouldn't rise too fast, or convergence drops dramatically
		var maxRise = 0.5; // Max rise: 50%
		speed = speed + Math.min(targetSpeed - speed, maxRise * speed);

		// Apply forces
		if (adjustSizes === true) {

			for (n = 0; n < order; n += PPN) {
				if (NodeMatrix[n + NODE_FIXED] !== 1) {

					// Adaptive auto-speed: a node's step is lowered when it swings
					swinging = NodeMatrix[n + NODE_MASS] * Math.sqrt(
						(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) *
						(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) +
						(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY]) *
						(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY])
					);

					factor = (0.1 * speed) / (1 + Math.sqrt(speed * swinging));

					// With overlap prevention, cap the step so nodes don't jump over each other
					df = Math.sqrt(
						NodeMatrix[n + NODE_DX] * NodeMatrix[n + NODE_DX] +
						NodeMatrix[n + NODE_DY] * NodeMatrix[n + NODE_DY]
					);

					factor = Math.min(factor * df, MAX_FORCE) / df;

					// Updating node's position
					newX = NodeMatrix[n + NODE_X] + NodeMatrix[n + NODE_DX] * factor;
					NodeMatrix[n + NODE_X] = newX;

					newY = NodeMatrix[n + NODE_Y] + NodeMatrix[n + NODE_DY] * factor;
					NodeMatrix[n + NODE_Y] = newY;
				}
			}
		} else {

			for (n = 0; n < order; n += PPN) {
				if (NodeMatrix[n + NODE_FIXED] !== 1) {

					// Adaptive auto-speed: a node's step is lowered when it swings
					swinging = NodeMatrix[n + NODE_MASS] * Math.sqrt(
						(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) *
						(NodeMatrix[n + NODE_OLD_DX] - NodeMatrix[n + NODE_DX]) +
						(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY]) *
						(NodeMatrix[n + NODE_OLD_DY] - NodeMatrix[n + NODE_DY])
					);

					factor = speed / (1 + Math.sqrt(speed * swinging));

					// Updating node's position
					newX = NodeMatrix[n + NODE_X] + NodeMatrix[n + NODE_DX] * factor;
					NodeMatrix[n + NODE_X] = newX;

					newY = NodeMatrix[n + NODE_Y] + NodeMatrix[n + NODE_DY] * factor;
					NodeMatrix[n + NODE_Y] = newY;
				}
			}
		}
	};

	this.getMetrics = function() {

		var nodeCount = NodeMatrix.length / PPN,
			edgeCount = EdgeMatrix.length / PPE;

		// Degree from the edge list (node base offset -> degree)
		var degrees = {},
			e, src, tgt, maxDegree = 0;

		for (e = 0; e < EdgeMatrix.length; e += PPE) {
			src = EdgeMatrix[e + EDGE_SOURCE];
			tgt = EdgeMatrix[e + EDGE_TARGET];
			degrees[src] = (degrees[src] || 0) + 1;
			degrees[tgt] = (degrees[tgt] || 0) + 1;
			if (degrees[src] > maxDegree) maxDegree = degrees[src];
			if (degrees[tgt] > maxDegree) maxDegree = degrees[tgt];
		}

		var avgDegree = nodeCount > 0 ? (2 * edgeCount) / nodeCount : 0,
			density = nodeCount > 1 ? edgeCount / (nodeCount * (nodeCount - 1) / 2) : 0;

		return {
			nodes: nodeCount,
			edges: edgeCount,
			avgDegree: avgDegree,
			maxDegree: maxDegree,
			density: density,
			speed: speed,
			speedEfficiency: speedEfficiency
		};
	};

	this.autoConfigure = function(arr_override) {

		const arr_metrics = SELF.getMetrics();
		const num_nodes = arr_metrics.nodes;

		const arr_settings = {
			scalingRatio: (num_nodes >= 100 ? 2.0 : 10.0), // Default: 10 small graphs, 2 big graphs (repulsion strength)
			gravity: (num_nodes >= 50000 ? 2.0 : 1.0), // Heavier gravity keeps very large graphs from drifting apart
			outboundAttractionDistribution: (arr_metrics.avgDegree > 0 && arr_metrics.maxDegree > 5 * arr_metrics.avgDegree), // "Dissuade hubs": helps when the degree distribution is hub-heavy
			barnesHutOptimize: (num_nodes >= 1000), // Default: OG enables BH at >=1000 nodes
			barnesHutTheta: 1.2, // Default
			jitterTolerance: 1 // The speed governor already scales jitter tolerance by sqrt(n)
		};

		if (arr_override) {
			
			for (const key in arr_override) {
				
				if (arr_override[key] == null) {
					continue;
				}
				
				arr_settings[key] = arr_override[key];
			}
		}

		SELF.setConfiguration(arr_settings);

		arr_settings.recommendedIterations = Math.min(2000, Math.round(100 + 20 * Math.sqrt(num_nodes))); // Indication of needed iterations scaled to graph size

		return arr_settings;
	};
}
