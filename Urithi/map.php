<?php 
$page_title = 'The LIS | Securing Land for prosperity';
include ('./includes/header.html');
if(!$_SESSION['email'])  
{  
	header("Location: login.php"); 
}
?>
<body>
<p class="topper"><span style="color:red">U</span>rithi<span style="color:green"> Co.</span> | Kenya

 <a class="logo" href="logout.php"><strong>Logout</strong></a>
 </p>	
	<div id="map" >
	</div>
	<div id="sidebar-left" >
		<div id="search">
				<div id="Tabs">
					<ul>
							<li id="li_tab1" onclick="tab('tab1')" ><a>Office to Parcels</a></li>
							<li id="li_tab2" onclick="tab('tab2')"><a>Queries</a></li>
					</ul>
					<div id="Content_Area">
						<div id="tab1"></div>
						
						<div id="tab2" style="display: none;">
							<h>Search Parcel</h>
							<hr></hr><br></br>
							<!--Search by LR number-->
							<p><label class='title' id="">File Reference &nbsp;<input type="text" id="lr_serch" name="lr_serch"  placeholder="Search by Name..." /></label></p>
							<!--Submit button-->
							<input type="button" id="searchBtn" value=""  />
							<br></br>
							<h>New Parcel</h>
							<hr></hr>
							<div id="results"></div>
							<form action="newparcels.php" method="post" id="parcel_form">
									<p><label class="title" id="name_label">Name: <input name="name" id="name" type="text" size="48" maxlength="100" placeholder="Enter name..."></label></p>
									<br></br>
									<p><label class="title" id="geom_label">Geometry: <br></br><textarea name="geom" id="geom" type="text" size="48" maxlength="100" placeholder="Enter coordinates..."></textarea></label></p>
									<input name="submit" id="insertBtn" type="submit" value="Save Parcel">
									<input type="submit" value="Clear" onClick="clearform();" />
							</form>
						</div>
				</div> 
			</div>
		</div>
	</div>
<script type="text/javascript">
	function clearform(){
		document.getElementById("geom").value=""; 
		document.getElementById("name").value="";
	}
	//base Layers			
		var mapquestOSM = new L.TileLayer("http://{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png", {
			subdomains: ["otile1", "otile2", "otile3", "otile4"],
			attribution: 'Tiles courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>.'+ 
			'Map data (c) <a href="http://www.openstreetmap.org/" target="_blank">OpenStreetMap</a> contributors, CC-BY-SA.'
		});
      
    	var osm = new L.tileLayer("http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      		subdomains: ["a","b","c"],
			attribution:'&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		});
			
		var esri = new L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
 				attribution: '&copy; <a href="http://www.esri.com/">Esri</a>, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP,and the GIS User Community'
 			});
	
		/*color scheme*/
		function getColor(d) {
			return d > 5.0 ? '#800026' :
				   d > 4.0 ? '#BD0026' :
				   d > 3.0 ? '#E31A1C' :
				   d > 2.0 ? '#FC4E2A' :
				   d > 1.0 ? '#FD8D3C' :
				   d > 0.5 ? '#FEB24C' :
				   d > 0.1 ? '#FED976' :
						     '#FFEDA0' ;
		};
		
		var highlightStyle = {
			color: '#2262CC', 
			weight: 3,
			opacity: 0.6,
			fillOpacity: 0.65,
			fillColor: '#99C68E'
		};

				
		//Overlays from postgres database
		var parcels = new L.geoJson(null, {
			
			style: function (feature) {
				return {
					fillColor: getColor(feature.properties.area_ha),
					weight: 2,
					opacity: 1,
					color: '#99C68E',
					dashArray: '3',
					fillOpacity: 0.7
				};
				
			},
			
			onEachFeature: function (feature, layer) {
				layer.setStyle(style);
				
				(function(layer, properties){
					//create a mouseover event
					layer.on("mouseover", function (e) {
					// Change the style to the highlighted version
					layer.setStyle(highlightStyle);
					// Create a popup with a unique ID linked to this record
					var popup = $("<div></div>", {
						id: "popup-" + properties.size,
						css: {
							position: "absolute",
							top: "25px",
							right: "50px",
							zIndex: 1002,
							backgroundColor: "white",
							padding: "6px 8px",
							border: "3px solid #ccc"
						}
					});
					// Insert a headline into that popup
					var hed = $("<div></div>", {
						text: " Name: " + properties.lr_no+" ,"+" Parcel Size: " + properties.area_ac +" Acres",
						css: {fontSize: "font: 14px/16px Arial, Helvetica, sans-serif", marginBottom: "3px"}
					}).appendTo(popup);
					// Add the popup to the map
					popup.appendTo("#map");
				  });
				  // Create a mouseout event that undoes the mouseover changes
				  layer.on("mouseout", function (e) {
					// Start by reverting the style back
					layer.setStyle(style); 
					// And then destroying the popup
					$("#popup-" + properties.size).remove();
				  });
				  // Close the "anonymous" wrapper function, and call it while passing
				  // in the variables necessary to make the events work the way we want.
				})(layer, feature.properties);
			}
		});
		$.getJSON("postgis_polygon_geojson.php", function (data) {
			parcels.addData(data);
		}).complete(function () {
			map.fitBounds(parcels.getBounds([-1.2307,36.8114],[-1.1931,36.8940]));
		});
									
		//Map object
		var map = new L.Map('map',{ 
			center: new L.latLng([-1.2096, 36.8562]),
			//center: new L.latLng([0.138595, 32.575298]),
			zoomControl : false,
			minZoom : 8,
			maxZoom : 18,
			layers: [osm, parcels]
		});

		var baseLayers = {
			"MapQuest Streets": mapquestOSM,
      		"OpenStreetMap": osm,
      		"Esri Imagery": esri
		};
		var overlays = {
			"Parcels": parcels
		};
		
		L.control.zoom({position:'topright'}).addTo(map);
			
		layersControl = new L.Control.Layers(baseLayers, overlays,{collapsed: true, position: 'bottomright'	});
		map.addControl(layersControl);

//leftbar		
		var sidebar = L.control.sidebar('sidebar-left');
		
		setTimeout(function () {
            sidebar.toggle();
        }, 500);
		
        setInterval(function () {
            sidebar.show();
        }, 5000);
	
		map.addControl(sidebar);
		
//bounding fix
        var southWest = new L.LatLng(-4.0177,26.6411);
		var northEast = new L.LatLng(4.3245,42.2974);
		var bounds = new L.LatLngBounds(southWest, northEast);
		map.setMaxBounds(bounds);
		
//search osm
	map.addControl( new L.Control.Search({
		url: 'http://nominatim.openstreetmap.org/search?format=json&q={s}',
		jsonpParam: 'json_callback',
		propertyName: 'display_name',
		propertyLoc: ['lat','lon']
	}));
		
	var drawnItems = new L.FeatureGroup();	
	map.addLayer(drawnItems);

	var style = {color:'green', opacity: 1.0, fillOpacity: 0.8, weight: 1, clickable: true};
	
//geojson search
	$('#searchBtn').click(function(){
		$.ajax({
			type: "POST",
			url:  "lr_serch.php?x="+$("#lr_serch").val(),
			dataType: 'json',
			cache: false,
 			async: true,
			success: function (response) {

				geojsonLayer = L.geoJson(response, {
				   style: function (feature) {
								return {
									color: '#0c6d51',
									weight: 2,
									opacity: 1,
									fillColor: '#00ffff',
									fillOpacity: 0.7
								};
							},
					onEachFeature: function (feature, layer) {
					  if (feature.properties) {
						var content = '<table border="1" style="border-collapse:collapse;" cellpadding="2">' +
							  '<tr>' + '<th>Name</th>' + '<td>' + feature.properties.f_r + '</td>' + '</tr>' +
							  '<tr>' + '<th>Hectares.</th>' + '<td>' + feature.properties.area_ha + '</td>' + '</tr>' +
							  '<table>';
						layer.bindPopup(content);
					  }
					},
				}).addTo(map);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) { 
				
			} 
		});

	});
/*Routing*/
	var control = L.Routing.control({
		waypoints: [
				L.latLng(-1.2057,36.9155),
				L.latLng(-1.2085,36.8621)
			],
			createMarker: function(i, wp) {
				return L.marker(wp.latLng, {
					draggable: true,
					icon: new L.Icon.Label.Default({ labelText: String.fromCharCode(65 + i) })
				});
			},
		geocoder: L.Control.Geocoder.nominatim(),
		routeWhileDragging: true,
		reverseWaypoints: true,
		showAlternatives: true,
		altLineOptions: {
			styles: [
				{color: 'black', opacity: 0.15, weight: 9},
				{color: 'white', opacity: 0.8, weight: 6},
				{color: 'blue', opacity: 0.4, weight: 2, dashArray: '10'}
			]
		}
	});

	L.Routing.errorControl(control).addTo(map);
	
	var controlDiv = control.onAdd(map);
	document.getElementById('tab1').appendChild(controlDiv);
/*End of javascript	*/
</script>
<script type="text/javascript">
	function tab(tab) {
		document.getElementById('tab1').style.display = 'none';
		document.getElementById('tab2').style.display = 'none';
		document.getElementById('li_tab1').setAttribute("class", "");
		document.getElementById('li_tab2').setAttribute("class", "");
		document.getElementById(tab).style.display = 'block';
		document.getElementById('li_'+tab).setAttribute("class", "active");
		}
</script>

